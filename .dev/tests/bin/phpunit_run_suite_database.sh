#!/bin/bash

(command -v phpunit > /dev/null && cd ../ && phpunit --testsuite database -d memory_limit=1024M ./)
