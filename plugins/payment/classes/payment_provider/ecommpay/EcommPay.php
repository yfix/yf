<?php

// https://ecommpay.com/

class EcommPay {

	private $_key_public       = null;
	private $_key_private      = null;

	private $options_level_max = 2;

	public function __construct( $key_public, $key_private ) {
		if( empty( $key_public ) ) {
			throw new InvalidArgumentException( 'key_public (site_id) is empty' );
		}
		if( empty( $key_private ) ) {
			throw new InvalidArgumentException( 'key_private (salt) is empty' );
		}
		$this->_key_public       = $key_public;
		$this->_key_private      = $key_private;
	}

	public function key( $name = 'public', $value = null ) {
		if( !in_array( $name, array( 'public', 'private', 'private_test' ) ) ) {
			return( null );
		}
		$_name  = '_key_' . $name;
		$_value = &$this->{ $_name };
		// set
		if( !empty( $value ) && is_string( $value ) ) { $_value = $value; }
		// get
		return( $_value );
	}

	public function options_to_str( array $options, $level = 1 ) {
		if( $level > $this->options_level_max ) { return( null ); }
		$result = array();
		ksort( $options );
		foreach( $options as $key => $value ) {
			$_value = null;
			switch( true ) {
				case is_bool( $value ):
					$_value = $value ? '1' : '0';
					break;
				case is_scalar( $value ) && !is_resource($value):
					$_value = (string)$value;
					break;
				case is_array( $value ):
					$_value = $this->options_to_str( $value, $level + 1 );
					break;
				case is_null( $value ):
				default:
					break;
			}
			if( !isset( $_value ) ) { continue; }
			$result[ $key ] = $key . ':' . $_value;
		}
		$result = implode( ';', $result );
		return( $result );
	}

	public function signature( array $options, $is_request = true ) {
		$_ = &$options;
		$request = array();
		// compile string
		unset( $_[ 'signature' ] );
		$str = $this->options_to_str( $_ );
		// add salt
		$key = $this->_key_private;
		$str = $str . ';' . $key;
		// create signature
		$result = $this->str_to_sign( $str );
		return( $result );
	}

	public function str_to_sign( $str ) {
		$result = sha1( $str );
		return( $result );
	}

}
