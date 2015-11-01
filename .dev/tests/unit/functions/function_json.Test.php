<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

/**
 * @requires extension json
 */
class function_json extends yf_unit_tests {
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
		$this->assertEquals( json_encode($json), json_encode(json_encode($a)) );
		$this->assertEquals( json_encode(json_encode($json)), json_encode(json_encode(json_encode($a))) );
	}
}
