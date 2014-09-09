#!/bin/bash
 
# http://stackoverflow.com/questions/59895/can-a-bash-script-tell-what-directory-its-stored-in
DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )

$DIR/stop_server.sh
$DIR/run_server.sh >/dev/null 2>&1 &
