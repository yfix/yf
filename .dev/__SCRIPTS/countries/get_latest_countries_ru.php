#!/usr/bin/php
<?php

$url = 'https://ru.wikipedia.org/wiki/ISO_3166-1';
$result_file = dirname(__FILE__).'/countries_ru.php';
$suffix = '_ru';

require dirname(__FILE__).'/get_latest_countries.php';
