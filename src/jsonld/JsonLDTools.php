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

class JsonLDTools {
	public static function jsonize($jsonldobject) {
		/* create a new object, so we don't modify the original one. */
		$returnobj = clone $jsonldobject;
	
		/* test each key/value for array. If so, recursion… */
// 		foreach (get_object_vars($returnobj) as $key => $value) {
// 			if (empty($value)) {
// 				Log::addDebugLog("$key is empty");
// 				continue;
// 			}

// 			Log::addDebugLog("Check if $key contains an array.");
// 			if (is_array($value)) {
// 				Log::addDebugLog("$key contains array, jsonizing.");
// // 				$value = static::jsonize_array($value);
// 			}
	
// 			/* filter empty objects */
// 			Log::addDebugLog("inner unclutter $value");
// 			$value = (object) array_filter((array) $value);
// 		}
	
		/* strip empty key/value-pairs */
		Log::addDebugLog("outer unclutter " . serialize($returnobj));
		$returnobj = (object) array_filter((array) $returnobj);
		
		return json_encode($returnobj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
	
	private function jsonize_array($array) {
		foreach ($array as &$value) {
			Log::addDebugLog("Jsonizing $value");
			$value = jsonize($value);
		}
		
		return $array;
	}
	
	public static function fillPersonFromRecord($person, $record) {
		$person->name =  $record->getAllNames()[$record->getPrimaryName()]['full'];
		$person->media = $record->findHighlightedMedia();
		$person->gender = $record->getSex();
		
		/* Dates */
		$birthdate = $record->getBirthDate()->display(false, '%Y-%m-%d', false);
		if (preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $birthdate) === 1) {
			$person->birthDate = $birthdate;
		}
		$deathDate = $record->getDeathDate()->display(false, '%Y-%m-%d', false);
		if (preg_match('/[0-9]{4}-[0-9][0-9]-[0-9][0-9]/', $deathDate) === 1) {
			$person->deathDate = $deathDate;
		}
		
		return $person;
	}
}
