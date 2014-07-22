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
	public $units = array(
		array( 'копейка',  'копейки',  'копеек',     1 ),
		array( 'гривна',   'гривни',   'гривен',     1 ),
		array( 'тысяча',   'тысячи',   'тысяч',      1 ),
		array( 'миллион',  'миллиона', 'миллионов',  0 ),
		array( 'миллиард', 'милиарда', 'миллиардов', 0 ),
	);
	public $digits = array(
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
		$digits = $this->digits;
		$sing = array( 'плюс', 'минус' ); $sing_force = false;
		$currency_id = $this->currency_id( $currency_id );
		$units = $this->units; $units[ 1 ] = &$this->currency[ $currency_id ];
		// separate float on integer and fractional
		$number_format = localeconv();
		$decimal_point = $number_format[ 'decimal_point' ];
		list( $part1, $part2 ) = explode( $decimal_point, sprintf( '%015.2f', $num ) );
		$out = array();
		$part1 < 0 && $out[] = $sing[ 1 ];
		$part1 > 0 && $sing_force && $out[] = $sing[ 0 ];
		var_dump( $part1, $out );
		// part1 - integer
		if( abs( $part1 ) > 0 ) {
			// separate by 3 digits
			foreach( str_split( $part1, 3 ) as $unit => $digits3 ) {
				if( !(int)$digits3 ) { continue; }
				// get unit
				$unit = sizeof($units) - $unit - 1;
				$gender = $units[$unit][3];
				// separate by 1 digit
				list( $d3, $d2, $d1 ) = $digits3;
				// 1xx-9xx
				$d3 > 0  && $out[] = $digits[ 3 ][ $d3 ];
				// 20-99
				$d2 > 1  && $out[] = $digits[ 2 ][ $d2 ];
				// 10-19
				$d2 == 1 && $out[] = $digits[ 1 ][ $d1 ];
				// 1-9
				$d1 > 0 && $d2 != 1 && $out[] = $digits[ 0 ][ $gender ][ $d1 ];
				$out[] = $this->morph( $digits3, $units[ $unit ] );
			}
		} else {
			$out[] = $nul;
			$out[] = $this->morph( $part1, $units[1] );
		}
		// part2 - fractional
		$out[] = (int)$part2.' '.$this->morph($part2, $units[0] );
		$result = join( ' ', $out );
		var_dump( $num, $result );
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
