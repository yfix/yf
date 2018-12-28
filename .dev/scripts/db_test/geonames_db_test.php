#!/usr/bin/php
<?php

require_once __DIR__ . '/geonames_db_init.php';

echo '== columns inside geo_geoname ==' . PHP_EOL;
print_r(db()->utils()->list_columns('geo_geoname'));

echo '== indexes inside geo_geoname ==' . PHP_EOL;
print_r(db()->utils()->list_indexes('geo_geoname'));

echo '== tables ==' . PHP_EOL;
print_r(db()->utils()->list_tables());

echo '== views ==' . PHP_EOL;
print_r(db()->utils()->list_views());

echo '== procedures ==' . PHP_EOL;
print_r(db()->utils()->list_procedures());

echo '== triggers ==' . PHP_EOL;
print_r(db()->utils()->list_triggers());
