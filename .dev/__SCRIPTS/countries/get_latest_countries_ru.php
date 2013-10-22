#!/usr/bin/php
<?php

$lang = 'ru';
$suffix = '_'.$lang;
$url = 'https://'.$lang.'.wikipedia.org/wiki/ISO_3166-1';
$result_file = dirname(__FILE__).'/countries_'.$lang.'.php';

require dirname(__FILE__).'/get_latest_countries.php';
