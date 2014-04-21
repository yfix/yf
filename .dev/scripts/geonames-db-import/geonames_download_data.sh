#!/bin/bash

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

echo "
AF,Africa,6255146
AS,Asia,6255147
EU,Europe,6255148
NA,North America,6255149
OC,Oceania,6255151
SA,South America,6255150
AN,Antarctica,6255152
" > ./continentCodes.txt

cd $orig_dir
mkdir -p ./data/postalCodes/
cd ./data/postalCodes/
if [ ! -f allCountries.txt ]; then
	wget -N http://download.geonames.org/export/zip/allCountries.zip
    unzip -o allCountries.zip
	rm allCountries.zip
fi

cd $orig_dir
