#!/usr/bin/php
<?php

$lang = 'ru';
$suffix = '_'.$lang;
$url = 'https://'.$lang.'.wikipedia.org/wiki/ISO_3166-1';
$result_file = __DIR__.'/countries_'.$lang.'.php';

require __DIR__.'/get_latest_countries.php';
