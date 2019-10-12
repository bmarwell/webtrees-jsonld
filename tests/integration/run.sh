#!/usr/bin/env bash

shopt -s nullglob
for file in ./tests/integration/scripts/*.sh; do
    if ! docker exec webtrees-integration_web_1 "/scripts/$(basename ${file})"; then
     echo "[ERROR] running ${file}";
     exit 1;
   fi
done
