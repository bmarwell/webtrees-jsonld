#!/usr/bin/env bash
set -euo pipefail

# wait for container to be up
while [[ ! "$(curl -sS --url 'http://localhost/index.php')"  ]]; do
  sleep 5
done



curl -sS -XPOST --url "http://localhost/index.php&route=setup&dbtype=mysql&dbhost=db&dbuser=webtrees&dbpass=webtrees&dbname=webtrees&tblpfx=wt_&wtuser=webtrees&wtpass=webtrees&step=2"
