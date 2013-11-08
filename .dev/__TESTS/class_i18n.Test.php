<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_i18n_test extends PHPUnit_Framework_TestCase {
#	public static $driver_bak = array();
	public static function setUpBeforeClass() {
		define('DEFAULT_LANG', 'en');
		_class('i18n')->TR_VARS['en']['unit_test_var1'] = 'unit_test_value1';
#		self::$driver_bak = tpl()->DRIVER_NAME;
#		tpl()->DRIVER_NAME = 'smarty';
#		parent::setUpBeforeClass();
	}
	public static function tearDownAfterClass() {
#		tpl()->DRIVER_NAME = self::$driver_bak;
#		parent::tearDownAfterClass();
	}
	public function test_10() {
		$this->assertEquals('unit_test_value1', t('unit_test_var1'));
		$this->assertEquals('unit_test_value1', t(' unit_test_var1'));
		$this->assertEquals('unit_test_value1', t('unit_test_var1 '));
		$this->assertEquals('unit_test_value1', t(' unit_test_var1 '));
		$this->assertEquals('unit_test_value1', t('   unit_test_var1   '));
/*
		t("Test var")."<br /><br />";
		t("::forum::Test var")."<br /><br />";
		t("::forum__new_post::Test var")."<br /><br />";
		t("::gallery::Test var")."<br /><br />";
		t("::bl_ablabla::Test var")."<br /><br />";
		t("Read %numreads times", array("%numreads" => "0"))."<br /><br />";
		t("Read %numreads times", array("%numreads" => "1"))."<br /><br />";
		t("Read %numreads times", array("%numreads" => "2"))."<br /><br />";
		t("Read %numreads times", array("%numreads" => "11"))."<br /><br />";
		t("Read %numreads times", array("%numreads" => "20"))."<br /><br />";
		t("Read %numreads times", array("%numreads" => "10001"))."<br /><br />";

		t("While searching %num folders found", array("%num" => "0"))
		t("While searching %num folders found", array("%num" => "1"))
		t("While searching %num folders found", array("%num" => "2"))
		t("While searching %num folders found", array("%num" => "3"))
		t("While searching %num folders found", array("%num" => "4"))
		t("While searching %num folders found", array("%num" => "5"))
		t("While searching %num folders found", array("%num" => "9"))
		t("While searching %num folders found", array("%num" => "10"))
		t("While searching %num folders found", array("%num" => "11"))
		t("While searching %num folders found", array("%num" => "12"))
		t("While searching %num folders found", array("%num" => "20"))
		t("While searching %num folders found", array("%num" => "100"))
		t("While searching %num folders found", array("%num" => "101"))
		t("While searching %num folders found", array("%num" => "111"))
		t("While searching %num folders found", array("%num" => "10003"))

#	* {t(While searching %num folders found,%num=1001)}
#	* В процессе поиска {Найдено %num папок|0:Папок не найдено|1:Найдена %num папка|2,3,4:Найдено %num папки|11-14:Найдено %num папок|Найдено %num папок}
#	function _process_sub_patterns($text = '', $args = array()) {

//		$this->assertEquals('', t('[img]http://www.google.com/intl/en_ALL/images/logo.gif[/img]'));
*/
	}
}
