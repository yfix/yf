#!/bin/bash

(
	command -v php57 > /dev/null && \
	cd ../ && \
	for f in `find . -type f -iname "*.test.php"`; do echo $f; php57 /usr/local/bin/phpunit $f; done
)
