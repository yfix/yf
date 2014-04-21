#!/bin/bash

. ./geoip_mysql_config.sh

usage() {
	echo ""
	echo "Example: " $0 " -a all"
    exit -1
}

if [ $# -lt 1 ]; then
	usage
	exit 1
fi

case "$action" in
    download)
		./geoip_download_data.sh;
		./geoip_import_data.sh;

    import)
		./geoip_import_data.sh;

    all)
		./geoip_download_data.sh;
		./geoip_import_data.sh;
	;;
esac

if [ $? == 0 ]; then 
	echo "[OK]"
else
	echo "[FAILED]"
fi

exit 0
