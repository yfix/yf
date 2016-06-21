<?php

ini_set('display_errors', 'off');
error_reporting(0);

$required = [
#	'memcached',
	'nginx',
#	'apache2',
#	'sphinxsearch',
#	'mysqld',
	'php5-fpm',
	'memcached_connect',
#	'sphinxsearch_connect',
	'mysqld_connect',
];
$sphinxsearch_host = '127.0.0.1:3308';
$mysql_user = 'root';
$mysql_pswd = '123456';

foreach ((array)$required as $name) {
	$is_alive = false;
	$self_failed = false;
	$pid = '';
	if ($name == 'memcached') {
		$pid = '/var/run/memcached.pid';
		exec('pgrep memcached', $is_alive, $self_failed);
	} elseif ($name == 'nginx') {
		$pid = '/usr/local/nginx/logs/nginx.pid';
		exec('pgrep nginx', $is_alive, $self_failed);
	} elseif ($name == 'apache2') {
		$pid = '/var/run/apache2.pid';
		exec('pgrep apache2', $is_alive, $self_failed);
	} elseif ($name == 'sphinxsearch') {
		$pid = '/usr/local/sphinx/log/searchd.pid';
		exec('pgrep searchd', $is_alive, $self_failed);
	} elseif ($name == 'php5-fpm') {
		$pid = '/var/run/php5-fpm.pid';
		exec('pgrep php5-fpm', $is_alive, $self_failed);
	} elseif ($name == 'mysqld') {
		exec('pgrep mysqld', $is_alive, $self_failed);
	} elseif ($name == 'memcached_connect') {
		if (class_exists('memcache')) {
			$m = new memcache();
		} elseif (class_exists('memcached')) {
			$m = new memcached();
		} else {
			$self_failed = 'php extensions memcache and memcached not installed';
		}
		if (is_object($m)) {
			$is_alive = (bool)$m->addServer('localhost', 11211);
		}
	} elseif ($name == 'sphinxsearch_connect') {
		if (function_exists('mysql_connect')) {
			ini_set('mysql.connect_timeout', 1);
			if (is_resource($mc = mysql_connect($sphinxsearch_host, '', '', true))) {
				mysql_close($mc);
				$is_alive = true;
			}
		} else {
			$self_failed = 'function mysql_connect() not exists';
		}
	} elseif ($name == 'mysqld_connect') {
		if (function_exists('mysql_connect')) {
			ini_set('mysql.connect_timeout', 2);
			if (is_resource($mc = mysql_connect('localhost', $mysql_user, $mysql_pswd, true))) {
				mysql_close($mc);
				$is_alive = true;
			}
		} else {
			$self_failed = 'function mysql_connect() not exists';
		}
	}
	if (!$is_alive) {
		echo 'ERROR: '.$name. PHP_EOL;
		if ($self_failed) {
			echo 'SELF FAILED: "'.$self_failed.'", "'.print_r($is_alive, 1). PHP_EOL;
			if ($pid) {
				echo 'PROCESS NOT FOUND: '.$pid.' ('.file_get_contents($pid).')'. PHP_EOL;
				if (!is_readable($pid)) {
					echo ', PID is not readable: '.$pid. PHP_EOL;
				}
			}
		}
		exit();
	} elseif ($pid) {
		$pid_from_file = trim(file_get_contents($pid));
		$pids = (array)$is_alive;
		if ($pid_from_file && !in_array($pid_from_file, $pids)) {
			echo 'ERROR: not found PID from file in running processes';
			exit();
		}
	}
}
// Script should show this only if ALL tests passed
exit('OK');
