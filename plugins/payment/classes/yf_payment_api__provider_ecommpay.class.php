<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_ecommpay extends yf_payment_api__provider_remote {

	public $URL              = 'https://terminal.ecommpay.com/';
	public $URL_TEST         = 'https://terminal-sandbox.ecommpay.com/';
	public $KEY_PUBLIC       = null;  // site id
	public $KEY_PRIVATE      = null;  // salt

	public $IS_DEPOSITION = true;
	// public $IS_PAYMENT    = true;

	public $_options_transform = array(
		'title'        => 'description',
		'operation_id' => 'external_id',
		'public_key'   => 'site_id',
		'key_public'   => 'site_id',
		'test'         => 'test_mode',
	);

	public $_options_transform_reverse = array(
		'description' => 'title',
		'external_id' => 'operation_id',
		'site_id'     => 'key_public',
	);

	public $_status = array(
		'success'    => 'success',
		'new'        => 'in_progress',
		'waitAccept' => 'in_progress',
		'process'    => 'in_progress',
		'fail'       => 'refused',
		'canceled'   => 'refused',
	);

	public $currency_default = 'RUB';
	public $currency_allow = array(
		'USD' => array(
			'currency_id' => 'USD',
			'active'      => true,
		),
		'EUR' => array(
			'currency_id' => 'EUR',
			'active'      => true,
		),
		'UAH' => array(
			'currency_id' => 'UAH',
			'active'      => true,
		),
		'RUB' => array(
			'currency_id' => 'RUB',
			'active'      => true,
		),
	);

	// public $fee = 5; // 5%

	public $service_allow = array(
		'Ecommpay',
	);

	public $url_result = null;
	public $url_server = null;

	public function _init() {
		// load api
		require_once( __DIR__ . '/payment_provider/ecommpay/EcommPay.php' );
		$this->api = new EcommPay( $this->KEY_PUBLIC, $this->KEY_PRIVATE );
		$this->url_result = url( '/api/payment/provider?name=ecommpay&operation=response' );
		$this->url_server = url( '/api/payment/provider?name=ecommpay&operation=response&server=true' );
		// parent
		parent::_init();
	}

	public function key( $name = 'public', $value = null ) {
		$value = $this->api->key( $name, $value );
		return( $value );
	}

	public function key_reset() {
		$this->key( 'public',  $this->KEY_PUBLIC  );
		$this->key( 'private', $this->KEY_PRIVATE );
	}

	public function signature( $options, $request = true ) {
		$result = $this->api->signature( $options, $request );
		return( $result );
	}

	public function _form_options( $options ) {
		$_ = $options;
		// transform
		foreach ((array)$this->_options_transform as $from => $to ) {
			if( isset( $_[ $from ] ) ) {
				$_[ $to ] = $_[ $from ];
				unset( $_[ $from ] );
			}
		}
		// url
		if( !empty( $_[ 'url_result' ] )
			|| empty( $_[ 'success_url'  ] )
		) {
			$url = $this->_url( $options );
			$_[ 'success_url' ] = $url . '&status=success';
			$_[ 'callback_method' ] = 4;
			unset( $_[ 'url_result' ] );
		}
		if( !empty( $_[ 'url_result' ] )
			|| empty( $_[ 'decline_url' ] )
		) {
			$url = $this->_url( $options );
			$_[ 'decline_url' ] = $url . '&status=fail';
			$_[ 'callback_method' ] = 4;
			unset( $_[ 'url_result' ] );
		}
		unset( $_[ 'url_server' ] );
		// default
		// todo: get currency minor unit from payment_api
		$payment_api = $this->payment_api;
		list( $_currency_id, $currency ) = $payment_api->get_currency__by_id( array(
			'currency_id' => $_[ 'currency' ],
		));
		if( empty( $_currency_id ) ) {
			// $result = array(
				// 'status'         => false,
				// 'status_message' => 'Неизвестная валюта',
			// );
			return( null );
		}
		$units = pow( 10, $currency[ 'minor_units' ] );
		$_[ 'amount' ] = (int)( $_[ 'amount' ] * $units );
		// site id
		empty( $_[ 'site_id' ] ) && $_[ 'site_id' ] = $this->KEY_PUBLIC;
		if( empty( $_[ 'amount' ] ) || empty( $_[ 'site_id' ] ) ) { $_ = null; }
		return( $_ );
	}

	public function _url( $options, $is_server = false ) {
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( $is_server ) {
			$url = $_url_server ?: $this->url_server;
		} else {
			$url = $_url_result ?: $this->url_result;
		}
		$result = $url . '&operation_id=' . $_operation_id;
		return( $result );
	}

	public function _form( $data, $options = null ) {
		if( empty( $data ) ) { return( null ); }
		$_ = &$options;
		$is_array = (bool)$_[ 'is_array' ];
		$form_options = $this->_form_options( $data );
		$signature    = $this->api->signature( $form_options );
		if( empty( $signature ) ) { return( null ); }
		$form_options[ 'signature' ] = $signature;
		if( !empty( $this->TEST_MODE ) || !empty( $_[ 'test_mode' ] ) ) {
			$url = &$this->URL_TEST;
		} else {
			$url = &$this->URL;
		}
		$result = array();
		if( $is_array ) {
			$result[ 'url' ] = $url;
		} else {
			$result[] = '<form id="_js_provider_ecommpay_form" method="post" accept-charset="utf-8" enctype="application/x-www-form-urlencoded" action="' . $url . '" class="display: none;">';
		}
		foreach ((array)$form_options as $key => $value ) {
			if( $is_array ) {
				$result[ 'data' ][ $key ] = $value;
			} else {
				$result[] = sprintf( '<input type="hidden" name="%s" value="%s" />', $key, $value );
			}
		}
		if( !$is_array ) {
			$result[] = '</form>';
			$result = implode( PHP_EOL, $result );
		}
		return( $result );
	}

	public function _api_response() {
		$payment_api = $this->payment_api;
		$test_mode = &$this->TEST_MODE;
		$is_server = !empty( $_GET[ 'server' ] );
		$result = null;
		// check operation
		$operation_id = (int)$_GET[ 'operation_id' ];
		/* // test data
		$this->key( 'private',      'xXceiJgnFURU0lq9' );
		$this->key( 'private_test', 'AxlrteZIreEpMddf' );
		$this->hash_method( 'sha256' );
		$_POST = array (
			'ik_co_id' => '54be5909bf4efc7f6b8ab8f5',
			'ik_co_prs_id' => '203295131974',
			'ik_inv_id' => '33274174',
			'ik_inv_st' => 'success',
			'ik_inv_crt' => '2015-01-23 11:20:09',
			'ik_inv_prc' => '2015-01-23 11:20:09',
			'ik_trn_id' => '',
			'ik_pm_no' => 'ID_4233',
			'ik_pw_via' => 'test_interkassa_test_xts',
			'ik_am' => '100.00',
			'ik_co_rfn' => '100.0000',
			'ik_ps_price' => '103.00',
			'ik_cur' => 'USD',
			'ik_desc' => 'Пополнение счета (Interkassa)',
			'ik_x_user_id' => '3',
			'_ik_x_user_id' => '3',
			'ik_sign' => 'mgNlOcdt6ydxAZZvAPEZYo7PZRoWnM/zvlgk2pdZe20=',
		); // */
/*
	&status=success
		string '$_GET' (length=5)
		array (size=4)
			'test_mode' => string '1' (length=1)
			'status' => string 'success' (length=7)
			'object' => string 'payment_test' (length=12)
			'action' => string 'provider' (length=8)
		string '$_POST' (length=6)
		array (size=12)
			'ik_co_id' => string '54be5909bf4efc7f6b8ab8f5' (length=24)
			'ik_inv_id' => string '33226688' (length=8)
			'ik_inv_st' => string 'success' (length=7)
			'ik_inv_crt' => string '2015-01-21 13:14:26' (length=19)
			'ik_inv_prc' => string '2015-01-21 13:14:26' (length=19)
			'ik_pm_no' => string 'ID_4233' (length=7)
			'ik_pw_via' => string 'test_interkassa_test_xts' (length=24)
			'ik_am' => string '100.00' (length=6)
			// Checkout Refund - Сумма зачисления на счет кассы.
			'ik_co_rfn' => string '97.0000' (length=7)
			// Paysystem Price - Сумма платежа в платежной системе.
			'ik_ps_price' => string '100.00' (length=6)
			'ik_cur' => string 'USD' (length=3)
			'ik_desc' => string 'Пополнение счета (Interkassa)' (length=44)
			'ik_x_user_id' => string '3' (length=1)
	&status=fail
		string '$_GET' (length=5)
		array (size=4)
			'status' => string 'fail' (length=4)
		string '$_POST' (length=6)
		array (size=12)
			'ik_inv_st' => string 'canceled' (length=8)
			'ik_inv_prc' => string '' (length=0)
	&status=pending
		string '$_GET' (length=5)
		array (size=4)
			'status' => string 'pending' (length=7)
		string '$_POST' (length=6)
		array (size=12)
			'ik_inv_st' => string 'waitAccept' (length=10)
			'ik_inv_prc' => string '' (length=0)
 */
		$payment = $_POST;
		// response POST:
		$signature = $payment[ 'ik_sign' ];
		// check signature
		if( !$test_mode && empty( $signature ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Пустая подпись',
			);
			return( $result );
		}
		$_signature = $this->signature( $payment, false );
		if( !( $test_mode && empty( $signature ) ) && $signature != $_signature ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверная подпись',
			);
			return( $result );
		}
		// update operation
		$response = $this->_response_parse( $payment );
		// check public key (ik_co_id)
		$key_public = $response[ 'key_public' ];
		$_key_public = $this->key( 'public' );
		if( $key_public != $_key_public ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный ключ (ik_co_id)',
			);
			return( $result );
		}
		// check status
		$state = $response[ 'ik_inv_st' ];
		list( $payment_status_name, $status_message ) = $this->_state( $state );
		// test
		// $response[ 'operation_id' ] = '3304';
		// update account, operation data
		$result = $this->_api_deposition( array(
			'provider_name'       => 'interkassa',
			'response'            => $response,
			'payment_status_name' => $payment_status_name,
			'status_message'      => $status_message,
		));
		return( $result );
	}

	public function _response_parse( $response ) {
		$_ = $response;
		// transform
		foreach( (array)$this->_options_transform_reverse as $from => $to  ) {
			if( isset( $_[ $from ] ) ) {
				$_[ $to ] = $_[ $from ];
				unset( $_[ $from ] );
			}
		}
		return( $_ );
	}

	public function get_currency( $options ) {
		$_       = &$options;
		$api     = $this->api;
		$allow   = &$this->currency_allow;
		$default = $this->currency_default;
		// chech: allow currency_id
		$id     = $_[ 'currency_id' ];
		$result = $default;
		if( isset( $allow[ $id ] ) && $allow[ $id ][ 'active' ] ) {
			$result = $id;
		}
		return( $result );
	}

	public function deposition( $options ) {
		$payment_api = $this->payment_api;
		$_              = $options;
		$data           = &$_[ 'data'           ];
		$options        = &$_[ 'options'        ];
		$operation_data = &$_[ 'operation_data' ];
		// prepare data
		$user_id      = (int)$operation_data[ 'user_id' ];
		$operation_id = (int)$data[ 'operation_id' ];
		$account_id   = (int)$data[ 'account_id'   ];
		$provider_id  = (int)$data[ 'provider_id'  ];
		$amount       = $payment_api->_number_float( $data[ 'amount' ] );
		$currency_id  = $this->get_currency( $options );
		if( empty( $operation_id ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Не определен код операции',
			);
			return( $result );
		}
		// currency conversion
		$amount_currency = $payment_api->currency_conversion( array(
			'conversion_type' => 'buy',
			'currency_id'     => $currency_id,
			'amount'          => $amount,
		));
		if( empty( $amount_currency ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Невозможно произвести конвертацию валют',
			);
			return( $result );
		}
		// fee
		$fee = $this->fee;
		$amount_currency_total = $payment_api->fee( $amount_currency, $fee );
		// prepare request form
		$form_data  = array(
			'user_id'               => $user_id,
			'operation_id'          => $operation_id,
			'account_id'            => $account_id,
			'provider_id'           => $provider_id,
			'currency_id'           => $currency_id,
			'fee'                   => $fee,
			'amount'                => $amount,
			'amount_currency'       => $amount_currency,
			'amount_currency_total' => $amount_currency_total,
		);
		// $description = implode( '#', array_values( $description ) );
		$form_options = array(
			'amount'       => $amount_currency_total,
			'currency'     => $currency_id,
			'operation_id' => $operation_id,
			'title'        => $data[ 'title' ],
			'description'  => $operation_id,
			// 'description'  => $description,
			// 'result_url'   => $result_url,
			// 'server_url'   => $server_url,
		);
		$form = $this->_form( $form_options );
		// $form = $this->_form( $form_options, array( 'is_array' => true, ) );
		// save options
		$operation_options = array(
			'request' => array( array(
				'data'     => $form_data,
				'form'     => $form_options,
				'datetime' => $operation_data[ 'sql_datetime' ],
			))
		);
		$result = $payment_api->operation_update( array(
			'operation_id' => $operation_id,
			'options'      => $operation_options,
		));
		if( !$result[ 'status' ] ) { return( $result ); }
		$result = array(
			'form'           => $form,
			'status'         => true,
			'status_message' => 'Поплнение через сервис: Интеркасса',
		);
		return( $result );
	}

}
