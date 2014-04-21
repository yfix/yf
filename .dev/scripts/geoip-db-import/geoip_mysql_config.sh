#!/bin/bash

# Default values for database variables.
#db_host="localhost"
#db_port=3306
#db_user="root"
#db_pswd="123456"
db_name="geonames"

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
