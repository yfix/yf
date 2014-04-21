#!/bin/bash

. ./geonames_mysql_config.sh

./geonames_download_data.sh;
./geonames_import_data.sh;
