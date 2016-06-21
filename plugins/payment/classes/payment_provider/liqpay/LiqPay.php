<?php

class LiqPay {

	protected $_key_public;
	protected $_key_private;

	public function __construct( $key_public, $key_private ) {
		if( empty( $key_public ) ) {
			throw new InvalidArgumentException( 'key_public is empty' );
		}
		if( empty( $key_private ) ) {
			throw new InvalidArgumentException( 'key_private is empty' );
		}
		$this->_key_public  = $key_public;
		$this->_key_private = $key_private;
	}

	public function key( $name = 'public', $value = null ) {
		if( !in_array( $name, [ 'public', 'private' ] ) ) {
			return( null );
		}
		$_name  = '_key_' . $name;
		$_value = &$this->{ $_name };
		// set
		if( !empty( $value ) && is_string( $value ) ) { $_value = $value; }
		// get
		return( $_value );
	}

	public function api( $url, $options = [] ) {
		$url = 'https://www.liqpay.com/api/'.$url;
		$key_public  = $this->_key_public;
		$key_private = $this->_key_private;
		$data[ 'public_key' ] = $key_public;
		$data = json_encode( array_merge( $data, $options ) );
		$str = ''
			. $key_private
			. $data
			. $key_private
		;
		$signature = $this->str_to_sign( $str );
		$post = "data={$data}&signature={$signature}";
		// curl
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL           , $url  );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_POST          , 1     );
		curl_setopt( $ch, CURLOPT_POSTFIELDS    , $post );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1     );
		$response = curl_exec($ch);
		curl_close($ch);
		return( json_decode($response) );
	}

	public function signature( $options, $is_request = true ) {
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$data = [];
		array_push( $data
			, $this->key( 'private' )
			, $_amount
			, $_currency
			, $_public_key
			, $_order_id
			, $_type
			, $_description
		);
		if( $is_request ) {
			array_push( $data
				, $_result_url
				, $_server_url
			);
		} else {
			array_push( $data
				, $_status
				, $_transaction_id
				, $_sender_phone
			);
		}
		$str = implode( '', $data );
		$result = $this->str_to_sign( $str );
		return( $result );
	}


	public function str_to_sign($str) {
		$result = base64_encode(sha1($str,1));
		return( $result );
	}

}
