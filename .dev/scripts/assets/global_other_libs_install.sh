#!/bin/bash

DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )
TMP_DIR=$DIR/tmp
BIN_DIR=/usr/local/bin
mkdir -p $TMP_DIR

# other deps
sudo apt-get install -y jpegoptim libjpeg-progs optipng

if [ ! -f "$BIN_DIR/dart2js" ]; then 
	(cd $TMP_DIR && wget -nc http://storage.googleapis.com/dart-archive/channels/stable/release/latest/sdk/dartsdk-linux-x64-release.zip && unzip -n dartsdk-linux-x64-release.zip && mv -f dart-sdk $BIN_DIR/vendor)
	ln -vs $BIN_DIR/vendor/dart-sdk/bin/dart2js $BIN_DIR/dart2js
	chmod +x $BIN_DIR/dart2js
fi

if [ ! -f "$BIN_DIR/pngout" ]; then 
	(cd $TMP_DIR && wget -nc http://static.jonof.id.au/dl/kenutils/pngout-20130221-linux.tar.gz && tar -xzf pngout-20130221-linux.tar.gz && mv -n pngout-20130221-linux $BIN_DIR/vendor)
	ln -vs $BIN_DIR/vendor/pngout-20130221-linux/x86_64/pngout $BIN_DIR/pngout
	chmod +x $BIN_DIR/pngout
fi

rm -rf $TMP_DIR
