#!/bin/bash

DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )

echo "=== php deps"
$DIR/global_php_libs_install.sh

echo "=== node deps"
$DIR/global_node_libs_install.sh

echo "=== java deps"
$DIR/global_java_libs_install.sh

echo "=== ruby deps"
$DIR/global_ruby_libs_install.sh

echo "=== other deps"
$DIR/global_other_libs_install.sh

rm -rf $DIR/tmp
