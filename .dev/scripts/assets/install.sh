#!/bin/bash

# php deps
composer global self-update
composer global install

# node deps
npm install uglify-js@1 && mkdir -p vendor/uglifyjs && mv node_modules vendor/uglifyjs
npm install
export UGLIFYJS_BIN=vendor/uglifyjs/node_modules/uglify-js/bin/uglifyjs
export AUTOPREFIXER_BIN=node_modules/autoprefixer/autoprefixer

# java deps
mkdir -p vendor/java
wget http://dl.google.com/closure-compiler/compiler-latest.zip && unzip compiler-latest.zip -d vendor/java/compiler
export CLOSURE_JAR=vendor/java/compiler/compiler.jar
wget http://closure-stylesheets.googlecode.com/files/closure-stylesheets-20111230.jar && mv closure-stylesheets-20111230.jar vendor/java
export GSS_JAR=vendor/java/closure-stylesheets-20111230.jar
wget https://github.com/downloads/nzakas/cssembed/cssembed-0.4.5.jar && mv cssembed-0.4.5.jar vendor/java
export CSSEMBED_JAR=vendor/java/cssembed-0.4.5.jar
wget http://yui.zenfs.com/releases/yuicompressor/yuicompressor-2.4.7.zip && unzip yuicompressor-2.4.7.zip -d vendor/java
export YUI_COMPRESSOR_JAR=vendor/java/yuicompressor-2.4.7/build/yuicompressor-2.4.7.jar

# # ruby deps
sudo apt-get install -y bundler
bundle install

# other deps
sudo apt-get install -y jpegoptim libjpeg-progs optipng

wget -q http://storage.googleapis.com/dart-archive/channels/stable/release/latest/sdk/dartsdk-linux-x64-release.zip && unzip dartsdk-linux-x64-release.zip && mv dart-sdk vendor
export DART_BIN=vendor/dart-sdk/bin/dart2js

wget -q http://static.jonof.id.au/dl/kenutils/pngout-20130221-linux.tar.gz && tar -xzf pngout-20130221-linux.tar.gz && mv pngout-20130221-linux vendor
export PNGOUT_BIN=vendor/pngout-20130221-linux/x86_64/pngout

#script: ./bin/phpunit -v
