# json-ld module for webtrees

## Contents

* [Introduction](#introduction)
* [Installation](#installation)
* [Verification](#verification)
* [Links](#links)
* [License](#license)

### Introduction

This module for webtrees will generate json-ld-compatible code für bots,
search engines, spiders and other engines. For more information visit [schema.org](http://schema.org)
or [json-ld.org](http://json-ld.org/).

### Installation

1. Copy the folder src/jsonld (only the subfolder) to your webtrees/modules_v3-folder.
2. Go to the admin menu and modules, then enable the JsonLD-Module.

### Update

If you have a previous version of JsonLD installed, remove the folder `modules_v4/jsonld`. The new Foldername reflects
the title (which has capital letters). Otherwise, this plugin will not work!

### Verification

To see if it works, open up the [Google Structured Data Testing Tool](https://developers.google.com/structured-data/testing-tool/)
and paste the url of any public individual.
You should also see a new tab on individuals, containing the hidden source code and also
the human-readable version inside a html pre-tag.

#### Content Negotiation with Accept Header

This module supports HTTP content negotiation. When you request an individual page with the 
`Accept: application/ld+json` header, the module will return pure JSON-LD data without any HTML.

Example using curl:

```shell
# Get JSON-LD data directly using Accept header
curl -H "Accept: application/ld+json" "http://path.to/webtrees/individual.php?pid=I1&ged=AllGED"
```

The response will have:
- `Content-Type: application/ld+json; charset=utf-8` header
- Pure JSON-LD data (no HTML wrapper)

For normal HTML requests, the module adds a `Link` header to advertise that JSON-LD is available:
```
Link: <http://path.to/webtrees/individual.php?pid=I1&ged=AllGED>; rel="alternate"; type="application/ld+json"
```

You can also extract JSON-LD from the embedded HTML script tag:

```shell
curl -so - "http://​path.to/​webtrees/​individual.​php?​pid=I1&​ged=AllGED" | \
    xmllint --html --xpath "//​script[@​id='json-​ld-​data']/​text()" - 2>/dev/null | \
    jq -C '.​'
```

### Links

* Forum discussion: [Webtrees: Schema.org](http://www.webtrees.net/index.php/en/forum/2-open-discussion/27014-schema-org).
* The parent project’s website is [webtrees.net](http://webtrees.net).
* German Description: [Webtrees-Module](https://www.bmarwell.de/projekte/webtrees-module/).
* Examples on how to parse with curl (German): [Webtrees-Plugin: json-ld](https://blog.bmarwell.de/webtrees-plugin-json-ld/).
* Google's testing tool: [Structured Data Testing Tool](https://developers.google.com/structured-data/testing-tool/).

### License

webtrees json-ld: online genealogy json-ld-module.
Copyright (C) 2015 webtrees development team

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
 along with this program. If not, see <http://www.gnu.org/licenses/>.
