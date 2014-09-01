#!/bin/bash

(command -v hhvm > /dev/null && cd ../ && hhvm /usr/local/bin/phpunit ./)