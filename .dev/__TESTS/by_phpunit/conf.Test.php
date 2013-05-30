<?php

require_once dirname(dirname(dirname(__FILE__))).'/share/functions/profy_conf.php';

class conf_test extends PHPUnit_Framework_TestCase {

    public function test_2() {
		$GLOBALS["PROJECT_CONF"]["test"]["test"] = "55";
        $this->assertEquals(module_conf("test", "test"), "55");
    }
    public function test_3() {
		$GLOBALS["PROJECT_CONF"]["test"]["test"]["sub"] = "sub";
        $this->assertEquals(module_conf("test", "test::sub"), "sub");
	}
    public function test_4() {
		module_conf("test", array(
			"key1"	=> "val1",
			"key2"	=> "val2",
			"key3"	=> "val3",
		));
		$_conf_should_be = array(
			"test" => array(
				"key1"	=> "val1",
				"key2"	=> "val2",
				"key3"	=> "val3",
			),
		);
       	$this->assertEquals($GLOBALS["PROJECT_CONF"], $_conf_should_be);
    }
    public function test_5() {
		module_conf("test", array(
			"key1"			=> "val1",
			"key2::sub1"	=> "val21",
			"key2::sub2"	=> "val22",
			"key2::sub3::ss1"	=> "val231",
			"key2::sub3::ss2"	=> "val232",
			"key2::sub4::ss1::sss1"	=> "val2411",
			"key2::sub4::ss1::sss2"	=> "val2412",
		));
		$_conf_should_be = array(
			"test" => array(
				"key1"	=> "val1",
				"key2"	=> array(
					"sub1"	=> "val21",
					"sub2"	=> "val22",
					"sub3"	=> array(
						"ss1"	=> "val231",
						"ss2"	=> "val232",
					),
					"sub4"	=> array(
						"ss1"	=> array(
							"sss1"	=> "val2411",
							"sss2"	=> "val2412",
						),
					),
				),
			),
		);
       	$this->assertEquals($GLOBALS["PROJECT_CONF"], $_conf_should_be);
    }
    public function test_12() {
		$GLOBALS["CONF"]["test"] = "55";
        $this->assertEquals(conf("test"), "55");
    }
    public function test_13() {
		$GLOBALS["CONF"]["test"]["sub"] = "sub1";
        $this->assertEquals(conf("test::sub"), "sub1");
	}
    public function test_14() {
		conf(array(
			"key1"	=> "val1",
			"key2"	=> "val2",
			"key3"	=> "val3",
		));
		$_conf_should_be = array(
			"key1"	=> "val1",
			"key2"	=> "val2",
			"key3"	=> "val3",
		);
       	$this->assertEquals($GLOBALS["CONF"], $_conf_should_be);
    }
    public function test_15() {
		conf(array(
			"key1"			=> "val1",
			"key2::sub1"	=> "val21",
			"key2::sub2"	=> "val22",
			"key2::sub3::ss1"	=> "val231",
			"key2::sub3::ss2"	=> "val232",
			"key2::sub4::ss1::sss1"	=> "val2411",
			"key2::sub4::ss1::sss2"	=> "val2412",
		));
		$_conf_should_be = array(
			"key1"	=> "val1",
			"key2"	=> array(
				"sub1"	=> "val21",
				"sub2"	=> "val22",
				"sub3"	=> array(
					"ss1"	=> "val231",
					"ss2"	=> "val232",
				),
				"sub4"	=> array(
					"ss1"	=> array(
						"sss1"	=> "val2411",
						"sss2"	=> "val2412",
					),
				),
			),
		);
       	$this->assertEquals($GLOBALS["CONF"], $_conf_should_be);
    }
    public function test_16() {
		$GLOBALS["CONF"] = array(
			"key2"	=> array(
				"sub4"	=> array(
					"ss1"	=> array(
						"sss2"	=> "val2412",
					),
				),
			),
		);
       	$this->assertEquals(conf("key2::sub4::ss1::sss2"), "val2412");
    }
    public function test_22() {
		$GLOBALS["DEBUG"]["test"] = "55";
        $this->assertEquals(debug("test"), "55");
    }
    public function test_23() {
		$GLOBALS["DEBUG"]["test"]["sub"] = "sub1";
        $this->assertEquals(debug("test::sub"), "sub1");
	}
    public function test_24() {
		debug(array(
			"key1"	=> "val1",
			"key2"	=> "val2",
			"key3"	=> "val3",
		));
		$_conf_should_be = array(
			"key1"	=> "val1",
			"key2"	=> "val2",
			"key3"	=> "val3",
		);
       	$this->assertEquals($GLOBALS["DEBUG"], $_conf_should_be);
    }
    public function test_25() {
		debug(array(
			"key1"			=> "val1",
			"key2::sub1"	=> "val21",
			"key2::sub2"	=> "val22",
			"key2::sub3::ss1"	=> "val231",
			"key2::sub3::ss2"	=> "val232",
			"key2::sub4::ss1::sss1"	=> "val2411",
			"key2::sub4::ss1::sss2"	=> "val2412",
		));
		$_conf_should_be = array(
			"key1"	=> "val1",
			"key2"	=> array(
				"sub1"	=> "val21",
				"sub2"	=> "val22",
				"sub3"	=> array(
					"ss1"	=> "val231",
					"ss2"	=> "val232",
				),
				"sub4"	=> array(
					"ss1"	=> array(
						"sss1"	=> "val2411",
						"sss2"	=> "val2412",
					),
				),
			),
		);
       	$this->assertEquals($GLOBALS["DEBUG"], $_conf_should_be);
    }
    public function test_26() {
		$GLOBALS["DEBUG"] = array(
			"key2"	=> array(
				"sub4"	=> array(
					"ss1"	=> array(
						"sss2"	=> "val2412",
					),
				),
			),
		);
       	$this->assertEquals(debug("key2::sub4::ss1::sss2"), "val2412");
    }
/*
    public function test_27() {
		debug(array(
			"key2::sub4::ss1::"	=> "val0",
			"key2::sub4::ss1::"	=> "val1",
			"key2::sub4::ss1::"	=> "val2",
#			"key2::sub4::ss1::5"=> "val5",
		));
		$_conf_should_be = array(
			"key2"	=> array(
				"sub4"	=> array(
					"ss1"	=> array(
						0 => "val0",
						1 => "val1",
						2 => "val2",
#						5 => "val5",
					),
				),
			),
		);
       	$this->assertEquals($GLOBALS["DEBUG"], $_conf_should_be);
    }
*/
}
