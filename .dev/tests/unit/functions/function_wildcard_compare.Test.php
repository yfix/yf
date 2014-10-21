<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_wildcard_compare_test extends PHPUnit_Framework_TestCase {
	public function test_basic() {
		$this->assertFalse( wildcard_compare('regex are useful', '') );
		$this->assertFalse( wildcard_compare('regex are * useful', '') );
		$this->assertFalse( wildcard_compare('regex are * useful', 'regex') );
		$this->assertFalse( wildcard_compare('regex are * useful', 'regex are') );
		$this->assertFalse( wildcard_compare('regex are * useful', 'regex are useful') );
		$this->assertFalse( wildcard_compare('regex are ? useful', 'regex are useful') );

		$this->assertTrue( wildcard_compare('regex are * useful', 'regex are always useful') );
		$this->assertTrue( wildcard_compare('* are * useful', 'regex are always useful') );
		$this->assertTrue( wildcard_compare('* are * useful', ' are  useful') );
		$this->assertTrue( wildcard_compare('regex are ? useful', 'regex are 1 useful') );
		$this->assertTrue( wildcard_compare('regex are ?? useful', 'regex are 12 useful') );
		$this->assertTrue( wildcard_compare('regex are ??? useful', 'regex are 123 useful') );
		$this->assertTrue( wildcard_compare('regex ?? are ???? useful', 'regex 12 are 1234 useful') );
		$this->assertTrue( wildcard_compare('????? are ?????? useful', 'regex are always useful') );
		$this->assertTrue( wildcard_compare('on[ce]* are ?????? useful', 'one are always useful') );
		$this->assertTrue( wildcard_compare('on[ce]* are ?????? useful', 'once are always useful') );
		$this->assertTrue( wildcard_compare('on[ce]* are ?????? useful', 'onccce are always useful') );
	}
}