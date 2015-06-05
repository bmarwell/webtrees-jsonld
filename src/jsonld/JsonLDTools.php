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
	/**
	 * Serialize an object into json. Empty values are stripped 
	 * by unsetting the fields.
	 * @param unknown $jsonldobject
	 * @return Object the uncluttered object, no null values.
	 */
	public static function jsonize($jsonldobject) {
		/* create a new object, so we don't modify the original one. */
		$returnobj = clone $jsonldobject;
		
		$returnobj = static::empty_object($returnobj);
	
		/* strip empty key/value-pairs */
		Log::addDebugLog("outer unclutter " . serialize($returnobj));
		$returnobj = (object) array_filter((array) $returnobj);
		
		return $returnobj;
	}
	
	/**
	 * Unset empty fields from object.
	 * @param Object $obj
	 */
	private static function empty_object($obj) {
		// TODO: if array, recursion for each array element…
		// then return the array;
		
		$returnobj = clone $obj;
		
		foreach (get_object_vars($returnobj) as $key => $value) {
			if (is_object($value)) {
				static::empty_object($returnobj->$key);
			}
			
			if (empty($value)) {
				unset($returnobj->{$key});
			}
			
		}
		
		return $returnobj;
	}
	
	/**
	 * For a given person object (re-)set the fields with sane 
	 * values from a gedcom-record.
	 * @var Person $person
	 * @var GedcomRecord $record
	 */
	public static function fillPersonFromRecord($person, $record) {
		// TODO: strip html
		$person->name =  $record->getAllNames()[$record->getPrimaryName()]['fullNN'];
		$person->media = $record->findHighlightedMedia();
		$person->gender = $record->getSex();
		
		/* Dates */
		// XXX: match beginning and end of string
		$birthdate = $record->getBirthDate()->display(false, '%Y-%m-%d', false);
		if (preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $birthdate) === 1) {
			$person->birthDate = strip_tags($birthdate);
		} else if (preg_match('/between/', $birthdate)) {
			$person->birthDate = strip_tags($record->getBirthDate()->MinDate()->format('%Y') . 
				'/' . $record->getBirthDate()->MaxDate()->format('%Y'));
		}
		
		$deathDate = $record->getDeathDate()->display(false, '%Y-%m-%d', false);
		if (preg_match('/[0-9]{4}-[0-9][0-9]-[0-9][0-9]/', $deathDate) === 1) {
			$person->deathDate = strip_tags($deathDate);
		} else if (preg_match('/between/', $deathDate)) {
			$person->deathDate = strip_tags($record->getDeathDate()->MinDate()->format('%Y') . 
				'/' . $record->getDeathDate()->MaxDate());
		}
		
		/*
		 * TODO: Add thumbnail, add image, address etc.
		 */ 
		
		return $person;
	}
	
	/**
	 * Adds parents to a person, taken from the supplied record.
	 * @var Person $person
	 * @var GedcomRecord $record
	 */
	public static function addParentsFromRecord($person, $record) {
		$parentFamily = $record->getPrimaryChildFamily();
		if (!$parentFamily) {
			/* No family, no parents to be added */ 
			return $person;
		}
		
		$husband = new Person();
		$husband = static::fillPersonFromRecord($husband, $parentFamily->getHusband());
		array_push($person->parents, $husband);
		
		$wife = new Person();
		$wife = static::fillPersonFromRecord($wife, $parentFamily->getWife());
		array_push($person->parents, $wife);
		
		return $person;
	}
	
}