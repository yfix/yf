#!/bin/bash

. ./geonames_mysql_config.sh

echo "Creating database $db_name..."
$mysql -Bse "CREATE DATABASE IF NOT EXISTS $db_name DEFAULT CHARACTER SET utf8;"

echo "Creating geonames tables into $db_name..."
$mysql $db_name < ./sql/geonames_db_struct.sql

echo "Truncating \"geonames\" database"
$mysql $db_name < ./sql/geonames_truncate_db.sql

echo "Importing geonames dumps into database $db_name"
$mysql --local-infile=1 $db_name < ./sql/geonames_import_data.sql

echo "Creating indexes for $db_name..."
$mysql $db_name < ./sql/geonames_add_indexes.sql
