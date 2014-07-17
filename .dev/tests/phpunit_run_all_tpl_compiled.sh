#!/bin/bash

for f in $(find . -type f -iname "*_compiled.test.php"); do echo $f; phpunit $f; done
#files=$(find . -type f -iname "*_compiled.test.php");
#echo $files;
#phpunit $files;
