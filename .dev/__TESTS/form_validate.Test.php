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
			'name' => array(array(0 => 'trim', 1 => NULL)),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_11() {
		$rules_raw = array(
			'name' => array('trim'),
		);
		$rules_cleaned = array(
			'name' => array(array(0 => 'trim', 1 => NULL)),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_12() {
		$rules_raw = array(
			'name' => 'trim|required',
		);
		$rules_cleaned = array(
			'name' => array(array(0 => 'trim', 1 => NULL), array(0 => 'required', 1 => NULL)),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_13() {
		$rules_raw = array(
			'name' => array('trim','required'),
		);
		$rules_cleaned = array(
			'name' => array(array(0 => 'trim', 1 => NULL), array(0 => 'required', 1 => NULL)),
		);
		$this->assertEquals($rules_cleaned, _class('form2')->_validate_rules_cleanup($rules_raw) );
	}
	public function test_cleanup_21() {
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

}