#!/bin/bash

echo "== test php version DEFAULT =="
./phpunit_run_all_separately.sh
echo "== test php version 5.4 =="
./phpunit_run_all_separately_php54.sh
echo "== test php version 5.5 =="
./phpunit_run_all_separately_php55.sh
echo "== test php version 5.6 =="
./phpunit_run_all_separately_php56.sh
echo "== test php version 7.0 =="
./phpunit_run_all_separately_php70.sh
echo "== test php version HHVM =="
./phpunit_run_all_separately_hhvm.sh

