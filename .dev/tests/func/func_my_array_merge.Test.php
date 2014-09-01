<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class func_my_array_merge_test extends PHPUnit_Framework_TestCase {
	public function test_simple() {
		$this->assertEquals(my_array_merge(array('key1' => 1), array('key1' => 1)), array('key1' => 1));
	}
	public function test_complex() {
		$to_merge_1 = array(
			'auth_user' => array(
				'EXEC_AFTER_LOGIN'		=> array(
					array('_add_login_activity'),
				),
			),
			'send_mail'	=> array(
				'USE_MAILER'	=> 'phpmailer',
			),
			'tpl' => array(
				'ALLOW_LANG_BASED_STPLS' => 1,
				'CUSTOM_META_INFO'		=> 1,
			),
			'graphics'	=> array(
				'META_KEYWORDS'			=> 'keyword2',
				'META_DESCRIPTION'		=> 'description2',
				'EMBED_CSS'			=> 0,
			),
			'i18n' => array(
				'REPLACE_UNDERSCORE' => 1,
			),
		);
		$to_merge_2 = array(
			'main'	=> array(
				'USE_CUSTOM_ERRORS'		=> 1,
				'STATIC_PAGES_ROUTE_TOP'=> 1,
			),
			'auth_user' => array(
				'URL_SUCCESS_LOGIN' => './?object=account', 
				'EXEC_AFTER_LOGIN'		=> array(
					array('_add_login_activity'),
				),
			),
			'graphics'	=> array(
				'CSS_ADD_RESET'		=> 1,
			),
			'debug_info' => array(
				'_SHOW_NOT_TRANSLATED'	=> 1,
				'_SHOW_I18N_VARS'	 => 1,
			),
			'rewrite'	=> array(
				'_rewrite_add_extension'	=> '/',
			),
			'comments'	=> array(
				'USE_TREE_MODE' => 1,
			),
			'register'	=> array(
				'NICK_ALLOWED_SYMBOLS'	=> array('а-я','a-z','0-9','_','\-','@','#',' '),
			),
			'validate'	=> array(
				'NICK_ALLOWED_SYMBOLS'	=> array('а-я','a-z','0-9','_','\-','@','#',' '),
			),
			'bb_codes'	=> array(
				'SMILIES_DIR'	=> 'uploads/forum/smilies/',
			),
		);
		$merged = array(
			'send_mail' => array (
				'USE_MAILER' => 'phpmailer',
			),
			'tpl' => array (
				'ALLOW_LANG_BASED_STPLS' => 1,
				'CUSTOM_META_INFO' => 1,
			),
			'i18n' => array (
				'REPLACE_UNDERSCORE' => 1,
			),
			'main' => array (
				'USE_CUSTOM_ERRORS' => 1,
				'STATIC_PAGES_ROUTE_TOP' => 1,
			),
			'debug_info' => array (
				'_SHOW_NOT_TRANSLATED' => 1,
				'_SHOW_I18N_VARS' => 1,
			),
			'rewrite' => array (
				'_rewrite_add_extension' => '/',
			),
			'comments' => array (
				'USE_TREE_MODE' => 1,
			),
			'register' => array (
				'NICK_ALLOWED_SYMBOLS' => array(0 => 'а-я',1 => 'a-z',2 => '0-9',3 => '_',4 => '\\-',5 => '@',6 => '#',7 => ' ',),
			),
			'validate' => array (
				'NICK_ALLOWED_SYMBOLS' => array(0 => 'а-я',1 => 'a-z',2 => '0-9',3 => '_',4 => '\\-',5 => '@',6 => '#',7 => ' ',),
			),
			'bb_codes' => array (
				'SMILIES_DIR' => 'uploads/forum/smilies/',
			),
			'auth_user' => array (
				'URL_SUCCESS_LOGIN' => './?object=account',
				'EXEC_AFTER_LOGIN' => array (
					0 => array (
					0 => '_add_login_activity',
					),
				),
			),
			'graphics' => array (
				'META_KEYWORDS' => 'keyword2',
				'META_DESCRIPTION' => 'description2',
				'EMBED_CSS' => 0,
				'CSS_ADD_RESET' => 1,
			),
		);
		$this->assertEquals(my_array_merge((array)$to_merge_1, $to_merge_2), $merged);
	}
}
