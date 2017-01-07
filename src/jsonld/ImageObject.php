<?php
/**
 * webtrees json-ld: online genealogy json-ld-module.
 * Copyright (C) 2015 webtrees development team
 *
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

/**
 * Image object which represents http://schema.org/ImageObject.
 * @author bmarwell@gmail.com
 *
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
