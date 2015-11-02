#!/bin/bash

(command -v php53 > /dev/null && cd ../ && php55 /usr/local/bin/phpunit ./)
