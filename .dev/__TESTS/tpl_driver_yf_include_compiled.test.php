<?php

$GLOBALS['PROJECT_CONF']['tpl']['COMPILE_TEMPLATES'] = true;

require_once dirname(__FILE__).'/tpl_driver_yf_include.test.php';
class tpl_driver_yf_include_compiled_test extends tpl_driver_yf_include_test {
	function tearDown() { _class('dir')->delete_dir('./stpls_compiled/', $delete_start_dir = true); }
}
