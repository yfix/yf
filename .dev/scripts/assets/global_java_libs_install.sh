#!/bin/bash

DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )
TMP_DIR=$DIR/tmp
BIN_DIR=/usr/local/bin
mkdir -p $TMP_DIR

JAVA_EXISTS=$(command java 2> /dev/null)
if [ -z "$JAVA_EXISTS" ]; then 
	sudo apt-get install -y openjdk-7-jre
fi

# java deps
mkdir -p $BIN_DIR/vendor/java

if [ ! -f "$BIN_DIR/yuicompressor" ]; then 
	(cd $TMP_DIR && wget -nc http://yui.zenfs.com/releases/yuicompressor/yuicompressor-2.4.7.zip && unzip -n yuicompressor-2.4.7.zip -d $BIN_DIR/vendor/java)
	ln -vs $BIN_DIR/vendor/java/yuicompressor-2.4.7/build/yuicompressor-2.4.7.jar $BIN_DIR/yuicompressor.jar
	echo 'java -jar '$BIN_DIR'/yuicompressor.jar "$@"' > $BIN_DIR/yuicompressor
	chmod +x $BIN_DIR/yuicompressor
fi

rm -rf $DIR/tmp
