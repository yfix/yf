<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

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
class class_validate_test extends PHPUnit_Framework_TestCase {
	public function test_required_1() {
#		$this->assertEquals(true, _class('validate')->required(true) );
	}

}