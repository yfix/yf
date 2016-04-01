<?php

class yf_payment_api__currency {

	public $base        = 'UAH';
	public $main        = 'UNT';
	public $main_shadow = 'USD';

	public $rate = array(
		'buy'  => +3.5, // 3%
		'sell' => -2.5, // 2%
	);
	public $user_id     = null;

	public $provider_default = 'nbu';
	public $provider = array(
		// country banks: 1-9
		'nbu'  => array(
			'id'    => 1,
			'code'  => 'nbu',
			'short' => 'НБУ',
			'full'  => 'Национальный банк Украины',
			'base'  => 'UAH',
			'method' => array(
				'json' => true,
				'xml'  => true,
				'html' => true,
			),
		),
		'cbr'  => array(
			'id'    => 2,
			'code'  => 'cbr',
			'short' => 'ЦБ РФ',
			'base'  => 'RUB',
			'full'  => 'Центральный банк Российской Федерации',
			'method' => array(
				'xml' => true,
			),
		),
		// other banks: 101-...
		'p24'  => array(
			'id'    => 101,
			'code'  => 'p24',
			'short' => 'Приват24',
			'full'  => 'ПриватБанк',
			'base'  => 'UAH',
			'method' => array(
				'json' => true,
			),
		),
		// other site: 201-...
		'cashex'  => array(
			'id'    => 201,
			'code'  => 'cashex',
			'short' => 'CashExchange',
			'full'  => 'CashExchange.com.ua',
			'base'  => 'UAH',
			'method' => array(
				'xml'  => true,
				'json' => true,
			),
		),
	);

	public $index = array();

	public $provider_allow = array(
		'nbu' => true,
		'cbr' => true,
	);

	public $cache = array();

	public $payment_api = null;
	public $api         = null;

	public function _init() {
		$this->api         = _class( 'api' );
		$this->payment_api = _class( 'payment_api' );
		// index
		$index    = &$this->index;
		$provider = &$this->provider;
		foreach( $provider as $key => &$item ) {
			$id = $item[ 'id' ];
			$index[ 'provider' ][ 'id' ][ $id ] = &$item;
		}
	}

