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


class jsonld_Place extends JsonLD {
	/**
	 * Name of the place
	 * @var String the name of the place.
	 */
	public $name;

	/**
	 * Geo-Location of the place
	 * @var GeoLocation the geoLocation.
	 */
	public $geo;
	
	/**
	 * The address of this place.
	 * @var Address the address.
	 */
	public $address;
	
	function __construct($addContext = FALSE) {
		parent::__construct("Place", $addContext);
	}
}