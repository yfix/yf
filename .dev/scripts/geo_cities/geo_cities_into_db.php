#!/usr/bin/php
<?php

require_once dirname(dirname(__FILE__)).'/scripts_init.php';

$lang = 'ru';
$table = DB_PREFIX. 'geo_cities';
if ( ! db()->utils()->table_exists($table) || $force) {
	db()->utils()->drop_table($table);
	db()->utils()->create_table($table, array(), $error);
}
$country_ids = array();
foreach (db_geonames()->select('code','geoname_id')->from('geo_country')->get_2d() as $code => $id) {
	$id && $country_ids[$code] = $id;
}
$region_ids = array();
foreach (db_geonames()->select('code','geoname_id')->from('geo_admin1')->get_2d() as $code => $id) {
	$id && $region_ids[$code] = $id;
}
if ($lang) {
	$sql = '
		SELECT g.id, a.name, g.name AS name_eng, g.country, g.latitude, g.longitude, g.admin1, g.population
		FROM geo_geoname AS g
		LEFT JOIN geo_alternate_name AS a ON a.geoname_id = g.id
		WHERE 
			g.feature_class = "p"
			AND g.population > 10000
			AND a.language_code = "'._es($lang).'"
		GROUP BY g.id
		ORDER BY g.country, a.name COLLATE utf8_unicode_ci
	';
}
$to_update = array();
foreach (db_geonames()->get_all($sql) as $a) {
	$to_update[$a['id']] = array(
		'id'			=> $a['id'],
		'country'		=> $a['country'],
		'name'			=> $a['name'],
		'name_eng'		=> $a['name_eng'],
		'population'	=> $a['population'],
		'lat'			=> todecimal($a['latitude'], 6),
		'lon'			=> todecimal($a['longitude'], 6),
		'region_id'		=> $region_ids[$a['country'].'.'.$a['admin1']],
	);
}
db()->replace_safe($table, $to_update);

db()->query('DELETE FROM '.$table.' WHERE country != "ua"') or print_r(db()->error());
db()->update($table, array('active' => 1), 'country = "ua"');

echo 'Trying to get 2 first records: '.PHP_EOL;
print_r(db()->get_all('SELECT * FROM '.$table.' LIMIT 2'));
