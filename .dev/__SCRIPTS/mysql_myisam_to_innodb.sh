#!/bin/bash

trap "exit -1;" SIGQUIT SIGTERM SIGINT

db_name="$1"
if [ -z $db_name ]; then
	echo "== ERROR: please provide db_name as first argument to this script";
	exit;
else
	echo "== Starting converting from MyISAM into InnoDB for "$db_name
fi

tables=`echo "show tables;" | mysql $mysql_login --column-names=0 $db_name | grep -v '(' | tr '\n' ' '`
for table in $tables; do
	echo "== "$table;
	table_meta=`echo "SHOW CREATE TABLE $table;" | mysql $mysql_login --column-names=0 $db_name`
	is_innodb=`echo "$table_meta" | fgrep -i innodb`
	if [ ! -z "$is_innodb" ]; then
		echo "OK: INNODB";
	else
		echo "MYISAM, converting...";
		sql="ALTER TABLE "$table" ENGINE='InnoDB';";
		time (echo "$sql" | mysql --verbose $mysql_login --column-names=0 $db_name)
	fi
done
