#!/bin/bash

(command -v codecept > /dev/null && cd ../ && codecept run functional)
