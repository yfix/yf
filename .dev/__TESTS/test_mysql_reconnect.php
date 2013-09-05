<?php

class test_mysql_reconnect {

	function show() {
		main()->no_graphics(1);
#		db()->RECONNECT_USE_LOCKING = false;
#		db()->RECONNECT_NUM_TRIES = 100000;

		error_reporting(E_ALL & ~E_NOTICE);
		set_time_limit(0);
		header('Content-Encoding: none;');
		ini_set('zlib.output_compression',0);
		ob_implicit_flush(1);
		ob_end_flush();
		if (!preg_match("~(curl|wget)~ims", $_SERVER['HTTP_USER_AGENT'])) {
			echo str_repeat(" ", 1000); // Needed to send first packet to browser and ask him to keep polling content
		}

		echo "<pre>";
		foreach(range(1,1000) as $n) {
			echo "\n".$n.") ";
			print_r(db()->get_one("show tables"));
			sleep(1);
		}
	}
}
