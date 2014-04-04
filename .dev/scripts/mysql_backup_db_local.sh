#!/bin/bash

trap "exit -1;" SIGQUIT SIGTERM SIGINT

db_name="$1"
if [ -z "$db_name" ]; then
	echo "Error: please provide db_name as first argument to this script"; exit;
fi

cur_date=`date +%F`
backup_dir="/home/mysql_backups/"$db_name"/"$cur_date"/"
mkdir $backup_dir -p

tables=`echo "show tables;" | mysql --column-names=0 $db_name | grep -v "("`
for table in $tables
do
	backup_path=$backup_dir""$table".sql";
	echo $backup_path;
	mysqldump -v --force --opt --comments=false --quote-names $db_name $table > $backup_path
	gzip -f -1 $backup_path
done
