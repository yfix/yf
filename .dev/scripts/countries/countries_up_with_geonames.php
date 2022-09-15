#!/usr/bin/env php
<?php

require_once dirname(__DIR__) . '/scripts_init.php';

$table = DB_PREFIX . 'geo_countries';

$capital_ids = [];
foreach (db_geonames()->from('geo_geoname')->where('feature_code', '=', 'pplc')->get_all() as $a) {
    $capital_ids[$a['country']] = $a['id'];
}

$to_update = [];
foreach (db_geonames()->from('geo_country')->get_all() as $a) {
    $to_update[$a['code']] = [
        'code' => $a['code'],
        'cont' => $a['continent'],
        'tld' => substr($a['tld'], 1),
        'currency' => $a['currency'],
        'area' => $a['area'],
        'population' => $a['population'],
        'phone_prefix' => $a['phone_prefix'],
        'languages' => $a['languages'],
        'geoname_id' => $a['geoname_id'],
        'capital_id' => $capital_ids[$a['code']],
    ];
}

db()->update_batch_safe($table, $to_update, 'code');

echo 'Trying to get 2 first records: ' . PHP_EOL;
print_r(db()->get_all('SELECT * FROM ' . $table . ' LIMIT 2'));
