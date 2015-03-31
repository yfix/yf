<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_webmoney extends yf_payment_api__provider_remote {

	public $URL         = 'https://merchant.webmoney.ru/lmi/payment.asp';
	public $KEY_PUBLIC  = null; // merchant
	public $KEY_PRIVATE = null; // pass
	public $HASH_METHOD = 'sha256'; // signature hash method: md5, sha256; sign - not support (need payee key on server - no good idea)

	public $IS_DEPOSITION = true;
	// public $IS_PAYMENT    = true;

	public $_api_request_timeout = 30;  // sec

	public $_options_transform = array(
		'amount'       => 'LMI_PAYMENT_AMOUNT',
		'title'        => 'LMI_PAYMENT_DESC',
		'operation_id' => 'LMI_PAYMENT_NO',
		'key_public'   => 'LMI_PAYEE_PURSE', // Z111111111111, E111111111111
	);

	public $_options_transform_reverse = array(
		'amt'         => 'amount',
		'ccy'         => 'currency',
		'details'     => 'title',
		'ext_details' => 'description',
		'order'       => 'operation_id',
		'merchant'    => 'public_key',
	);

	public $_status = array(
		'ok'   => 'success',
		'wait' => 'in_progress',
		'fail' => 'refused',
	);

	public $currency_default = 'USD';
	public $currency_allow = array(
/*
 */
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

	public $purse_by_currency = array(
		'USD' => array(
			'id'     => 'Z272631242756',
			'active' => true,
		),
		'EUR' => array(
			'id'     => 'E208376760367',
			'active' => true,
		),
		'UAH' => array(
			'id'     => 'U403573875538',
			'active' => true,
		),
		'RUB' => array(
			'id'     => 'R661456872042',
			'active' => true,
		),
	);

	// public $fee = 0.1; // 2%

	public $service_allow = array(
		'WebMoney',
	);

	public $url_result = null;
	public $url_server = null;

	public function _init() {
		$this->payment_api = _class( 'payment_api' );
		// load api
		require_once( __DIR__ . '/payment_provider/webmoney/WebMoney.php' );
		$this->api = new WebMoney( $this->KEY_PUBLIC, $this->KEY_PRIVATE, $this->HASH_METHOD );
		$this->url_result = url( '/api/payment/provider?name=webmoney&operation=response' );
		$this->url_server = url( '/api/payment/provider?name=webmoney&operation=response&server=true' );
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

	public function hash_method( $value = null ) {
		$value = $this->api->hash_method( $value );
		return( $value );
	}

	public function hash_method_reset() {
		$this->api->hash_method( $this->HASH_METHOD );
	}

	public function signature( $options, $is_request = true ) {
		$result = $this->api->signature( $options, $is_request );
		return( $result );
	}

	public function _description( $value ) {
		if( empty( $value ) ) { return( null ); }
		$result = base64_encode( $value );
		return( $result );
	}

	public function _purse_by_currency( $options ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$purse = &$this->purse_by_currency;
		if( empty( $purse[ $_currency ] )
			|| empty( $purse[ $_currency ][ 'active' ] ) ) {
			return( null );
		}
		$result = $this->purse_by_currency[ $_currency ][ 'id' ];
		return( $result );
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
		if( !empty( $_[ 'url_result' ] ) ) {
			$_[ 'LMI_RESULT_URL'    ] =  $_[ 'url_result' ];
			$_[ 'LMI_RESULT_METHOD' ] = 1;
			unset( $_[ 'url_result' ] );
		}
		if( !empty( $_[ 'url_server' ] ) ) {
			$_[ 'LMI_RESULT_URL'     ] = $_[ 'url_server' ] . '&status=result';
			$_[ 'LMI_SUCCESS_URL'    ] = $_[ 'url_server' ] . '&status=success';
			$_[ 'LMI_FAIL_URL'       ] = $_[ 'url_server' ] . '&status=fail';
			unset( $_[ 'url_server' ] );
		}
		$url = $this->_url( $options, $is_server = true );
		if( empty( $_[ 'LMI_RESULT_URL' ] ) ) {
			$_[ 'LMI_RESULT_URL' ] = $url . '&status=result';
		}
		if( empty( $_[ 'LMI_SUCCESS_URL'    ] ) ) {
			$_[ 'LMI_SUCCESS_URL'   ] = $url . '&status=success';
		}
		if( empty( $_[ 'LMI_FAIL_URL'    ] ) ) {
			$_[ 'LMI_FAIL_URL'   ] = $url . '&status=fail';
		}
		$_[ 'LMI_RESULT_METHOD'  ] = 1;
		$_[ 'LMI_SUCCESS_METHOD' ] = 1;
		$_[ 'LMI_FAIL_METHOD'    ] = 1;
		// default
		// amount
		$_[ 'LMI_PAYMENT_AMOUNT' ] = number_format( $_[ 'LMI_PAYMENT_AMOUNT' ], 2, '.', '' );
		// purse
		if( empty( $_[ 'LMI_PAYEE_PURSE' ] ) ) {
			$value = $this->_purse_by_currency( $options );
			if( empty( $value ) ) { return( null ); }
			$_[ 'LMI_PAYEE_PURSE' ] = $value;
			unset( $_[ 'currency' ] );
		}
		// description
		if( !empty( $_[ 'LMI_PAYMENT_DESC' ] ) ) {
			$_[ 'LMI_PAYMENT_DESC_BASE64' ] = $this->_description( $_[ 'LMI_PAYMENT_DESC' ] );
			unset( $_[ 'LMI_PAYMENT_DESC' ] );
		}
		unset( $_[ 'description' ] );
		if( empty( $_[ 'LMI_PAYEE_PURSE' ] )
			|| empty( $_[ 'LMI_PAYMENT_AMOUNT' ] )
			|| ( empty( $_[ 'LMI_PAYMENT_DESC' ] ) && empty( $_[ 'LMI_PAYMENT_DESC_BASE64' ] ) )
		) { $_ = null; }
		return( $_ );
	}

	public function _form( $data, $options = null ) {
		if( empty( $data ) ) { return( null ); }
		$_ = &$options;
		$is_array = (bool)$_[ 'is_array' ];
		$form_options = $this->_form_options( $data );
		$signature    = $this->signature( $form_options );
		if( empty( $signature ) ) { return( null ); }
		$form_options[ 'signature' ] = $signature;
		$url = $this->URL;
		$result = array();
		if( $is_array ) {
			$result[ 'url' ] = $url;
		} else {
			$result[] = '<form id="_js_provider_webmoney_form" method="post" accept-charset="windows-1251" action="' . $url . '" class="display: none;">';
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
		// response POST:
		$payment   = $_POST[ 'payment'   ];
		$signature = $_POST[ 'signature' ];
		// test data
		// $payment = 'amt=16.00&ccy=UAH&details=Поплнение счета (Приват 24)&ext_details=3#71#9#3#16&pay_way=webmoney&order=71&merchant=104702&state=test&date=171214180311&ref=test payment&payCountry=UA';
		// $signature = '585b0c173ec36300a5ff77f6cbd9f195492f0c0d';
		// check signature
		if( empty( $signature ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Пустая подпись',
			);
			return( $result );
		}
		$_signature = $this->signature( $payment, $is_request = false );
		if( $signature != $_signature ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверная подпись',
			);
			return( $result );
		}
		// update operation
		$response = $this->_response_parse( $payment );
		// check public key (merchant)
		$public_key = $response[ 'public_key' ];
		$_public_key = $this->KEY_PUBLIC;
		if( $public_key != $_public_key ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный ключ (merchant)',
			);
			return( $result );
		}
		// check status
		// ok, fail, test, wait
		$state = $response[ 'state' ];
		if( $this->TEST_MODE && $state == 'test' ) { $state = 'ok'; }
		list( $payment_status_name, $status_message ) = $this->_state( $state );
		// update account, operation data
		$result = $this->_api_deposition( array(
			'provider_name'       => 'webmoney',
			'response'            => $response,
			'payment_status_name' => $payment_status_name,
			'status_message'      => $status_message,
		));
		return( $result );
	}

	public function _response_parse( $response ) {
		$options = explode( '&', $response );
		$_ = array();
		foreach( (array)$options as $option ) {
			list( $key, $value ) = explode( '=', $option );
			$_[ $key ] = $value;
		}
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
		$payment_api    = $this->payment_api;
		$_              = $options;
		$data           = &$_[ 'data'           ];
		$options        = &$_[ 'options'        ];
		$operation_data = &$_[ 'operation_data' ];
		// prepare data
		$user_id        = (int)$operation_data[ 'user_id' ];
		$operation_id   = (int)$data[ 'operation_id' ];
		$account_id     = (int)$data[ 'account_id'   ];
		$provider_id    = (int)$data[ 'provider_id'  ];
		$amount         = $payment_api->_number_float( $data[ 'amount' ] );
		$currency_id    = $this->get_currency( $options );
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
			'status_message' => 'Поплнение через сервис: Приват24',
		);
		return( $result );
	}

	public function payment( $options ) {
		$payment_api    = $this->payment_api;
		$_              = $options;
		$data           = &$_[ 'data'           ];
		$options        = &$_[ 'options'        ];
		$operation_data = &$_[ 'operation_data' ];
		// prepare data
		$user_id        = (int)$operation_data[ 'user_id' ];
		$operation_id   = (int)$data[ 'operation_id' ];
		$account_id     = (int)$data[ 'account_id'   ];
		$provider_id    = (int)$data[ 'provider_id'  ];
		$amount         = $payment_api->_number_float( $data[ 'amount' ] );
		$currency_id    = $this->get_currency( $options );
		if( empty( $operation_id ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Не определен код операции',
			);
			return( $result );
		}
		// currency conversion
		$amount_currency = $payment_api->currency_conversion( array(
			'conversion_type' => 'sell',
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
		// prepare
		$method_id = $options[ 'method_id' ];
		$request   = array(
			'operation_id' => $operation_id,
			'amount'       => $amount_currency_total,
			// 'currency'     => 'UAH',
			'title'        => $options[ 'operation_title' ],
			'account'      => $options[ 'account' ],
		);
		$result = $this->api_request( $method_id, $request );
		ini_set( 'html_errors', 0 );
		var_dump( $options, $request, $result );
		exit;
		list( $status, $status_message ) = $result;
	}
}
