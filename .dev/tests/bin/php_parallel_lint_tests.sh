#!/bin/bash

# composer global require jakub-onderka/php-parallel-lint
(
	cd ../;
	parallel-lint -e php --exclude libs --exclude vendor --exclude _tmp .
)