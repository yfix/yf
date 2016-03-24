<?php

require __DIR__.'/_init.php';

queue()->listen($conf['prefix']. $conf['queue'], function($item) {
	var_dump($item);
});
/*
while(true) {
	$item = queue()->get($conf['prefix']. $conf['queue']);
	if ($item) {
		var_dump($item);
		usleep(200000);
	} else {
		usleep(500000);
	}
}
*/