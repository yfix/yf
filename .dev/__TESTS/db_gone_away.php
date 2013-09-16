<?php

db()->query("SET wait_timeout=1");

$body .= print_r(db()->query_fetch("SELECT @@wait_timeout"), 1);
$body .= "<br />\n";

$q = db()->query("SELECT * FROM test_countries LIMIT 2");
while ($A = db()->fetch_assoc($q)) {
	$body .= print_r($A, 1);
	$body .= "<br />\n";
	sleep(1);
}

$body .= "<br />If db reconnected successfully - the n you should see result below:<br />\n";
$q = db()->query("SELECT * FROM test_countries ORDER BY n DESC LIMIT 2");
while ($a = db()->fetch_assoc($q)) {
	$body .= print_r($A, 1);
	$body .= "<br />\n";
	sleep(1);
}
