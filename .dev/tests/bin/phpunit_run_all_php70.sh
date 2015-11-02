#!/bin/bash

(command -v php70 > /dev/null && cd ../ && php70 /usr/local/bin/phpunit ./)
