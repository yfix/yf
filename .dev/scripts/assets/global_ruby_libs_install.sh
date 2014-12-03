#!/bin/bash

DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )

# # ruby deps
sudo apt-get install -y bundler
bundle install
