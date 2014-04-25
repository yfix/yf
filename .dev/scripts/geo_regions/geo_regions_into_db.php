#!/usr/bin/php
<?php

require_once dirname(dirname(__FILE__)).'/scripts_init.php';

$lang = 'ru';
$table = DB_PREFIX. 'geo_regions';
if ( ! db()->utils()->table_exists($table) || $force) {
	db()->utils()->drop_table($table);
	db()->utils()->create_table($table);
}

$country_ids = array();
foreach (db_geonames()->select('code','geoname_id')->from('geo_country')->get_2d() as $code => $id) {
	$id && $country_ids[$code] = $id;
}
$capital_ids = array();
foreach (db_geonames()->from('geo_geoname')->where('feature_code', '=', 'ppla')->get_all() as $a) {
	$a['id'] && $capital_ids[$a['country'].$a['admin1']] = $a['id'];
}

$sql = 'SELECT id, name, country, admin1 AS code FROM geo_geoname WHERE feature_code = "adm1"';
if ($lang) {
	$sql = 
		'SELECT g.id, a.name, g.name AS name_eng, g.country, g.admin1 AS code
		FROM geo_geoname AS g
		LEFT JOIN geo_alternate_name AS a ON g.id = a.geoname_id
		WHERE a.language_code = "'._es($lang).'"
			AND g.id IN (
				SELECT h.child_id FROM geo_hierarchy AS h
				WHERE h.feature_code = "ADM"
					AND parent_id IN('.implode(',', $country_ids).')
			)
		GROUP BY g.id
		ORDER BY g.country, a.name COLLATE utf8_unicode_ci
	';
}
$to_update = array();
foreach (db_geonames()->get_all($sql) as $a) {
	$to_update[$a['id']] = array(
		'id'			=> $a['id'],
		'country'		=> $a['country'],
		'code'			=> $a['code'],
		'name'			=> $a['name'],
		'name_eng'		=> $a['name_eng'],
// TODO: need additional efforts to fill all capitals, example: Kiev and Kievskaya oblast does not have detected capital_id
		'capital_id'	=> $capital_ids[$a['country'].$a['code']],
	);
}
db()->replace_safe($table, $to_update);

db()->query('DELETE FROM '.$table.' WHERE country != "ua"');
db()->update($table, array('active' => 1), 'country = "ua"');

echo 'Trying to get 2 first records: '.PHP_EOL;
print_r(db()->get_all('SELECT * FROM '.$table.' LIMIT 2'));
