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
    exit -1
}

download_geoip_data() {
	echo "Downloading geoip data ..." 

	orig_dir=$(pwd)
	mkdir -p ./data/
	cd ./data/

	if [ ! -f GeoLite2-City-Blocks.csv ]; then
		wget -N http://geolite.maxmind.com/download/geoip/database/GeoLite2-City-CSV.zip
	    unzip -j -o GeoLite2-City-CSV.zip
	    rm GeoLite2-City-CSV.zip
	fi
	if [ ! -f GeoLite2-Country-Blocks.csv ]; then
		wget -N http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country-CSV.zip
	    unzip -j -o GeoLite2-Country-CSV.zip
	    rm GeoLite2-Country-CSV.zip
	fi

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

    all)
		download_geoip_data;
	;;
esac

if [ $? == 0 ]; then 
	echo "[OK]"
else
	echo "[FAILED]"
fi

exit 0
