#!/bin/bash

echo "== test php version DEFAULT =="
./phpunit_run_all.sh
echo "== test php version 5.3 =="
./phpunit_run_all_53.sh
echo "== test php version 5.4 =="
./phpunit_run_all_54.sh
echo "== test php version 5.5 =="
./phpunit_run_all_55.sh
echo "== test php version 5.6 =="
./phpunit_run_all_56.sh
echo "== test php version HHVM =="
./phpunit_run_all_hhvm.sh

