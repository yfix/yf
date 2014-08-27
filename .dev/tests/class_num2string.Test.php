<?php

require __DIR__.'/yf_unit_tests_setup.php';

class class_num2string_test extends PHPUnit_Framework_TestCase {

	public function test_num2str_uah_ru() {
#		$this->assertEquals( 'пятьсот пятьдесят пять триллионов четыреста сорок четыре милиарда триста тридцать три миллиона двести двадцать две тысячи сто одиннадцать гривен 99 копеек', common()->num2str( '555 444 333 222 111.999', 'uah', 'ru' ) );
#		$this->assertEquals( 'п`ятьсот п`ятьдесят п`ять трильйонів чотиреста сорок чотири мільярда триста тридцять три мільйона двісті двадцять дві тисячі сто одиннадцять гривень 99 копійок', common()->num2str( '555 444 333 222 111.999', 'uah', 'ua' ) );
#		$this->assertEquals( 'five hundred fifty five trillions four hundred forty four milliards three hundred thirty three millions two hundred twenty two thousands one hundred eleven grivnas 99 kopecks', common()->num2str( '555 444 333 222 111.999', 'uah', 'en' ) );
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
#		$this->assertEquals( 'two hundred twenty two milliards two hundred twenty two millions two hundred twenty two thousands two hundred twenty two euros 22 cents', common()->num2str( 222222222222.222, 'EUR', 'EN' ) );
		// ----------------------
		$value = true;
		$sign = _class( 'common_num2string', 'class/common' )->sign( $value );
		$this->assertEquals( $value, $sign );
		$this->assertEquals( 'плюс одна гривна 1 копейка',  common()->num2str(1.01) );
		$this->assertEquals( 'минус одна гривна 1 копейка',  common()->num2str(-1.01) );
		// ----------------------
		$value = false;
		$sign = _class( 'common_num2string', 'class/common' )->sign( $value );
		$this->assertEquals( $value, $sign );
		$this->assertEquals( 'одна гривна 1 копейка',  common()->num2str(1.01) );
		$this->assertEquals( 'минус одна гривна 1 копейка',  common()->num2str(-1.01) );
		// ----------------------
		$value_ok = false;
		$value = _class( 'common_num2string', 'class/common' )->cent_number( $value_ok );
		$this->assertEquals( $value_ok, $value );
		$this->assertEquals( 'ноль гривен ноль копеек', common()->num2str(-0) );
		$this->assertEquals( 'ноль гривен ноль копеек', common()->num2str(0) );
		$this->assertEquals( 'ноль гривен ноль копеек', common()->num2str(+0.0) );
		$this->assertEquals( 'ноль гривен двадцать три копейки', common()->num2str(0.23) );
		$this->assertEquals( 'минус двенадцать гривен ноль копеек', common()->num2str(-12.0) );
		_class( 'common_num2string', 'class/common' )->cent_number( true );
		$this->assertEquals( 'ноль гривен 0 копеек',    common()->num2str(0) );
		// ----------------------
		$value_ok = false;
		$value = _class( 'common_num2string', 'class/common' )->cent_zero( $value_ok );
		$this->assertEquals( $value_ok, $value );
		$this->assertEquals( 'ноль гривен', common()->num2str(-0) );
		$this->assertEquals( 'минус двенадцать гривен', common()->num2str(-12.0) );
		_class( 'common_num2string', 'class/common' )->cent_zero( true );
		$this->assertEquals( 'ноль гривен 0 копеек',    common()->num2str(0) );
	}

