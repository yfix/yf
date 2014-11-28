#!/bin/bash

DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )

export COMPOSER_HOME=/usr/local/share/composer/
export YF_BIN_PATH=/usr/local/bin/yf

(
COMPOSER_EXISTS=$(command composer 2> /dev/null)
if [ -z "$COMPOSER_EXISTS" ]; then 
	cd /usr/local/bin/
	curl -sS https://getcomposer.org/installer | php
	ln -s composer.phar composer
fi

cd $COMPOSER_HOME && composer global self-update
composer global require symfony/console:~2.4

if [ ! -L "$YF_BIN_PATH" ]; then 
	ln -s $DIR/yf $YF_BIN_PATH;
fi
)