	public function load( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		@$provider = &$this->provider[ strtolower( $_provider ) ];
		if( ! $provider ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Загрузка курса валют: провайдер не найден',
			);
			return( $result );
		}
		$provider_id = $provider[ 'id' ];
		// method
		$method_id = null;
		@$method = &$provider[ 'method' ];
		if( @$_method ) {
			if( !@$method[ $_method ] ) {
				$result = array(
					'status'         => false,
					'status_message' => 'Загрузка курса валют: метод не найден',
				);
				return( $result );
			}
			$method_id = $_method;
		} else {
			if( is_array( $method ) ) {
				foreach( $method as $id => $active ) {
					if( $active ) { $method_id = $id; break; }
				}
			}
		}
		// date
		$date = 0;
		if( @$_date ) {
			if( !is_numeric( $_date ) ) {
				$date = @strtotime( $_date );
				if( is_int( $date ) ) {
					$options[ 'date' ] = $date;
				}
			}
		}
		// DEBUG
		// $options[ 'is_debug' ] = true;
		// cache
		@$result = $this->cache[ __FUNCTION__ ][ $provider_id ][ $method_id ][ $date ];
		if( !@$_is_force_load && $result ) { return( $result ); }
		// load
		$code = &$provider[ 'code' ];
		$load = __FUNCTION__ .'__'. $code;
			$method_id && $load .= '_'. $method_id;
		$status = method_exists( $this, $load );
		if( !$status ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Загрузка курса валют: обработчик не найден',
			);
			return( $result );
		}
		$result = $this->{ $load }( $options );
		// cache
		$this->cache[ __FUNCTION__ ][ $provider_id ][ $method_id ][ $date ] = $result;
		return( $result );
	}

	// Национальный банк Украины: html
	public function load__nbu_html( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$api         = $this->api;
		$payment_api = $this->payment_api;
		// prepare request options
		$url = 'http://www.bank.gov.ua/control/uk/curmetal/detail/currency?period=daily';
		// date
		if( @$_date ) {
			$tpl = 'd.m.Y';
			$url = 'http://www.bank.gov.ua/control/uk/curmetal/currency/search?formType=searchFormDate&time_step=daily&date='. date( $tpl, $_date );
		}
		// request
		$request_options = array(
			'is_response_raw' => true,
		);
		@$_request_options && $request_options = array_replace_recursive(
			$request_options, $_request_options
		);
		$result = $api->_request( $url, null, $request_options );
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

	// Национальный банк Украины: json
	public function load__nbu_json( $options = null ) {
		$result = $this->load__nbu( $options );
		return( $result );
	}

	// Национальный банк Украины: xml
	public function load__nbu_xml( $options = null ) {
		$result = $this->load__nbu( $options );
		return( $result );
	}

	// Национальный банк Украины: json, xml
	public function load__nbu( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$api         = $this->api;
		$payment_api = $this->payment_api;
		// base url
		$url = 'http://bank.gov.ua/NBUStatService/v1/statdirectory/exchange';
		$uri = array();
		// method
		switch( @$_method ) {
			case 'xml':  $uri[] = 'xml';  break;
			default:
			case 'json': $uri[] = 'json'; break;
		}
		// date
		if( @$_date ) {
			$tpl = 'Ymd';
			$uri[] = 'date='. date( $tpl, $_date );
		}
		// url
		$uri = implode( '&', $uri );
		$uri && $url .= '?'. $uri;
		// prepare request options
		$request_options = array(
			'is_redirect' => true,
		);
		@$_request_options && $request_options = array_replace_recursive(
			$request_options, $_request_options
		);
		$result = $api->_request( $url, null, $request_options );
		@list( $status, $response ) = $result;
		if( empty( $status ) ) { return( null ); }
		// prepare
		$count = count( $response );
		if( $count < 1 ) { return( null ); }
		$data = array();
		$currencies = $payment_api->currencies;
		foreach( $response as $i => $item ) {
			if( @$_method == 'xml' ) { $item = (array)$item; }
			$currency_id = $item[ 'cc' ];
			if( empty( $currencies[ $currency_id ] ) ) { continue; }
			$from_value = 100;
			$to_value   = $item[ 'rate' ] * $from_value;
			$data[] = array(
				'from'       => $currency_id,
				'to'         => 'UAH',
				'from_value' => $from_value,
				'to_value'   => $to_value,
			);
		}
		return( $data );
	}

	// Центральный банк Российской Федерации: xml
	public function load__cbr_xml( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$api         = $this->api;
		$payment_api = $this->payment_api;
		// prepare request options
		$url = 'http://www.cbr.ru/scripts/XML_daily.asp';
		// date
		if( @$_date ) {
			$tpl = 'd/m/Y';
			$url = $url .'?date_req='. date( $tpl, $_date );
		}
		$request_options = array(
			'is_redirect'     => true,
			'is_response_xml' => true,
		);
		@$_request_options && $request_options = array_replace_recursive(
			$request_options, $_request_options
		);
		$result = $api->_request( $url, null, $request_options );
		list( $status, $response ) = $result;
		if( empty( $status ) ) { return( null ); }
		// prepare
		$count = count( $response );
		if( $count < 1 ) { return( null ); }
		$data = array();
		$currencies = $payment_api->currencies;
		foreach( $response as $i => $item ) {
			$currency_id = (string)$item->CharCode;
			if( empty( $currencies[ $currency_id ] ) ) { continue; }
			$from_value = (string)$item->Nominal;
			$to_value   = (string)$item->Value;
				$from_value = $payment_api->_number_float( $from_value, 6, null, null, ',' );
				$to_value   = $payment_api->_number_float( $to_value, 6, null, null, ',' );
			$data[] = array(
				'from'       => $currency_id,
				'to'         => 'RUB',
				'from_value' => $from_value,
				'to_value'   => $to_value,
			);
		}
		return( $data );
	}

	// ПриватБанк: json
	public function load__p24_json( $options = null ) {
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
		@$_request_options && $request_options = array_replace_recursive(
			$request_options, $_request_options
		);
		$result = $api->_request( $url, null, $request_options );
		list( $status, $response ) = $result;
		if( empty( $status ) ) { return( null ); }
		// prepare
		$count = count( $response );
		if( $count < 1 ) { return( null ); }
		$data = array();
		$currencies = $payment_api->currencies;
		foreach( $response as $i => $item ) {
			$currency_id = $item[ 'ccy' ];
			if( empty( $currencies[ $currency_id ] ) ) { continue; }
			$from_value = 100;
			$to_value_buy  = $item[ 'buy'  ] * $from_value;
			$to_value_sale = $item[ 'sale' ] * $from_value;
			$data[] = array(
				'from'       => $currency_id,
				'to'         => 'UAH',
				'from_value' => $from_value,
				'to_value'   => $to_value_sale,
			);
			$data[] = array(
				'from'       => 'UAH',
				'to'         => $currency_id,
				'from_value' => $to_value_buy,
				'to_value'   => $from_value,
			);
		}
		return( $data );
	}

	// CashExchange.com.ua: xml
	public function load__cashex_xml( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$api         = $this->api;
		$payment_api = $this->payment_api;
		// prepare request options
		$url = 'http://api.cashex.com.ua/XmlApi.ashx';
		$request_options = array(
			'is_redirect'     => true,
			'is_response_raw' => true,
		);
		@$_request_options && $request_options = array_replace_recursive(
			$request_options, $_request_options
		);
		$result = $api->_request( $url, null, $request_options );
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

	// CashExchange.com.ua: xml
	public function load__cashex_json( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$api         = $this->api;
		$payment_api = $this->payment_api;
		// prepare request options
		$url = 'http://api.cashex.com.ua/api/v1/exchange';
		$request_options = array(
			'is_redirect'     => true,
		);
		@$_request_options && $request_options = array_replace_recursive(
			$request_options, $_request_options
		);
		$result = $api->_request( $url, null, $request_options );
		list( $status, $response ) = $result;
		if( empty( $status ) ) { return( null ); }
		// prepare
		$count = count( $response );
		if( $count < 1 ) { return( null ); }
		$data = array();
		$currencies = $payment_api->currencies;
		foreach( $response as $i => $item ) {
			$currency_id = $item[ 'Currency' ];
			if( empty( $currencies[ $currency_id ] ) ) { continue; }
			$from_value = 100;
			$to_value_buy  = $item[ 'Buy'  ] * $from_value;
			$to_value_sale = $item[ 'Sale' ] * $from_value;
			$data[] = array(
				'from'       => $currency_id,
				'to'         => 'UAH',
				'from_value' => $from_value,
				'to_value'   => $to_value_sale,
			);
			$data[] = array(
				'from'       => 'UAH',
				'to'         => $currency_id,
				'from_value' => $to_value_buy,
				'to_value'   => $from_value,
			);
		}
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
		$k_buy  = 1 + $this->rate[ 'buy'  ] / 100;
		$k_sell = 1 + $this->rate[ 'sell' ] / 100;
		$result = $_currency_rate;
		foreach( $result as $index => &$item ) {
			if( $item[ 'from' ] == $_main ) {
				// buy
				$value = &$item[ 'from_value' ];
				$value = $value * $k_buy;
			} elseif( $item[ 'to' ] == $_main ) {
				// sell
				$value = &$item[ 'to_value' ];
				$value = $value * $k_sell;
			}
		}
		return( $result );
	}

	public function prepare( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_currency_rate ) ) { return( null ); }
		// base
		if( !@$_base && @$_provider ) {
			$_base = $this->provider[ $_provider ][ 'base' ];
		}
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
		if( !@$_provider || !@$_currency_rate ) { return( null ); }
		$provider_id = @$this->provider[ $_provider ][ 'id' ];
		if( !@$provider_id ) { return( null ); }
		// var
		$payment_api = $this->payment_api;
		$sql_datetime = $payment_api->sql_datetime();
		// add datetime, value
		$decimals = 6;
		foreach( $_currency_rate as $index => &$item ) {
			!@$item[ 'provider_id' ] && $item[ 'provider_id' ] = $provider_id;
			$item[ 'datetime' ] = &$sql_datetime;
			$value = &$item[ 'from_value' ];
				$value = $payment_api->_number_mysql( $value, $decimals );
			$value = &$item[ 'to_value' ];
				$value = $payment_api->_number_mysql( $value, $decimals );
		}
		if( !@$_currency_rate ) { return( null ); }
		// store
		$result = db()->table( 'payment_currency_rate' )->insert( $_currency_rate
			// , array( 'sql' => true, )
		);
		return( $result );
	}

	public function provider( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$result = null;
		$provider = &$this->provider;
		$index    = &$this->index;
		// default
		$_provider = strtolower( @$_provider ?: $this->provider_default );
		// provider_id
		if( @$_provider_id ) {
			$_provider_id = (int)$_provider_id;
			if( !is_array( $index[ 'provider' ][ 'id' ][ $_provider_id ] ) ) { return( $result ); }
			$result  = $index[ 'provider' ][ 'id' ][ $_provider_id ];
		}
		// allow
		if( !@$_is_force && !@$this->provider_allow[ $_provider ] ) { return( $result ); }
		!$result && $result = $provider[ $_provider ];
		return( $result );
	}

	public function rates( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$_type = @$_type == 'sell' ? 'sell' : 'buy';
		if( !@$_type || !@$_currency_id ) { return( null ); }
		$payment_api = $this->payment_api;
		// provider
		$provider_item = $this->provider( $options );
		if( !$provider_item ) { return( null ); }
		$provider    = $provider_item[ 'code' ];
		$provider_id = $provider_item[ 'id'   ];
		// cache
		$currency_rate = $this->cache[ __FUNCTION__ ][ $provider_id ][ $_type ][ $_currency_id ];
		if( $_[ 'force' ] || !$currency_rate ) {
			list( $currency_id, $currency ) = $payment_api->get_currency__by_id(array( 'currency_id' => $_currency_id ));
			if( @$_type == 'buy' ) {
				$target = 'to';
				$source = 'from';
			} else {
				$target = 'from';
				$source = 'to';
			}
			$key_value = $target . '_value';
			$key_rate  = $source . '_value';
			$sql = db()->table( 'payment_currency_rate' )
				->select( '*', 'max( datetime ) as latest' )
				->where( 'provider_id', '=', $provider_id )
				->where( $target, '=', $_currency_id )
				->group_by( $source )
				->sql();
			$result = db()->table( 'payment_currency_rate as l' )
				->select( 'l.*' )
				->inner_join( "( $sql ) as r", 'l.datetime = r.latest AND l.from = r.from AND l.to = r.to', true )
				->where( 'l.provider_id', '=', $provider_id )
				->where( "l.$target", '=', $_currency_id )
				->order_by( 'datetime', 'DESC' )
				->get_all()
				// ->sql()
			;
			if( empty( $result ) ) {
				$currency_id_default = &$payment_api->currency_id_default;
				foreach( $payment_api->currencies as $key => $item ) {
					if( $currency_id_default == $key ) { continue; }
					$currency_rate[ $key ] = array(
						'value' => 1.0,
						'rate'  => 1.0,
					);
				}
			} else {
				foreach( $result as $id => $item ) {
					$key   = $item[ $source    ];
					$value = $item[ $key_value ];
					$rate  = $item[ $key_rate  ];
					$currency_rate[ $key ] = array(
						'value' => (float)$value,
						'rate'  => (float)$rate,
					);
				}
			}
		}
		// get from db
		return( $currency_rate );
	}

	public function rate( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$result = null;
		$_type = @$_type == 'sell' ? 'sell' : 'buy';
		if( !@$_from && !@$_currency_id ) { return( $result ); }
		// provider
		$provider = $this->provider( $options );
		if( !$provider ) { return( $result ); }
		// currency base
		$from = @$_from ?: $_currency_id;
		$to   = @$_to   ?: $provider[ 'base' ];
		$o = array(
			'type'        => $_type,
			'currency_id' => $from,
			'provider'    => $_provider,
		);
		$rates = $this->rates( $o );
		$r = &$rates[ $to ];
		if( !@$r ) { return( $result ); }
		$result = $r[ 'rate' ] / $r[ 'value' ];
		// round
/*
		if( !@$_round ) {
			$payment_api = $this->payment_api;
			list( $currency_id, $currency ) = $payment_api->get_currency__by_id( array(
				'currency_id' => $to,
			));
			if( @!$currency_id  ) { return( $result ); }
			$result = $payment_api->_number_float( $result, $currency[ 'minor_units' ] );
		}
 */
		return( $result );
	}

	public function load_rate( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$result = null;
		if( !@$_from && !@$_currency_id ) { return( $result ); }
		// provider
		$provider = $this->provider( $options );
		if( !$provider ) { return( $result ); }
		!$_provider && $_provider = $provider[ 'code' ];
		// currency base, main
		!@$_main && $_main = $this->main;
		$base = $provider[ 'base' ];
		$from = @$_from ?: $_currency_id;
		$to   = @$_to   ?: $base;
		// request
		$o = array(
			'provider' => $_provider,
			'method'   => @$_method,
			'date'     => @$_date,
			'is_force' => @$_is_force,
		);
		// request
		$data = $this->load( $o );
		if( !$data ) { return( $result ); }
		// processing
		if( $from == $base ) {
			$data = $this->reverse( array( 'provider' => $_provider, 'currency_rate' => $data, ));
		}
		if( ( $from != $base && $to != $base ) || ( $from == $_main || $to == $_main ) ) {
			$data = $this->prepare( array( 'provider' => $_provider, 'currency_rate' => $data, ));
		}
		if( $from == $_main || $to == $_main ) {
			$data = $this->correction( array( 'provider' => $_provider, 'currency_rate' => $data, ));
		}
		foreach( (array)$data as $idx => $item ) {
			if( $item[ 'from' ] == $from && $item[ 'to' ] == $to ) {
				// if( $base == $to ) {
					$result = $item[ 'to_value'   ] / $item[ 'from_value' ];
				// } else {
					// $result = $item[ 'from_value' ] / $item[ 'to_value'   ];
				// }
				break;
			}
		}
		return( $result );
	}

	public function conversion( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		if( !@$_amount ) { return( null ); }
		$result = $this->rate( $options );
		if( is_null( $result ) ) { return( null ); }
		$result *= $_amount;
		// if( !@$_round ) {
			$payment_api = $this->payment_api;
			$to = @$_to ?: $_currency_id;
			list( $currency_id, $currency ) = $payment_api->get_currency__by_id( array(
				'currency_id' => $to,
			));
			if( @!$currency_id  ) { return( $result ); }
			$result = $payment_api->_number_float( $result, $currency[ 'minor_units' ] );
		// }
		return( $result );
	}

}
