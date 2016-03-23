<?php

require __DIR__.'/_redis.php';

$channel = $conf['prefix']. $conf['channel'];

foreach(range(1,1000) as $i) {
	echo $i. PHP_EOL;
	$redis->publish($channel, 'hello '.$i);
	sleep(1);
}