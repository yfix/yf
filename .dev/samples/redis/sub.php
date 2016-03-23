<?php

require __DIR__.'/_redis.php';

$channel = $conf['prefix']. $conf['channel'];

$redis->subscribe(array($channel), function($redis, $channel, $msg) {
	echo $channel.' | '.$msg. PHP_EOL;
});
