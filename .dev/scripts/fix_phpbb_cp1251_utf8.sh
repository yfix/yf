#!bin/bash

echo 'SELECT CONCAT(`TABLE_NAME`, ".",`COLUMN_NAME`) FROM `COLUMNS` WHERE `TABLE_SCHEMA` = "svit_forum3" AND `DATA_TYPE` REGEXP "(text|char)"' | mysql information_schema > /tmp/cols.txt
for col in `cat /tmp/cols.txt`; do
	echo $col;
done;