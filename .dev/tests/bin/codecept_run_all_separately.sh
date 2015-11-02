#!/bin/bash

(
cd ../
for f in $(find ./acceptance -type f -name '*Cept.php'); do
	echo $f;
	codecept run acceptance $f;
done
for f in $(find ./functional -type f -name '*Cest.php'); do
	echo $f;
	codecept run functional $f;
done
for f in $(find ./unit -type f -name '*.Test.php'); do
	echo $f;
	codecept run unit $f;
done
)