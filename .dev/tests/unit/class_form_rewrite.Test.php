<?php  

require_once __DIR__.'/yf_unit_tests_setup.php';

/* TODO:
*/

class class_form_rewrite_test extends PHPUnit_Framework_TestCase {

	private static $_bak_settings = array();

	public static function setUpBeforeClass() {
		$_GET = array(
			'object' => 'dynamic',
			'action' => 'unit_test_form',
		);
		self::$_bak_settings['REWRITE_MODE'] = $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'];
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
	}

	public static function tearDownAfterClass() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = self::$_bak_settings['REWRITE_MODE'];
	}

	public function test_rewrite_form_url() {
		$this->assertEquals(
			'<form method="post" action="http://'.$_SERVER['HTTP_HOST'].'/dynamic/unit_test_form" class="form-horizontal" name="form_action" autocomplete="1"><fieldset></fieldset></form>'
			, str_replace(PHP_EOL, '', trim(form()))
		);
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = self::$_bak_settings['REWRITE_MODE'];
		$this->assertEquals(
			'<form method="post" action="http://'.$_SERVER['HTTP_HOST'].'/?object=dynamic&action=unit_test_form" class="form-horizontal" name="form_action" autocomplete="1"><fieldset></fieldset></form>'
			, str_replace(PHP_EOL, '', trim(form()))
		);

	}
}
