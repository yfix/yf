#!/bin/bash

# http://stackoverflow.com/questions/59895/can-a-bash-script-tell-what-directory-its-stored-in
DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )
 
DOCROOT="$DIR/public"
ROUTER="$DIR/router.php"
HOST=0.0.0.0
PORT=33380
 
PHP=$(which php)
if [ $? != 0 ] ; then
    echo "Unable to find PHP"
    exit 1
fi

if [ ! -f "$DOCROOT" ]; then
	mkdir -p $DOCROOT;
fi

$PHP -S $HOST:$PORT -t $DOCROOT $ROUTER
