#!/bin/bash

(
cd ../
for f in $(find ./unit/ -type f -name '*.Test.php'); do
	echo $f;
	php70 /usr/local/share/composer/vendor/bin/codecept run unit $f;
done
)