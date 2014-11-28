#!/bin/bash

DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )
TMP_DIR=$DIR/tmp

# php deps
#export COMPOSER_HOME=/usr/local/share/composer/
COMPOSER_EXISTS=$(command composer 2> /dev/null)
if [ -z "$COMPOSER_EXISTS" ]; then 
	cd /usr/local/bin/
	curl -sS https://getcomposer.org/installer | php
	ln -s composer.phar composer
fi
composer global self-update
composer global install

# node deps
npm install uglify-js@1 && mkdir -p $DIR/vendor/uglifyjs && mv $DIR/node_modules $DIR/vendor/uglifyjs
npm install
export UGLIFYJS_BIN=$DIR/vendor/uglifyjs/node_modules/uglify-js/bin/uglifyjs
export AUTOPREFIXER_BIN=$DIR/node_modules/autoprefixer/autoprefixer

# java deps
mkdir -p vendor/java
(cd $TMP_DIR && wget http://dl.google.com/closure-compiler/compiler-latest.zip && unzip compiler-latest.zip -d $DIR/vendor/java/compiler)
export CLOSURE_JAR=$DIR/vendor/java/compiler/compiler.jar
(cd $TMP_DIR && wget http://closure-stylesheets.googlecode.com/files/closure-stylesheets-20111230.jar && mv closure-stylesheets-20111230.jar $DIR/vendor/java)
export GSS_JAR=$DIR/vendor/java/closure-stylesheets-20111230.jar
(cd $TMP_DIR && wget https://github.com/downloads/nzakas/cssembed/cssembed-0.4.5.jar && mv cssembed-0.4.5.jar $DIR/vendor/java)
export CSSEMBED_JAR=$DIR/vendor/java/cssembed-0.4.5.jar
(cd $TMP_DIR && wget http://yui.zenfs.com/releases/yuicompressor/yuicompressor-2.4.7.zip && unzip yuicompressor-2.4.7.zip -d $DIR/vendor/java)
export YUI_COMPRESSOR_JAR=$DIR/vendor/java/yuicompressor-2.4.7/build/yuicompressor-2.4.7.jar

# # ruby deps
sudo apt-get install -y bundler
bundle install

# other deps
sudo apt-get install -y jpegoptim libjpeg-progs optipng

(cd $TMP_DIR && wget -q http://storage.googleapis.com/dart-archive/channels/stable/release/latest/sdk/dartsdk-linux-x64-release.zip && unzip dartsdk-linux-x64-release.zip && mv dart-sdk $DIR/vendor)
export DART_BIN=$DIR/vendor/dart-sdk/bin/dart2js

(cd $TMP_DIR && wget -q http://static.jonof.id.au/dl/kenutils/pngout-20130221-linux.tar.gz && tar -xzf pngout-20130221-linux.tar.gz && mv pngout-20130221-linux $DIR/vendor)
export PNGOUT_BIN=$DIR/vendor/pngout-20130221-linux/x86_64/pngout

rm -rf $TMP_DIR
