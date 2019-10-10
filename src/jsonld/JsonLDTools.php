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

use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\Note;
use Fisharebest\Webtrees\Repository;
use Fisharebest\Webtrees\Source;

/**
 * Various static function used for JsonLD-output.
 */
class JsonLDTools
{

    /**
     * Serialize an object into json. Empty values are stripped
     * by unsetting the fields.
     * @param Person $jsonldobject the person (or any other object) to jsonize.
     * @return GedcomRecord|Individual|Family|Source|Repository|Media|Note the uncluttered object, no null values.
     */
    public static function jsonize($jsonldobject)
    {
        if (empty($jsonldobject) || (!is_object($jsonldobject))) {
            return new Person(true);
        }
        /* create a new object, so we don't modify the original one. */
        /** @var GedcomRecord|Individual|Family|Source|Repository|Media|Note $returnobj */
        $returnobj = clone $jsonldobject;

        $returnobj = static::emptyObject($returnobj);

        /* strip empty key/value-pairs */
        $returnobj = (object) array_filter((array) $returnobj);

        return $returnobj;
    }

    /**
     * Unset empty fields from object.
     * @param GedcomRecord|Individual|Family|Source|Repository|Media|Note|ImageObject|JsonLD_Place $obj
     * @return ImageObject|Family|GedcomRecord|Individual|Media|Note|Repository|Source|JsonLD_Place
     */
    private static function emptyObject(&$obj)
    {
        if (is_array($obj)) {
            /*
             * arrays cannot be modified this easily,
             * a new one is passed for readability.
             */
            $newarray = array();
            foreach ($obj as $key => $value) {
                array_push($newarray, static::emptyObject($value));
            }

            return $newarray;
        } elseif (is_string($obj) || (is_int($obj))) {
            /* this is just fine */
            return $obj;
        }
        $returnobj = clone $obj;

        foreach (get_object_vars($returnobj) as $key => $value) {
            if (is_object($value)) {
                static::emptyObject($returnobj->$key);
                $value = static::emptyObject($returnobj->$key);
            }

            if (is_array($value)) {
                $returnobj->$key = static::emptyObject($returnobj->$key);
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
     * @var GedcomRecord|Individual|Family|Source|Repository|Media|Note $record
     * @return Person
     */
    public static function fillPersonFromRecord($person, $record)
    {
        /* check if record exists */
        if (empty($record)) {
            return $person;
        }

        $person->name = $record->getAllNames()[$record->getPrimaryName()]['fullNN'];
        $person->givenName = $record->getAllNames()[$record->getPrimaryName()]['givn'];
        $person->familyName = $record->getAllNames()[$record->getPrimaryName()]['surn'];
        //         $person->familyName =  $record->getAllNames()[$record->getPrimaryName()]['surname'];
        $person->gender = $record->sex();
        $person->setId($record->url());

        /* Dates */
        // XXX: match beginning and end of string, doesn't seem to work.
        $birthdate = $record->getBirthDate()->display(false, '%Y-%m-%d', false);
        if (preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $birthdate) === 1) {
            $person->birthDate = strip_tags($birthdate);
        } elseif (preg_match('/between/', $birthdate)) {
            $person->birthDate = strip_tags($record->getBirthDate()->maximumDate()->format('%Y') .
                '/' . $record->getBirthDate()->maximumDate()->format('%Y'));
        }

        $deathDate = $record->getDeathDate()->display(false, '%Y-%m-%d', false);
        if (preg_match('/[0-9]{4}-[0-9][0-9]-[0-9][0-9]/', $deathDate) === 1) {
            $person->deathDate = strip_tags($deathDate);
        } elseif (preg_match('/between/', $deathDate)) {
            $person->deathDate = strip_tags($record->getDeathDate()->maximumDate()->format('%Y') .
                '/' . $record->getDeathDate()->maximumDate());
        }

        /* add highlighted image */
        if ($record->findHighlightedMediaFile()) {
            $person->image = static::createMediaObject($record->findHighlightedMediaFile());
            $person->image = static::emptyObject($person->image);
        }

        // TODO: Get place object.
        if ($record->getBirthPlace()->url()) {
            echo "found";
            $person->birthPlace = new JsonLD_Place();
            $person->birthPlace->name = $record->getBirthPlace();
            $person->birthPlace->setId($record->getBirthPlace());
            $person->birthPlace = static::emptyObject($person->birthPlace);
        }

        if ($record->getDeathPlace()->url()) {
            echo "found";
            $person->deathPlace = new JsonLD_Place();
            $person->deathPlace->name = $record->getDeathPlace();
            $person->deathPlace->setId($record->getDeathPlace());
            $person->deathPlace = static::emptyObject($person->deathPlace);
        }

        /*
         * TODO: Add spouse, etc.
         */

        return $person;
    }

    /**
     * @param Media $media
     * @return ImageObject
     */
    private static function createMediaObject($media)
    {
        $imageObject = new ImageObject();

        if (empty($media)) {
            return $imageObject;
        }

        $imageObject->contentUrl = WT_BASE_URL . $media->getHtmlUrlDirect();
        $imageObject->name = $media->getAllNames()[$media->getPrimaryName()]['fullNN'];
        // [0]=width [1]=height [2]=filetype ['mime']=mimetype
        $imageObject->width = $media->getImageAttributes()[0];
        $imageObject->height = $media->getImageAttributes()[1];
        $imageObject->description = strip_tags($media->getFullName());
        $imageObject->thumbnailUrl = WT_BASE_URL . $media->getHtmlUrlDirect('thumb');
        $imageObject->setId($media->getAbsoluteLinkUrl());

        $imageObject->thumbnail = new ImageObject();
        $imageObject->thumbnail->contentUrl = WT_BASE_URL . $media->getHtmlUrlDirect('thumb');
        $imageObject->thumbnail->width = $media->getImageAttributes('thumb')[0];
        $imageObject->thumbnail->height = $media->getImageAttributes('thumb')[1];
        $imageObject->thumbnail->setId($media->getAbsoluteLinkUrl());

        $imageObject->thumbnail = static::emptyObject($imageObject->thumbnail);

        return $imageObject;
    }

    /**
     * Adds parents to a person, taken from the supplied record.
     * @var Person $person the person where parents should be added to.
     * @var GedcomRecord|Individual $record the person's gedcom record.
     * @return Person
     */
    public static function addParentsFromRecord($person, $record)
    {
        if (empty($record)) {
            return $person;
        }

        if (empty($record->getPrimaryChildFamily())) {
            return $person;
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

    /**
     * @param Person $person
     * @param Individual $record
     * @return mixed
     */
    public static function addChildrenFromRecord($person, $record)
    {
        if (empty($record)) {
            return $person;
        }

        if (empty($record->getSpouseFamilies())) {
            return $person;
        }

        /** @var Individual[] $children */
        $children = array();
        /* we need a unique array first */
        foreach ($record->getSpouseFamilies() as $fam) {
            foreach ($fam->getChildren() as $child) {
                $children[$child->getXref()] = $child;
            }
        }

        foreach ($children as $child) {
            if (!$child->canShow()) {
                continue;
            }
            $childPerson = new Person();
            $childPerson = static::fillPersonFromRecord($childPerson, $child);
            $person->addChild($childPerson);
        }

        return $person;
    }
}
