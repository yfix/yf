<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class func_translit_test extends PHPUnit_Framework_TestCase {
	public function test_01() {
		$this->assertEquals('Test', common()->make_translit('Тест'));
		$this->assertEquals('FILTR JUNIOR VAUEN (pach/10 sht)', common()->make_translit('ФИЛЬТР JUNIOR VAUEN (пач/10 шт)'));
		$this->assertEquals('FOLGA DLYA  KALYANA AL FAKHER Pach35', common()->make_translit('ФОЛЬГА ДЛЯ  КАЛЬЯНА AL FAKHER Пач35'));
// TODO: lot of tests
	}
}