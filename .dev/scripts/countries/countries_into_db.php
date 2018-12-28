#!/usr/bin/php
<?php

$lang = 'ru';
if (strlen($argv[2]) == 2) {
    $lang = $argv[2];
}

require_once dirname(__DIR__) . '/scripts_init.php';

function load_data($lang)
{
    require __DIR__ . '/countries' . ($lang && $lang != 'en' ? '_' . $lang : '') . '.php';
    return $data;
}
$data = load_data($lang);
if ( ! $data) {
    exit('Error: $data is missing');
}
foreach ($data as &$v) {
    $v['name_eng'] = $v['name'];
}
if ($lang != 'en') {
    $data_en = load_data('en');
    foreach ($data as $k => &$v) {
        $v['name_eng'] = $data_en[$k]['name'];
    }
}
$table = DB_PREFIX . 'geo_countries';
if ( ! db()->utils()->table_exists($table) || $force) {
    db()->utils()->drop_table($table);
    db()->utils()->create_table($table);
}
db()->insert_safe($table, $data) or print_r(db()->error());
db()->update($table, ['active' => 1], 'code IN("ua","ru","by","es","de","us")');

echo 'Trying to get 2 first records: ' . PHP_EOL;
print_r(db()->get_all('SELECT * FROM ' . $table . ' LIMIT 2'));
