#!/bin/bash

(command -v php54 > /dev/null && cd ../ && php54 /usr/local/bin/phpunit ./)
