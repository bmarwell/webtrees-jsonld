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
		$returnobj = clone $obj;
		
		foreach (get_object_vars($returnobj) as $key => $value) {
			if (is_object($value)) {
				static::empty_object($returnobj->$key);
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
		/* check if record exists */
		if (empty($record)) {
			return null;
		}
		
		$person->name =  $record->getAllNames()[$record->getPrimaryName()]['fullNN'];
		$person->gender = $record->getSex();
		$person->setId($record->getAbsoluteLinkUrl());
		
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
			$person->media = static::createMediaObject($record->findHighlightedMedia());
			$person->media = static::empty_object($person->media);
		}
		
		// TODO: Get place object.
		if ($record->getBirthPlace()) {
			$person->birthPlace = new jsonld_Place();
			$person->birthPlace->name = $record->getBirthPlace();
			$person->birthPlace->setId($record->getBirthPlace());
			$person->birthPlace = static::empty_object($person->birthPlace);
		}
		
		if ($record->getDeathPlace()) {
			$person->deathPlace = new jsonld_Place();
			$person->deathPlace->name = $record->getDeathPlace();
			$person->deathPlace->setId($record->getDeathPlace());
			$person->deathPlace = static::empty_object($person->deathPlace);
		}
		
		/*
		 * TODO: Add, etc.
		 */
		
		return $person;
	}
	
	private static function createMediaObject($media) {
		$imageObject = new ImageObject();
		
		if (empty($media)) {
			return $imageObject;
		}
		
		$imageObject->contentUrl = WT_SERVER_NAME . WT_SCRIPT_PATH . $media->getHtmlUrlDirect();
		$imageObject->name = $media->getAllNames()[$media->getPrimaryName()]['fullNN'];
		// [0]=width [1]=height [2]=filetype ['mime']=mimetype
		$imageObject->width = $media->getImageAttributes()[0];
		$imageObject->height = $media->getImageAttributes()[1];
		$imageObject->description = strip_tags($media->getFullName());
		$imageObject->thumbnailUrl = WT_SERVER_NAME . WT_SCRIPT_PATH . $media->getHtmlUrlDirect('thumb');
		$imageObject->setId($media->getAbsoluteLinkUrl());
			
		$imageObject->thumbnail = new ImageObject();
		$imageObject->thumbnail->contentUrl = WT_SERVER_NAME . WT_SCRIPT_PATH . $media->getHtmlUrlDirect('thumb');
		$imageObject->thumbnail->width = $media->getImageAttributes('thumb')[0];
		$imageObject->thumbnail->height = $media->getImageAttributes('thumb')[1];
		$imageObject->thumbnail->setId($media->getAbsoluteLinkUrl());
		
		$imageObject->thumbnail = static::empty_object($imageObject->thumbnail);
		
		return $imageObject;
	}
	
	/**
	 * Adds parents to a person, taken from the supplied record.
	 * @var Person $person the person where parents should be added to.
	 * @var WT_GedcomRecord $record the person's gedcom record.
	 */
	public static function addParentsFromRecord($person, $record) {
		if (empty($record)) {
			return null;
		}
		
		if (empty($record->getPrimaryChildFamily())) {
			return null;
		}
		
		$parentFamily = $record->getPrimaryChildFamily();
		
		if (!$parentFamily) {
			/* No family, no parents to be added */ 
			return $person;
		}
		
		if ($parentFamily->getHusband()) {
			$husband = new Person();
			$husband = static::fillPersonFromRecord($husband, $parentFamily->getHusband());
			$person->addParent($husband);
		}
		
		if ($parentFamily->getWife()) {
			$wife = new Person();
			$wife = static::fillPersonFromRecord($wife, $parentFamily->getWife());
			$person->addParent($wife);
		}
		
		return $person;
	}
	
}
