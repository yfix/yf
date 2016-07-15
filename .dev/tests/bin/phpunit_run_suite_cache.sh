#!/bin/bash

(command -v phpunit > /dev/null && cd ../ && phpunit --testsuite functional-only-cache -d memory_limit=1024M)
