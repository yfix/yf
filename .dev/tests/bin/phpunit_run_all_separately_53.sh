#!/bin/bash

(
	command -v php53 > /dev/null && \
	cd ../ && \
	for f in `find . -type f -iname "*.test.php"`; do echo $f; php53 /usr/local/bin/phpunit $f; done
)
