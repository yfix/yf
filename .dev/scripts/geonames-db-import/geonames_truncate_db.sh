#!/bin/bash

. ./geonames_mysql_config.sh

echo "Truncating \"geonames\" database"
$mysql $db_name < ./sql/geonames_truncate_db.sql
