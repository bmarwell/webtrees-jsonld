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
     * @return Person
     * @var Individual $individual
     * @var Person $person
     */
    public static function fillPersonFromRecord($person, $individual)
    {
        /* check if record exists */
        if (empty($individual)) {
            return $person;
        }

        $person->name = $individual->getAllNames()[$individual->getPrimaryName()]['fullNN'];
        $person->givenName = $individual->getAllNames()[$individual->getPrimaryName()]['givn'];
        $person->familyName = $individual->getAllNames()[$individual->getPrimaryName()]['surn'];
        //         $person->familyName =  $record->getAllNames()[$record->getPrimaryName()]['surname'];
        $person->gender = $individual->sex();
        $person->setId($individual->url());

        /* Dates */
        // XXX: match beginning and end of string, doesn't seem to work.
        $birthdate = $individual->getBirthDate()->display(false, '%Y-%m-%d', false);
        if (preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $birthdate) === 1) {
            $person->birthDate = strip_tags($birthdate);
        } elseif (preg_match('/between/', $birthdate)) {
            $person->birthDate = strip_tags($individual->getBirthDate()->maximumDate()->format('%Y') .
                '/' . $individual->getBirthDate()->maximumDate()->format('%Y'));
        }

        $deathDate = $individual->getDeathDate()->display(false, '%Y-%m-%d', false);
        if (preg_match('/[0-9]{4}-[0-9][0-9]-[0-9][0-9]/', $deathDate) === 1) {
            $person->deathDate = strip_tags($deathDate);
        } elseif (preg_match('/between/', $deathDate)) {
            $person->deathDate = strip_tags($individual->getDeathDate()->maximumDate()->format('%Y') .
                '/' . $individual->getDeathDate()->maximumDate());
        }

        /* add highlighted image */
        if ($individual->findHighlightedMediaFile()) {
            $person->image = static::createMediaObject($individual->findHighlightedMediaFile());
            $person->image = static::emptyObject($person->image);
        }

        // TODO: Get place object.
        if ($individual->getBirthPlace()->url()) {
            $person->birthPlace = new JsonLD_Place();
            $person->birthPlace->name = $individual->getBirthPlace();
            $person->birthPlace->setId($individual->getBirthPlace());
            $person->birthPlace = static::emptyObject($person->birthPlace);
        }

        if ($individual->getDeathPlace()->url()) {
            $person->deathPlace = new JsonLD_Place();
            $person->deathPlace->name = $individual->getDeathPlace();
            $person->deathPlace->setId($individual->getDeathPlace());
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
     * @return Person
     * @var GedcomRecord|Individual $record the person's gedcom record.
     * @var Person $person the person where parents should be added to.
     */
    public static function addParentsFromRecord($person, $record)
    {
        if (empty($record)) {
            return $person;
        }

        if (empty($record->primaryChildFamily())) {
            return $person;
        }

        $parentFamily = $record->primaryChildFamily();

        if (!$parentFamily) {
            /* No family, no parents to be added */
            return $person;
        }

        $husbandInd = $parentFamily->husband();
        if ($husbandInd && $husbandInd->canShow()) {
            Auth::checkIndividualAccess($husbandInd);
            $husband = new Person();
            $husband = static::fillPersonFromRecord($husband, $husbandInd);
            $person->addParent($husband);
        }

        $wifeInd = $parentFamily->wife();
        if ($wifeInd && $wifeInd->canShow()) {
            Auth::checkIndividualAccess($wifeInd);
            $wife = new Person();
            $wife = static::fillPersonFromRecord($wife, $wifeInd);
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

        if (empty($record->spouseFamilies())) {
            return $person;
        }

        /** @var Individual[] $children */
        $children = array();
        /* we need a unique array first */
        foreach ($record->spouseFamilies() as $fam) {
            foreach ($fam->children() as $child) {
                $children[$child->getXref()] = $child;
            }
        }

        foreach ($children as $child) {
            if (!$child->canShow()) {
                continue;
            }
            Auth::checkIndividualAccess($child);
            $childPerson = new Person();
            $childPerson = static::fillPersonFromRecord($childPerson, $child);
            $person->addChild($childPerson);
        }

        return $person;
    }
}
