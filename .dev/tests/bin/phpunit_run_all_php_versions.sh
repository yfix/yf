#!/bin/bash

echo "== test php version DEFAULT =="
./phpunit_run_all.sh
echo "== test php version 5.4 =="
./phpunit_run_all_php54.sh
echo "== test php version 5.5 =="
./phpunit_run_all_php55.sh
echo "== test php version 5.6 =="
./phpunit_run_all_php56.sh
echo "== test php version 7.0 =="
./phpunit_run_all_php70.sh
echo "== test php version HHVM =="
./phpunit_run_all_hhvm.sh

