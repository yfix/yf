#!/usr/bin/env php
<?php

require_once dirname(__DIR__) . '/scripts_utils.php';

// TODO: try json api from wikipedia
//$u = 'http://en.wikipedia.org/w/api.php?format=json&action=query&titles=ISO_3166-1&prop=langlinks';
//print_r(json_decode(file_get_contents($u), 1));

// TODO: list of continents and country mapping
//$url = 'http://unstats.un.org/unsd/methods/m49/m49regin.htm';

$url = $url ?: 'https://en.wikipedia.org/wiki/ISO_3166-1';
$result_file = $result_file ?: __DIR__ . '/countries.php';
$suffix = $suffix ?: '';
$mtpl = isset($mtpl) ? $mtpl : [
    'id' => 1,
    'code' => 1,
    'code3' => 2,
    'num' => 3,
    'name' => 0,
];

if (!function_exists('_var_export')) {
    function _var_export($data)
    {
        $str = var_export($data, 1);
        $str = str_replace('  ', "\t", $str);
        $str = preg_replace('~=>[\s]+array\s\(~ims', '=> [', $str);
        $str = str_replace('array (', '[', $str);
        $str = str_replace(')', ']', $str);
        $str = preg_replace('~=>[\s]+array\(~ims', '=> [', $str);
        return $str;
    }
}

if (!function_exists('data_get_latest_countries')) {
    function data_get_latest_countries()
    {
        global $url, $result_file, $suffix, $mtpl;

        $f2 = __DIR__ . '/' . basename($url) . '.table' . $suffix . '.html';
        if (!file_exists($f2) || filemtime($f2) <= (time() - 86400 * 10)) {
            $html1 = file_get_contents($url);
            $regex1 = '~<table[^>]*wikitable[^>]*>(.*?)</table>~ims';
            preg_match($regex1, $html1, $m1);
            file_put_contents($f2, $m1[1]);
        }
        $html2 = file_get_contents($f2);

        $tmp_tbl = html_table_to_array($html2);
        $data = [];
        foreach ($tmp_tbl as $v) {
            $id = $v[$mtpl['id']];
            if (!$id) {
                continue;
            }
            $data[$id] = [
                'code' => $id,
                'code3' => $v[$mtpl['code3']],
                'num' => $v[$mtpl['num']],
                'name' => str_replace(['[', ']', '(', ')'], '', $v[$mtpl['name']]),
                'cont' => '',
                'active' => 0,
            ];
        }
        foreach (['UA', 'RU', 'US', 'DE', 'FR', 'ES', 'GB'] as $c) {
            $data[$c]['active'] = 1;
        }

        $f4 = $result_file;
        file_put_contents($f4, '<?' . 'php' . PHP_EOL . '$data = ' . _var_export($data, 1) . ';');
        print_r($data);
    }
}

data_get_latest_countries();
