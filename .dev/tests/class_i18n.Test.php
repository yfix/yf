<?php

require_once __DIR__.'/yf_unit_tests_setup.php';

class class_i18n_test extends PHPUnit_Framework_TestCase {
	public static $no_cache = null;
	public static function setUpBeforeClass() {
		self::$no_cache = cache()->NO_CACHE;
		cache()->NO_CACHE = true;
		define('DEFAULT_LANG', 'en');
		_class('i18n')->USE_TRANSLATE_CACHE = false;
	}
	public static function tearDownAfterClass() {
		cache()->NO_CACHE = self::$no_cache;
	}
	public function test_simple() {
		_class('i18n')->TR_VARS['en']['unit_test_var1'] = 'unit_test_value1';
		$this->assertEquals('unit_test_value1', t('unit_test_var1'));
		$this->assertEquals('unit_test_value1', t(' unit_test_var1'));
		$this->assertEquals('unit_test_value1', t('unit_test_var1 '));
		$this->assertEquals('unit_test_value1', t(' unit_test_var1 '));
		$this->assertEquals('unit_test_value1', t('   unit_test_var1   '));
	}
	public function test_underscores() {
		_class('i18n')->TR_VARS['en']['unit_test_var1'] = 'unit_test_value1';
		$this->assertEquals('unit_test_value1', t('unit test var1'));
	}
	public function test_namespaces() {
		_class('i18n')->TR_VARS['en']['unit_test_var1'] = 'unit_test_value1';
		_class('i18n')->TR_VARS['en']['::prefix::unit_test_var1'] = 'unit_test_value2';
		_class('i18n')->TR_VARS['en']['::prefix_with_underscores::unit_test_var1'] = 'unit_test_value3';
		$this->assertEquals('unit_test_value1', t('unit_test_var1'));
		$this->assertEquals('unit_test_value2', t('::prefix::unit_test_var1'));
		$this->assertEquals('unit_test_value3', t('::prefix_with_underscores::unit_test_var1'));
		$this->assertEquals('unit_test_value1', t('::not_existing_prefix::unit_test_var1'));
		$this->assertEquals('wrongprefix::unit test var1', t('wrongprefix::unit_test_var1'));
		$this->assertEquals(':wrongprefix:unit test var1', t(':wrongprefix:unit_test_var1'));
		$this->assertEquals('unit test var1:wrongprefix:', t('unit_test_var1:wrongprefix:'));
	}
	public function test_special_symbols() {
		$this->assertEquals('[img]http://www.google.com/intl/en ALL/images/logo.gif[/img]', t('[img]http://www.google.com/intl/en_ALL/images/logo.gif[/img]'));
	}
	public function test_patterns_direct() {
		$str = 'В процессе поиска {Найдено %num папок|0:Папок не найдено|1:Найдена %num папка|2,3,4:Найдено %num папки|11-14:Найдено %num папок|Найдено %num папок}';
		$this->assertEquals('В процессе поиска Папок не найдено', _class('i18n')->_process_sub_patterns($str, array('%num' => '0')) );
		$this->assertEquals('В процессе поиска Найдена 1 папка', _class('i18n')->_process_sub_patterns($str, array('%num' => '1')) );
		$this->assertEquals('В процессе поиска Найдено 2 папки', _class('i18n')->_process_sub_patterns($str, array('%num' => '2')) );
		$this->assertEquals('В процессе поиска Найдено 3 папки', _class('i18n')->_process_sub_patterns($str, array('%num' => '3')) );
		$this->assertEquals('В процессе поиска Найдено 4 папки', _class('i18n')->_process_sub_patterns($str, array('%num' => '4')) );
		$this->assertEquals('В процессе поиска Найдено 5 папок', _class('i18n')->_process_sub_patterns($str, array('%num' => '5')) );
		$this->assertEquals('В процессе поиска Найдено 6 папок', _class('i18n')->_process_sub_patterns($str, array('%num' => '6')) );
		$this->assertEquals('В процессе поиска Найдено 7 папок', _class('i18n')->_process_sub_patterns($str, array('%num' => '7')) );
		$this->assertEquals('В процессе поиска Найдено 8 папок', _class('i18n')->_process_sub_patterns($str, array('%num' => '8')) );
		$this->assertEquals('В процессе поиска Найдено 9 папок', _class('i18n')->_process_sub_patterns($str, array('%num' => '9')) );
		$this->assertEquals('В процессе поиска Найдено 10 папок', _class('i18n')->_process_sub_patterns($str, array('%num' => '10')) );
		$this->assertEquals('В процессе поиска Найдено 11 папок', _class('i18n')->_process_sub_patterns($str, array('%num' => '11')) );
		$this->assertEquals('В процессе поиска Найдено 12 папок', _class('i18n')->_process_sub_patterns($str, array('%num' => '12')) );
		$this->assertEquals('В процессе поиска Найдено 13 папок', _class('i18n')->_process_sub_patterns($str, array('%num' => '13')) );
		$this->assertEquals('В процессе поиска Найдено 14 папок', _class('i18n')->_process_sub_patterns($str, array('%num' => '14')) );
		$this->assertEquals('В процессе поиска Найдено 15 папок', _class('i18n')->_process_sub_patterns($str, array('%num' => '15')) );
		$this->assertEquals('В процессе поиска Найдено 100 папок', _class('i18n')->_process_sub_patterns($str, array('%num' => '100')) );
		$this->assertEquals('В процессе поиска Найдено 1222 папки', _class('i18n')->_process_sub_patterns($str, array('%num' => '1222')) );
	}
	public function test_patterns_t() {
		$var = 'While searching %num folders found';
		$translation = 'В процессе поиска {Найдено %num папок|0:Папок не найдено|1:Найдена %num папка|2,3,4:Найдено %num папки|11-14:Найдено %num папок|Найдено %num папок}';

		_class('i18n')->TR_VARS['ru'][strtolower(str_replace(' ', '_', $var))] = $translation;
		_class('i18n')->_loaded['ru'] = true;

		$this->assertEquals('В процессе поиска Папок не найдено', t($var, array('%num' => '0'), 'ru') );
		$this->assertEquals('В процессе поиска Найдена 1 папка', t($var, array('%num' => '1'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 2 папки', t($var, array('%num' => '2'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 3 папки', t($var, array('%num' => '3'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 4 папки', t($var, array('%num' => '4'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 5 папок', t($var, array('%num' => '5'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 6 папок', t($var, array('%num' => '6'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 7 папок', t($var, array('%num' => '7'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 8 папок', t($var, array('%num' => '8'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 9 папок', t($var, array('%num' => '9'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 10 папок', t($var, array('%num' => '10'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 11 папок', t($var, array('%num' => '11'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 12 папок', t($var, array('%num' => '12'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 13 папок', t($var, array('%num' => '13'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 14 папок', t($var, array('%num' => '14'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 15 папок', t($var, array('%num' => '15'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 100 папок', t($var, array('%num' => '100'), 'ru') );
		$this->assertEquals('В процессе поиска Найдено 1222 папки', t($var, array('%num' => '1222'), 'ru') );
	}
}
