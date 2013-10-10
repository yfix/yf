#!/bin/bash

for f in `find . type f -name "*.test.php"`; do echo $f; phpunit --colors $f; done