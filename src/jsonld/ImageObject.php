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

/**
 * Image object which represents http://schema.org/ImageObject.
 */
class ImageObject extends JsonLD {

  /**
   * Actual bytes of the media object.
   */
  public $contentUrl;

  /**
   * A name for this image.
   * @var String
   */
  public $name;

  /**
   * Some words about this image.
   * @var String
   */
  public $description;

  /**
   * Width of the image in pixels.
   * @var int
   */
  public $width;

  /**
   * Height of the image in pixels.
   * @var int
   */
  public $height;

  /**
   * A direct link to a thumbnail image file.
   * @var String
   */
  public $thumbnailUrl;

  /**
   * Details on the thumbnail, it is an image object itself.
   * @var ImageObject
   */
  public $thumbnail;

  public function __construct() {
    parent::__construct("ImageObject");
  }
}
