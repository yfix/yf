#!/bin/bash

trap "exit -1;" SIGQUIT SIGTERM SIGINT

db_name="$1"
if [ -z "$db_name" ]; then
	echo "Error: please provide db_name as first argument to this script"; exit;
fi

mysql="mysql"

tables=`echo "show tables;" | $mysql --column-names=0 $db_name | grep -v '(' | tr '\n' ' '`
for table in $tables; do
	echo "== "$table;
	table_meta=`echo "SHOW CREATE TABLE $table;" | $mysql --column-names=0 $db_name`
	is_innodb=`echo "$table_meta" | fgrep -i innodb`
	if [ ! -z "$is_innodb" ]; then
		echo "OK: INNODB";
	else
		echo "MYISAM, converting...";
		sql="ALTER TABLE "$table" ENGINE='InnoDB';";
#		echo $sql
		time (echo "$sql" | $mysql --verbose --column-names=0 $db_name)
	fi
done
