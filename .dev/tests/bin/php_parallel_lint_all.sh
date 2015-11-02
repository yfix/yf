#!/bin/bash

# composer global require jakub-onderka/php-parallel-lint
DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )
(
	cd ../../../;
	binary="parallel-lint"
	if [[ -f "$TRAVIS_BUILD_DIR/vendor/bin/$binary" ]]; then
		binary=$TRAVIS_BUILD_DIR"/vendor/bin/"$binary;
	elif [[ -f "$DIR/vendor/bin/$binary" ]]; then
		binary=$DIR"/vendor/bin/"$binary;
	fi
	$binary -e php --exclude libs --exclude vendor .
)