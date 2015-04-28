<?php

class yf_payment_api__currency {

	public $base        = 'UAH';
	public $main        = 'UNT';
	public $main_shadow = 'USD';

	public $rate = array(
		'buy'  => +3.5, // 3%
		'sell' => -2.5, // 2%
	);
	public $user_id         = null;

	public $payment_api = null;
	public $api         = null;

	public function _init() {
		$this->api         = _class( 'api' );
		$this->payment_api = _class( 'payment_api' );
	}

	public function load_from_NBU( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$api         = $this->api;
		$payment_api = $this->payment_api;
		// prepare request options
		// $url = 'http://resources.finance.ua/ru/public/currency-cash.json';
		// $request_options = array(
			// 'is_response_json' => true,
		// );
		// $result = $api->_request( $url, $post, $request_options );
		$url = 'http://www.bank.gov.ua/control/uk/curmetal/detail/currency?period=daily';
		$result = $api->_request( $url );
		list( $status, $response ) = $result;
		if( empty( $status ) ) { return( $result ); }
		require_php_lib( 'sf_dom_crawler' );
		$crawler = new \Symfony\Component\DomCrawler\Crawler( $response );
		$table = $crawler->filter( '.content > table' )->eq( 3 );
		$count = $table->count();
		if( $count < 1 ) { return( null ); }
		$table_data = array();
		$table->filter( 'tr' )->each( function( $node, $i ) use( &$table_data ) {
			$table_data[] = $node->filter( 'td' )->extract( array( '_text' ) );
		});
		$count = count( $table_data );
		if( $count < 1 ) { return( null ); }
		$data = array();
		$currencies = $payment_api->currencies;
		foreach( $table_data as $i => $item ) {
			$currency_id = $item[ 1 ];
			if( empty( $currencies[ $currency_id ] ) ) { continue; }
			$from_value = $item[ 2 ];
			$to_value   = $item[ 4 ];
			$data[] = array(
				'from'       => $currency_id,
				'to'         => 'UAH',
				'from_value' => $from_value,
				'to_value'   => $to_value,
			);
		}
		return( $data );
	}

	public function load_from_Privat24( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$api         = $this->api;
		$payment_api = $this->payment_api;
		// prepare request options
		$url = 'https://api.privatbank.ua/p24api/pubinfo?json&exchange&coursid=3';
		$request_options = array(
			'is_response_json' => true,
		);
		$result = $api->_request( $url, null, $request_options );
		list( $status, $response ) = $result;
		if( empty( $status ) ) { return( $result ); }
		return( $response );
	}

	public function load_from_CashExchange( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$api         = $this->api;
		$payment_api = $this->payment_api;
		// prepare request options
		$url = 'http://cashexchange.com.ua/XmlApi.ashx';
		$result = $api->_request( $url );
		list( $status, $response ) = $result;
		if( empty( $status ) ) { return( $result ); }
		require_php_lib( 'sf_dom_crawler' );
		$crawler = new \Symfony\Component\DomCrawler\Crawler( $response );
		$table = $crawler->filter( 'element' );
		$count = $table->count();
		if( $count < 1 ) { return( null ); }
		$currencies = $payment_api->currencies;
		$data = array();
		$table->each( function( $node, $i ) use( &$currencies, &$data ) {
			$currency_id = $node->filter( 'currency' )->text();
			if( empty( $currencies[ $currency_id ] ) ) { return; }
			$buy  = $node->filter( 'buy' )->text();
			$sale = $node->filter( 'sale' )->text();
			$data[] = array(
				'from'       => $currency_id,
				'to'         => 'UAH',
				'from_value' => 1,
				'to_value'   => $buy,
			);
			$data[] = array(
				'from'       => 'UAH',
				'to'         => $currency_id,
				'from_value' => $sale,
				'to_value'   => 1,
			);
		});
		return( $data );
	}

	public function reverse( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_currency_rate ) ) { return( null ); }
		// reverse
		$result = $_currency_rate;
		foreach( $_currency_rate as $i => $item ) {
			$from = $item[ 'from' ];
			$to   = $item[ 'to'   ];
			$result[] = array(
				'from'       => $item[ 'to'         ],
				'to'         => $item[ 'from'       ],
				'from_value' => $item[ 'to_value'   ],
				'to_value'   => $item[ 'from_value' ],
			);
		}
		return( $result );
	}

	public function correction( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_currency_rate ) ) { return( null ); }
		// currency
		empty( $_main ) && $_main = $this->main;
		// correction
		$k_buy  = 1 + $this->rate[ buy  ] / 100;
		$k_sell = 1 + $this->rate[ sell ] / 100;
		$result = $_currency_rate;
		foreach( $result as $index => &$item ) {
			$value = &$item[ 'to_value' ];
			if( $item[ 'from' ] == $_main ) {
				// buy
				$value = $value * $k_buy;
			} elseif( $item[ 'to' ] == $_main ) {
				// sell
				$value = $value * $k_sell;
			}
		}
		return( $result );
	}

	public function prepare( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_currency_rate ) ) { return( null ); }
		// currency
		empty( $_base        ) && $_base        = $this->base;
		empty( $_main        ) && $_main        = $this->main;
		empty( $_main_shadow ) && $_main_shadow = $this->main_shadow;
		// create index
		$index = array();
		$currencies = array();
		foreach( $_currency_rate as $i => &$item ) {
			$from = $item[ 'from' ];
			$to   = $item[ 'to'   ];
			$name = $from .'-'. $to;
			// relations
			$index[ $name ] = &$item;
			// available
			$currencies[ $from ] = $from;
			$currencies[ $to   ] = $to;
		}
		// find main relations
		$main_relations = $currencies;
		unset( $main_relations[ $_base ] );
		foreach( $main_relations as $id1 ) {
			foreach( $main_relations as $id2 ) {
				if( $id1 == $id2 ) { continue; }
				if(
					!empty( $index[ $id1 .'-'. $_base ] )
					&& !empty( $index[ $id2 .'-'. $_base ] )
				) {
					$from_value = $index[ $id1 .'-'. $_base ][ 'from_value' ] / $index[ $id2 .'-'. $_base ][ 'from_value' ];
					$to_value = $index[ $id1 .'-'. $_base ][ 'to_value' ] / $index[ $id2 .'-'. $_base ][ 'to_value' ];
					$_currency_rate[] = array(
						'from'       => $id1,
						'to'         => $id2,
						'from_value' => $from_value,
						'to_value'   => $to_value,
					);
				}
			}
		}
		// find main shadow
		$result = $_currency_rate;
		foreach( $_currency_rate as $i => &$item ) {
			$from = $item[ 'from' ];
			$to   = $item[ 'to'   ];
			// add base
			$base_id = null;
			$_main_shadow == $from && $base_id = 'from';
			$_main_shadow == $to   && $base_id = 'to';
			if( !empty( $base_id ) ) {
				$base_item = $item;
				$base_item[ $base_id ] = $_main;
				$result[] = $base_item;
			}
		}
		return( $result );
	}

	public function update( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_currency_rate ) ) { return( null ); }
		// var
		$payment_api = $this->payment_api;
		$sql_datetime = $payment_api->sql_datetime();
		// add date time
		foreach( $_currency_rate as $index => &$item ) {
			$item[ 'datetime' ] = &$sql_datetime;
		}
		// store
		$result = db()->table( 'payment_currency_rate' )->insert( $_currency_rate );
		return( $result );
	}

}
