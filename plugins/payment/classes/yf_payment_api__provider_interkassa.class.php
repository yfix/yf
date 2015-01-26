<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_interkassa extends yf_payment_api__provider_remote {

	public $ENABLE = true;

	public $payment_api = null;
	public $api         = null;

	public $URL              = 'https://sci.interkassa.com/';
	public $KEY_PUBLIC       = null;  // Checkout ID, Идентификатор кассы
	public $KEY_PRIVATE      = null;  // secret key
	public $KEY_PRIVATE_TEST = null;  // secret key for test
	public $HASH_METHOD      = 'md5'; // signature hash method

	public $TEST_MODE   = null;

	public $_options_transform = array(
		'amount'       => 'ik_am',
		'currency'     => 'ik_cur',
		'title'        => 'ik_desc',
		'description'  => 'ik_x_desc',
		'operation_id' => 'ik_pm_no',
		'public_key'   => 'ik_co_id',
		'key_public'   => 'ik_co_id',
		'test'         => 'test_mode',
	);

	public $_options_transform_reverse = array(
		'ik_am'     => 'amount',
		'ik_cur'    => 'currency',
		'ik_desc'   => 'title',
		'ik_x_desc' => 'description',
		'ik_pm_no'  => 'operation_id',
		'ik_co_id'  => 'key_public',
	);

	public $currency_default = 'UAH';
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

	public $url_result = null;
	public $url_server = null;

	public function _init() {
		$this->payment_api = _class( 'payment_api' );
		// load api
		require_once( __DIR__ . '/payment_provider/interkassa/Interkassa.php' );
		$this->api = new Interkassa( $this->KEY_PUBLIC, $this->KEY_PRIVATE, $this->KEY_PRIVATE_TEST, $this->HASH_METHOD, $this->TEST_MODE );
		$this->url_result = url( '/api/payment/provider?name=interkassa&operation=response' );
		$this->url_server = url( '/api/payment/provider?name=interkassa&operation=response&server=true' );
		// parent
		parent::_init();
	}

	public function key( $name = 'public', $value = null ) {
		$value = $this->api->key( $name, $value );
		return( $value );
	}

	public function key_reset() {
		$this->api->key( 'public',       $this->KEY_PUBLIC       );
		$this->api->key( 'private',      $this->KEY_PRIVATE      );
		$this->api->key( 'private_test', $this->KEY_PRIVATE_TEST );
	}

	public function hash_method( $value = null ) {
		$value = $this->api->hash_method( $value );
		return( $value );
	}

	public function hash_method_reset() {
		$this->api->hash_method( $this->HASH_METHOD );
	}

	public function signature( $options ) {
		$result = $this->api->signature( $options );
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
			|| empty( $_[ 'ik_suc_uc' ] )
			|| empty( $_[ 'ik_pnd_u'  ] )
			|| empty( $_[ 'ik_fal_u'  ] )
		) {
			$url = $this->_url( $options );
			$_[ 'ik_suc_u' ] = $url . '&status=success';
			$_[ 'ik_suc_m' ] ='post';
			$_[ 'ik_pnd_u' ] = $url . '&status=pending';
			$_[ 'ik_pnd_m' ] ='post';
			$_[ 'ik_fal_u' ] = $url . '&status=fail';
			$_[ 'ik_fal_m' ] ='post';
			unset( $_[ 'url_result' ] );
		}
		if( !empty( $_[ 'url_server' ] ) || empty( $_[ 'ik_ia_u' ] ) ) {
			$url_server = $this->_url( $options, $is_server = true );
			$_[ 'ik_ia_u' ] = $url_server . '&status=interaction';
			$_[ 'ik_ia_m' ] ='post';
			unset( $_[ 'url_server' ] );
		}
		// default
		$_[ 'ik_am' ] = number_format( $_[ 'ik_am' ], 2, '.', '' );
		empty( $_[ 'ik_co_id'   ] ) && $_[ 'ik_co_id'   ] = $this->KEY_PUBLIC;
		if( empty( $_[ 'ik_am' ] ) || empty( $_[ 'ik_co_id' ] ) ) { $_ = null; }
		if( !empty( $this->TEST_MODE ) || !empty( $_[ 'test_mode' ] ) ) {
			unset( $_[ 'test' ] );
			$_[ 'ik_act'    ] = 'payway';
			$_[ 'ik_pw_via' ] = 'test_interkassa_test_xts';
		}
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
		$form_options[ 'ik_sign' ] = $signature;
		$url = &$this->URL;
		$result = array();
		if( $is_array ) {
			$result[ 'url' ] = $url;
		} else {
			$result[] = '<form id="_js_provider_interkassa_form" method="post" accept-charset="utf-8" action="' . $url . '" class="display: none;">';
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
		$is_server = !empty( $_GET[ 'server' ] );
		$result = null;
		// check operation
		$operation_id = (int)$_GET[ 'operation_id' ];
		// test data
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
		);
		$payment = $_POST;
		// response POST:
		$signature = $payment[ 'ik_sign' ];
		// check signature
		if( empty( $signature ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Пустая подпись',
			);
			return( $result );
		}
		$_signature = $this->api->signature( $payment );
		if( $signature != $_signature ) {
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
		// ok, fail, test, wait
		$state = $response[ 'ik_inv_st' ];
		// $state_test = $response[ 'ik_pw_via' ] == 'test_interkassa_test_xts';
		// if( $this->TEST_MODE && $state_test ) { $state = 'ok'; }
		// $payment_status_name = 'success';
		switch( $state ) {
			case 'success':
				$payment_status_name = 'success';
				$status_message      = 'Выполнено: ';
				break;
			case 'new':
			case 'waitAccept':
			case 'process':
				$payment_status_name = 'in_progress';
				$status_message      = 'Ожидание: ';
				break;
			case 'fail':
			case 'canceled':
			default:
				$payment_status_name = 'refused';
				$status_message      = 'Отклонено: ';
				break;
		}
		// update account, operation data
		$result = $this->_api_deposition( array(
			'provider_name'       => 'interkassa',
			'response'            => $response,
			'payment_status_name' => $payment_status_name,
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
		$default = $this->default;
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
