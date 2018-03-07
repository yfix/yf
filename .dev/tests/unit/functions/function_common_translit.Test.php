<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_common_translit_test extends yf\tests\wrapper {
	public function test_simple() {
	}
	public function test_complex() {
		$this->assertEquals('Test', common()->make_translit('Тест'));
		$this->assertEquals('FILTR JUNIOR VAUEN (pach/10 sht)', common()->make_translit('ФИЛЬТР JUNIOR VAUEN (пач/10 шт)'));
		$this->assertEquals('FOLGA DLYA  KALYANA AL FAKHER Pach35', common()->make_translit('ФОЛЬГА ДЛЯ  КАЛЬЯНА AL FAKHER Пач35'));
	}
}