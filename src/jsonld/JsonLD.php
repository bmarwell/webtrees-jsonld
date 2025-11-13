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

abstract class JsonLD {

  /**
   * Creates a JsonLD-Objekt with type set to $jsonldtype.
   * @param String $jsonldtype
   * @param bool|FALSE $addContext
   */
  public function __construct($jsonldtype, $addContext = false) {
    $context = "@context";
    $type = "@type";
    $id = "@id";

    if ($addContext === true) {
      $this->$context = "http://schema.org";
    }

    $this->$type = $jsonldtype;
    $this->$id = null;
  }

  /**
   * Setter for @id. Sadly, schema.org and json-ld do require an @id-field,
   * but php of course doesn't allow this directly. You can still do set the
   * variable name to a variable and then double-reference it, what happens just here.
   * @param String $newId a new identifier (URI, etc.).
   */
  public function setId($newId) {
    $id = "@id";
    $this->$id = $newId;
  }
}
