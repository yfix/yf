<?php

require_once __DIR__.'/yf_unit_tests_setup.php';

class class_i18n_test extends yf\tests\wrapper {
	public static $no_cache = null;
	public static function setUpBeforeClass() {
		self::$no_cache = cache()->NO_CACHE;
		cache()->NO_CACHE = true;
		define('DEFAULT_LANG', 'en');
		_class('i18n')->TR_VARS['en'] = [];
		_class('i18n')->TRANSLATE_ENABLED = true;
		_class('i18n')->USE_TRANSLATE_CACHE = false;
		_class('i18n')->ALLOW_SESSION_LANG = false;
		_class('i18n')->VARS_IGNORE_CASE = true;
		_class('i18n')->TRACK_FIRST_LETTER_CASE	= true;
	}
	public static function tearDownAfterClass() {
		_class('i18n')->TR_VARS['en'] = [];
		cache()->NO_CACHE = self::$no_cache;
	}
	public function test_simple() {
		_class('i18n')->TR_VARS['en'] = [];
		_class('i18n')->TR_VARS['en']['unit_test_var1'] = 'unit_test_value1';
		$this->assertEquals('unit_test_value1', t('unit_test_var1'));
		$this->assertEquals('unit_test_value1', t(' unit_test_var1'));
		$this->assertEquals('unit_test_value1', t('unit_test_var1 '));
		$this->assertEquals('unit_test_value1', t(' unit_test_var1 '));
		$this->assertEquals('unit_test_value1', t('   unit_test_var1   '));
		_class('i18n')->TR_VARS['en']['unit test var1'] = 'unit test value1';
		$this->assertEquals('unit test value1', t('unit test var1'));
		$this->assertEquals('unit test value1', t(' unit test var1'));
		$this->assertEquals('unit test value1', t('unit test var1 '));
		$this->assertEquals('unit test value1', t(' unit test var1 '));
		$this->assertEquals('unit test value1', t('   unit test var1   '));
	}
	public function test_first_letter() {
		_class('i18n')->TR_VARS['en'] = [];
		_class('i18n')->TR_VARS['en']['unit test var1'] = 'unit test value1';
		$this->assertEquals('unit test value1', t('unit test var1'));
		$this->assertEquals('Unit test value1', t('Unit test var1'));
	}
	public function test_first_unicode() {
		_class('i18n')->TR_VARS['en'] = [];
		_class('i18n')->TR_VARS['en']['моя переменная'] = 'перевод';
		$this->assertEquals('перевод', t('моя переменная'));
		$this->assertEquals('Перевод', t('Моя переменная'));
	}
	public function test_underscores() {
		_class('i18n')->TR_VARS['en'] = [];
		_class('i18n')->TR_VARS['en']['unit_test_var1'] = 'unit_test_value1';
		$this->assertNotEquals('unit_test_value1', t('unit test var1'));
		$this->assertNotEquals('unit test value1', t('unit_test_var1'));
	}
	public function test_namespaces() {
		_class('i18n')->TR_VARS['en'] = [];
		_class('i18n')->TR_VARS['en']['unit_test_var1'] = 'unit_test_value1';
		_class('i18n')->TR_VARS['en']['::prefix::unit_test_var1'] = 'unit_test_value2';
		_class('i18n')->TR_VARS['en']['::prefix_with_underscores::unit_test_var1'] = 'unit_test_value3';
		$this->assertEquals('unit_test_value1', t('unit_test_var1'));
		$this->assertEquals('unit_test_value2', t('::prefix::unit_test_var1'));
		$this->assertEquals('unit_test_value3', t('::prefix_with_underscores::unit_test_var1'));
		$this->assertEquals('unit_test_value1', t('::not_existing_prefix::unit_test_var1'));
		$this->assertEquals('wrongprefix::unit_test_var1', t('wrongprefix::unit_test_var1'));
		$this->assertEquals(':wrongprefix:unit_test_var1', t(':wrongprefix:unit_test_var1'));
		$this->assertEquals('unit_test_var1:wrongprefix:', t('unit_test_var1:wrongprefix:'));
	}
	public function test_special_symbols() {
		_class('i18n')->TR_VARS['en'] = [];
		$this->assertEquals('[img]http://www.google.com/intl/en_ALL/images/logo.gif[/img]', t('[img]http://www.google.com/intl/en_ALL/images/logo.gif[/img]'));
	}
	public function test_vars_simple() {
		_class('i18n')->TR_VARS['en'] = [];
		$this->assertEquals('my translate a=b', t('my translate a=b'));
		$this->assertEquals('%insert translate', t('%insert translate'));
		$this->assertEquals('translate %insert', t('translate %insert'));
		$this->assertEquals('my %insert translate', t('my %insert translate'));
		$this->assertEquals('my test translate', t('my %insert translate', ['%insert' => 'test']));

		$this->assertEquals('my %insert translate', t('::test::my %insert translate'));
		$this->assertEquals('my test translate', t('::test::my %insert translate', ['%insert' => 'test']));

		$this->assertEquals('my test, test, test translate', t('::test::my %insert, %insert, %insert translate', ['%insert' => 'test']));
	}
	public function test_vars_complex() {
		_class('i18n')->TR_VARS['en'] = [];
		$this->assertEquals(
			'my test1, test2, test3, test4 translate', 
			t('::test::my %insert1, %insert2, %insert3, %insert4 translate', ['%insert1' => 'test1', '%insert2' => 'test2', '%insert3' => 'test3', '%insert4' => 'test4'])
		);
		$this->assertEquals(
			'my <b>test1</b>, <i>test2</i> translate', 
			t('::test::my <b>%insert1</b>, <i>%insert2</i> translate', ['%insert1' => 'test1', '%insert2' => 'test2'])
		);
		$this->assertEquals(
			'my <img src="https://www.google.com/images/srpr/logo3w.png">, <b>test1</b>, <i>test2</i> translate', 
			t('::test::my <img src="https://www.google.com/images/srpr/logo3w.png">, <b>%insert1</b>, <i>%insert2</i> translate', ['%insert1' => 'test1', '%insert2' => 'test2'])
		);
		$this->assertEquals(
			'my <img src="https://www.google.com/images/srpr/logo3w.png">, <b>test1</b>, <i>test2</i> translate', 
			t('::test::my <img src="%url">, <b>%inser</b>, <i>%ins</i> translate', ['%inser' => 'test1', '%ins' => 'test2', '%url' => 'https://www.google.com/images/srpr/logo3w.png'])
		);
	}
	public function test_patterns_direct() {
		_class('i18n')->TR_VARS['en'] = [];
		$str = 'В процессе поиска {Найдено %num папок|0:Папок не найдено|1:Найдена %num папка|2,3,4:Найдено %num папки|11-14:Найдено %num папок|Найдено %num папок}';
		$this->assertEquals('В процессе поиска Папок не найдено', _class('i18n')->_process_sub_patterns($str, ['%num' => '0']) );
		$this->assertEquals('В процессе поиска Найдена 1 папка', _class('i18n')->_process_sub_patterns($str, ['%num' => '1']) );
		$this->assertEquals('В процессе поиска Найдено 2 папки', _class('i18n')->_process_sub_patterns($str, ['%num' => '2']) );
		$this->assertEquals('В процессе поиска Найдено 3 папки', _class('i18n')->_process_sub_patterns($str, ['%num' => '3']) );
		$this->assertEquals('В процессе поиска Найдено 4 папки', _class('i18n')->_process_sub_patterns($str, ['%num' => '4']) );
		$this->assertEquals('В процессе поиска Найдено 5 папок', _class('i18n')->_process_sub_patterns($str, ['%num' => '5']) );
		$this->assertEquals('В процессе поиска Найдено 6 папок', _class('i18n')->_process_sub_patterns($str, ['%num' => '6']) );
		$this->assertEquals('В процессе поиска Найдено 7 папок', _class('i18n')->_process_sub_patterns($str, ['%num' => '7']) );
		$this->assertEquals('В процессе поиска Найдено 8 папок', _class('i18n')->_process_sub_patterns($str, ['%num' => '8']) );
		$this->assertEquals('В процессе поиска Найдено 9 папок', _class('i18n')->_process_sub_patterns($str, ['%num' => '9']) );
		$this->assertEquals('В процессе поиска Найдено 10 папок', _class('i18n')->_process_sub_patterns($str, ['%num' => '10']) );
		$this->assertEquals('В процессе поиска Найдено 11 папок', _class('i18n')->_process_sub_patterns($str, ['%num' => '11']) );
		$this->assertEquals('В процессе поиска Найдено 12 папок', _class('i18n')->_process_sub_patterns($str, ['%num' => '12']) );
		$this->assertEquals('В процессе поиска Найдено 13 папок', _class('i18n')->_process_sub_patterns($str, ['%num' => '13']) );
		$this->assertEquals('В процессе поиска Найдено 14 папок', _class('i18n')->_process_sub_patterns($str, ['%num' => '14']) );
		$this->assertEquals('В процессе поиска Найдено 15 папок', _class('i18n')->_process_sub_patterns($str, ['%num' => '15']) );
		$this->assertEquals('В процессе поиска Найдено 100 папок', _class('i18n')->_process_sub_patterns($str, ['%num' => '100']) );
		$this->assertEquals('В процессе поиска Найдено 1222 папки', _class('i18n')->_process_sub_patterns($str, ['%num' => '1222']) );
	}
	public function test_patterns_t() {
		_class('i18n')->TR_VARS['en'] = [];
		_class('i18n')->TR_VARS['ru'] = [];

		$var = 'While searching %num folders found';
		$translation = 'В процессе поиска {Найдено %num папок|0:Папок не найдено|1:Найдена %num папка|2,3,4:Найдено %num папки|11-14:Найдено %num папок|Найдено %num папок}';

		_class('i18n')->TR_VARS['ru'][strtolower($var)] = $translation;
		_class('i18n')->_loaded['ru'] = true;

		$this->assertEquals('В процессе поиска Папок не найдено', t($var, ['%num' => '0'], 'ru') );
		$this->assertEquals('В процессе поиска Найдена 1 папка', t($var, ['%num' => '1'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 2 папки', t($var, ['%num' => '2'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 3 папки', t($var, ['%num' => '3'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 4 папки', t($var, ['%num' => '4'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 5 папок', t($var, ['%num' => '5'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 6 папок', t($var, ['%num' => '6'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 7 папок', t($var, ['%num' => '7'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 8 папок', t($var, ['%num' => '8'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 9 папок', t($var, ['%num' => '9'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 10 папок', t($var, ['%num' => '10'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 11 папок', t($var, ['%num' => '11'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 12 папок', t($var, ['%num' => '12'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 13 папок', t($var, ['%num' => '13'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 14 папок', t($var, ['%num' => '14'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 15 папок', t($var, ['%num' => '15'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 100 папок', t($var, ['%num' => '100'], 'ru') );
		$this->assertEquals('В процессе поиска Найдено 1222 папки', t($var, ['%num' => '1222'], 'ru') );
	}
	public function test_vars_unicode() {
		_class('i18n')->TR_VARS['en'] = [];
		$this->assertEquals('Моя переменная a=b', t('Моя переменная a=b'));
		$this->assertEquals('Моя %insert переменная', t('Моя %insert переменная'));
		$this->assertEquals('Моя тестовая переменная', t('Моя %insert переменная', ['%insert' => 'тестовая']));

		$this->assertEquals('Моя %insert переменная', t('::test::Моя %insert переменная'));
		$this->assertEquals('Моя тестовая переменная', t('::test::Моя %insert переменная', ['%insert' => 'тестовая']));

		$this->assertEquals('Моя тестовая тестовая тестовая переменная', t('::test::Моя %insert %insert %insert переменная', ['%insert' => 'тестовая']));
	}
	public function test_un_html_entities() {
		_class('i18n')->TR_VARS['en'] = [];
		$this->assertEquals('&quot;var&quot;', t('&quot;var&quot;'));
		_class('i18n')->TR_VARS['en']['"var"'] = 'translation';
		$this->assertEquals('translation', t('&quot;var&quot;'));
	}
}
