#!/bin/bash

DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )

# node deps
npm install -g \
	uglify-js \
	uglifycss \
	coffee-script \
	stylus \
	nib \
	ember-precompile \
	typescript \
	less \
	handlebars \
	autoprefixer
