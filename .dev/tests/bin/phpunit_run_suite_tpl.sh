#!/bin/bash

(command -v phpunit > /dev/null && cd ../ && phpunit --testsuite tpl -d memory_limit=1024M ./)
