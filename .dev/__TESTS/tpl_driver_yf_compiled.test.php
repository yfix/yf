<?php

$GLOBALS['PROJECT_CONF']['tpl']['COMPILE_TEMPLATES'] = true;

function _delete_compiled_dir() {
	return _class('dir')->delete_dir('./stpls_compiled/', $delete_start_dir = true);
}

require_once dirname(__FILE__).'/tpl_driver_yf_bugs.test.php';
class tpl_driver_yf_bugs_test__compiled extends tpl_driver_yf_bugs_test {
	public function tearDown() { _delete_compiled_dir(); }
}

require_once dirname(__FILE__).'/tpl_driver_yf_core.test.php';
class tpl_driver_yf_core_test__compiled extends tpl_driver_yf_core_test {
	public function tearDown() { _delete_compiled_dir(); }
}

require_once dirname(__FILE__).'/tpl_driver_yf_form.test.php';
class tpl_driver_yf_form_test__compiled extends tpl_driver_yf_form_test {
	public function tearDown() { _delete_compiled_dir(); }
}

require_once dirname(__FILE__).'/tpl_driver_yf_translate.test.php';
class tpl_driver_yf_translate_test__compiled extends tpl_driver_yf_translate_test {
	public function tearDown() { _delete_compiled_dir(); }
}
