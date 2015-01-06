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
	private $_public_key  = null;
	private $_private_key = null;

	public function __construct( $public_key, $private_key ) {
		if( empty( $public_key ) ) {
			throw new InvalidArgumentException( 'public_key (merchant) is empty' );
		}
		if( empty( $private_key ) ) {
			throw new InvalidArgumentException( 'private_key is empty' );
		}
		$this->_public_key  = $public_key;
		$this->_private_key = $private_key;
	}


	/**
	 * Call API
	 *
	 * @param string $url
	 * @param array $params
	 *
	 * @return string
	 */
/*
	public function api($url, $params = array()) {
		$url = 'https://www.liqpay.com/api/'.$url;

		$public_key = $this->_public_key;
		$private_key = $this->_private_key;
		$data = json_encode(array_merge(compact('public_key'), $params));
		$signature = base64_encode(sha1($private_key.$data.$private_key, 1));
		$postfields = "data={$data}&signature={$signature}";

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$postfields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

		$server_output = curl_exec($ch);

		curl_close($ch);

		return json_decode($server_output);
	}
 */

	/**
	 * cnb_signature
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	public function cnb_signature( $options ) {
		$_ = &$options;
		$request = array();
		foreach ((array)$this->_signature_allow as $key ) {
			$request[] = $key . '=' . $_[ $key ];
		}
		$request = implode( '&', $request );
		$signature = $this->string_to_sign( $request );
		return( $signature );
	}

	/**
	 * string_to_sign
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public function string_to_sign( $str ) {
		$signature = sha1( md5( $str . $this->_private_key ) );
		return( $signature );
	}

}
