<?php

class db_pf {
	function exists () { return true; }
	function __call($name, $arguments) { return false; }
	public static function __callStatic($name, $arguments) { return false; }
}
class db_pf2 {
	function exists () { return true; }
	function __call($name, $arguments) { trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING); return false; }
	public static function __callStatic($name, $arguments) { trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING); return false; }
}
class db_pf22 extends db_pf2 {
}
class db_pf222 extends db_pf22 {
}
class db_pf2222 extends db_pf222 {
}
function db_pf() {
	static $_instance;
	if (is_null($_instance)) { $_instance = new db_pf(); }
	return $_instance;
}
function db_pf2() {
	$_instance = &$GLOBALS[__FUNCTION__];
	if (is_null($_instance)) { $_instance = new db_pf(); }
	return $_instance;
}
###################################
$max = 10000;

$GLOBALS['db_pf'] = new db_pf();
$db_pf = new db_pf();
$db_pf2222 = new db_pf();

$ts = microtime(true); for ($i = 0; $i < $max; $i++) { $db_pf->exists(); }
echo $max." var call existed method = ".round(microtime(true) - $ts, 4)."\n";

$ts = microtime(true); for ($i = 0; $i < $max; $i++) { $db_pf->exists(); }
echo $max." var call existed method for 3-level inherited classes = ".round(microtime(true) - $ts, 4)."\n";

$ts = microtime(true); for ($i = 0; $i < $max; $i++) { $GLOBALS['db_pf']->exists(); }
echo $max." globals call existed method = ".round(microtime(true) - $ts, 4)."\n";

$ts = microtime(true); for ($i = 0; $i < $max; $i++) { $GLOBALS['db_pf']->test(); }
echo $max." globals call not existed method = ".round(microtime(true) - $ts, 4)."\n";

$ts = microtime(true); for ($i = 0; $i < $max; $i++) { db_pf::test(); }
echo $max." pure static call not existed method = ".round(microtime(true) - $ts, 4)."\n";

$ts = microtime(true); for ($i = 0; $i < $max; $i++) { db_pf2::test(); }
echo $max." pure static call not existed method with mutable errors = ".round(microtime(true) - $ts, 4)."\n";

$ts = microtime(true); for ($i = 0; $i < $max; $i++) { db_pf::existed(); }
echo $max." pure static call existed method = ".round(microtime(true) - $ts, 4)."\n";

$ts = microtime(true); for ($i = 0; $i < $max; $i++) { db_pf()->test(); }
echo $max." function proxy call not existed method = ".round(microtime(true) - $ts, 4)."\n";

$ts = microtime(true); for ($i = 0; $i < $max; $i++) { db_pf2()->test(); }
echo $max." function proxy call not existed method mutable errors = ".round(microtime(true) - $ts, 4)."\n";

$ts = microtime(true); for ($i = 0; $i < $max; $i++) { db_pf()->existed(); }
echo $max." function proxy call existed method = ".round(microtime(true) - $ts, 4)."\n";

$ts = microtime(true); for ($i = 0; $i < $max; $i++) { db_pf2()->existed(); }
echo $max." function proxy call existed method mutable errors = ".round(microtime(true) - $ts, 4)."\n";
