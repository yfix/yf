#!/bin/bash

for f in `find . -type f -iname "*.test.php"`; do echo $f; phpunit $f; done