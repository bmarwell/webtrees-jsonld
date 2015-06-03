<?php
/**
 * webtrees json-ld: online genealogy json-ld-module.
 * Copyright (C) 2015 Benjamin
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

class jsonld_WT_Module extends WT_Module implements WT_Module_Tab {
	
	/*
	 * Module configuration
	 */
	public function getTitle() {
		return "JsonLD";
	}
	public function getDescription() {
		return "Adds json-ld-data to persons as described in schema.org/Person";
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
	
	/**
	 * Generate the HTML content of this tab.
	 *
	 * @return string
	*/
	public function getTabContent() {
		$jsonld = <<<EOT
<script type="application/ld+json">
{
  "@context": "http://schema.org",
  "@type": "Person",
  "image": "%%image%%",
  "name": "%%fullname%%",
  "gender": "%%gender%%"
}
</script>
EOT;
		// get values for replacement
		// TODO: care about null…
		global $controller;
// 		$person = $controller->getSignificantIndividual();
		$fullname =  $controller->record->getFullName();
		$media = $controller->record->findHighlightedMedia();
		$gender = $controller->record->getSex();
		
		// insert values.
		// TODO: care about NULL;
		$jsonld = str_replace("%%image%%", $media, $jsonld);
		$jsonld = str_replace("%%fullname%%", strip_tags($fullname), $jsonld);
		$jsonld = str_replace("%%gender%%", $gender, $jsonld);
		$jsonld = $jsonld . '<pre>' . htmlspecialchars($jsonld) . '</pre>';
		
		return $jsonld;
	}
	
	/**
	 * Is this tab empty?  If so, we don't always need to display it.
	 *
	 * @return bool
	*/
	public function hasTabContent() {
		global $controller;
		
		return $controller->record->canShowName();
	}
	
	/**
	 * Can this tab load asynchronously?
	 *
	 * @return bool
	*/
	public function canLoadAjax() {
		return false;
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
