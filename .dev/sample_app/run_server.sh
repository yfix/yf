#!/bin/bash
 
DOCROOT="$(pwd)/public"
ROUTER="$(pwd)/index.php"
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
