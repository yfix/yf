#!/bin/bash

(command -v phpunit > /dev/null && cd ../ && phpunit --testsuite plugins-all -d memory_limit=1024M)
