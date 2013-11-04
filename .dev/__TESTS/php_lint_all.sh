#!/bin/bash

cd ../../;
for f in `find . -type f -name "*.php" | grep -v /libs/`; do php -l $f; done