	public function test_num2str_uah_ua() {
		$value = 'UA';
		$value_id = _class( 'common_num2string', 'class/common' )->lang_id( $value );
		$this->assertEquals( $value, $value_id );

		$value = 'UAH';
		$value_id = _class( 'common_num2string', 'class/common' )->currency_id( $value );
		$this->assertEquals( $value, $value_id );

		$this->assertEquals( 'нуль гривень 1 копійка',  common()->num2str( 0.01 ) );
		$this->assertEquals( 'двісті двадцять дві тисячі двісті двадцять дві гривні 45 копійок', common()->num2str( 222222.45 ) );
	}
	public function test_num2str_uah_en() {
		_class( 'common_num2string', 'class/common' )->lang_id( 'en' );
		_class( 'common_num2string', 'class/common' )->currency_id( 'uah' );
		$this->assertEquals( 'zero grivnas 1 kopeck',  common()->num2str( 0.01 ) );
		$this->assertEquals( 'two hundred twenty two thousands two hundred twenty two grivnas 45 kopecks', common()->num2str( 222222.45 ) );
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
		_class( 'common_num2string', 'class/common' )->lang_id( 'en' );
		_class( 'common_num2string', 'class/common' )->currency_id( 'rub' );
		$this->assertEquals( 'zero roubles 1 kopeck',  common()->num2str( 0.01 ) );
		$this->assertEquals( 'two hundred twenty two thousands two hundred twenty two roubles 45 kopecks', common()->num2str( 222222.45 ) );
	}
	public function test_num2str_usd_ru() {
		$currency    = 'USD';
		$currency_id = _class( 'common_num2string', 'class/common' )->currency_id( $currency );
		$this->assertEquals( $currency, $currency_id );

		$this->assertEquals( 'ноль долларов 1 цент',  common()->num2str( 0.01 ) );
		$this->assertEquals( 'двести двадцать две тысячи двести двадцать два доллара 45 центов', common()->num2str( 222222.45 ) );
	}
	public function test_num2str_usd_ua() {
		_class( 'common_num2string', 'class/common' )->lang_id( 'ua' );
		_class( 'common_num2string', 'class/common' )->currency_id( 'usd' );
		$this->assertEquals( 'нуль доларів 1 цент',  common()->num2str( 0.01 ) );
		$this->assertEquals( 'двісті двадцять дві тисячі двісті двадцять два долара 45 центів', common()->num2str( 222222.45 ) );
	}
	public function test_num2str_usd_en() {
		_class( 'common_num2string', 'class/common' )->lang_id( 'en' );
		_class( 'common_num2string', 'class/common' )->currency_id( 'usd' );
		$this->assertEquals( 'zero dollars 1 cent',  common()->num2str( 0.01 ) );
		$this->assertEquals( 'two hundred twenty two thousands two hundred twenty two dollars 45 cents', common()->num2str( 222222.45 ) );
	}
	public function test_num2str_eur_ru() {
		$currency    = 'EUR';
		$currency_id = _class( 'common_num2string', 'class/common' )->currency_id( $currency );
		$this->assertEquals( $currency, $currency_id );

		$this->assertEquals( 'ноль евро 1 цент',  common()->num2str( 0.01 ) );
		$this->assertEquals( 'двести двадцать две тысячи двести двадцать два евро 45 центов', common()->num2str( 222222.45 ) );
	}
	public function test_num2str_eur_ua() {
		_class( 'common_num2string', 'class/common' )->lang_id( 'ua' );
		_class( 'common_num2string', 'class/common' )->currency_id( 'eur' );
		$this->assertEquals( 'нуль євро 1 цент',  common()->num2str( 0.01 ) );
		$this->assertEquals( 'двісті двадцять дві тисячі двісті двадцять два євро 45 центів', common()->num2str( 222222.45 ) );
	}
	public function test_num2str_eur_en() {
		_class( 'common_num2string', 'class/common' )->lang_id( 'en' );
		_class( 'common_num2string', 'class/common' )->currency_id( 'eur' );
		$this->assertEquals( 'zero euros 1 cent',  common()->num2str( 0.01 ) );
		$this->assertEquals( 'two hundred twenty two thousands two hundred twenty two euros 45 cents', common()->num2str( 222222.45 ) );
#		$this->assertEquals( 'two hundred twenty two milliards two hundred twenty two millions two hundred twenty two thousands two hundred twenty two euros 22 cents', common()->num2str( 222222222222.222 ) );
	}
}
