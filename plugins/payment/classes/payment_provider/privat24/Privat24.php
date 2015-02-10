<?php

class Privat24 {

	protected $_currency_allow = array( 'EUR', 'UAH', 'USD' );
	protected $_signature_allow = array(
		'amt',         // amount
		'ccy',         // currency
		'details',
		'ext_details',
		'pay_way',     // privat24
		'order',       // order_id
		'merchant',
		// 'return_url',
		// 'server_url',
	);
	protected $_key_public  = null;
	protected $_key_private = null;

	public function __construct( $key_public, $key_private ) {
		if( empty( $key_public ) ) {
			throw new InvalidArgumentException( 'key_public (merchant) is empty' );
		}
		if( empty( $key_private ) ) {
			throw new InvalidArgumentException( 'key_private is empty' );
		}
		$this->_key_public  = $key_public;
		$this->_key_private = $key_private;
	}

	public function key( $name = 'public', $value = null ) {
		if( !in_array( $name, array( 'public', 'private' ) ) ) {
			return( null );
		}
		$_name  = '_key_' . $name;
		$_value = &$this->{ $_name };
		// set
		if( !empty( $value ) && is_string( $value ) ) { $_value = $value; }
		// get
		return( $_value );
	}

	public function signature( $options, $is_request = true ) {
		$_ = &$options;
		if( $is_request ) {
			$request = array();
			foreach ((array)$this->_signature_allow as $key ) {
				$request[] = $key . '=' . $_[ $key ];
			}
			$request = implode( '&', $request );
			$_ = $request;
		}
		$result = $this->str_to_sign( $_ );
		return( $result );
	}

	public function str_to_sign( $str ) {
		$signature = sha1( md5( $str . $this->_key_private ) );
		return( $signature );
	}

}
