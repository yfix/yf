#!/bin/bash

DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )
TMP_DIR=$DIR/tmp
BIN_DIR=/usr/local/bin
mkdir -p $TMP_DIR

# php deps
export COMPOSER_HOME=/usr/local/share/composer/
COMPOSER_EXISTS=$(command composer 2> /dev/null)
if [ -z "$COMPOSER_EXISTS" ]; then 
	cd $BIN_DIR
	curl -sS https://getcomposer.org/installer | php
	ln -s composer.phar composer
fi
composer global self-update
composer global install --dev

# node deps
export UGLIFYJS_BIN=$DIR/vendor/uglifyjs/node_modules/uglify-js/bin/uglifyjs
if [ ! -f "$UGLIFYJS_BIN" ]; then
#	npm install uglify-js@1 && mkdir -p $DIR/vendor/uglifyjs && mv -n $DIR/node_modules $DIR/vendor/uglifyjs
	npm install -g uglify-js@1
fi
ln -s $BIN_DIR/uglifyjs $UGLIFYJS_BIN

npm install
export AUTOPREFIXER_BIN=$DIR/node_modules/autoprefixer/autoprefixer

# java deps
mkdir -p vendor/java

export CLOSURE_JAR=$DIR/vendor/java/compiler/compiler.jar
if [ ! -f "$CLOSURE_JAR" ]; then
	(cd $TMP_DIR && wget -nc http://dl.google.com/closure-compiler/compiler-latest.zip && unzip -n compiler-latest.zip -d $DIR/vendor/java/compiler)
fi

export GSS_JAR=$DIR/vendor/java/closure-stylesheets-20111230.jar
if [ ! -f "$GSS_JAR" ]; then
	(cd $TMP_DIR && wget -nc http://closure-stylesheets.googlecode.com/files/closure-stylesheets-20111230.jar && mv -n closure-stylesheets-20111230.jar $DIR/vendor/java)
fi

export CSSEMBED_JAR=$DIR/vendor/java/cssembed-0.4.5.jar
if [ ! -f "$CSSEMBED_JAR" ]; then
	(cd $TMP_DIR && wget -nc https://github.com/downloads/nzakas/cssembed/cssembed-0.4.5.jar && mv -n cssembed-0.4.5.jar $DIR/vendor/java)
fi

export YUI_COMPRESSOR_JAR=$DIR/vendor/java/yuicompressor-2.4.7/build/yuicompressor-2.4.7.jar
if [ ! -f "$YUI_COMPRESSOR_JAR" ]; then
	(cd $TMP_DIR && wget -nc http://yui.zenfs.com/releases/yuicompressor/yuicompressor-2.4.7.zip && unzip -n yuicompressor-2.4.7.zip -d $DIR/vendor/java)
fi

# # ruby deps
sudo apt-get install -y bundler
bundle install

# other deps
sudo apt-get install -y jpegoptim libjpeg-progs optipng

export DART_BIN=$DIR/vendor/dart-sdk/bin/dart2js
if [ ! -f "$DART_BIN" ]; then
	(cd $TMP_DIR && wget -nc http://storage.googleapis.com/dart-archive/channels/stable/release/latest/sdk/dartsdk-linux-x64-release.zip && unzip -n dartsdk-linux-x64-release.zip && mv -f dart-sdk $DIR/vendor)
fi

export PNGOUT_BIN=$DIR/vendor/pngout-20130221-linux/x86_64/pngout
if [ ! -f "$PNGOUT_BIN" ]; then
	(cd $TMP_DIR && wget -nc http://static.jonof.id.au/dl/kenutils/pngout-20130221-linux.tar.gz && tar -xzf pngout-20130221-linux.tar.gz && mv -n pngout-20130221-linux $DIR/vendor)
fi

rm -rf $TMP_DIR
