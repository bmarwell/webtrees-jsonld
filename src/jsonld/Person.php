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

use Fisharebest\Webtrees\Place;

/**
 * Person for serializing into json-ld. Vars have name from schema.org.
 */
class Person extends JsonLD {

  /**
   * @var
   */
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

  /**
   * Date of Birth in ISO.
   * @var String $birthDate ;
   */
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

  /**
   * E-Mail adress as string.
   * @var String $email
   */
  public $email;

  /**
   * @var String $url
   */
  public $url;

  /**
   * @var array $address
   */
  public $address = array();

  /**
   * U or M or F.
   * @var string Gender
   */
  public $gender = "U";

  /**
   * @var array
   */
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

  /**
   * @param bool|FALSE $addContext
   */
  public function __construct($addContext = false) {
    parent::__construct("Person", $addContext);
  }

  /**
   * @param Person $address
   */
  public function addAddress($address) {
    array_push($this->address, $address);
  }

  /**
   * @param Person $parent
   */
  public function addParent(Person $parent) {
    array_push($this->parents, $parent);
  }

  /**
   * @param Person $child
   */
  public function addChild(Person $child) {
    array_push($this->children, $child);
  }
}
