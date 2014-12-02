#!/bin/bash

DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )
TMP_DIR=$DIR/tmp
BIN_DIR=/usr/local/bin
mkdir -p $TMP_DIR

source $DIR/composer_require_global.sh

# node deps
npm install -g uglify-js@1
npm install -g
#export AUTOPREFIXER_BIN=$DIR/node_modules/autoprefixer/autoprefixer

# java deps
mkdir -p $BIN_DIR/vendor/java

(cd $TMP_DIR && wget -nc http://dl.google.com/closure-compiler/compiler-latest.zip && unzip -n compiler-latest.zip -d $BIN_DIR/vendor/java/compiler)
ln -vs $BIN_DIR/vendor/java/compiler/compiler.jar $BIN_DIR/closure-compiler.jar

(cd $TMP_DIR && wget -nc http://closure-stylesheets.googlecode.com/files/closure-stylesheets-20111230.jar && mv -n closure-stylesheets-20111230.jar $BIN_DIR/vendor/java)
ln -vs $BIN_DIR/vendor/java/closure-stylesheets-20111230.jar $BIN_DIR/closure-stylesheets.jar

(cd $TMP_DIR && wget -nc https://github.com/downloads/nzakas/cssembed/cssembed-0.4.5.jar && mv -n cssembed-0.4.5.jar $BIN_DIR/vendor/java)
ln -vs $BIN_DIR/vendor/java/cssembed-0.4.5.jar $BIN_DIR/cssembed.jar

(cd $TMP_DIR && wget -nc http://yui.zenfs.com/releases/yuicompressor/yuicompressor-2.4.7.zip && unzip -n yuicompressor-2.4.7.zip -d $BIN_DIR/vendor/java)
ln -vs $BIN_DIR/vendor/java/yuicompressor-2.4.7/build/yuicompressor-2.4.7.jar $BIN_DIR/yuicompressor.jar

# # ruby deps
sudo apt-get install -y bundler
bundle install

# other deps
sudo apt-get install -y jpegoptim libjpeg-progs optipng

(cd $TMP_DIR && wget -nc http://storage.googleapis.com/dart-archive/channels/stable/release/latest/sdk/dartsdk-linux-x64-release.zip && unzip -n dartsdk-linux-x64-release.zip && mv -f dart-sdk $BIN_DIR/vendor)
ln -vs $BIN_DIR/vendor/dart-sdk/bin/dart2js $BIN_DIR/dart2js

(cd $TMP_DIR && wget -nc http://static.jonof.id.au/dl/kenutils/pngout-20130221-linux.tar.gz && tar -xzf pngout-20130221-linux.tar.gz && mv -n pngout-20130221-linux $BIN_DIR/vendor)
ln -vs $BIN_DIR/vendor/pngout-20130221-linux/x86_64/pngout $BIN_DIR/pngout

rm -rf $TMP_DIR
