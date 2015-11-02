#!/bin/bash

(
	command -v php54 > /dev/null && \
	cd ../ && \
	for f in `find . -type f -iname "*.test.php"`; do echo $f; php54 /usr/local/bin/phpunit $f; done
)
