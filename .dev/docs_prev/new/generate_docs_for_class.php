#!/usr/bin/php
<?php

define('YF_PATH', dirname(dirname(dirname(__DIR__))).'/');
$f = YF_PATH.'classes/yf_validate.class.php';

require_once __DIR__.'/yf_docs_generator.class.php';

$result = yf_docs_generator::parse_file($f);
print_r($result);
