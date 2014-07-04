<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_num2string_test extends PHPUnit_Framework_TestCase {
	public function test_num2str_uah_ru() {
#		$this->assertEquals( '', common()->num2str(0) );
#		$this->assertEquals( '', common()->num2str(.01) );
#		$this->assertEquals( '', common()->num2str(.02) );
#		$this->assertEquals( '', common()->num2str(.03) );
#		$this->assertEquals( '', common()->num2str(.04) );
#		$this->assertEquals( '', common()->num2str(.05) );
#		$this->assertEquals( '', common()->num2str(.06) );
#		$this->assertEquals( '', common()->num2str(.07) );
#		$this->assertEquals( '', common()->num2str(.08) );
#		$this->assertEquals( '', common()->num2str(.09) );
#		$this->assertEquals( '', common()->num2str(.1) );
#		$this->assertEquals( '', common()->num2str(.15) );
#		$this->assertEquals( '', common()->num2str(.21) );
#		$this->assertEquals( '', common()->num2str(.23) );
#		$this->assertEquals( 'одна гривна 00 копеек', common()->num2str(1) );
#		$this->assertEquals( '', common()->num2str(2) );
#		$this->assertEquals( '', common()->num2str(3) );
#		$this->assertEquals( '', common()->num2str(4) );
#		$this->assertEquals( '', common()->num2str(5) );
#		$this->assertEquals( '', common()->num2str(6) );
#		$this->assertEquals( '', common()->num2str(7) );
#		$this->assertEquals( '', common()->num2str(8) );
#		$this->assertEquals( '', common()->num2str(9) );
#		$this->assertEquals( '', common()->num2str(1.00) );
#		$this->assertEquals( '', common()->num2str(1.01) );
#		$this->assertEquals( '', common()->num2str(2.5) );
#		$this->assertEquals( '', common()->num2str(21) );
		$this->assertEquals( 'двенадцать гривен 05 копеек', common()->num2str(12.05) );
		$this->assertEquals( 'сто двенадцать гривен 05 копеек', common()->num2str(112.05) );
		$this->assertEquals( 'двести гривен 00 копеек', common()->num2str(200) );
		$this->assertEquals( 'двести пятьдесят шесть гривен 67 копеек', common()->num2str(256.67) );
		$this->assertEquals( 'одна тысяча сто одиннадцать гривен 11 копеек', common()->num2str(1111.11) );
#		$this->assertEquals( 'двести двадцать две тысячи двести двадцать две гривни 45 копеек', common()->num2str(222222.45) );
		$this->assertEquals( 'двенадцать миллионов триста сорок пять тысяч шестьсот семьдесят восемь гривен 90 копеек', common()->num2str(12345678.90) );
		$this->assertEquals( 'один миллиард двести тридцать четыре миллиона пятьсот шестьдесят семь тысяч восемьсот девяносто гривен 99 копеек', common()->num2str(1234567890.99) );
		$this->assertEquals( 'три милиарда двести тридцать четыре миллиона пятьсот шестьдесят семь тысяч восемьсот девяносто гривен 99 копеек', common()->num2str(3234567890.99) );
	}
	public function test_num2str_uah_uk() {
// TODO
	}
	public function test_num2str_uah_en() {
// TODO
	}
	public function test_num2str_rub_ru() {
// TODO
	}
	public function test_num2str_rub_en() {
// TODO
	}
	public function test_num2str_usd_ru() {
// TODO
	}
	public function test_num2str_usd_uk() {
// TODO
	}
	public function test_num2str_usd_en() {
// TODO
	}
	public function test_num2str_eur_ru() {
// TODO
	}
	public function test_num2str_eur_uk() {
// TODO
	}
	public function test_num2str_eur_en() {
// TODO
	}
}