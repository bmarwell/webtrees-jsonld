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

abstract class JsonLD {
	
	function __construct ($jsonldtype, $addContext = FALSE) {
		Log::addDebugLog("creating jsonld-object");
		$context = "@context";
		$type = "@type";
		
		if ($addContext === true) {
			Log::addDebugLog("adding context…");
			$this->$context = "http://schema.org";
		}
		
		$this->$type = $jsonldtype;
	}
}