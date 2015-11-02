#!/bin/bash

(
	cd ../;
	for f in `find . -type f -name "*.Test.php"`; do php -l $f; done
)