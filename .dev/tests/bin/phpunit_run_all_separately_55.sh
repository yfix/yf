#!/bin/bash

(
	command -v php55 > /dev/null && \
	cd ../ && \
	for f in `find . -type f -iname "*.test.php"`; do echo $f; php55 /usr/local/bin/phpunit $f; done
)
