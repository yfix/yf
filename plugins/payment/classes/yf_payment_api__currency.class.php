<?php

if( !function_exists( 'array_replace_recursive' ) ) {
	trigger_error( 'Not exists function "array_replace_recursive ( PHP 5 >= 5.3.0 )"', E_USER_ERROR );
}

class yf_payment_api__currency {

	public $user_id_default = null;
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

}
