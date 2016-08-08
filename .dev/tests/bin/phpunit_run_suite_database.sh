#!/bin/bash

(command -v phpunit > /dev/null && cd ../ && phpunit --testsuite db-all -d memory_limit=1024M)
