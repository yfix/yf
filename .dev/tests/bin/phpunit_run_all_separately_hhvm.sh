#!/bin/bash

(
	cd ../
	for f in `find . -type f -iname "*.test.php"`; do echo $f; hhvm /usr/local/bin/phpunit $f; done
)