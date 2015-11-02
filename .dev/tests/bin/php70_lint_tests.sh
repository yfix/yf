#!/bin/bash

(
	cd ../;
	for f in `find . -type f -name "*.Test.php"`; do php70 -l $f; done
)