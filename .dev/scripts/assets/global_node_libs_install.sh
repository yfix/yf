#!/bin/bash

DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )

# node deps
sudo apt-get install -y npm

npm install -g \
	coffee-script \
	typescript \
	less \
	handlebars \
	autoprefixer
