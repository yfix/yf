#!/bin/bash

(
	cd ../
	for f in $(find . -type f -iname "*_compiled.test.php"); do echo $f; phpunit $f; done
)