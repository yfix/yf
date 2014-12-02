#!/bin/bash

DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )
BIN_DIR=/usr/local/bin

# php deps
export COMPOSER_HOME=/usr/local/share/composer/
COMPOSER_EXISTS=$(command composer 2> /dev/null)
if [ -z "$COMPOSER_EXISTS" ]; then 
	cd $BIN_DIR
	curl -sS https://getcomposer.org/installer | php
	ln -vs composer.phar composer
fi
composer global self-update
composer global require --dev leafo/lessphp leafo/scssphp ptachoire/cssembed mrclay/minify meenie/javascript-packer "patchwork/jsqueeze:1.*" "natxet/CssMin:3.0.*" "yfix/packager:dev-master" 

