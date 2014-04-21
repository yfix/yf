<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_i18n_test extends PHPUnit_Framework_TestCase {
	public static function setUpBeforeClass() {
		define('DEFAULT_LANG', 'en');
		$_GET['no_core_cache'] = true;
		_class('i18n')->USE_TRANSLATE_CACHE = false;
		_class('i18n')->TR_VARS['en']['unit_test_var1'] = 'unit_test_value1';
	}
	public static function tearDownAfterClass() {
	}
	public function test_10() {
		$this->assertEquals('unit_test_value1', t('unit_test_var1'));
		$this->assertEquals('unit_test_value1', t(' unit_test_var1'));
		$this->assertEquals('unit_test_value1', t('unit_test_var1 '));
		$this->assertEquals('unit_test_value1', t(' unit_test_var1 '));
		$this->assertEquals('unit_test_value1', t('   unit_test_var1   '));
	}
	public function test_20() {
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
	public function test_30() {
		$str = 'В процессе поиска {Найдено %num папок|0:Папок не найдено|1:Найдена %num папка|2,3,4:Найдено %num папки|11-14:Найдено %num папок|Найдено %num папок}';
		_class('i18n')->TR_VARS['ru']['While searching %num folders found'] = $str;
#		$this->assertEquals('', t('While searching %num folders found', array('%num' => '0'), 'ru') );
#		$this->assertEquals('', t('While searching %num folders found', array('%num' => '1'), 'ru') );
	}
	public function test_40() {
//		$this->assertEquals('', t('[img]http://www.google.com/intl/en_ALL/images/logo.gif[/img]'));
/*
		t("Test var");
		t("::forum::Test var");
		t("::forum__new_post::Test var");
		t("::gallery::Test var");
		t("::bl_ablabla::Test var");
*/
	}
}
