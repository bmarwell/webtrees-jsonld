#!/usr/bin/env bash
set -xeuo pipefail

WT_VERSION="$1"

rm -rf /webtrees.zip || true
rm -rf /webtrees || true
rm -rf /wt_data || true

curl -sSL --url "https://github.com/fisharebest/webtrees/releases/download/2.0.0-beta.4/webtrees-${WT_VERSION}.zip"  -o /webtrees.zip \
  && unzip /webtrees.zip \
  && mv /webtrees/modules_v4 /modules_v4 && ln -s /modules_v4 /webtrees/modules_v4 \
  && rm -rf /webtrees/data && mkdir /wt_data && ln -s /wt_data /webtrees/data && chmod 777 /wt_data

tail -f /dev/null