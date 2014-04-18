#!/bin/bash

# Default values for database variables.
#db_host="localhost"
#db_port=3306
#db_user="root"
#db_pswd="123456"
db_name="geonames"

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

download_geonames_data() {
	echo "Downloading GeoNames.org data ..." 

	orig_dir=$(pwd)
	mkdir -p ./data/
	cd ./data/

	wget -N http://download.geonames.org/export/dump/admin1CodesASCII.txt
	wget -N http://download.geonames.org/export/dump/admin2Codes.txt
	wget -N http://download.geonames.org/export/dump/featureCodes_en.txt
	wget -N http://download.geonames.org/export/dump/timeZones.txt
	wget -N http://download.geonames.org/export/dump/countryInfo.txt
	if [ ! -f allCountries.txt ]; then
		wget -N http://download.geonames.org/export/dump/allCountries.zip
	    unzip -o allCountries.zip
	    rm allCountries.zip
	fi
	if [ ! -f alternateNames.txt ]; then
		wget -N http://download.geonames.org/export/dump/alternateNames.zip
		unzip -o alternateNames.zip
		rm alternateNames.zip
	fi
	if [ ! -f hierarchy.txt ]; then
		wget -N http://download.geonames.org/export/dump/hierarchy.zip
		unzip -o hierarchy.zip
		rm hierarchy.zip
	fi
	cd $orig_dir
	mkdir -p ./data/postalCodes/
	cd ./data/postalCodes/
	if [ ! -f allCountries.txt ]; then
		wget -N http://download.geonames.org/export/zip/allCountries.zip
	    unzip -o allCountries.zip
		rm allCountries.zip
	fi

	cd $orig_dir
}

sort_geonames_data() {
	orig_dir=$(pwd)
	cd ./data/

	sort -n allCountries.txt -o allCountries_sorted.txt
	egrep "\s(A|P)\s" allCountries_sorted.txt > allCountries.txt
#	mv -vf allCountries_sorted.txt allCountries.txt

	sort -n alternateNames.txt -o alternateNames_sorted.txt
	mv -vf alternateNames_sorted.txt alternateNames.txt

	cd ./postalCodes/

	sort -n allCountries.txt -o allCountries_sorted.txt
	mv -vf allCountries_sorted.txt allCountries.txt

	cd $orig_dir
}

if [ $# -lt 1 ]; then
	usage
	exit 1
fi

# Deals with operation mode 2 (Database issues...)
# Parses command line parameters.
while getopts "a:u:p:h:r:n:" opt; 
do
    case $opt in
        a) action=$OPTARG ;;
        u) db_user=$OPTARG ;;
        p) db_pswd=$OPTARG ;;
        h) db_host=$OPTARG ;;
        r) db_port=$OPTARG ;;
        n) db_name=$OPTARG ;;
    esac
done


case $action in
    download-data)
        download_geonames_data
        exit 0
    ;;
    sort-data)
        sort_geonames_data
        exit 0
    ;;
esac

mysql="mysql -v"
if [ ! -z "$db_host" ]; then
	mysql=$mysql" -h "$db_host;
fi
if [ ! -z "$db_port" ]; then
	mysql=$mysql" -P "$db_port;
fi
if [ ! -z "$db_user" ]; then
	mysql=$mysql" -u "$db_user;
fi
if [ ! -z "$db_pswd" ]; then
	mysql=$mysql" -p"$db_pswd;
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
		download_geonames_data;

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
	;;
esac

if [ $? == 0 ]; then 
	echo "[OK]"
else
	echo "[FAILED]"
fi

exit 0
