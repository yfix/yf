#!/bin/bash

DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )
TMP_DIR=$DIR/tmp
BIN_DIR=/usr/local/bin
mkdir -p $TMP_DIR

# java deps
mkdir -p $BIN_DIR/vendor/java

if [ ! -f "$BIN_DIR/closure-compiler" ]; then 
	(cd $TMP_DIR && wget -nc http://dl.google.com/closure-compiler/compiler-latest.zip && unzip -n compiler-latest.zip -d $BIN_DIR/vendor/java/compiler)
	ln -vs $BIN_DIR/vendor/java/compiler/compiler.jar $BIN_DIR/closure-compiler.jar
	echo 'java -jar '$BIN_DIR'/closure-compiler.jar "$@"' > $BIN_DIR/closure-compiler
	chmod +x $BIN_DIR/closure-compiler
fi

if [ ! -f "$BIN_DIR/closure-stylesheets" ]; then 
	(cd $TMP_DIR && wget -nc http://closure-stylesheets.googlecode.com/files/closure-stylesheets-20111230.jar && mv -n closure-stylesheets-20111230.jar $BIN_DIR/vendor/java)
	ln -vs $BIN_DIR/vendor/java/closure-stylesheets-20111230.jar $BIN_DIR/closure-stylesheets.jar
	echo 'java -jar '$BIN_DIR'/closure-stylesheets.jar "$@"' > $BIN_DIR/closure-stylesheets
	chmod +x $BIN_DIR/closure-stylesheets
fi

if [ ! -f "$BIN_DIR/cssembed" ]; then 
	(cd $TMP_DIR && wget -nc https://github.com/downloads/nzakas/cssembed/cssembed-0.4.5.jar && mv -n cssembed-0.4.5.jar $BIN_DIR/vendor/java)
	ln -vs $BIN_DIR/vendor/java/cssembed-0.4.5.jar $BIN_DIR/cssembed.jar
	echo 'java -jar '$BIN_DIR'/cssembed.jar "$@"' > $BIN_DIR/cssembed
	chmod +x $BIN_DIR/cssembed
fi

if [ ! -f "$BIN_DIR/yuicompressor" ]; then 
	(cd $TMP_DIR && wget -nc http://yui.zenfs.com/releases/yuicompressor/yuicompressor-2.4.7.zip && unzip -n yuicompressor-2.4.7.zip -d $BIN_DIR/vendor/java)
	ln -vs $BIN_DIR/vendor/java/yuicompressor-2.4.7/build/yuicompressor-2.4.7.jar $BIN_DIR/yuicompressor.jar
	echo 'java -jar '$BIN_DIR'/yuicompressor.jar "$@"' > $BIN_DIR/yuicompressor
	chmod +x $BIN_DIR/yuicompressor
fi

rm -rf $DIR/tmp
