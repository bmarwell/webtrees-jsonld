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

namespace bmarwell\WebtreesModules\jsonld;

use Fisharebest\Webtrees\Log;
use Fisharebest\Webtrees\Place;

/**
 * Person for serializing into json-ld. Vars have name from schema.org.
 */
class Person extends JsonLD
{
    public $name;

    /**
     * First name, personal name, forename, christian name.
     * Middle names should go to additionalName.
     * @var String
     */
    public $givenName;

    /**
     * Last name of this person.
     * @var String the last name or family name.
     */
    public $familyName;
    public $birthDate;

    /**
     * BirthPlace
     * @var Place;
     */
    public $birthPlace;

    /**
     * Place of Death
     * @var Place
     */
    public $deathPlace;

    /**
     * Date of death.
     * @var String $deathDate ;
     */
    public $deathDate;

    public $email;
    public $url;
    public $address = array();
    public $gender = "U";
    public $parents = array();

    /**
     * The children of this person.
     * @var Person[] the children, regardless of their families.
     */
    public $children = array();
    /**
     * A gedcom imageObject record.
     * @var ImageObject $image
     */
    public $image;

    function __construct($addContext = FALSE)
    {
        Log::addDebugLog("creating person, context is $addContext");
        parent::__construct("Person", $addContext);
    }

    function addAddress($address)
    {
        array_push($this->address, $address);
    }

    function addParent($parent)
    {
        array_push($this->parents, $parent);
    }

    function addChild($child)
    {
        array_push($this->children, $child);
    }
}