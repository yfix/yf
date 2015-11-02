#!/bin/bash

(
	command -v php70 > /dev/null && \
	cd ../ && \
	for f in `find . -type f -iname "*.test.php"`; do echo $f; php70 /usr/local/bin/phpunit $f; done
)
