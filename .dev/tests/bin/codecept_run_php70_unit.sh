#!/bin/bash

(command -v codecept > /dev/null && cd ../ && php70 /usr/local/share/composer/vendor/bin/codecept run unit)
