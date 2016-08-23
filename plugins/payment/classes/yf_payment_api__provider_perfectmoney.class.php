<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_perfectmoney extends yf_payment_api__provider_remote {

	public $URL              = 'https://perfectmoney.is/api/step1.asp';
	public $KEY_PUBLIC       = null;     // id
	public $KEY_PRIVATE      = null;     // secret key

	public $HASH_KEY         = 'V2_HASH';

	public $PAYEE_NAME       = null;
	public $PURSE_ID         = null;     // purse_id by currency_id
/* example for project_conf.php:
	public $PURSE_ID         = array(    // purse_id by currency_id
		'UAH' => '...',
	);
*/
	public $PURSE_UNITS      = [
		'USD' => [ 'decimals' => 2 ],
		'EUR' => [ 'decimals' => 2 ],
		'OAU' => [ 'decimals' => 0 ],
	];

	public $URL_API = 'https://perfectmoney.is/acct/%method.asp';

	public $method_allow = [
		'order' => [
			'payin' => [
				'perfectmoney',
			],
			'payout' => [
				'perfectmoney',
			],
		],
		'payin' => [
			'perfectmoney' => [
				'title'       => 'Perfect Money',
				'icon'        => 'perfectmoney',
				'currency' => [
					'USD' => [
						'currency_id' => 'USD',
						'active'      => true,
					],
					// 'EUR' => array(
						// 'currency_id' => 'EUR',
						// 'active'      => true,
					// ),
				],
			],
		],
		'api' => [
			// spend preview/verification
			'verify' => [
				'uri' => [
					'%method' => 'verify',
				],
			],
			// spend (payout)
			'spend' => [
				'uri' => [
					'%method' => 'confirm',
				],
			],
		],
		'payout' => [
			'perfectmoney' => [
				'title' => 'Perfect Money',
				'icon'  => 'perfectmoney',
				'amount' => [
					'min' => 5,
					'max' => 200,
				],
				// 'is_fee' => true,
				'fee' => [
					'out' => [
						'rt'  => 1.99, // 0.5% (1.99%)
						// 'fix' => 10,
					],
				],
				'is_currency' => true,
				'currency' => [
					'USD' => [
						'currency_id' => 'USD',
						'active'      => true,
					],
				],
				'request_field' => [
					'Amount',
					'AccountID',
					'PassPhrase',
					'Payer_Account',
					'Payee_Account',
					'Memo',
					'PAYMENT_ID',
				],
				'field' => [
					'account',
				],
				'order' => [
					'account',
				],
				'option' => [
					'account' => 'Счет',
				],
				'option_validation_js' => [
					'account' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 8,
						'maxlength' => 8,
						'pattern'   => '^U[0-9]{7}$',
					],
				],
				'option_validation' => [
					'account' => 'required|length[8,8]|regex:~^U[0-9]{7}$~',
				],
				'option_validation_message' => [
					'account' => 'обязательное поле: U1234567 (U и 7 цифр)',
				],
			],
		],
	];

	public $_api_transform = [
		'operation_id' => 'PAYMENT_ID',
		'account'      => 'Payee_Account',
		'amount'       => 'Amount',
		'title'        => 'Memo',
	];

	public $_api_transform_reverse = [
		'ERROR'             => 'state',
		'PAYMENT_ID'        => 'operation_id',
		'PAYMENT_BATCH_NUM' => 'provider_operation_id',
		'PAYMENT_AMOUNT'    => 'amount',
		'Payee_Account'     => 'account',
	];

	public $_options_transform = [
		'amount'       => 'PAYMENT_AMOUNT',
		'currency'     => 'PAYMENT_UNITS',
		'title'        => 'SUGGESTED_MEMO',
		'operation_id' => 'PAYMENT_ID',
	];

	public $_options_transform_reverse = [
		'PAYMENT_AMOUNT'    => 'amount',
		'PAYMENT_UNITS'     => 'currency',
		'SUGGESTED_MEMO'    => 'title',
		'PAYMENT_ID'        => 'operation_id',
		'PAYMENT_BATCH_NUM' => 'provider_operation_id',
	];

	public $_status = [
		'success' => 'success',
		'fail'    => 'refused',
	];

	public $_payout_status = [
		'success' => 'success',
		'error'   => 'processing',
		'refused' => 'refused',
	];

	public $_payout_status_message = [
		'success' => 'Выполнено',
		'error'   => 'Ошибка при выполнении',
		'refused' => 'Отклонено',
	];

	public $currency_default = 'USD';
	public $currency_allow = [
		'USD' => [
			'currency_id' => 'USD',
			'active'      => true,
		],
		'EUR' => [
			'currency_id' => 'EUR',
			'active'      => true,
		],
	];

	public $API_SERVER_HOST = 'robot.pm';

	public $service_allow = [
		'Perfect Money',
	];

	public $url_result = null;
	public $url_server = null;

	public function _init() {
		if( !$this->ENABLE ) { return( null ); }
		$this->payment_api = _class( 'payment_api' );
		// load api
		require_once( __DIR__ . '/payment_provider/perfectmoney/PerfectMoney.php' );
		$this->api = new PerfectMoney( $this->KEY_PUBLIC, $this->KEY_PRIVATE );
		$this->url_result = url_user( '/api/payment/provider?name=perfectmoney&operation=response' );
		$this->url_server = url_user( '/api/payment/provider?name=perfectmoney&operation=response&server=true' );
		// parent
		parent::_init();
	}

	public function key( $name = 'public', $value = null ) {
		if( !$this->ENABLE ) { return( null ); }
		$value = $this->api->key( $name, $value );
		return( $value );
	}

	public function key_reset() {
		if( !$this->ENABLE ) { return( null ); }
		$this->key( 'public',  $this->KEY_PUBLIC  );
		$this->key( 'private', $this->KEY_PRIVATE );
	}

	public function signature( $options, $request = true ) {
		if( !$this->ENABLE ) { return( null ); }
		$result = $this->api->signature( $options, $request );
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
		// currency_id
		$currency_id = &$options[ 'currency' ];
		if( empty( $currency_id ) ) { return( null ); }
		// purse
		if( empty( $_[ 'PAYEE_ACCOUNT' ] ) ) {
			$purse_id = $this->PURSE_ID[ $currency_id ];
			if( empty( $purse_id ) ) { return( null ); }
			$_[ 'PAYEE_ACCOUNT' ] = $purse_id;
		}
		// title
		if( !empty( $_[ 'SUGGESTED_MEMO' ] ) ) {
			$_[ 'SUGGESTED_MEMO_NOCHANGE' ] = true;
		}
		// url
		if( !empty( $_[ 'url_result' ] )
			|| empty( $_[ 'PAYMENT_URL'   ] )
			|| empty( $_[ 'NOPAYMENT_URL' ] )
		) {
			$url = $this->_url( $options );
			if( empty( $_[ 'PAYMENT_URL'   ] ) ) {
				$_[ 'PAYMENT_URL'        ] = $url . '&status=success';
				$_[ 'PAYMENT_URL_METHOD' ] = 'POST';
			}
			if( empty( $_[ 'NOPAYMENT_URL'   ] ) ) {
				$_[ 'NOPAYMENT_URL'        ] = $url . '&status=fail';
				$_[ 'NOPAYMENT_URL_METHOD' ] = 'POST';
			}
			unset( $_[ 'url_result' ] );
		}
		if( !empty( $_[ 'url_server' ] )
			|| empty( $_[ 'STATUS_URL' ] )
		) {
			$url = $this->_url( $options, $is_server = true );
			if( empty( $_[ 'STATUS_URL' ] ) ) {
				$_[ 'STATUS_URL' ] = $url;
			}
			unset( $_[ 'url_server' ] );
		}
		// default
		$amount = number_format( $_[ 'PAYMENT_AMOUNT' ], $this->PURSE_UNITS[ $currency_id ][ 'decimals' ] ?: 2, '.', '' );
		if( $amount != $_[ 'PAYMENT_AMOUNT' ] ) { return( null ); }
		$_[ 'PAYMENT_AMOUNT' ] = $amount;
		if( $this->is_test() ) {
			$_[ 'PAYMENT_AMOUNT' ] = '0.01';
		}
		$_[ 'PAYEE_NAME' ] = $this->PAYEE_NAME ?: 'Service';
		return( $_ );
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

	public function _form( $data, $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		if( empty( $data ) ) { return( null ); }
		$_ = &$options;
		// START DUMP
		$payment_api = $this->payment_api;
		$payment_api->dump([ 'name' => 'PerfectMoney', 'operation_id' => @(int)$_[ 'data' ][ 'operation_id' ] ]);
		$is_array = (bool)$_[ 'is_array' ];
		$form_options = $this->_form_options( $data );
		// DUMP
		$payment_api->dump([ 'var' => $form_options ]);
		$url = &$this->URL;
		$result = [];
		if( $is_array ) {
			$result[ 'url' ] = $url;
		} else {
			$result[] = '<form id="_js_provider_perfectmoney_form" method="post" accept-charset="utf-8" action="' . $url . '" class="display: none;">';
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
		if( !$this->ENABLE ) { return( null ); }
		$payment_api = $this->payment_api;
		// var
		$test_mode = &$this->TEST_MODE;
		$is_server = !empty( $_GET[ 'server' ] );
		$result    = null;
		// server host
		$ip               = $this->_ip();
		$host             = gethostbyaddr( $ip );
		$is_server_origin = $host === $this->API_SERVER_HOST;
		// check operation
		// $_operation_id = (int)$_GET[ 'operation_id' ];
		$operation_id = (int)$_POST[ 'PAYMENT_ID' ];
		// START DUMP
		$payment_api->dump( [ 'name' => 'PerfectMoney', 'operation_id' => (int)$operation_id ]);
		// check origin server
		if( !$is_server && !$is_server_origin && !$test_mode ) {
			$result = [
				'status'         => false,
				'status_message' => 'Разрешены запросы только от сервера',
			];
			// DUMP
			$payment_api->dump([ 'var' => [ $result, [
				'ip'              => $ip,
				'host'            => $host,
				'api_server_host' => $this->API_SERVER_HOST,
			]]]);
			return( $result );
		}
		/* // test data
		$api->key( 'private', "ohboyi'msogood1" );
		$_POST = array (
			'PAYMENT_ID'        => 'AB-123',
			'PAYEE_ACCOUNT'     => 'U123456',
			'PAYMENT_AMOUNT'    => '300.00',
			'PAYMENT_UNITS'     => 'USD',
			'PAYMENT_BATCH_NUM' => '789012',
			'PAYER_ACCOUNT'     => 'U456789',
			'TIMESTAMPGMT'      => '876543210',
			'V2_HASH'           => '1CC09524986EDC51F7BEA9E6973F5187',
		); // */
		// response
		$response = $_POST;
		// operation_id
		if( empty( $operation_id ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Не определен код операции',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
		// signature
		$signature  = @$response[ $this->HASH_KEY ];
		$_signature = $this->signature( $response, false );
		$is_signature_ok = $signature == $_signature;
		// check status
		$state = null;
		$state = @$_GET[ 'status' ]; // disable user request by comment this line
		// server status always is success
		if( $is_server ) {
			if( ! $is_signature_ok ) {
				$result = [
					'status'         => false,
					'status_message' => 'Неверная подпись',
				];
				// DUMP
				$payment_api->dump([ 'var' => $result ]);
				return( $result );
			}
			$state = 'success';
		}
		list( $status_name, $status_message ) = $this->_state( $state );
		// check state
		if( !$state || !$status_name ) {
			$result = [
				'status'         => false,
				'status_message' => 'Неизвестное состояние',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
		$status = $status_name == 'success';
		// check signature
		if( empty( $signature ) && $status && !$test_mode ) {
			$result = [
				'status'         => false,
				'status_message' => 'Пустая подпись',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
		if( !$is_signature_ok && $status && !( $test_mode && empty( $signature ) ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Неверная подпись',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
		// get response
		$_response = $this->_response_parse( $response );
		// check operation data
		$operation = $payment_api->operation( [ 'operation_id' => $operation_id ] );
		$_operation_id = @$operation[ 'operation_id' ];
		$amount        = @$_response[ 'amount'       ];
		// $_amount       = @$operation[ 'amount'       ];
		$is_operation_ok =
			$operation_id == $_operation_id
			// && ( $amount == $_amount || $this->is_test() )
		;
		if( !$is_operation_ok ) {
			$result = [
				'status'         => false,
				'status_message' => 'Неверные данные запроса',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
		// update account, operation data
		$result = $this->_api_deposition( [
			'provider_name'  => 'perfectmoney',
			'response'       => $_response,
			'status_name'    => $status_name,
			'status_message' => $status_message,
		]);
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
		return( $_ );
	}

	public function api_account( $options = null, &$request ) {
		// import options
		is_array( $request ) && extract( $request, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$account_id = $this->KEY_PUBLIC;
		$password   = $this->KEY_PRIVATE_API;
		if( !$account_id && !$password ) { return( null ); }
		// AccountID, PassPhrase
		! @$_AccountID  && $request[ 'AccountID'  ] = $account_id;
		! @$_PassPhrase && $request[ 'PassPhrase' ] = $password;
		return( $request );
	}

	public function api_request( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		if( is_string( $options ) ) { $_method_id = $options; }
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// method
		$method = $this->api_method( [
			'type'      => 'api',
			'method_id' => @$_method_id,
		]);
		if( empty( $method ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Метод запроса не найден',
			];
			return( $result );
		}
		// method handler
		if( !empty( $method[ 'is_handler' ] ) ) {
			$handler = 'api_request__'. $method[ 'is_handler' ];
			if( !method_exists( $this, $handler ) ) {
				$result = [
					'status'         => false,
					'status_message' => 'Опработчик метода запроса не найден',
				];
				return( $result );
			}
			$result = $this->{ $handler }( $options );
			return( $result );
		}
		// request
		$request = [];
		!empty( $_option ) && $request = $_option;
// DEBUG
// var_dump( $url, $request, $request_option );
// exit;
		// add options
		!empty( $method[ 'option' ] ) && $request = array_merge_recursive(
			$request, $method[ 'option' ]
		);
		// api account
		$this->api_account( $method, $request );
		// url
		$object = $this->api_url( $method, $options );
		if( isset( $object[ 'status' ] ) && $object[ 'status' ] === false ) { return( $object ); }
		$url = $object;
		// request options
		$request_option = [];
		// xml
		$request[ 'api_version' ] = '1';
		$request_option[ 'is_response_form' ] = true;
		@$_is_debug && $request_option[ 'is_debug' ] = true;
			// header
			is_array( $_header ) && $request_option = array_merge_recursive( $request_option, [ 'header' => $_header ] );
		// test
		if( $this->is_test() ) {
			switch( $_method_id ) {
				case 'spend':
					$request[ 'Amount' ] = '0.01';
					break;
			}
		}
		// request
// DEBUG
// var_dump( $url, $request, $request_option ); exit;
		$result = $this->_api_request( $url, $request, $request_option );
// DEBUG
// var_dump( $result ); exit;
		return( $result );
	}

	public function api_payout( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// method
		$method = $this->api_method( [
			'type'      => 'payout',
			'method_id' => @$_method_id,
		]);
		if( empty( $method ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Метод запроса не найден',
			];
			return( $result );
		}
		$payment_api = &$this->payment_api;
		// operation_id
		!$this->is_test() && $_operation_id = (int)$_operation_id;
		$operation_id = $_operation_id;
		if( empty( $_operation_id ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Не определен код операции',
			];
			return( $result );
		}
		// currency_id
		$currency_id = $this->get_currency_payout( $options );
		if( empty( $currency_id ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Неизвестная валюта',
			];
			return( $result );
		}
		// amount min/max
		$result = $this->amount_limit( [
			'amount'      => $_amount,
			'currency_id' => $currency_id,
			'method'      => $method,
		]);
		if( empty( $result[ 'status' ] ) ) { return( $result ); }
		// amount currency conversion
		$result = $this->currency_conversion_payout( [
			'options' => $options,
			'method'  => $method,
			'amount'  => $_amount,
		]);
		if( empty( $result[ 'status' ] ) ) { return( $result ); }
		$amount_currency       = $result[ 'amount_currency' ];
		$amount_currency_total = $result[ 'amount_currency_total' ];
		$currency_id           = $result[ 'currency_id' ];
		// default
		$amount = @$method[ 'is_fee' ] ? $amount_currency_total : $amount_currency;
		// request
		$request = [];
		@$method[ 'request_option' ] && $request = $method[ 'request_option' ];
		// add common fields
		!@$request[ 'Payer_Account' ] && $request[ 'Payer_Account' ] = $this->PURSE_ID[ $currency_id ];
		if( ! @$request[ 'Payer_Account' ] ) {
			$result = [
				'status'         => false,
				'status_message' => 'Требуется настройка счет плательщика',
			];
			return( $result );
		}
		// account
		@$_account && $request[ 'account' ] = $_account;
		// test account self
		$this->is_test() && $request[ 'account' ] = $this->PURSE_ID[ $currency_id ];
		if( ! @$request[ 'account' ] ) {
			$result = [
				'status'         => false,
				'status_message' => 'Требуется указать счет получателя',
			];
			return( $result );
		}
		// title
		@$_title           && $request[ 'title' ] = $_title;
		@$_operation_title && $request[ 'title' ] = $_operation_title;
		// test amount
		$this->is_test() && $amount = '0.01';
		$amount = number_format( $amount, $this->PURSE_UNITS[ $currency_id ][ 'decimals' ] ?: 2, '.', '' );
		$request[ 'amount'       ] = $amount;
		$request[ 'operation_id' ] = $operation_id;
		// transform
		$this->option_transform( [
			'option'    => &$request,
			'transform' => $this->_api_transform,
		]);
		// api account
		$this->api_account( $method, $request );
		// check request field
		foreach( $method[ 'request_field' ] as $key ) {
			$value = &$request[ $key ];
			if( !isset( $value ) ) {
				$result = [
					'status'         => false,
					'status_message' => 'Отсутствуют данные запроса: '. $key,
				];
				return( $result );
			}
		}
// DEBUG
// var_dump( $request ); exit;
		// START DUMP
		$request_dump = $request;
		unset( $request_dump[ 'PassPhrase' ] );
		$payment_api->dump( [ 'name' => 'PerfectMoney', 'operation_id' => $operation_id,
			'var' => [ 'request' => $request_dump ]
		]);
		// update processing
		$sql_datetime = $payment_api->sql_datetime();
		$operation_options = [
			'processing' => [ [
				'provider_name' => 'perfectmoney',
				'datetime'      => $sql_datetime,
			]],
		];
		$operation_update_data = [
			'operation_id'    => $operation_id,
			'datetime_update' => $sql_datetime,
			'options'         => $operation_options,
		];
		$payment_api->operation_update( $operation_update_data );
		// request options
		$request_option = [
			'method_id' => 'spend',
			'option'    => $request,
			'is_debug'  => @$_is_debug,
		];
// DEBUG
// var_dump( $request_option ); exit;
		$result = $this->api_request( $request_option );
// DEBUG
// ini_set( 'html_errors', 1 );
// var_dump( $result ); exit;
		// DUMP
		$payment_api->dump( [ 'var' => [ 'response' => $result ]]);
		if( empty( $result ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Невозможно отправить запрос',
			];
			return( $result );
		}
		@list( $status, $response ) = $result;
		if( !@$status ) {
			$result = [
				'status'         => false,
				'status_message' => @$response ?: @$result[ 'status_message' ] ?: 'unknown error',
			];
			return( $result );
		}
		if( !@$response ) {
			$result = [
				'status'         => false,
				'status_message' => 'Невозможно декодировать ответ: '. var_export( $response, true ),
			];
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
		$result = [
			'status'         => &$status_name,
			'status_message' => &$status_message,
		];
		$status_name    = false;
		$status_message = null;
		$error = &$response[ 'state' ];
		$state = empty( $error );
		switch( true ) {
			// success
			case $state:
				$status         = 'success';
				$status_name    = true;
				$status_message = 'Выполнено';
				break;
			// refused
			// case $error == '...':
				// $status         = 'refused';
				// $status_message = $error;
				// break;
			// processing
			default:
				$status         = 'error';
				$status_message = 'Ошибка: '. $response[ 'state' ];
				break;
		}
		@$status_message && $response[ 'message' ] = $status_message;
		if( !$status_name ) { return( $result ); }
		// check status
		list( $status_name, $status_message ) = $this->_state( $status
			, $this->_payout_status
			, $this->_payout_status_message
		);
		$status_message = @$status_message ?: @$error;
		!@$error && $error = $status_name;
		// update account, operation data
		$payment_type = 'payment';
		$operation_data = [
			'operation_id'   => $operation_id,
			'provider_force' => @$_provider_force,
			'provider_name'  => 'perfectmoney',
			'state'          => $state,
			'status_name'    => $status_name,
			'status_message' => $status_message,
			'payment_type'   => $payment_type,
			'response'       => $response,
		];
// DEBUG
// var_dump( $operation_data ); exit;
		// DUMP
		$payment_api->dump( [ 'var' => [ 'payment_type' => $payment_type, 'update operation' => $operation_data ]]);
		$result = $this->{ '_api_' . $payment_type }( $operation_data );
		// DUMP
		$payment_api->dump( [ 'var' => [ 'update result' => $result ]]);
		return( $result );
	}

}
