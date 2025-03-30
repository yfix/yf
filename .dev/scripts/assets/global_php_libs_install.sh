#!/bin/bash

DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )

# php deps
source $DIR/global_composer_install.sh

unlink $DIR/composer.lock

composer global require --dev \
	leafo/lessphp \
	leafo/scssphp \
	ptachoire/cssembed \
	mrclay/minify \
	"natxet/CssMin:3.0.*" \
	"yfix/packager:dev-master"
