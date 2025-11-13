<?php
/**
 * webtrees json-ld: online genealogy json-ld-module.
 * Copyright (C) 2015-2025 webtrees development team
 *
 * SPDX-License-Identifier: Apache-2.0 OR EUPL-1.2
 *
 * This program is dual-licensed under Apache-2.0 OR EUPL-1.2.
 * See LICENSE file for details.
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
