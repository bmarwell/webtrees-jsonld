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
 
use WT\Log;

/**
 * Person for serializing into json-ld. Vars have name from schema.org.
 */
class Person extends JsonLD {
	var $name;
	var $birthDate;
	var $email;
	var $url;
	var $address = array();
	var $gender = "U";
	var $parents = array();
	/**
	 * A gedcom image record (not done yet).
	 * @var Image $image
	 */
	var $image;

	function __construct($addContext = FALSE) {
		Log::addDebugLog("creating person, context is $addContext");
		parent::__construct("Person", $addContext);
	}
	
	function addAddress($address) {
		array_push($this->address, $address);
	}
	
	function addParent($person) {
		array_push($this->parents, $person);
	}
}