<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_my_array_merge_test extends yf\tests\wrapper {
	public function test_simple() {
		$this->assertEquals(my_array_merge(['key1' => 1], ['key1' => 1]), ['key1' => 1]);
	}
	public function test_complex() {
		$to_merge_1 = [
			'auth_user' => [
				'EXEC_AFTER_LOGIN'		=> [
					['_add_login_activity'],
				],
			],
			'send_mail'	=> [
				'USE_MAILER'	=> 'phpmailer',
			],
			'tpl' => [
				'ALLOW_LANG_BASED_STPLS' => 1,
				'CUSTOM_META_INFO'		=> 1,
			],
			'graphics'	=> [
				'META_KEYWORDS'			=> 'keyword2',
				'META_DESCRIPTION'		=> 'description2',
				'EMBED_CSS'			=> 0,
			],
			'i18n' => [
				'REPLACE_UNDERSCORE' => 1,
			],
		];
		$to_merge_2 = [
			'main'	=> [
				'USE_CUSTOM_ERRORS'		=> 1,
				'STATIC_PAGES_ROUTE_TOP'=> 1,
			],
			'auth_user' => [
				'URL_SUCCESS_LOGIN' => './?object=account', 
				'EXEC_AFTER_LOGIN'		=> [
					['_add_login_activity'],
				],
			],
			'graphics'	=> [
				'CSS_ADD_RESET'		=> 1,
			],
			'debug_info' => [
				'_SHOW_NOT_TRANSLATED'	=> 1,
				'_SHOW_I18N_VARS'	 => 1,
			],
			'rewrite'	=> [
				'_rewrite_add_extension'	=> '/',
			],
			'comments'	=> [
				'USE_TREE_MODE' => 1,
			],
			'register'	=> [
				'NICK_ALLOWED_SYMBOLS'	=> ['а-я','a-z','0-9','_','\-','@','#',' '],
			],
			'validate'	=> [
				'NICK_ALLOWED_SYMBOLS'	=> ['а-я','a-z','0-9','_','\-','@','#',' '],
			],
			'bb_codes'	=> [
				'SMILIES_DIR'	=> 'uploads/forum/smilies/',
			],
		];
		$merged = [
			'send_mail' => [
				'USE_MAILER' => 'phpmailer',
			],
			'tpl' => [
				'ALLOW_LANG_BASED_STPLS' => 1,
				'CUSTOM_META_INFO' => 1,
			],
			'i18n' => [
				'REPLACE_UNDERSCORE' => 1,
			],
			'main' => [
				'USE_CUSTOM_ERRORS' => 1,
				'STATIC_PAGES_ROUTE_TOP' => 1,
			],
			'debug_info' => [
				'_SHOW_NOT_TRANSLATED' => 1,
				'_SHOW_I18N_VARS' => 1,
			],
			'rewrite' => [
				'_rewrite_add_extension' => '/',
			],
			'comments' => [
				'USE_TREE_MODE' => 1,
			],
			'register' => [
				'NICK_ALLOWED_SYMBOLS' => [0 => 'а-я',1 => 'a-z',2 => '0-9',3 => '_',4 => '\\-',5 => '@',6 => '#',7 => ' ',],
			],
			'validate' => [
				'NICK_ALLOWED_SYMBOLS' => [0 => 'а-я',1 => 'a-z',2 => '0-9',3 => '_',4 => '\\-',5 => '@',6 => '#',7 => ' ',],
			],
			'bb_codes' => [
				'SMILIES_DIR' => 'uploads/forum/smilies/',
			],
			'auth_user' => [
				'URL_SUCCESS_LOGIN' => './?object=account',
				'EXEC_AFTER_LOGIN' => [
					0 => [
					0 => '_add_login_activity',
					],
				],
			],
			'graphics' => [
				'META_KEYWORDS' => 'keyword2',
				'META_DESCRIPTION' => 'description2',
				'EMBED_CSS' => 0,
				'CSS_ADD_RESET' => 1,
			],
		];
		$this->assertEquals(my_array_merge((array)$to_merge_1, $to_merge_2), $merged);
	}
}
