<?php

require __DIR__.'/_init.php';

foreach(range(1,1000) as $i) {
	echo $i. PHP_EOL;
	pubsub()->pub($conf['prefix']. $conf['channel'], 'hello '.$i);
	sleep(1);
}