<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_form_validate_test extends PHPUnit_Framework_TestCase {
	public function test_cleanup_10() {
		$rules_raw = array(
			'name' => 'trim',
		);
		$rules_cleaned = array(
			'name' => array(
				array(0 => 'trim', 1 => NULL)
			),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_11() {
		$rules_raw = array(
			'name' => array('trim'),
		);
		$rules_cleaned = array(
			'name' => array(
				array(0 => 'trim', 1 => NULL)
			),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_12() {
		$rules_raw = array(
			'name' => array('trim', new stdClass, null, '', ' ', false, "\t\t"),
		);
		$rules_cleaned = array(
			'name' => array(
				array(0 => 'trim', 1 => NULL)
			),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_13() {
		$rules_raw = array(
			'name' => 'trim||||||||||||',
		);
		$rules_cleaned = array(
			'name' => array(
				array(0 => 'trim', 1 => NULL)
			),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_14() {
		$rules_raw = array(
			'name' => array('trim||||||||||||',false,null,' | '),
		);
		$rules_cleaned = array(
			'name' => array(
				array(0 => 'trim', 1 => NULL)
			),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_15() {
		$rules_raw = array(
			'name' => array(' trim | ',false,null,' | '),
		);
		$rules_cleaned = array(
			'name' => array(
				array(0 => 'trim', 1 => NULL)
			),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_22() {
		$rules_raw = array(
			'name' => 'trim|required',
		);
		$rules_cleaned = array(
			'name' => array(
				array(0 => 'trim', 1 => NULL),
				array(0 => 'required', 1 => NULL),
			),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_23() {
		$rules_raw = array(
			'name' => array('trim','required'),
		);
		$rules_cleaned = array(
			'name' => array(
				array(0 => 'trim', 1 => NULL),
				array(0 => 'required', 1 => NULL),
			),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_24() {
		$rules_raw = array(
			'captcha' => 'trim|captcha',
		);
		$rules_cleaned = array(
			'captcha' => array(
				array(0 => 'trim', 1 => NULL),
				array(0 => 'captcha', 1 => NULL),
			),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_25() {
		$rules_raw = array(
			'name' => array( 'trim|required|min_length[2]|max_length[12]|is_unique[user.login]|xss_clean' ),
		);
		$rules_cleaned = array(
			'name' => array(
				array(0 => 'trim', 1 => NULL),
				array(0 => 'required', 1 => NULL),
				array(0 => 'min_length', 1 => '2'),
				array(0 => 'max_length', 1 => '12'),
				array(0 => 'is_unique', 1 => 'user.login'),
				array(0 => 'xss_clean', 1 => NULL),
			),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_26() {
		$closure = function($in){ return module('register')->_login_not_exists($in); };
		$rules_raw = array(
			'name' => array( 'trim|required|min_length[2]|max_length[12]|is_unique[user.login]|xss_clean', $closure ),
		);
		$rules_cleaned = array(
			'name' => array(
				array(0 => 'trim', 1 => NULL),
				array(0 => 'required', 1 => NULL),
				array(0 => 'min_length', 1 => '2'),
				array(0 => 'max_length', 1 => '12'),
				array(0 => 'is_unique', 1 => 'user.login'),
				array(0 => 'xss_clean', 1 => NULL),
				array(0 => $closure, 1 => NULL),
			),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_27() {
		$closure = function($in){ return module('register')->_login_not_exists($in); };
		$rules_raw = array(
			'name' => array( 'trim|required', 'min_length[2]|max_length[12]|is_unique[user.login]', 'xss_clean', $closure ),
		);
		$rules_cleaned = array(
			'name' => array(
				array(0 => 'trim', 1 => NULL),
				array(0 => 'required', 1 => NULL),
				array(0 => 'min_length', 1 => '2'),
				array(0 => 'max_length', 1 => '12'),
				array(0 => 'is_unique', 1 => 'user.login'),
				array(0 => 'xss_clean', 1 => NULL),
				array(0 => $closure, 1 => NULL),
			),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_31() {
		$closure = function($in){ return module('register')->_login_not_exists($in); };
		$rules_raw = array(
			'name' => array( 'trim|required', 'min_length[2]|max_length[12]|is_unique[user.login]', 'xss_clean', $closure ),
			'captcha' => 'trim|captcha',
			'content' => 'trim|required',
		);
		$rules_cleaned = array(
			'name' => array(
				array(0 => 'trim', 1 => NULL),
				array(0 => 'required', 1 => NULL),
				array(0 => 'min_length', 1 => '2'),
				array(0 => 'max_length', 1 => '12'),
				array(0 => 'is_unique', 1 => 'user.login'),
				array(0 => 'xss_clean', 1 => NULL),
				array(0 => $closure, 1 => NULL),
			),
			'captcha' => array(
				array(0 => 'trim', 1 => NULL),
				array(0 => 'captcha', 1 => NULL),
			),
			'content' => array(
				array(0 => 'trim', 1 => NULL),
				array(0 => 'required', 1 => NULL),
			),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_32() {
		$closure = function($in){ return module('register')->_login_not_exists($in); };
		$rules_raw = array(
			'__before__' => 'trim',
			'name' => array( 'required', 'min_length[2]|max_length[12]|is_unique[user.login]', 'xss_clean', $closure ),
			'captcha' => 'captcha',
			'content' => 'required',
		);
		$rules_cleaned = array(
			'name' => array(
				array(0 => 'trim', 1 => NULL),
				array(0 => 'required', 1 => NULL),
				array(0 => 'min_length', 1 => '2'),
				array(0 => 'max_length', 1 => '12'),
				array(0 => 'is_unique', 1 => 'user.login'),
				array(0 => 'xss_clean', 1 => NULL),
				array(0 => $closure, 1 => NULL),
			),
			'captcha' => array(
				array(0 => 'trim', 1 => NULL),
				array(0 => 'captcha', 1 => NULL),
			),
			'content' => array(
				array(0 => 'trim', 1 => NULL),
				array(0 => 'required', 1 => NULL),
			),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_33() {
		$closure = function($in){ return module('register')->_login_not_exists($in); };
		$rules_raw = array(
			'__before__' => array('trim','required'),
			'__after__' => 'md5',
			'name' => array( 'min_length[2]', 'max_length[12]|is_unique[user.login]', 'xss_clean', $closure ),
			'captcha' => 'captcha',
			'content' => '',
		);
		$rules_cleaned = array(
			'name' => array(
				array(0 => 'trim', 1 => NULL),
				array(0 => 'required', 1 => NULL),
				array(0 => 'min_length', 1 => '2'),
				array(0 => 'max_length', 1 => '12'),
				array(0 => 'is_unique', 1 => 'user.login'),
				array(0 => 'xss_clean', 1 => NULL),
				array(0 => $closure, 1 => NULL),
				array(0 => 'md5', 1 => NULL),
			),
			'captcha' => array(
				array(0 => 'trim', 1 => NULL),
				array(0 => 'required', 1 => NULL),
				array(0 => 'captcha', 1 => NULL),
				array(0 => 'md5', 1 => NULL),
			),
			'content' => array(
				array(0 => 'trim', 1 => NULL),
				array(0 => 'required', 1 => NULL),
				array(0 => 'md5', 1 => NULL),
			),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}

}