#!/usr/bin/env bash
set -euo pipefail

## curl test for receiving JSON-LD.

JSON_LD_OUT=$(curl --silent --show-error \
  -H "accept: application/ld+json" --url "http://localhost/index.php?router=individual&pid=1" -o -);

# TODO: remove
echo "${JSON_LD_OUT}"

if [[ 0 -ne $? ]]; then
  echo "Problem executing curl. Output:"
  echo "${JSON_LD_OUT}"
  exit 1;
fi


## add some JQ tests.
