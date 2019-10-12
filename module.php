<?php
/**
 * webtrees json-ld: online genealogy json-ld-module.
 * Copyright (C) 2015 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace bmhm\WebtreesModules\jsonld;

use Composer\Autoload\ClassLoader;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleTabInterface;
use Fisharebest\Webtrees\Module\ModuleTabTrait;
use Fisharebest\Webtrees\Tree;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
        // TODO: this duplicates logic from both router/web.php and IndividualController.
        //  - remove when solved: https://github.com/fisharebest/webtrees/issues/2615
        $tree = app(Tree::class);
        $xref = $request->getQueryParams()['xref'];
        $individual = Individual::getInstance($xref, $tree);

        return $individual;
    }

    /* ****************************
     * Implements MiddlewareInterface
     * ****************************/

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $acceptHeader = $request->getHeader("accept");
        if (!in_array("application/ld+json", $acceptHeader)) {
            // pass through.
            return $handler->handle($request);
        }

        $individual = $this->getIndividualFromCurrentTree($request);

        return response($this->createJsonLdForIndividual($individual), 200, array(
            "Content-Type" => "application/ld+json"
        ));
    }


};

