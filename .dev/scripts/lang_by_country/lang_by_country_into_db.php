#!/usr/bin/php
<?php

require_once dirname(dirname(__FILE__)).'/scripts_init.php';

$data = array();
foreach((array)db()->select('code','languages')->from('countries')->get_2d() as $code => $langs) {
	foreach (explode(',', $langs) as $lang) {
		$lang = substr($lang, 0, 2);
		if ($lang) {
			$data[$code.'.'.$lang] = array(
				'lang'		=> $lang,
				'country'	=> $code,
			);
		}
	}
}

$table = DB_PREFIX. 'geo_lang_to_country';
if ( ! db()->utils()->table_exists($table) || $force) {
	db()->utils()->drop_table($table);
	db()->utils()->create_table($table);
}

db()->insert_safe($table, $data) or print_r(db()->error());

echo 'Trying to get 2 first records: '.PHP_EOL;
print_r(db()->get_all('SELECT * FROM '.$table.' LIMIT 2'));
