<?php

require_once dirname(dirname(__DIR__)).'/tests/yf_unit_tests_setup.php';

$redlock = _class('wrapper_redlock');
$redlock->setup();
while (true) {
	$name = 'battle_id:99999';
	$lock = $redlock->lock($name, 10*1000);
	if ($lock) {
		print_r($lock);
		sleep(5);
		$redlock->unlock($lock);
	} else {
		print 'Lock not acquired'. PHP_EOL;
	}
}
