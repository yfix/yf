<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_webmoney extends yf_payment_api__provider_remote {

	public $URL         = 'https://merchant.webmoney.ru/lmi/payment.asp';
	public $KEY_PUBLIC  = null; // purse_id
	public $KEY_PRIVATE = null; // secret key
	public $HASH_METHOD = 'sha256'; // signature hash method: md5, sha256; sign - not support (need payee key on server - no good idea)

	// public $fee = 0.5; // PC = 0.5%, AC = 2%

	public $URL_API = 'https://w3s.wmtransfer.com/asp/%method';
	public $API_REDIRECT_URI = null;

	public $method_allow = array(
		'order' => array(
			'payin' => array(
				'webmoney',
			),
			'payout' => array(
				'webmoney_p2p',
			),
		),
		'payin' => array(
			'webmoney' => array(
				'title' => 'WebMoney',
				'icon'  => 'webmoney',
				// 'fee'         => 2, // 0.1%
				'currency' => array(
					'USD' => array(
						'currency_id' => 'USD',
						'active'      => true,
					),
				),
			),
		),
		'api' => array(
			// XML inteface: X2
			'payout_p2p' => array(
				'uri' => array(
					'%method' => 'XMLTransCert.asp',
				),
			),
			// XML inteface: X9
			'balance' => array(
				'uri' => array(
					'%method' => 'XMLPursesCert.asp',
				),
			),
		),
		'payout' => array(
			'webmoney_p2p' => array(
				'title' => 'WebMoney',
				'icon'  => 'webmoney',
				'amount' => array(
					'min' => 5,
					'max' => 200,
				),
				// 'is_fee' => true,
				'fee' => array(
					'out' => array(
						'rt'  => 0.5,
						// 'fix' => 10,
					),
				),
				'is_currency' => true,
				'currency' => array(
					'USD' => array(
						'currency_id' => 'USD',
						'active'      => true,
					),
				),
				'request_option' => array(
					'pattern_id' => 'p2p',
				),
				'request_field' => array(
					'pattern_id',
					'to',
					'amount',
					'label',
					'comment',
					'message',
				),
				'field' => array(
					'to',
				),
				'order' => array(
					'to',
				),
				'option' => array(
					'to' => 'Номер счета',
				),
				'option_validation_js' => array(
					'to' => array(
						'type'      => 'text',
						'required'  => true,
						'minlength' => 11,
						'maxlength' => 26,
						// 'pattern'   => '^\d+$',
						'pattern'   => '^41001[0-9]{4,19}(?:[1-9]{2})$',
					),
				),
				'option_validation' => array(
					'to' => 'required|length[11,26]|regex:~^41001[0-9]{4,19}(?:[1-9]{2})$~',
				),
				'option_validation_message' => array(
					'to' => 'обязательное поле от 11 до 26 цифр',
				),
			),
		),
	);

	public $_api_transform = array(
		'title'           => 'message',
	);

	public $_api_transform_reverse = array(
		'status'          => 'state',
		'contract_amount' => 'amount',
		'payment_id'      => 'external_id',
	);

	public $_options_transform = array(
		'amount'       => 'LMI_PAYMENT_AMOUNT',
		'title'        => 'LMI_PAYMENT_DESC',
		'operation_id' => 'LMI_PAYMENT_NO',
		'key_public'   => 'LMI_PAYEE_PURSE', // Z111111111111, E111111111111
	);

	public $_options_transform_reverse = array(
		'LMI_PAYMENT_AMOUNT' => 'amount',
		'LMI_PAYMENT_DESC'   => 'title',
		'LMI_PAYMENT_NO'     => 'operation_id',
		'LMI_PAYEE_PURSE'    => 'key_public',
		'LMI_MODE'           => 'test',
		'LMI_HASH'           => 'signature',
	);

	public $_status = array(
		'success' => 'success',
		'wait'    => 'in_progress',
		'fail'    => 'refused',
	);

	public $currency_default = 'USD';
	public $currency_allow = array(
		/*
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
		*/
	);

	public $purse = null;
	public $purse_by_currency = array(
		/*
		'USD' => array(
			'id'     => 'Zxxxxxxxxxxxx',
			'active' => true,
		),
		'EUR' => array(
			'id'     => 'Exxxxxxxxxxxx',
			'active' => true,
		),
		'UAH' => array(
			'id'     => 'Uxxxxxxxxxxxx',
			'active' => true,
		),
		'RUB' => array(
			'id'     => 'Rxxxxxxxxxxxx',
			'active' => true,
		),
		*/
	);

	// public $fee = 0.1; // 2%

	public $ip_filter = array(
		'212.118.48.0/24',
		'212.158.173.0/24',
		'91.200.28.0/24',
		'91.227.52.0/24',
	);

	public $service_allow = array(
		'WebMoney',
	);

	public $url_result = null;
	public $url_server = null;

	public function _init() {
		if( !$this->ENABLE ) { return( null ); }
		// default
		$purse = $this->_purse_by_currency(array( 'is_key' => false ));
		if( $purse[ 'status' ] === false ) { throw new InvalidArgumentException( $purse[ 'status_message' ] ); }
		// load api
		require_once( __DIR__ . '/payment_provider/webmoney/WebMoney.php' );
		$this->api = new WebMoney( $purse[ 'id' ], $purse[ 'key' ], $purse[ 'hash_method' ] );
		$this->url_result = url_user( '/api/payment/provider?name=webmoney&operation=response' );
		$this->url_server = url_user( '/api/payment/provider?name=webmoney&operation=response&server=true' );
		// DEBUG
		$is_test = $this->is_test();
		if( $is_test && @$_GET[ 'result_test' ] ) {
			$result_test = $_GET[ 'result_test' ] == '1' || $_GET[ 'result_test' ] == 'true' ? 1: 0;
			// test: 0 - success; 1 - fail.
			// $_[ 'LMI_SIM_MODE' ] = 0;
			// $_[ 'LMI_SIM_MODE' ] = 1;
			$_SESSION[ 'payin' ][ 'result_test' ] = $result_test;
		}
		// parent
		parent::_init();
	}

	public function key( $name = 'public', $value = null ) {
		if( !$this->ENABLE || !@$this->api ) { return( null ); }
		$value = $this->api->key( $name, $value );
		return( $value );
	}

	public function key_reset() {
		if( !$this->ENABLE ) { return( null ); }
		$this->key( 'public',  $this->KEY_PUBLIC  );
		$this->key( 'private', $this->KEY_PRIVATE );
	}

	public function hash_method( $value = null ) {
		if( !$this->ENABLE || !@$this->api ) { return( null ); }
		$value = $this->api->hash_method( $value );
		return( $value );
	}

	public function hash_method_reset() {
		if( !$this->ENABLE ) { return( null ); }
		$this->api->hash_method( $this->HASH_METHOD );
	}

	public function signature( $options, $is_request = true ) {
		if( !$this->ENABLE ) { return( null ); }
		$result = $this->api->signature( $options, $is_request );
		return( $result );
	}

	public function _description( $value ) {
		if( !$this->ENABLE ) { return( null ); }
		if( empty( $value ) ) { return( null ); }
		$result = base64_encode( $value );
		return( $result );
	}

	public function _purse_by_currency( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// currency
		$currency_id = @$_currency_id ?: @$_currency ?: @$this->currency_default;
		if( ! @$currency_id ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неизвестный код валюты',
			);
			return( $result );
		}
		// purse
		$purse = &$this->purse_by_currency;
		if( ! @$purse[ $currency_id ] ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неизвестная валюта',
			);
			return( $result );
		}
		if( ! @$purse[ $currency_id ][ 'active' ] ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Валюта не активна',
			);
			return( $result );
		}
		$result = $purse[ $currency_id ];
		$this->purse = $result;
		// hash method
		if( ! @$result[ 'hash_method' ] ) {
			$result[ 'hash_method' ] = $this->HASH_METHOD;
		}
		// setup
		if( @$_is_key === false ) {
			$this->key( 'public',  $result[ 'id'  ] );
			$this->key( 'private', $result[ 'key' ] );
			$this->hash_method( $result[ 'hash_method' ] );
		}
		return( $result );
	}

	public function _url( $options, $is_server = false ) {
		if( !$this->ENABLE ) { return( null ); }
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
		if( !$this->ENABLE ) { return( null ); }
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
			$_[ 'LMI_RESULT_URL'    ] = $_[ 'url_result' ] . '&status=result';
			$_[ 'LMI_SUCCESS_URL'   ] = $_[ 'url_result' ] . '&status=success';
			$_[ 'LMI_FAIL_URL'      ] = $_[ 'url_result' ] . '&status=fail';
			$_[ 'LMI_RESULT_METHOD' ] = 1;
			unset( $_[ 'url_result' ] );
		}
		if( !empty( $_[ 'url_server' ] ) ) {
			$_[ 'LMI_RESULT_URL'     ] = $_[ 'url_server' ] . '&status=result';
			unset( $_[ 'url_server' ] );
		}
		$url        = $this->_url( $options, $is_server = false );
		$url_server = $this->_url( $options, $is_server = true  );
		if( empty( $_[ 'LMI_RESULT_URL' ] ) ) {
			$_[ 'LMI_RESULT_URL' ] = $url_server . '&status=result';
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
			$purse = $this->_purse_by_currency( $options );
			if( $purse[ 'status' ] === false ) { return( null ); }
			$_[ 'LMI_PAYEE_PURSE' ] = $purse[ 'id' ];
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
		// DEBUG
		$is_test = $this->is_test();
		if( $is_test && @$_SESSION[ 'payin' ][ 'result_test' ] ) {
			$result_test = $_SESSION[ 'payin' ][ 'result_test' ];
			// test: 0 - success; 1 - fail.
			// $_[ 'LMI_SIM_MODE' ] = 0;
			// $_[ 'LMI_SIM_MODE' ] = 1;
			$_[ 'LMI_SIM_MODE' ] = $result_test;
		}
		return( $_ );
	}

	public function _form( $data, $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		if( empty( $data ) ) { return( null ); }
		$_ = &$options;
		$is_array = (bool)$_[ 'is_array' ];
		$form_options = $this->_form_options( $data );
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

	public function _get_operation( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// get operation options
		$operation_id = (int)$_operation_id;
		if( empty( $operation_id ) ) { return( null ); }
		$payment_api = $this->payment_api;
		$operation = $payment_api->operation( array(
			'operation_id' => $operation_id,
		));
		if( empty( $operation ) ) { return( null ); }
		return( $operation );
	}

	public function __api_response__check( $operation_id, $response ) {
		if( !$this->ENABLE ) { return( null ); }
		$payment_api = $this->payment_api;
		// check response options
		$operation = $this->_get_operation( $response );
		if( !is_array( $operation[ 'options' ][ 'request' ][0][ 'data' ] ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный ответ: отсутствуют данные операции',
			);
			// DUMP
			$payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		$request = @$operation[ 'options' ][ 'request' ][0][ 'data' ];
		// check operation_id
		if( @$request[ 'operation_id' ] != @$response[ 'operation_id' ] ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный ответ: operation_id',
			);
			// DUMP
			$payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		// check amount
		if( @$request[ 'amount' ] != @$response[ 'amount' ] ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный ответ: amount',
			);
			// DUMP
			$payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		// check payee purse
		$purse = $this->_purse_by_currency( $request );
		if( @$response[ 'key_public' ] != @$purse[ 'id' ] ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный ответ: payee purse',
			);
			// DUMP
			$payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		return( true );
	}

	public function __api_response__prerequest( $operation_id, $response ) {
		if( !$this->ENABLE ) { return( null ); }
		$payment_api = $this->payment_api;
		// check response
		$response = $this->_response_parse( $response );
		$result = $this->__api_response__check( $operation_id, $response );
		if( $result !== true ) { return( $result ); }
		// update operation
		$sql_datetime = $payment_api->sql_datetime();
		$operation_options = array(
			'response' => array( array(
				'data'     => $response,
				'datetime' => $sql_datetime,
			)),
		);
		$operation_update_data = array(
			'operation_id'    => $operation_id,
			'options'         => $operation_options,
		);
		$payment_api->operation_update( $operation_update_data );
		if( !$result[ 'status' ] ) { return( $result ); }
		return( true );
	}

	public function __api_response__result( $operation_id, $response ) {
		if( !$this->ENABLE ) { return( null ); }
		$payment_api = $this->payment_api;
		// check response
		$_response = $this->_response_parse( $response );
		$result = $this->__api_response__check( $operation_id, $_response );
		if( $result !== true ) { return( $result ); }
		// check signature
		$signature  = $_response[ 'signature' ];
		$is_signature = isset( $signature );
		if( !$is_signature ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Пустая подпись',
			);
			// DUMP
			$payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		$_signature = $this->signature( $response, $is_request = false );
		if( $signature != $_signature ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверная подпись',
			);
			// DUMP
			$payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		// save options
		$sql_datetime = $payment_api->sql_datetime();
		$operation_options = array(
			'response' => array( array(
				'data'     => $_response,
				'datetime' => $sql_datetime,
			))
		);
		$result = $payment_api->operation_update( array(
			'operation_id' => $operation_id,
			'options'      => $operation_options,
		));
		if( !$result[ 'status' ] ) { return( $result ); }
		$result = array(
			'status'         => true,
			'status_message' => 'Поплнение через сервис: WebMoney',
		);
		return( $result );
	}

	public function __api_response__success( $operation_id, $response ) {
		if( !$this->ENABLE ) { return( null ); }
		$payment_api = $this->payment_api;
		if( empty( $response[ 'LMI_SYS_INVS_NO' ] )
			|| empty( $response[ 'LMI_SYS_TRANS_NO' ] )
			|| empty( $response[ 'LMI_SYS_TRANS_DATE' ] )
		) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный ответ: отсутствуют данные транзакции',
			);
			// DUMP
			$payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		// check response options
		$_response = $this->_response_parse( $response );
		$operation = $this->_get_operation( $_response );
		if( !is_array( $operation[ 'options' ][ 'response' ][0][ 'data' ] ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный ответ: отсутствуют данные операции',
			);
			// DUMP
			$payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		$r = end( $operation[ 'options' ][ 'response' ] );
		$_data = @$r[ 'data' ];
		// check transaction data
		if(
			$_data[ 'LMI_SYS_INVS_NO' ] != $response[ 'LMI_SYS_INVS_NO' ]
			|| $_data[ 'LMI_SYS_TRANS_NO' ] != $response[ 'LMI_SYS_TRANS_NO' ]
			|| $_data[ 'LMI_SYS_TRANS_DATE' ] != $response[ 'LMI_SYS_TRANS_DATE' ]
			|| empty( $_data[ 'signature' ] )
		) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный ответ: данные операции не совпадают',
			);
			// DUMP
			$payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		return( true );
	}

	public function __api_response__fail( $response ) {
		if( !$this->ENABLE ) { return( null ); }
		$payment_api = $this->payment_api;
		$result = array(
			'status'         => false,
			'status_message' => 'Отказано в транзакции',
		);
		// DUMP
		$payment_api->dump(array( 'var' => $result ));
		return( true );
	}

	public function _api_response( $request ) {
		if( !$this->ENABLE ) { return( null ); }
		$payment_api = $this->payment_api;
		$is_server = !empty( $_GET[ 'server' ] );
		$result = null;
		// check operation
		$operation_id = (int)$_GET[ 'operation_id' ];
		// START DUMP
		$payment_api->dump( array( 'name' => 'WebMoney', 'operation_id' => (int)$operation_id ));
		// check ip
		if( $is_server ) {
			$ip_allow = $this->_check_ip();
			if( $ip_allow === false ) {
				// DUMP
				$payment_api->dump( array( 'var' => 'ip not allow' ));
				return( null );
			}
		}
		// response
		$response = @$_POST;
		// prerequest is empty
		if( ! @$response ) {
			// DUMP
			$payment_api->dump(array( 'var' => array( 'PREREQUEST' => 'is empty' )));
			$result = array( 'is_raw' => true, 'OK' );
			return( $result );
		}
		// prerequest
		if( @$response[ 'LMI_PREREQUEST' ] ) {
			// DUMP
			$payment_api->dump(array( 'var' => array( 'PREREQUEST' => 'YES' )));
			$result = $this->__api_response__prerequest( $operation_id, $response );
			$state = ( $result === true ? 'YES' : 'NO' );
			$result = array( 'is_raw' => true, $state );
			return( $result );
		}
		$_response = $this->_response_parse( $response );
		// check operation_id
		if( $operation_id != (int)$_response[ 'operation_id' ] ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный код операции',
			);
			// DUMP
			$payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		// check status
		$is_test = null;
		isset( $_response[ 'test' ] ) && $is_test =  (int)$_response[ 'test' ] == 1 ? true : false;
		switch( $_GET[ 'status' ] ) {
			case 'result':
				$state = 'wait';
				$result = $this->__api_response__result( $operation_id, $response );
				return( $result );
				break;
			case 'success':
				$result = $this->__api_response__success( $operation_id, $response );
				if( $result !== true ) { return( $result ); }
				$state = 'success';
				break;
			case 'fail':
			default:
				$result = $this->__api_response__fail( $operation_id, $response );
				if( $result !== true ) { return( $result ); }
				$state = 'fail';
				break;
		}
		list( $status_name, $status_message ) = $this->_state( $state );
		// update account, operation data
		$result = $this->_api_deposition( array(
			'provider_name'  => 'webmoney',
			'response'       => $_response,
			'status_name'    => $status_name,
			'status_message' => $status_message,
		));
		return( $result );
	}

	public function _response_parse( $response ) {
		if( !$this->ENABLE ) { return( null ); }
		$_ = $response;
		// transform
		foreach( (array)$this->_options_transform_reverse as $from => $to  ) {
			if( isset( $_[ $from ] ) ) {
				$_[ $to ] = $_[ $from ];
				unset( $_[ $from ] );
			}
		}
		if( !empty( $_[ 'title' ] ) ) {
			$_[ 'title' ] = iconv( 'windows-1251', 'utf-8', $_[ 'title' ] );
		}
		return( $_ );
	}

	public function api_request( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		if( is_string( $options ) ) { $_method_id = $options; }
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// method
		$method = $this->api_method( array(
			'type'      => 'api',
			'method_id' => @$_method_id,
		));
		if( empty( $method ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Метод запроса не найден',
			);
			return( $result );
		}
		// method handler
		if( !empty( $method[ 'is_handler' ] ) ) {
			$handler = 'api_request__'. $method[ 'is_handler' ];
			if( !method_exists( $this, $handler ) ) {
				$result = array(
					'status'         => false,
					'status_message' => 'Опработчик метода запроса не найден',
				);
				return( $result );
			}
			$result = $this->{ $handler }( $options );
			return( $result );
		}
		// request
		$request = array();
		is_array( @$_option ) && $request = $_option;
// DEBUG
// var_dump( $url, $request, $request_option );
// exit;
		// add options
		is_array( $method[ 'option' ] ) && $request = array_replace_recursive(
			$method[ 'option' ], $request
		);
		// url
		$object = $this->api_url( $method, $options );
		if( isset( $object[ 'status' ] ) && $object[ 'status' ] === false ) { return( $object ); }
		$url = $object;
		// request options
		$request_option = array();
		@$_is_debug && $request_option[ 'is_debug' ] = true;
		if( @$method[ 'is_authorization' ] ) {
			$result = $this->api_authorization( $request_option );
			if( !@$result[ 'status' ] ) { return( $result ); }
		}
		// header
		is_array( $method[ 'header' ] ) && $request_option = array_replace_recursive( $request_option, array( 'header' => $method[ 'header' ] ) );
		is_array( $method[ 'request' ] ) && $request_option = array_replace_recursive( $request_option, $method[ 'request' ] );
		is_array( $_header ) && $request_option = array_replace_recursive( $request_option, array( 'header' => $_header ) );
		// request
// DEBUG
// var_dump( $url, $request, $request_option );
// exit;
		$result = $this->_api_request( $url, $request, $request_option );
// var_dump( $result );
// exit;
		return( $result );
	}

	public function api_payout( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// method
		$method = $this->api_method( array(
			'type'      => 'payout',
			'method_id' => @$_method_id,
		));
		if( empty( $method ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Метод запроса не найден',
			);
			return( $result );
		}
		$payment_api = &$this->payment_api;
		// operation_id
		$_operation_id = (int)$_operation_id;
		$operation_id = $_operation_id;
		if( empty( $_operation_id ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Не определен код операции',
			);
			return( $result );
		}
		// currency_id
		$currency_id = $this->get_currency_payout( $options );
		if( empty( $currency_id ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неизвестная валюта',
			);
			return( $result );
		}
		// amount min/max
		$result = $this->amount_limit( array(
			'amount'      => $_amount,
			'currency_id' => $currency_id,
			'method'      => $method,
		));
		if( ! @$result[ 'status' ] ) { return( $result ); }
		// amount currency conversion
		$result = $this->currency_conversion_payout( array(
			'options' => $options,
			'method'  => $method,
			'amount'  => $_amount,
		));
		if( empty( $result[ 'status' ] ) ) { return( $result ); }
		$amount_currency       = $result[ 'amount_currency' ];
		$amount_currency_total = $result[ 'amount_currency_total' ];
		$currency_id           = $result[ 'currency_id' ];
		// default
		$amount = @$method[ 'is_fee' ] ? $amount_currency_total : $amount_currency;
		$amount = $payment_api->_number_api( $amount, 2 );
		// request
		$request = array();
		@$method[ 'request_option' ] && $request = $method[ 'request_option' ];
		// add common fields
		$request[ 'label'        ] = ( @$this->SHOP_ID ?: 'shop id' ) . ':' . $operation_id;
		// amount
		$this->is_test() && $amount = '0.01';
		$request[ 'amount'       ] = $amount;
		// account
		@$_to && $request[ 'to' ] = $_to;
		// test
		if( $this->is_test() ) {
			$request += array(
				'test_payment' => 'true',
				'test_result'  => @$_test_result1 ?: 'success',
				// 'test_result'  => 'account_blocked',
				// 'test_result'  => 'illegal_params',
			);
		}
		// title
		@$_title           && $request[ 'title' ] = $_title;
		@$_operation_title && $request[ 'title' ] = $_operation_title;
		// transform
		$this->option_transform( array(
			'option'    => &$request,
			'transform' => $this->_api_transform,
		));
		// add fields
		$request[ 'comment' ] = &$request[ 'message' ];
		foreach( $method[ 'field' ] as $key ) {
			$value = &$request[ $key ];
			if( !isset( $value ) ) {
				$result = array(
					'status'         => false,
					'status_message' => 'Отсутствуют данные запроса: '. $key,
				);
				return( $result );
			}
		}
// DEBUG
// var_dump( $options,$request ); exit;
		// START DUMP
		$payment_api->dump( array( 'name' => 'YandexMoney', 'operation_id' => $operation_id,
			'var' => array( 'request' => $request )
		));
		// update processing
		$sql_datetime = $payment_api->sql_datetime();
		$operation_options = array(
			'processing' => array( array(
				'provider_name' => 'yandexmoney',
				'datetime'      => $sql_datetime,
			)),
		);
		$operation_update_data = array(
			'operation_id'    => $operation_id,
			'datetime_update' => $sql_datetime,
			'options'         => $operation_options,
		);
		$payment_api->operation_update( $operation_update_data );
		// request options
		$request_option = array(
			'method_id' => 'request-payment',
			'option'    => $request,
		);
		@$_is_debug && $request_option[ 'is_debug' ] = true;
		// DEBUG
		// var_dump( $request_option );
		$result = $this->api_request( $request_option );
		// DEBUG
		// var_dump( $result );
		// DUMP
		$payment_api->dump( array( 'var' => array( 'response' => $result )));
		if( @$result[ 'status' ] === false ) { return( $result ); }
		if( ! @$result ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Невозможно отправить запрос',
			);
			return( $result );
		}
		@list( $status, $response ) = $result;
		// DEBUG
		/*
		$this->is_test() && $status = true && $response = array (
			'status'                   => 'success',
			'contract'                 => 'The generated test outgoing money transfer to 410012771676199, amount 0.01',
			'balance'                  => 958.4,
			'request_id'               => 'test-p2p',
			'recipient_account_type'   => 'personal',
			'recipient_account_status' => 'anonymous',
			'test_payment'             => 'true',
			'contract_amount'          => 0.01,
			'money_source'             => array(
				'wallet' => array(
					'allowed' => true,
				),
			),
			'recipient_identified'     => false,
		); //*/
		if( !@$response ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Невозможно декодировать ответ: '. var_export( $response, true ),
			);
			return( $result );
		}
		// transform reverse
		foreach( $this->_api_transform_reverse as $from => $to ) {
			if( $from != $to && isset( $response[ $from ] ) ) {
				$response[ $to ] = $response[ $from ];
				unset( $response[ $from ] );
			}
		}
		// result
		list( $request_status, $state, $result ) = $this->_payout_status_handler( $response );
		// DEBUG
		// var_dump( $request_status, $state, $result ); exit;
		// request
		$request_id = @$response[ 'request_id' ];
		if( $request_status && $state == 'success' ) {
			if( !$request_id ) {
				$result = array(
					'status'         => 'error',
					'status_message' => 'Неверный ответ: отсутствует request_id',
				);
				return( $result );
			} else {
				$request_option =  array( 'request_id' => $request_id ) + $options;
				list( $request_status, $state, $result ) = $this->_payout_process( $request_option );
				// DEBUG
				// var_dump( $request_status, $state, $result ); exit;
			}
		}
		if( !$request_status ) { return( $result ); }
		$status_name    = &$result[ 'status'         ];
		$status_message = &$result[ 'status_message' ];
		// DEBUG
		// var_dump( $request_status, $state, $result );
		// exit;
		// update account, operation data
		$payment_type = 'payment';
		$operation_data = array(
			'operation_id'   => $operation_id,
			'provider_force' => @$_provider_force,
			'provider_name'  => 'yandexmoney',
			'state'          => $state,
			'status_name'    => $status_name,
			'status_message' => $status_message,
			'payment_type'   => $payment_type,
			'response'       => $response,
		);
// DEBUG
// var_dump( $operation_data ); exit;
		// DUMP
		$payment_api->dump( array( 'var' => array( 'payment_type' => $payment_type, 'update operation' => $operation_data )));
		$result = $this->{ '_api_' . $payment_type }( $operation_data );
		// DUMP
		$payment_api->dump( array( 'var' => array( 'update result' => $result )));
		return( $result );
	}

}
