#!/bin/bash

(command -v php56 > /dev/null && cd ../ && php56 /usr/local/bin/phpunit ./)
