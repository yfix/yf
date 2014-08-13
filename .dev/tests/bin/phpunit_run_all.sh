#!/bin/bash

(command -v phpunit > /dev/null && cd ../ && phpunit ./)
