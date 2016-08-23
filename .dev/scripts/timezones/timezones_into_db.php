#!/usr/bin/php
<?php

require_once dirname(__DIR__).'/scripts_init.php';

$data = require __DIR__.'/timezones.php';
if (!$data) {
	exit('Error: $data is missing');
}
$table = DB_PREFIX.'timezones';
if ( ! db()->utils()->table_exists($table) || $force) {
	db()->utils()->drop_table($table);
	db()->utils()->create_table($table);
}
db()->insert_safe($table, $data) or print_r(db()->error());

echo 'Trying to get 2 first records: '.PHP_EOL;
print_r(db()->get_all('SELECT * FROM '.$table.' LIMIT 2'));
