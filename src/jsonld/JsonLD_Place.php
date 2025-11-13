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

class JsonLD_Place extends JsonLD
{

    /**
     * Name of the place
     * @var String the name of the place.
     */
    public $name;

    /**
     * Geo-Location of the place
     * @var String the geoLocation.
     */
    public $geo;

    /**
     * The address of this place.
     * @var String the address.
     */
    public $address;

    /**
     * Construct using context?
     * @param bool|FALSE $addContext
     */
    public function __construct($addContext = false)
    {
        parent::__construct("Place", $addContext);
    }
}
