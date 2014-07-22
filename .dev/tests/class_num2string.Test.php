<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_num2string_test extends PHPUnit_Framework_TestCase {
	public function test_num2str_uah_ru() {
		$this->assertEquals( 'ноль гривен 0 копеек',       common()->num2str(-0) );
		$this->assertEquals( 'минус одна гривна 0 копеек', common()->num2str(-1) );
		$this->assertEquals( 'ноль гривен 0 копеек',       common()->num2str(0) );
		$this->assertEquals( 'ноль гривен 1 копейка',      common()->num2str(.01) );
		$this->assertEquals( 'ноль гривен 2 копейки',      common()->num2str(.02) );
		$this->assertEquals( 'ноль гривен 3 копейки',      common()->num2str(.03) );
		$this->assertEquals( 'ноль гривен 4 копейки',      common()->num2str(.04) );
		$this->assertEquals( 'ноль гривен 5 копеек',       common()->num2str(.05) );
		$this->assertEquals( 'ноль гривен 6 копеек',       common()->num2str(.06) );
		$this->assertEquals( 'ноль гривен 7 копеек',       common()->num2str(.07) );
		$this->assertEquals( 'ноль гривен 8 копеек',       common()->num2str(.08) );
		$this->assertEquals( 'ноль гривен 9 копеек',       common()->num2str(.09) );
		$this->assertEquals( 'ноль гривен 10 копеек',      common()->num2str(.1) );
		$this->assertEquals( 'ноль гривен 15 копеек',      common()->num2str(.15) );
		$this->assertEquals( 'ноль гривен 21 копейка',     common()->num2str(.21) );
		$this->assertEquals( 'ноль гривен 23 копейки',     common()->num2str(.23) );
		$this->assertEquals( 'одна гривна 0 копеек',       common()->num2str(1) );
		$this->assertEquals( 'две гривни 0 копеек',        common()->num2str(2) );
		$this->assertEquals( 'три гривни 0 копеек',        common()->num2str(3) );
		$this->assertEquals( 'четыре гривни 0 копеек',     common()->num2str(4) );
		$this->assertEquals( 'пять гривен 0 копеек',       common()->num2str(5) );
		$this->assertEquals( 'шесть гривен 0 копеек',      common()->num2str(6) );
		$this->assertEquals( 'семь гривен 0 копеек',       common()->num2str(7) );
		$this->assertEquals( 'восемь гривен 0 копеек',     common()->num2str(8) );
		$this->assertEquals( 'девять гривен 0 копеек',     common()->num2str(9) );
		$this->assertEquals( 'одна гривна 0 копеек',       common()->num2str(1.00) );
		$this->assertEquals( 'одна гривна 1 копейка',      common()->num2str(1.01) );
		$this->assertEquals( 'две гривни 50 копеек',       common()->num2str(2.5) );
		$this->assertEquals( 'двадцать одна гривна 0 копеек',  common()->num2str(21) );
		$this->assertEquals( 'двенадцать гривен 5 копеек',     common()->num2str(12.05) );
		$this->assertEquals( 'сто двенадцать гривен 5 копеек', common()->num2str(112.05) );
		$this->assertEquals( 'двести гривен 0 копеек',         common()->num2str(200) );
		$this->assertEquals( 'двести пятьдесят шесть гривен 67 копеек',      common()->num2str(256.67) );
		$this->assertEquals( 'одна тысяча сто одиннадцать гривен 11 копеек', common()->num2str(1111.11) );
		$this->assertEquals( 'двести двадцать две тысячи двести двадцать две гривни 45 копеек', common()->num2str(222222.45) );
		$this->assertEquals( 'двенадцать миллионов триста сорок пять тысяч шестьсот семьдесят восемь гривен 90 копеек', common()->num2str(12345678.90) );
		$this->assertEquals( 'один миллиард двести тридцать четыре миллиона пятьсот шестьдесят семь тысяч восемьсот девяносто гривен 99 копеек', common()->num2str(1234567890.99) );
		$this->assertEquals( 'три милиарда двести тридцать четыре миллиона пятьсот шестьдесят семь тысяч восемьсот девяносто гривен 99 копеек', common()->num2str(3234567890.99) );

		$value = true;
		$sign = _class( 'common_num2string', 'class/common' )->sign( $value );
		$this->assertEquals( $value, $sign );
		$this->assertEquals( 'плюс одна гривна 1 копейка',  common()->num2str(1.01) );
		$this->assertEquals( 'минус одна гривна 1 копейка',  common()->num2str(-1.01) );
		$value = false;
		$sign = _class( 'common_num2string', 'class/common' )->sign( $value );
		$this->assertEquals( $value, $sign );
		$this->assertEquals( 'одна гривна 1 копейка',  common()->num2str(1.01) );
		$this->assertEquals( 'минус одна гривна 1 копейка',  common()->num2str(-1.01) );
	}
	public function test_num2str_uah_uk() {
// TODO
	}
	public function test_num2str_uah_en() {
// TODO
	}
	public function test_num2str_rub_ru() {
		$currency_id__current = _class( 'common_num2string', 'class/common' )->currency_id();
		$currency_id = _class( 'common_num2string', 'class/common' )->currency_id( 'not_exists' );
		$this->assertEquals( $currency_id__current, $currency_id );

		$this->assertEquals( 'ноль гривен 0 копеек',   common()->num2str( 0 ) );
		$this->assertEquals( 'ноль рублей 0 копеек',   common()->num2str( 0, 'RUB' ) );

		$currency    = 'RUB';
		$currency_id = _class( 'common_num2string', 'class/common' )->currency_id( $currency );
		$this->assertEquals( $currency, $currency_id );

		$this->assertEquals( 'ноль рублей 1 копейка',  common()->num2str( 0.01 ) );
		$this->assertEquals( 'двести двадцать две тысячи двести двадцать два рубля 45 копеек', common()->num2str( 222222.45 ) );
	}
	public function test_num2str_rub_en() {
// TODO
	}
	public function test_num2str_usd_ru() {
		$currency    = 'USD';
		$currency_id = _class( 'common_num2string', 'class/common' )->currency_id( $currency );
		$this->assertEquals( $currency, $currency_id );

		$this->assertEquals( 'ноль долларов 1 копейка',  common()->num2str( 0.01 ) );
		$this->assertEquals( 'двести двадцать две тысячи двести двадцать два доллара 45 копеек', common()->num2str( 222222.45 ) );
	}
	public function test_num2str_usd_uk() {
// TODO
	}
	public function test_num2str_usd_en() {
// TODO
	}
	public function test_num2str_eur_ru() {
		$currency    = 'EUR';
		$currency_id = _class( 'common_num2string', 'class/common' )->currency_id( $currency );
		$this->assertEquals( $currency, $currency_id );

		$this->assertEquals( 'ноль евро 1 копейка',  common()->num2str( 0.01 ) );
		$this->assertEquals( 'двести двадцать две тысячи двести двадцать два евро 45 копеек', common()->num2str( 222222.45 ) );
	}
	public function test_num2str_eur_uk() {
// TODO
	}
	public function test_num2str_eur_en() {
// TODO
	}
}
