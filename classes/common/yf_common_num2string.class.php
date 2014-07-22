<?php

/**
*/
class yf_common_num2string {

	protected $_currency_id = null;
	// gender:  0 - male; 1 - female;
	public $currency = array(
		'UAH' => array( 'гривна', 'гривни',  'гривен',   1 ),
		'RUB' => array( 'рубль',  'рубля',   'рублей',   0 ),
		'USD' => array( 'доллар', 'доллара', 'долларов', 0 ),
	);
	public $unit = array(
		array( 'копейка',  'копейки',  'копеек',     1 ),
		array( 'гривна',   'гривни',   'гривен',     1 ),
		array( 'тысяча',   'тысячи',   'тысяч',      1 ),
		array( 'миллион',  'миллиона', 'миллионов',  0 ),
		array( 'миллиард', 'милиарда', 'миллиардов', 0 ),
	);

	function _init(){
		$this->_currency_id = current( array_keys( $this->currency ) );
	}

	function currency_id( $currency_id = null ){
		if( empty( $currency_id ) ) { $result = $this->_currency_id; }
		else {
			$result = strtoupper( $currency_id );
			$result = isset( $this->currency[ $result ] ) ? $result : $this->_currency_id;
			$this->_currency_id = $result;
		}
		return( $result );
	}

	/**
	* Returns the sum in words (for money)
	*/
// TODO: translation (RU, UK, EN)
// TODO: currencies (UAH, RUB USD, EUR)
	function num2str( $num, $currency_id = null ){
		$num = (float)$num;
		$nul = 'ноль';
		$ten = array(
			array( '', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять' ),
			array( '', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять' ),
		);
		$a20     = array( 'десять','одиннадцать','двенадцать','тринадцать','четырнадцать' ,'пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать' );
		$tens    = array( 2 => 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят' , 'восемьдесят', 'девяносто' );
		$hundred = array( '', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот' );
		$currency_id = $this->currency_id( $currency_id );
		$unit = $this->unit; $unit[ 1 ] = &$this->currency[ $currency_id ];
		// separate float on integer and fractional
		$number_format = localeconv();
		$decimal_point = $number_format[ 'decimal_point' ];
		list( $part1, $part2 ) = explode( $decimal_point, sprintf( '%015.2f', $num ) );
		$out = array();
		if( $part1 > 0 ) {
			// separate by 3 digits
			foreach( str_split( $part1, 3 ) as $uk => $v ) {
				if( !(int)$v ) { continue; }
				// get unit
				$uk = sizeof($unit) - $uk - 1;
				$gender = $unit[$uk][3];
				// separate by 1 digit
				list( $i1, $i2, $i3 ) = array_map( 'intval', str_split( $v, 1 ) );
				// 1xx-9xx
				$out[] = $hundred[$i1];
				if ($i2 > 1) {
					// 20-99
					$out[] = $tens[$i2].' '.$ten[$gender][$i3];
				} else {
					// 10-19 | 1-9
					$out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3];
				}
				// units without rub & kop
				if( $uk > 1 ) {
					$out[] = $this->morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
				}
			}
		} else {
			$out[] = $nul;
		}
		// part1 - integer
		$out[] = $this->morph( $part1, $unit[1][0], $unit[1][1], $unit[1][2] );
		// part2 - fractional
		$out[] = (int)$part2.' '.$this->morph($part2, $unit[0][0], $unit[0][1], $unit[0][2]);
		$result = trim( preg_replace( '/ {2,}/', ' ', join( ' ', $out ) ) );
		return( $result );
	}

	/**
	* Bow word form
	*/
	function morph($n, $f1, $f2, $f5) {
		$n = abs( (int)$n ) % 100;
		if( $n > 10 && $n < 20 ) { return( $f5 ); }
		$n = $n % 10;
		if( $n > 1 && $n < 5 ) { return( $f2 ); }
		if( $n == 1 ) { return $f1; }
		return( $f5 );
	}
}
