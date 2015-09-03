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

namespace bmarwell\WebtreesModuls\jsonld;

use Composer\Autoload\ClassLoader;

use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleTabInterface;

use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\Note;
use Fisharebest\Webtrees\Source;
use Fisharebest\Webtrees\Repository;
use Fisharebest\Webtrees\Media;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Filter;

/**
 * Class implementing application/ld+json output.
 * @author bmarwell
 *
 */
class JsonLdModule extends AbstractModule implements ModuleTabInterface {

    /** @var string location of the fancy treeview module files */
    var $directory;

    public function __construct()
    {
        parent::__construct('jsonld');
        $this->directory = WT_MODULES_DIR . $this->getName();
        $this->action = Filter::get('mod_action');
    }

	/* ****************************
	 * Module configuration
	 * ****************************/

	/** {@inheritdoc} */
	public function getName() {
		return "JsonLD";
	}

    public function getTitle() {
        return "JsonLD";
    }

	/** {@inheritdoc} */
	public function getDescription() {
		return "Adds json-ld-data to persons as described in schema.org/Person";
	}
	
	/** {@inheritdoc} */
	public function defaultAccessLevel() {
		return Auth::PRIV_PRIVATE;
	}
	
	/* ****************************
	 * Implements Tab
	 * ****************************/
	
	/**
	 * The user can re-arrange the tab order, but until they do, this
	 * is the order in which tabs are shown.
	 *
	 * @return int
	 */
	public function defaultTabOrder() {
		return 500;
	}

    public function getTabTitle() {
        return "JsonLD";
    }
	
	/**
	 * Generate the HTML content of this tab.
	 *
	 * @return string
	*/
	public function getTabContent() {
		global $controller;

        /** @var Person $person */
        $person = new Person(true);
        /** @var GedcomRecord|Individual|Family|Source|Repository|Media|Note $record */
        $record = $controller->getSignificantIndividual();
		
		// FIXME: record may be invisible!
		$person = JsonLDTools::fillPersonFromRecord($person, $record);
		$person = JsonLDTools::addParentsFromRecord($person, $record);
		$person = JsonLDTools::addChildrenFromRecord($person, $record);
		
		$jsonld = json_encode(
				JsonLDTools::jsonize($person), 
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		
		return static::getScriptTags($jsonld) . static::getTags($jsonld, "pre");
	}
	
	private static function getTags($stringenclosed, $tag = 'pre') {
		return "<$tag>" . $stringenclosed . "</$tag>";
	}
	
	private static function getScriptTags($stringenclosed) {
		return 
			  '<script type="application/ld+json" id="json-ld-data">'
			.  $stringenclosed . '</script>';
	}
	
	/**
	 * Is this tab empty?  If so, we don't always need to display it.
	 *
	 * @return bool
	*/
	public function hasTabContent() {
		global $controller;
		
		return 
			(count($controller->record->getAllNames()) > 0) /* no names, no cookies */
			&& ($controller->record->canShowName());         /* no id */
	}
	
	/**
	 * Can this tab load asynchronously?
	 *
	 * @return bool
	*/
	public function canLoadAjax() {
		return true;
	}
	
	/**
	 * Any content (e.g. Javascript) that needs to be rendered before the tabs.
	 *
	 * This function is probably not needed, as there are better ways to achieve this.
	 *
	 * @return string
	*/
	public function getPreLoadContent() {
		return '';
	}
	
	/**
	 * A greyed out tab has no actual content, but may perhaps have
	 * options to create content.
	 *
	 * @return bool
	*/
	public function isGrayedOut() {
		return false;
	}

}

return new JsonLdModule();