<?php

require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_yf_bugs_test extends PHPUnit_Framework_TestCase {
	public function test_bug_01() {
		$this->assertEquals('#description ', _tpl( '#description {execute(main,_show_block123123)}', array('description' => 'test') ));
	}
}