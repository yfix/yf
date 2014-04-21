#!/usr/bin/php
<?php

require_once dirname(dirname(__FILE__)).'/scripts_init.php';

$table = DB_PREFIX. 'countries';

$to_update = array();
foreach (db_geonames()->from('geo_country')->get_all() as $a) {
	$to_update[$a['code']] = array(
		'code'			=> $a['code'],
		'cont'			=> $a['continent'],
		'tld'			=> substr($a['tld'], 1),
		'currency'		=> $a['currency'],
		'area'			=> $a['area'],
		'population'	=> $a['population'],
		'phone_prefix'	=> $a['phone_prefix'],
		'languages'		=> $a['languages'],
		'geoname_id'	=> $a['geoname_id'],
#		'capital_id'	=> $a[],
	);
}

db()->update_batch_safe($table, $to_update, 'code');

echo 'Trying to get 2 first records: '.PHP_EOL;
print_r(db()->get_all('SELECT * FROM '.$table.' LIMIT 2'));
