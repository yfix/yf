#!/bin/bash

. ./geonames_mysql_config.sh

usage() {
	echo ""
	echo "Example: " $0 " -a all"
	echo ""
	echo "Usage: " $0 "-a <action> -u <user> -p <password> -h <host> -r <port> -n <db_name>"
	echo
    echo " Where <action> can be one of this: "
    echo "    all      			Do all required actions at once."
	echo "    download-data     Downloads the last packages of data available in GeoNames."
	echo "    sort-data     	Sort downloaded data, remove uneeded feature_classes (not A or P)."
    echo "    create-db         Creates the mysql database structure."
    echo "    create-tables     Creates the mysql tables with no data."
    echo "    import-dumps      Imports geonames data into db. A database is previously needed for this to work."
	echo "    drop-db           Removes the db completely."
    echo "    truncate-db       Removes geonames data from db."
    echo "    split-tables      Split tables into more specific entities."
    echo
    echo " The rest of parameters indicates the following information:"
	echo "    -u <user>     User name to access database server."
	echo "    -p <password> User password to access database server."
	echo "    -h <host>     Data Base Server address (default: localhost)."
	echo "    -r <port>     Data Base Server Port (default: 3306)"
	echo "    -n <db_name>   Data Base Name for the geonames.org data (default: geonames)"
	echo "================================================================================================"
    exit -1
}

if [ $# -lt 1 ]; then
	usage
	exit 1
fi

case "$action" in

    create-db)
        echo "Creating database $db_name..."
        $mysql -Bse "CREATE DATABASE IF NOT EXISTS $db_name DEFAULT CHARACTER SET utf8;"
    ;;

    create-tables)
        echo "Creating geonames tables into $db_name..."
        $mysql $db_name < ./sql/geonames_db_struct.sql
    ;;

    import-dumps)
        echo "Importing geonames dumps into database $db_name"
        $mysql --local-infile=1 $db_name < ./sql/geonames_import_data.sql
    ;;

    create-indexes)
        echo "Creating indexes for $db_name..."
        $mysql $db_name < ./sql/geonames_add_indexes.sql
    ;;
   
    drop-db)
        echo "Dropping $db_name database"
        $mysql -Bse "DROP DATABASE IF EXISTS $db_name;"
    ;;
        
    truncate-db)
        echo "Truncating \"geonames\" database"
        $mysql $db_name < ./sql/geonames_truncate_db.sql
    ;;

    split-tables)
        echo "Splitting tables by geo entities"
        $mysql $db_name < ./sql/geonames_split_tables.sql
    ;;

    all)
		./geonames_do_all.sh
	;;
esac

if [ $? == 0 ]; then 
	echo "[OK]"
else
	echo "[FAILED]"
fi

exit 0
