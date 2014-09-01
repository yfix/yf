#!/bin/bash

(command -v php53 > /dev/null && cd ../ && php53 /usr/local/bin/phpunit ./)
