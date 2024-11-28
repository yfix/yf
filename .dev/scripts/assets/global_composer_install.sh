#!/bin/bash

DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )
BIN_DIR=/usr/local/bin

# php deps
export COMPOSER_HOME=/usr/local/share/composer/
export PATH="$COMPOSER_HOME/vendor/bin:$PATH"
COMPOSER_EXISTS=$(command composer 2> /dev/null)
if [ -z "$COMPOSER_EXISTS" ]; then 
	sudo apt-get install -y curl
	cd $BIN_DIR
	curl -sS https://getcomposer.org/installer | php
	ln -vs composer.phar composer
fi
composer global self-update
