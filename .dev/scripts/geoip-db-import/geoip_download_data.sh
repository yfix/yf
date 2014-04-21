#!/bin/bash

echo "Downloading geoip data ..." 

orig_dir=$(pwd)
mkdir -p ./data/
cd ./data/

if [ ! -f GeoIPCountryWhois.csv ]; then
wget -N http://geolite.maxmind.com/download/geoip/database/GeoIPCountryCSV.zip
    unzip -j -o GeoIPCountryCSV.zip
    rm GeoIPCountryCSV.zip
fi
if [ ! -f GeoLiteCity-Blocks.csv ]; then
wget -N http://geolite.maxmind.com/download/geoip/database/GeoLiteCity_CSV/GeoLiteCity-latest.zip
    unzip -j -o GeoLiteCity-latest.zip
    rm GeoLiteCity-latest.zip
fi
if [ ! -f GeoIPASNum2.csv ]; then
wget -N http://download.maxmind.com/download/geoip/database/asnum/GeoIPASNum2.zip
    unzip -j -o GeoIPASNum2.zip
    rm GeoIPASNum2.zip
fi
if [ ! -f region_codes.csv ]; then
wget -N http://www.maxmind.com/download/geoip/misc/region_codes.csv
fi
# Cities-DMA Regions  Latest .csv (2013-09-27)   https://developers.google.com/adwords/api/docs/appendix/cities-DMAregions
if [ ! -f cities-DMAregions.csv ]; then
wget http://goo.gl/itBaJE -O cities-DMAregions.csv
fi

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
