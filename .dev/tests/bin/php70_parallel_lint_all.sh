#!/bin/bash

# composer global require jakub-onderka/php-parallel-lint
DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )
(
	cd ../../../;
	binary="parallel-lint"
	(command -v $binary > /dev/null) || binary=$DIR"/vendor/bin/"$binary
	$binary -p php70 -e php --exclude libs --exclude vendor .
)