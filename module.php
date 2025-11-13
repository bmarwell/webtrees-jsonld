<?php
/**
 * webtrees json-ld: online genealogy json-ld-module.
 * Copyright (C) 2015-2025 webtrees development team
 *
 * SPDX-License-Identifier: Apache-2.0 OR EUPL-1.2
 *
 * This program is dual-licensed under Apache-2.0 OR EUPL-1.2.
 * See LICENSE file for details.
 */

namespace bmhm\WebtreesModules\jsonld;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleTabInterface;
use Fisharebest\Webtrees\Module\ModuleTabTrait;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Tree;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

spl_autoload_register(function ($class) {
    $cwd = dirname(__FILE__);
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

    if (file_exists($file)) {
        require $file;
        return true;
    }

    $localFile = $cwd . '/' . str_replace('bmhm/WebtreesModules', 'src', $file);
    if (file_exists($localFile)) {
        require $localFile;
        return true;
    }

    return false;
});

/**
 * Class implementing application/ld+json output.
 * @author bmhm
 *
 */
return new class extends AbstractModule implements ModuleCustomInterface, ModuleTabInterface, MiddlewareInterface
{
    use ModuleCustomTrait;
    use ModuleTabTrait;

    /* ****************************
     * Module configuration
     * ****************************/

    public function title(): string
    {
        return "JsonLD";
    }

    /** {@inheritdoc} */
    public function description(): string
    {
        return I18N::translate("Adds json-ld-data to persons as described in schema.org/Person.");
    }

    /* ****************************
     * Module custom interface
     * ****************************/

    public function customModuleAuthorName(): string
    {
        return 'bmhm';
    }

    public function customModuleVersion(): string
    {
        return '2.0.0';
    }

    public function customModuleSupportUrl(): string
    {
        return 'https://github.com/bmhm/webtrees-jsonld';
    }

    /* ****************************
     * Implements Tab
     * ****************************/

    public function tabTitle(): string
    {
        return "Json-LD";
    }

    public function defaultTabOrder(): int
    {
        return 500;
    }

    public function hasTabContent(Individual $individual): bool
    {
        return
        (count($individual->getAllNames()) > 0) /* no names, no cookies */
        && ($individual->canShowName()); /* no id */
    }

    public function getTabContent(Individual $individual): string
    {
        Auth::checkIndividualAccess($individual);
        $jsonld = $this->createJsonLdForIndividual($individual);

        return static::getScriptTags($jsonld) . static::getTags($jsonld, "pre");
    }

    private static function getScriptTags($stringenclosed)
    {
        return
            '<script type="application/ld+json" id="json-ld-data">'
            . $stringenclosed . '</script>';
    }

    private static function getTags($stringenclosed, $tag = 'pre')
    {
        return "<$tag>" . $stringenclosed . "</$tag>";
    }

    public function canLoadAjax(): bool
    {
        return true;
    }

    public function isGrayedOut(Individual $individual): bool
    {
        return false;
    }

    /* *****************
     * Helper methods
     ********************/

    /**
     * @param Individual $individual
     * @return false|string
     */
    public function createJsonLdForIndividual(Individual $individual)
    {
        /** @var Person $person */
        $person = new Person(true);

        // FIXME: record may be invisible!
        $person = JsonLDTools::fillPersonFromRecord($person, $individual);
        $person = JsonLDTools::addParentsFromRecord($person, $individual);
        $person = JsonLDTools::addChildrenFromRecord($person, $individual);

        $jsonld = json_encode(
            JsonLDTools::jsonize($person),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
        return $jsonld;
    }

    /**
     * @param ServerRequestInterface $request
     * @return Individual|null
     */
    public function getIndividualFromCurrentTree(ServerRequestInterface $request)
    {
        $tree = $request->getAttribute('tree');
        $xref = $request->getAttribute('xref');
        $individual = Registry::individualFactory()->make($xref, $tree);
        Auth::checkIndividualAccess($individual);

        return $individual;
    }

    /* ****************************
     * Implements MiddlewareInterface
     * ****************************/

    /**
     * Process an incoming server request.
     *
     * Implements HTTP content negotiation for JSON-LD data.
     * When Accept header includes "application/ld+json", returns pure JSON-LD.
     * For normal HTML requests, adds a Link header to advertise JSON-LD availability.
     *
     * Future configuration possibilities (not yet implemented):
     * - Enable/disable Link header in HTML responses
     * - Configure which record types support JSON-LD (individuals, families, places, etc.)
     * - Control JSON-LD depth (e.g., include/exclude parents, children, spouses)
     * - Add support for JSON-LD frames for custom data structures
     * - Configure caching headers for JSON-LD responses
     * - Support for other JSON-LD media types (application/json with @context)
     * - Rate limiting for JSON-LD endpoint to prevent abuse
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $acceptHeader = $request->getHeader("accept");
        $requestAcceptsJsonLd = false;
        
        // Check if application/ld+json is in the Accept header
        foreach ($acceptHeader as $value) {
            if (str_contains($value, "application/ld+json")) {
                $requestAcceptsJsonLd = true;
                break;
            }
        }
        
        if (!$requestAcceptsJsonLd) {
            // For normal HTML responses, add Link header to advertise JSON-LD availability
            $response = $handler->handle($request);
            $currentUrl = $request->getUri();
            return $response->withHeader("Link", '<' . $currentUrl . '>; rel="alternate"; type="application/ld+json"');
        }

        $individual = $this->getIndividualFromCurrentTree($request);

        return response($this->createJsonLdForIndividual($individual), 200, array(
            "Content-Type" => "application/ld+json; charset=utf-8",
        ));
    }

};
