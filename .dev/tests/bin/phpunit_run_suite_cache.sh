#!/bin/bash

(command -v phpunit > /dev/null && cd ../ && phpunit --testsuite cache -d memory_limit=1024M ./)
