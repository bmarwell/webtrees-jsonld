# json-ld module for webtrees


## Contents

* [Introduction](#introduction)
* [Installation](#installation)
* [Verification](#verification)
* [Links](#links)
* [License](#license)

### Introduction

This module for webtrees (currently 1.6.x only) will generate json-ld-compatible code für bots, 
search engines, spiders and other engines. For more information visit [schema.org](http://schema.org)
or [json-ld.org](http://json-ld.org/).

### Installation
1. Copy the folder src/jsonld (only the subfolder) to your webtrees/modules_v3-folder.
2. Go to the admin menu and modules, then enable the JsonLD-Module.

### Verification
To see if it works, open up the [Google Structured Data Testing Tool](https://developers.google.com/structured-data/testing-tool/)
and paste the url of any public individual.
You should also see a new tab on individuals, containing the hidden source code and also 
the human-readable version inside a html pre-tag.

If you use curl, you can do sth. like this:
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
Copyright (C) 2015 Benjamin

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