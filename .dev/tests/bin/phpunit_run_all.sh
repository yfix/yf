#!/bin/bash

(command -v phpunit > /dev/null && cd ../ && phpunit -d memory_limit=1024M ./)
