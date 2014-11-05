#!/bin/bash

(command -v php57 > /dev/null && cd ../ && php57 /usr/local/bin/phpunit ./)
