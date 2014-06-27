<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

/**
 * @requires extension json
 */
class func_json extends PHPUnit_Framework_TestCase {
	public function test_json() {
		$a = array(
			'test1'	=> array(0,1,2,3,4),
			'test2'	=> array('key' => 'val',5,6),
		);
		$json = '{"test1":[0,1,2,3,4],"test2":{"key":"val","0":5,"1":6}}';
		$this->assertEquals( $json, json_encode($a) );
		$this->assertEquals( $a, json_decode($json, $assoc = true) );
		$this->assertEquals( $a, json_decode(json_encode($a), $assoc = true) );
		$this->assertEquals( $json, json_encode(json_decode($json, $assoc = true)) );
	}
}
