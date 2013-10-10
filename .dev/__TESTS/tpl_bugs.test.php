<?php

define('YF_PATH', dirname(dirname(dirname(__FILE__))).'/');
require YF_PATH.'classes/yf_main.class.php';
new yf_main('user', 1, 0);

function _tpl($stpl_text = '', $replace = array(), $name = '', $params = array()) {
	return tpl()->parse_string($stpl_text, $replace, $name, $params);
}

class tpl_core_test extends PHPUnit_Framework_TestCase {
	public function test_bug_01() {
		$this->assertEquals('#description ', _tpl( '#description {execute(shop,_show_block123123)}', array('description' => 'test') ));
	}
}