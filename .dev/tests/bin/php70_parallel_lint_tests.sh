#!/bin/bash

# composer global require jakub-onderka/php-parallel-lint
(
	cd ../;
	parallel-lint -p php70 -e php --exclude libs --exclude vendor --exclude _tmp .
)