#!/bin/bash

(command -v phpunit > /dev/null && cd ../ && phpunit --testsuite unit-only-tpl -d memory_limit=1024M)
