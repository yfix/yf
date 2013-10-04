<?php

define('YF_PATH', dirname(dirname(dirname(__FILE__))).'/');
require YF_PATH.'classes/yf_main.class.php';
new yf_main('user', 1, 0);

/*
		$rules_raw = array(
			'login'		=> array( 'trim|required|min_length[2]|max_length[12]|is_unique[user.login]|xss_clean', function($in){ return module('register')->_login_not_exists($in); } ),
			'email'		=> array( 'trim|required|valid_email|is_unique[user.email]', function($in){ return module('register')->_email_not_exists($in); } ),
			'emailconf'	=> 'trim|required|valid_email|matches[email]',
			'password'	=> 'trim|required', //|md5
			'pswdconf'	=> 'trim|required|matches[password]', // |md5
			'captcha'	=> 'trim|captcha',
		);

	* Examples of validate rules setting:
	* 	'name1' => 'trim|required',
	* 	'name2' => array('trim', 'required'),
	* 	'name3' => array('trim|required', 'other_rule|other_rule2|other_rule3'),
	* 	'name4' => array('trim|required', function() { return true; } ),
	* 	'name5' => array('trim', 'required', function() { return true; } ),
	* 	'__before__' => 'trim',
	* 	'__after__' => 'some_method2|some_method3',


$a = _class('form2')->_validate_rules_cleanup($rules_raw);
var_export($a);

*/
class form_validate_test extends PHPUnit_Framework_TestCase {
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