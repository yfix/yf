<?php

$conf = require __DIR__ . '/_conf.php';

$redis = new Redis();
$redis->connect($conf['host'], $conf['port']);
