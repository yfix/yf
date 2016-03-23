<?php

require __DIR__.'/_init.php';

foreach(range(1,1000) as $i) {
	echo $i. PHP_EOL;
	queue()->add($conf['prefix']. $conf['queue'], 'hello '.$i);
	sleep(1);
}
