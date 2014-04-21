#!/bin/bash

orig_dir=$(pwd)
cd ./data/

sort -n allCountries.txt -o allCountries_sorted.txt
egrep "\s(A|P)\s" allCountries_sorted.txt > allCountries.txt
#mv -vf allCountries_sorted.txt allCountries.txt

sort -n alternateNames.txt -o alternateNames_sorted.txt
mv -vf alternateNames_sorted.txt alternateNames.txt

cd ./postalCodes/

sort -n allCountries.txt -o allCountries_sorted.txt
mv -vf allCountries_sorted.txt allCountries.txt

cd $orig_dir
