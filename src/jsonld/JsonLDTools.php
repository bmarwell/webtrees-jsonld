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
 * Various static function used for JsonLD-output.
 * @author bmarwell
 *
 */
class JsonLDTools {
	/**
	 * Serialize an object into json. Empty values are stripped 
	 * by unsetting the fields.
	 * @param Person $jsonldobject the person (or any other object) to jsonize.
	 * @return Object the uncluttered object, no null values.
	 */
	public static function jsonize($jsonldobject) {
		// FIXME: Possible fatal error because we did not check for object.
		/* create a new object, so we don't modify the original one. */
		$returnobj = clone $jsonldobject;
		
		$returnobj = static::empty_object($returnobj);
	
		/* strip empty key/value-pairs */
		$returnobj = (object) array_filter((array) $returnobj);
		
		return $returnobj;
	}
	
	/**
	 * Unset empty fields from object.
	 * @param Object $obj
	 */
	private static function empty_object(&$obj) {
		
		if (is_array($obj)) {
			/* 
			 * arrays cannot be modified this easily,
			 * a new one is passed for readability.
			 */
			$newarray = array();
			foreach ($obj as $key => $value) {
				array_push($newarray, static::empty_object($value));
			}
			
			return $newarray;
		} else if (is_string($obj) || (is_int($obj))) {
			/* this is just fine */
			return $obj;
		}
		
		echo get_class($obj);
		$returnobj = clone $obj;
		
		foreach (get_object_vars($returnobj) as $key => $value) {
			if (is_object($value)) {
				$value = static::empty_object($returnobj->$key);
			}
			
			if (is_array($value)) {
				$returnobj->$key = static::empty_object($returnobj->$key);
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
	 * @var WT_GedcomRecord $record
	 */
	public static function fillPersonFromRecord($person, $record) {
		$person->name =  $record->getAllNames()[$record->getPrimaryName()]['fullNN'];
		$person->gender = $record->getSex();
		
		/* Dates */
		// XXX: match beginning and end of string, doesn't seem to work.
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
		
		/* add highlighted image */
		if ($record->findHighlightedMedia()) {
			$media = $record->findHighlightedMedia(); 
			$person->image = new ImageObject();
			$person->image->contentUrl = WT_SERVER_NAME . WT_SCRIPT_PATH . $media->getHtmlUrlDirect();
			$person->image->name = $media->getAllNames()[$media->getPrimaryName()]['fullNN'];
			// [0]=width [1]=height [2]=filetype ['mime']=mimetype
			$person->image->width = $media->getImageAttributes()[0];
			$person->image->height = $media->getImageAttributes()[1];
			$person->image->description = strip_tags($media->getFullName());
			$person->image->thumbnailUrl = WT_SERVER_NAME . WT_SCRIPT_PATH . $media->getHtmlUrlDirect('thumb');
			
			$person->image->thumbnail = new ImageObject();
			$person->image->thumbnail->contentUrl = WT_SERVER_NAME . WT_SCRIPT_PATH . $media->getHtmlUrlDirect('thumb');
			$person->image->thumbnail->width = $media->getImageAttributes('thumb')[0];
			$person->image->thumbnail->height = $media->getImageAttributes('thumb')[1];
		}
		
		/*
		 * TODO: Add address, places, relatives, etc.
		 */
		
		return $person;
	}
	
	/**
	 * Adds parents to a person, taken from the supplied record.
	 * @var Person $person the person where parents should be added to.
	 * @var WT_GedcomRecord $record the person's gedcom record.
	 */
	public static function addParentsFromRecord($person, $record) {
		$parentFamily = $record->getPrimaryChildFamily();
		if (!$parentFamily) {
			/* No family, no parents to be added */ 
			return $person;
		}
		
		$husband = new Person();
		$husband = static::fillPersonFromRecord($husband, $parentFamily->getHusband());
		$person->addParent($husband);
		
		$wife = new Person();
		$wife = static::fillPersonFromRecord($wife, $parentFamily->getWife());
		$person->addParent($wife);
		
		return $person;
	}
	
}