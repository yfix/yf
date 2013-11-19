<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class func_propose_url_from_name_test extends PHPUnit_Framework_TestCase {
	public function test_01() {
		$this->assertEquals('test', common()->_propose_url_from_name('Тест'));
		$this->assertEquals('filtr_junior_vauen_pach_10_sht', common()->_propose_url_from_name('ФИЛЬТР JUNIOR VAUEN (пач/10 шт)'));
		$this->assertEquals('folga_dlya_kalyana_al_fakher_pach35', common()->_propose_url_from_name('ФОЛЬГА ДЛЯ  КАЛЬЯНА AL FAKHER Пач35'));
// TODO: lot of tests
	}
}