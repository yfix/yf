#!/bin/bash

DIR=$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )

# php deps
source $DIR/global_php_libs_install.sh

# node deps
source $DIR/global_node_libs_install.sh

# java deps
source $DIR/global_java_libs_install.sh

# # ruby deps
source $DIR/global_ruby_libs_install.sh

# other deps
source $DIR/global_other_libs_install.sh

rm -rf $DIR/tmp
