<?php

$GLOBALS["db"]->query("SET wait_timeout=1");

$body .= print_R($GLOBALS["db"]->query_fetch("SELECT @@wait_timeout"), 1);
$body .= "<br />\n";

$Q = $GLOBALS["db"]->query("SELECT * FROM test_countries LIMIT 2");
while ($A = $GLOBALS["db"]->fetch_assoc($Q)) {
	$body .= print_R($A, 1);
	$body .= "<br />\n";
	sleep(1);
}

$body .= "<br />If db reconnected successfully - the n you should see result below:<br />\n";
$Q = $GLOBALS["db"]->query("SELECT * FROM test_countries ORDER BY n DESC LIMIT 2");
while ($A = $GLOBALS["db"]->fetch_assoc($Q)) {
	$body .= print_R($A, 1);
	$body .= "<br />\n";
	sleep(1);
}
