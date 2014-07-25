<?php

/**
*/
class yf_common_num2string {

	protected $_lang_id     = null;
	protected $_currency_id = null;
	// gender:  0 - male; 1 - female;
	protected $words = array(
		'RU' => array(
			'currency' => array(
				'UAH' => array(
					array( 'копейка', 'копейки', 'копеек', 1 ),
					array( 'гривна',  'гривни',  'гривен', 1 ),
				),
				'RUB' => array(
					array( 'копейка', 'копейки', 'копеек', 1 ),
					array( 'рубль',   'рубля',   'рублей', 0 ),
				),
				'USD' => array(
					array( 'цент',   'цента',   'центов',   0 ),
					array( 'доллар', 'доллара', 'долларов', 0 ),
				),
				'EUR' => array(
					array( 'цент', 'цента', 'центов', 0 ),
					array( 'евро', 'евро',  'евро',   0 ),
				),
			),
			'units' => array(
				array(),
				array(),
				array( 'тысяча',   'тысячи',    'тысяч',      1 ),
				array( 'миллион',  'миллиона',  'миллионов',  0 ),
				array( 'миллиард', 'милиарда',  'миллиардов', 0 ),
				array( 'триллион', 'триллиона', 'триллионов', 0 ),
			),
			'zero'   => 'ноль',
			'signs'  => array( 'плюс', 'минус' ),
			'digits' => array(
				// 1-9
				array(
					array( null, 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять' ),
					array( null, 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять' ),
				),
				// 10-19
				array( 'десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать' , 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать' ),
				// 20-99
				array( null, null,  'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят' , 'восемьдесят', 'девяносто' ),
				// 1xx-9xx
				array( null, 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот' ),
			),
		),
		'UA' => array(
			'currency' => array(
				'UAH' => array(
					array( 'копійка', 'копійки', 'копійок', 1 ),
					array( 'гривня',  'гривні',  'гривень', 1 ),
				),
				'RUB' => array(
					array( 'копійка', 'копійки', 'копійок', 1 ),
					array( 'рубль',   'рубля',   'рублів',  0 ),
				),
				'USD' => array(
					array( 'цент',  'цента',  'центів',  0 ),
					array( 'долар', 'долара', 'доларів', 0 ),
				),
				'EUR' => array(
					array( 'цент', 'цента', 'центів', 0 ),
					array( 'євро', 'євро',  'євро',   0 ),
				),
			),
			'units' => array(
				array(),
				array(),
				array( 'тисяча',   'тисячі',    'тисяч',      1 ),
				array( 'мільйон',  'мільйона',  'мільйонів',  0 ),
				array( 'мільярд',  'мільярда',  'мільярдів',  0 ),
				array( 'трильйон', 'трильйона', 'трильйонів', 0 ),
			),
			'zero'   => 'нуль',
			'signs'  => array( 'плюс', 'мінус' ),
			'digits' => array(
				// 1-9
				array(
					array( null, 'один', 'два', 'три', 'чотири', 'п`ять', 'шість', 'сім', 'вісім', 'дев`ять' ),
					array( null, 'одна', 'дві', 'три', 'чотири', 'п`ять', 'шість', 'сім', 'вісім', 'дев`ять' ),
				),
				// 10-19
				array( 'десять', 'одиннадцять', 'дванадцать', 'тринадцать', 'чотирнадцать', 'п`ятнадцать', 'шістнадцять', 'сімнадцять', 'вісімнадцять', 'дев`ятнадцать' ),
				// 20-99
				array( null, null, 'двадцять', 'тридцять', 'сорок', 'п`ятьдесят', 'шістдесят', 'сімідесят' , 'вісімдесят', 'дев`яносто' ),
				// 1xx-9xx
				array( null, 'сто', 'двісті', 'триста', 'чотиреста', 'п`ятьсот', 'шістсот', 'сімсот', 'вісімсот', 'дев`ятсот' ),
			),
		),
		'EN' => array(
			'currency' => array(
				'UAH' => array(
					array( 'kopeck', 'kopecks', 'kopecks', 1 ),
					array( 'grivna', 'grivnas', 'grivnas', 1 ),
				),
				'RUB' => array(
					array( 'kopeck', 'kopecks', 'kopecks', 1 ),
					array( 'rouble', 'roubles', 'roubles', 0 ),
				),
				'USD' => array(
					array( 'cent',   'cents',   'cents',   0 ),
					array( 'dollar', 'dollars', 'dollars', 0 ),
				),
				'EUR' => array(
					array( 'cent', 'cents', 'cents', 0 ),
					array( 'euro', 'euros', 'euros', 0 ),
				),
			),
			'units' => array(
				array(),
				array(),
				array( 'thousand', 'thousands', 'thousands', 1 ),
				array( 'million',  'millions',  'millions',  0 ),
				array( 'billion',  'milliards', 'billions',  0 ),
				array( 'trillion', 'trillions', 'trillions', 0 ),
			),
			'zero'   => 'zero',
			'signs'  => array( 'plus', 'minus' ),
			'digits' => array(
				// 1-9
				array(
					array( null, 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine' ),
					array( null, 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine' ),
				),
				// 10-19
				array( 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen' ),
				// 20-99
				array( null, null, 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety' ),
				// 1xx-9xx
				array( null, 'one hundred', 'two hundred', 'three hundred', 'four hundred', 'five hundred', 'six hundred', 'seven hundred', 'eight hundred', 'nine hundred' ),
			),
		),
	);
	protected $_sign_force        = false;
	protected $_cent_zero_force   = true;
	protected $_cent_number_force = true;

	function _init(){
		$_lang_id     = &$this->_lang_id;
		$_currency_id = &$this->_currency_id;
		$words        = &$this->words;
		// set as default: first lang, currency from words options
		$_lang_id     = current( array_keys( $words ) );
		$_currency_id = current( array_keys( $words[ $_lang_id ][ 'currency' ] ) );
	}

	function lang_id( $lang_id = null, $set = true ) {
		$_lang_id = &$this->_lang_id;
		if( empty( $lang_id ) ) { $result = $_lang_id; }
		else {
			$words  = &$this->words;
			$result = strtoupper( $lang_id );
			$result = isset( $words[ $result ] ) ? $result : $_lang_id;
			$set && $_lang_id = $result;
		}
		return( $result );
	}

	function currency_id( $currency_id = null, $set = true ) {
		$_lang_id     = $this->lang_id();
		$_currency_id = &$this->_currency_id;
		$currency     = &$this->words[ $_lang_id ][ 'currency' ];
		if( empty( $currency_id ) ) { $result = $_currency_id; }
		else {
			$result = strtoupper( $currency_id );
			$result = isset( $currency[ $result ] ) ? $result : $_currency_id;
			$set && $_currency_id = $result;
		}
		return( $result );
	}

	function sign( $force = null ){
		if( is_null( $force ) ) { $result = $this->_sign_force; }
		else {
			$result = (bool)$force;
			$this->_sign_force = $result;
		}
		return( $result );
	}

	function cent_number( $force = null ){
		if( is_null( $force ) ) { $result = $this->_cent_number_force; }
		else {
			$result = (bool)$force;
			$this->_cent_number_force = $result;
		}
		return( $result );
	}

	function cent_zero( $force = null ){
		if( is_null( $force ) ) { $result = $this->_cent_zero_force; }
		else {
			$result = (bool)$force;
			$this->_cent_zero_force = $result;
		}
		return( $result );
	}

	/**
	* Returns number to words for money (number spelling)
	*/
	function num2str( $float, $currency_id = null, $lang_id = null, $set = false ){
		$lang_id     = $this->lang_id(     $lang_id,     $set );
		$currency_id = $this->currency_id( $currency_id, $set );
		$words       = &$this->words[ $lang_id ];
		$digits = &$words[ 'digits' ];
		$signs  = &$words[ 'signs'  ];
		$units  = &$words[ 'units'  ];
			// prepare options
			$sign_force        = $this->sign();
			$cent_number_force = $this->cent_number();
			$cent_zero_force   = $this->cent_zero();
			$units[ 0 ] = &$words[ 'currency' ][ $currency_id ][ 0 ];
			$units[ 1 ] = &$words[ 'currency' ][ $currency_id ][ 1 ];
		// separate float on sign, integer, fractional
		$result = array();
		$number = trim( $float );
		// trim fractional by 2 digits
		$number_format = localeconv();
			$decimal_point = $number_format[ 'decimal_point' ];
			// $thousands_sep = $number_format[ 'thousands_sep' ];
			$thousands_sep = array( ' ', '.', ',', "'", '`' );
		list( $part1, $part2 ) = explode( $decimal_point, $number );
		$part2 = substr( $part2 . '00', 0, 2 );
		$number = str_replace( $thousands_sep, '', $part1 ) . '0' . $part2;
		// add sign word
		$sign_minus = substr( $number, 0, 1 ) == '-';
			// minus
			$sign_minus && $result[] = $signs[ 1 ];
			// plus
			!$sign_minus > 0 && $sign_force && $result[] = $signs[ 0 ];
			// remove sign
			$sign_minus && $number = substr( $number, 1 );
		// format number by 3 digits
		while( strlen( $number ) % 3 ) { $number = '0' . $number; }
		// processing number digits
		$digits_array = str_split( $number, 3 );
		$units_count = count( $digits_array ) - 1;
		foreach( $digits_array as $unit => $digits3 ) {
			$unit = $units_count - $unit;
			// skip zero if not dollar or cent
			if( (int)$digits3 == 0 && ( $unit > 1 || !( $cent_zero_force || $unit ) ) ) { continue; }
			if( $unit < 2 && !(int)$digits3 ) {
				// zero if dollar or cent
				$result[] = $cent_number_force && !$unit ? 0 : $words[ 'zero' ];
			} else {
				if( $cent_number_force && !$unit ) {
					// cent as int
					$result[] = (int)$digits3;
				} else {
					// separate by 1 digit
					list( $d3, $d2, $d1 ) = $digits3;
					// 1xx-9xx
					$d3 > 0  && $result[] = $digits[ 3 ][ $d3 ];
					// 20-99
					$d2 > 1  && $result[] = $digits[ 2 ][ $d2 ];
					// 10-19
					$d2 == 1 && $result[] = $digits[ 1 ][ $d1 ];
					// 1-9
					$d1 > 0 && $d2 != 1
						&& ( $gender = $units[ $unit ][ 3 ] ) > -1
						&& $result[] = $digits[ 0 ][ $gender ][ $d1 ];
				}
			}
			$result[] = $this->morph( $digits3, $units[ $unit ] );
		}
		$result = join( ' ', $result );
		return( $result );
	}

	/**
	* Bow word form
	*/
	protected function morph( $n, $unit ) {
		$n = abs( (int)$n ) % 100;
		if( $n > 10 && $n < 20 ) { return( $unit[2] ); }
		$n = $n % 10;
		if( $n > 1 && $n < 5 ) { return( $unit[1] ); }
		if( $n == 1 ) { return $unit[0]; }
		return( $unit[2] );
	}
}
