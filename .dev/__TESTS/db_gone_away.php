<?php

define('YF_PATH', dirname(dirname(dirname(__FILE__))).'/');
require YF_PATH.'classes/yf_main.class.php';
new yf_main('user', 1, 0);

db()->query('SET wait_timeout=1');

$body .= print_r(db()->query_fetch('SELECT @@wait_timeout'), 1);
$body .= '<br />'.PHP_EOL;

$q = db()->query('SELECT * FROM '.db('user').' LIMIT 2');
while ($A = db()->fetch_assoc($q)) {
	$body .= print_r($A, 1);
	$body .= '<br />'.PHP_EOL;
	sleep(1);
}

$body .= '<br />If db reconnected successfully - the n you should see result below:<br />'.PHP_EOL;
$q = db()->query('SELECT * FROM '.db('user').' ORDER BY n DESC LIMIT 2');
while ($a = db()->fetch_assoc($q)) {
	$body .= print_r($A, 1);
	$body .= '<br />'.PHP_EOL;
	sleep(1);
}

echo $body;