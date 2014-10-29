#!/bin/bash

(
	command -v php56 > /dev/null && \
	cd ../ && \
	for f in `find . -type f -iname "*.test.php"`; do echo $f; php56 /usr/local/bin/phpunit $f; done
)
