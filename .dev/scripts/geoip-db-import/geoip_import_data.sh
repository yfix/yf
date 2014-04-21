#!/bin/bash

. ./geoip_mysql_config.sh

echo "Importing geoip data v1"
$mysql --local-infile=1 $db_name < ./sql/geoip_import_data.sql

echo "Importing geoip data v2"
$mysql --local-infile=1 $db_name < ./sql/geoip_import_data2.sql
