<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_yandexmoney extends yf_payment_api__provider_remote {

	public $URL              = 'https://money.yandex.ru/quickpay/confirm.xml';
	public $SHOP_ID          = null;     // shop id
	public $KEY_PUBLIC       = null;     // account id
	public $KEY_PRIVATE      = null;     // secret key

	public $HASH_KEY         = 'sha1_hash';

	public $fee = 0.5; // PC = 0.5%, AC = 2%

	public $URL_API = 'https://money.yandex.ru/%method';
	public $API_REDIRECT_URI = null;

	public $method_allow = [
		'order' => [
			'payin' => [
				'yandexmoney',
			],
			'payout' => [
				'yandexmoney_p2p',
			],
		],
		'payin' => [
			'yandexmoney' => [
				'title' => 'Яндекс.Деньги',
				'icon'  => 'yandexmoney',
				'fee'         => 2, // 0.1%
				'currency' => [
					'RUB' => [
						'currency_id' => 'RUB',
						'active'      => true,
					],
				],
			],
		],
		'api' => [
			'authorize' => [
				'uri' => [
					'%method' => 'oauth/authorize',
				],
			],
			'token' => [
				'uri' => [
					'%method' => 'oauth/token',
				],
			],
			// token revoke
			'revoke' => [
				'is_authorization' => true,
				'request' => [
					'is_post'     => true,
					'is_http_raw' => true,
				],
				'uri' => [
					'%method' => 'api/revoke',
				],
			],
			// account-info
			'account-info' => [
				'is_authorization' => true,
				'uri' => [
					'%method' => 'api/account-info',
				],
			],
			// operation-history
			'operation-history' => [
				'is_authorization' => true,
				'uri' => [
					'%method' => 'api/operation-history',
				],
			],
			// operation-details
			'operation-details' => [
				'is_authorization' => true,
				'uri' => [
					'%method' => 'api/operation-details',
				],
			],
			// request-payment
			'request-payment' => [
				'is_authorization' => true,
				'uri' => [
					'%method' => 'api/request-payment',
				],
			],
			'process-payment' => [
				'is_authorization' => true,
				'uri' => [
					'%method' => 'api/process-payment',
				],
			],
		],
		/**
		 * Ограничения на выплаты:
		 *   Идентифицированный: 250 000 руб.
		 *   Именной:             60 000 руб.
		 *   Анонимный:           15 000 руб.
		 */
		'payout' => [
			'yandexmoney_p2p' => [
				'title' => 'YandexMoney',
				'icon'  => 'yandexmoney',
				'amount' => [
					'min' => 5,
					'max' => 200,
				],
				'is_fee' => true,
				'fee' => [
					'out' => [
						'rt'  => 0.5,
						// 'fix' => 10,
					],
				],
				'is_currency' => true,
				'currency' => [
					'RUB' => [
						'currency_id' => 'RUB',
						'active'      => true,
					],
				],
				'request_option' => [
					'pattern_id' => 'p2p',
				],
				'request_field' => [
					'pattern_id',
					'to',
					'amount',
					'label',
					'comment',
					'message',
				],
				'field' => [
					'to',
				],
				'order' => [
					'to',
				],
				'option' => [
					'to' => 'Номер счета',
				],
				'option_validation_js' => [
					'to' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 11,
						'maxlength' => 26,
						// 'pattern'   => '^\d+$',
						'pattern'   => '^41001[0-9]{4,19}(?:[1-9]{2})$',
					],
				],
				'option_validation' => [
					'to' => 'required|length[11,26]|regex:~^41001[0-9]{4,19}(?:[1-9]{2})$~',
				],
				'option_validation_message' => [
					'to' => 'обязательное поле от 11 до 26 цифр',
				],
			],
		],
	];

	public $_api_transform = [
		'title'           => 'message',
	];

	public $_api_transform_reverse = [
		'status'          => 'state',
		'contract_amount' => 'amount',
		'payment_id'      => 'external_id',
	];

	public $_options_transform = [
		'amount'       => 'sum',
		'title'        => 'targets',     // payment title
		'operation_id' => 'label',
	];

	public $_options_transform_reverse = [
		'sum'          => 'amount',
		'targets'      => 'title',
		'operation_id' => 'external_id',
		'label'        => 'operation_id',
	];

	public $_status = [
		'success' => 'success',
		'fail'    => 'refused',
	];

	public $_payout_status = [
		'success'                     => 'success',
		'in_progress'                 => 'in_progress',
		// refused
		'payment_refused'             => 'refused',
		'payee_not_found'             => 'refused',
		'authorization_reject'        => 'refused',
		'account_blocked'             => 'refused',
		'ext_action_required'         => 'refused',
		// error
		'error'                       => 'external error',
		'illegal_params'              => 'error',
		'illegal_param_label'         => 'error',
		'illegal_param_to'            => 'error',
		'illegal_param_amount'        => 'error',
		'illegal_param_amount_due'    => 'error',
		'illegal_param_comment'       => 'error',
		'illegal_param_message'       => 'error',
		'illegal_param_expire_period' => 'error',
		'not_enough_funds'            => 'error',
		'limit_exceeded'              => 'error',
		'contract_not_found'          => 'error',
		'money_source_not_available'  => 'error',
	];

	public $_payout_status_message = [
		'success'                     => 'Платеж проведен.',
		'in_progress'                 => 'Авторизация платежа не завершена. Повторите запрос спустя некоторое время.',
		// refused
		'payment_refused'             => 'Магазин отказал в приеме платежа.',
		'payee_not_found'             => 'Указанный счет не существует или не связанный со счетом пользователя.',
		'authorization_reject'        => 'В авторизации платежа отказано.',
		'account_blocked'             => 'Счет пользователя заблокирован.',
		'ext_action_required'         => 'В настоящее время данный тип платежа не может быть проведен.',
		// error
		'external error'              => 'Техническая ошибка, повторите вызов операции позднее.',
		'illegal_params'              => 'Обязательные параметры платежа отсутствуют или имеют недопустимые значения.',
		'illegal_param_label'         => 'Недопустимое значение параметра label.',
		'illegal_param_to'            => 'Недопустимое значение параметра счета (to).',
		'illegal_param_amount'        => 'Недопустимое значение параметра amount.',
		'illegal_param_amount_due'    => 'Недопустимое значение параметра amount_due.',
		'illegal_param_comment'       => 'Недопустимое значение параметра comment.',
		'illegal_param_message'       => 'Недопустимое значение параметра message.',
		'illegal_param_expire_period' => 'Недопустимое значение параметра expire_period.',
		'not_enough_funds'            => 'На счете плательщика недостаточно средств.',
		'limit_exceeded'              => 'Превышен один из лимитов на операции.',
		'contract_not_found'          => 'Отсутствует созданный (но не подтвержденный) платеж с заданным request_id.',
		'money_source_not_available'  => 'Запрошенный метод платежа (money_source) недоступен для данного платежа.',
	];

	public $currency_default = 'RUB';
	public $currency_allow = [
		'RUB' => [
			'currency_id' => 'RUB',
			'active'      => true,
		],
	];

	public $service_allow = [
		'Яндекс.Деньги',
	];

	public $url_result    = null;
	public $url_server    = null;
	public $url_authorize = null;

	public $provider_name = 'yandexmoney';
	public $provider_id   = null;
	public $provider      = null;
	public $access_token  = null;

	public function _init() {
		if( !$this->ENABLE ) { return( null ); }
		// parent
		parent::_init();
		// class
		$payment_api   = &$this->payment_api;
		$provider_name = &$this->provider_name;
		$provider_id   = &$this->provider_id;
		$provider      = &$this->provider;
		$access_token  = &$this->access_token;
		// load api
		require_once( __DIR__ . '/payment_provider/yandexmoney/YandexMoney.php' );
		$this->api = new YandexMoney( $this->KEY_PUBLIC, $this->KEY_PRIVATE );
		$this->url_result    = url_user( '/api/payment/provider?name=yandexmoney&operation=response' );
		$this->url_server    = url_user( '/api/payment/provider?name=yandexmoney&operation=response&server=true' );
		$this->url_authorize = url_user( '/api/payment/provider?name=yandexmoney&operation=authorize&server=true' );
		// provider options
		list( $provider_id, $provider ) = $payment_api->get_provider([ 'name' => $provider_name ]);
		$access_token = @$provider[ 'options' ][ 'authorize' ][ 'access_token' ];
			!is_string( $access_token ) && $access_token = null;
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
		unset( $_[ 'description' ], $_[ 'currency' ] );
		// payment title
		$title = '';
		if( @$_[ 'targets' ] ) {
			switch( true ) {
				case defined( 'SITE_ADVERT_NAME'  ):
					$title = SITE_ADVERT_NAME;
					break;
				case defined( 'SITE_ADVERT_TITLE'  ):
					$title = SITE_ADVERT_TITLE;
					break;
				case @$_SERVER[ 'SERVER_NAME' ]:
					$title = $_SERVER[ 'SERVER_NAME' ];
					break;
			}
			if( !empty( $title ) ) {
				$title .= ': '. $_[ 'targets' ];
				$_[ 'formcomment' ] = &$title;
				$_[ 'short-dest'  ] = &$title;
			}
		}
		// default
		$amount = number_format( $_[ 'sum' ], 2, '.', '' );
		if( (int)$amount != (int)$_[ 'sum' ] ) { return( null ); }
		$_[ 'sum' ] = $amount;
		if( $this->is_test() ) {
			$_[ 'sum' ] = '0.03';
		}
		// default fields
		$_[ 'label'         ] = ( @$this->SHOP_ID ?: 'shop id' ) . ':' . $_[ 'label' ];
		$_[ 'receiver'      ] = $this->key( 'public' );
		$_[ 'quickpay-form' ] = 'shop';
		$_[ 'paymentType'   ] = 'PC';
		$_[ 'need-flo'      ] = 'false';
		$_[ 'need-email'    ] = 'false';
		$_[ 'need-phone'    ] = 'false';
		$_[ 'need-address'  ] = 'false';
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
		$payment_api->dump([ 'name' => 'YandexMoney', 'operation_id' => @(int)$_[ 'data' ][ 'operation_id' ] ]);
		$is_array = (bool)$_[ 'is_array' ];
		$form_options = $this->_form_options( $data );
		// DUMP
		$payment_api->dump([ 'var' => $form_options ]);
		$url = &$this->URL;
		$result = [];
		if( $is_array ) {
			$result[ 'url' ] = $url;
		} else {
			$result[] = '<form id="_js_provider_yandexmoney_form" method="post" accept-charset="utf-8" action="' . $url . '" class="display: none;">';
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
		//* // TEST
		if( $this->is_test() ) {
			$operation_id = (int)$_GET[ 'operation_id' ];
			$this->key( 'private', '01234567890ABCDEF01234567890' );
			$_POST = [
				'notification_type' => 'p2p-incoming',
				'operation_id'      => '1234567',
				'amount'            => '300.00',
				'currency'          => '643',
				'datetime'          => '2011-07-01T09:00:00.000+04:00',
				'sender'            => '41001XXXXXXXX',
				'codepro'           => 'false',
				'sha1_hash'         => '090a8e7ebb6982a7ad76f4c0f0fa5665d741aafa',
				'withdraw_amount'   => '100.00',
				// shop_id:operation_id may be exists
				'label' => $this->SHOP_ID . ':' . $operation_id,
			];
		} // */
		// check shop_id, operation
		@list( $shop_id, $operation_id ) = explode( ':', $_POST[ 'label' ] );
		$operation_id = (int)$operation_id;
		// START DUMP
		$payment_api->dump( [ 'name' => 'YandexMoney', 'operation_id' => $operation_id ]);
		if( $shop_id != $this->SHOP_ID ) {
			$result = [
				'status'         => false,
				'status_message' => 'Не верный идентификатор магазина',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
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
		//* // TEST
		if( $this->is_test() ) {
			unset( $response[ 'label' ] );
		} // */
		$_signature = $this->signature( $response, false );
		$is_signature_ok = $signature == $_signature;
		if( !$is_signature_ok ) {
			$result = [
				'status'         => false,
				'status_message' => 'Неверная подпись',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
		// check status
		$state = 'fail';
		// check payin
		$is_codepro    = @$response[ 'codepro'    ] == 'true';
		$is_unaccepted = @$response[ 'unaccepted' ] == 'true';
			$is_payin = !( $is_codepro || $is_unaccepted );
		if( $is_server && $is_signature_ok && $is_payin ) {
			$state = 'success';
		}
		list( $status_name, $status_message ) = $this->_state( $state );
		$status = $status_name == 'success';
		// get response
		$_response = $this->_response_parse( $response );
		// check operation data
		$operation = $payment_api->operation( [ 'operation_id' => $operation_id ] );
		$_operation_id = @$operation[ 'operation_id' ];
		$amount        = @$_response[ 'amount'       ];
		// $_amount       = @$operation[ 'amount'       ];
		$is_operation_ok =
			$operation_id == (int)$_operation_id
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
		$_response[ 'operation_id' ] = $operation_id;
		$_response[ 'shop_id'      ] = $shop_id;
		// update account, operation data
		$result = $this->_api_deposition( [
			'provider_name'  => 'yandexmoney',
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

	// *********** API

	public function api_token_revoke( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// var
		$payment_api   = &$this->payment_api;
		$provider_name = &$this->provider_name;
		$provider_id   = &$this->provider_id;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// request
		$request = [
			'method_id' => 'revoke',
		];
		$result = $this->api_request( $request );
		$http_code = (int)$result[ 'http_code' ];
		$status         = null;
		$status_message = null;
		switch( $http_code ) {
			case 200:
				$status = true;
				$status_message = 'Токен успешно отозван';
				break;
			case 401:
				$status = false;
				$status_message = 'Указанный токен не существует';
				break;
			case 400:
			default:
				$status_message = 'Неверный запрос';
				break;
		}
		if( !is_null( $status ) ) {
			$r = $payment_api->provider_update( [
				'provider_id' => $provider_id,
				'options'     => [ 'redirect_uri' => null, 'authorize' => null ],
			], [ 'is_replace' => true ] );
			if( !@$r[ 'status' ] ) { return( $r ); }
		}
		$result = [
			'status'         => $status,
			'status_message' => $status_message,
		];
		return( $result );
	}

	public function _get_api_redirect_uri( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$result = $this->API_REDIRECT_URI;
		$_redirect_hash && $result .= '&redirect_hash='. $_redirect_hash;
		return( $result );
	}

	public function authorize_request( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// var
		$payment_api   = &$this->payment_api;
		$provider_name = &$this->provider_name;
		$provider_id   = &$this->provider_id;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$request_options = [];
		// redirect uri
		$redirect_hash = null;
		if( @$_redirect_uri ) {
			$redirect_hash = substr( md5( $_redirect_uri . time() ), 0, 8 );
			$r = $payment_api->provider_update( [
				'provider_id' => $provider_id,
				'options'     => [ 'redirect_uri' => [
					$redirect_hash => [
						'value'    => $_redirect_uri,
						'datetime' => $payment_api->sql_datetime(),
					],
				]],
			], [ 'is_replace' => true ] );
			if( !@$r[ 'status' ] ) { return( $r ); }
		}
		// request options
		$API_REDIRECT_URI = $this->_get_api_redirect_uri( [
			'redirect_hash' => $redirect_hash,
		]);
		$default = [
			'client_id'     => $this->API_KEY_PUBLIC,
			'client_secret' => $this->API_KEY_PRIVATE,
			'redirect_uri'  => $API_REDIRECT_URI,
			'response_type' => 'code',
			'instance_name' => 'admin',
			'scope'         => 'account-info operation-history operation-details incoming-transfers payment-p2p money-source("wallet")',
		];
		$request_options = $default;
		$method_id = 'authorize';
		is_array( $_option ) && $request_options = array_replace_recursive( $request_options, $_option );
		// method
		$method = $this->api_method( [
			'type'      => 'api',
			'method_id' => $method_id,
		]);
		if( empty( $method ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Метод запроса не найден',
			];
			return( $result );
		}
		// url
		$object = $this->api_url( $method, $options );
		if( isset( $object[ 'status' ] ) && $object[ 'status' ] === false ) { return( $object ); }
		$url = $object;
		// form
		$result = [];
		$result[] = '<form id="_js_provider_yandexmoney_authorize_form" method="post" accept-charset="utf-8" action="' . $url . '" class="display: none;">';
		foreach( $request_options as $key => $value ) {
			$result[] = sprintf( '<input type="hidden" name="%s" value="%s" />', $key, htmlentities( $value, ENT_COMPAT | ENT_HTML5, 'UTF-8', false ) );
		}
		$result[] = '<script>document.getElementById(\'_js_provider_yandexmoney_authorize_form\').submit();</script>';
		$result[] = '</form>';
		// DEBUG
		// var_dump( $result ); exit;
		$result = implode( PHP_EOL, $result );
		echo $result; exit;
		// return( $result );
	}

	public function _api_authorize() {
		if( !$this->ENABLE ) { return( null ); }
		// var
		$payment_api   = &$this->payment_api;
		$provider_name = &$this->provider_name;
		$provider_id   = &$this->provider_id;
		$provider      = &$this->provider;
		$is_server     = !empty( $_GET[ 'server' ] );
		$redirect_uri  = null;
		// START DUMP
		$payment_api->dump( [ 'name' => 'YandexMoney-authorize' ]);
		// token
		$code          = &$_GET[ 'code' ];
		$redirect_hash = &$_GET[ 'redirect_hash' ];
		$redirect_uri  = &$provider[ 'options' ][ 'redirect_uri' ][ $redirect_hash ][ 'value' ];
		// DUMP
		$payment_api->dump( [ 'var' => [
			'code'          => $code,
			'redirect_hash' => $redirect_hash,
		]]);
		if( $code && $redirect_hash ) {
			$API_REDIRECT_URI = $this->_get_api_redirect_uri( [
				'redirect_hash' => $redirect_hash,
			]);
			$request = [
				'method_id' => 'token',
				'option' => [
					'client_id'     => $this->API_KEY_PUBLIC,
					'client_secret' => $this->API_KEY_PRIVATE,
					'redirect_uri'  => $API_REDIRECT_URI,
					'grant_type'    => 'authorization_code',
					'code'          => $code,
				],
			];
			// DUMP
			$payment_api->dump( [ 'var' => [
				'token request' => $request,
			]]);
			list( $status, $response ) = $this->api_request( $request );
			// DUMP
			$payment_api->dump( [ 'var' => [
				'token response status' => $status,
				'token response'        => $response,
			]]);
			// store access_token
			if( @$status && @$response[ 'access_token' ] ) {
				$access_token = &$this->access_token;
				$access_token = $response[ 'access_token' ];
				$provider_id = &$this->provider_id;
				$r = $payment_api->provider_update( [
					'provider_id' => $provider_id,
					'options'     => [
						'authorize' => [
							'access_token' => $access_token,
							'datetime'     => $payment_api->sql_datetime(),
						],
						'redirect_uri' => [ $redirect_hash => null ],
					],
				], [ 'is_replace' => true ] );
				if( !@$r[ 'status' ] ) { return( $r ); }
			}
		} else {
			// DUMP
			$payment_api->dump( [ 'var' => [
				'error' => 'No code or redirect_hash',
			]]);
		}
		// redirect
		$redirect_uri = $redirect_uri ?: '/payments';
		return( _class( 'api' )->_redirect( $redirect_uri ) );
	}

	public function is_authorization() {
		$result = (bool)$this->access_token;
		return( $result );
	}

	public function api_authorization( &$options ) {
		$result = $this->is_authorization();
		if( !$result ) {
			$result = [
				'status'         => false,
				'status_message' => 'Требуется авторизация YandexMoney',
			];
			return( $result );
		}
		$options[ 'access_token' ] = $this->access_token;
		$result = [
			'status'         => true,
			'status_message' => 'Авторизация имеется',
		];
		return( $result );
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
		$request_option = [];
		@$_is_debug && $request_option[ 'is_debug' ] = true;
		if( @$method[ 'is_authorization' ] ) {
			$result = $this->api_authorization( $request_option );
			if( !@$result[ 'status' ] ) { return( $result ); }
		}
		// header
		is_array( $method[ 'header' ] ) && $request_option = array_replace_recursive( $request_option, [ 'header' => $method[ 'header' ] ] );
		is_array( $method[ 'request' ] ) && $request_option = array_replace_recursive( $request_option, $method[ 'request' ] );
		is_array( $_header ) && $request_option = array_replace_recursive( $request_option, [ 'header' => $_header ] );
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
		$_operation_id = (int)$_operation_id;
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
		if( ! @$result[ 'status' ] ) { return( $result ); }
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
		$amount = $payment_api->_number_api( $amount, 2 );
		// request
		$request = [];
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
			$request += [
				'test_payment' => 'true',
				'test_result'  => @$_test_result1 ?: 'success',
				// 'test_result'  => 'account_blocked',
				// 'test_result'  => 'illegal_params',
			];
		}
		// title
		@$_title           && $request[ 'title' ] = $_title;
		@$_operation_title && $request[ 'title' ] = $_operation_title;
		// transform
		$this->option_transform( [
			'option'    => &$request,
			'transform' => $this->_api_transform,
		]);
		// add fields
		$request[ 'comment' ] = &$request[ 'message' ];
		foreach( $method[ 'field' ] as $key ) {
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
// var_dump( $options,$request ); exit;
		// START DUMP
		$payment_api->dump( [ 'name' => 'YandexMoney', 'operation_id' => $operation_id,
			'var' => [ 'request' => $request ]
		]);
		// update processing
		$sql_datetime = $payment_api->sql_datetime();
		$operation_options = [
			'processing' => [ [
				'provider_name' => 'yandexmoney',
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
			'method_id' => 'request-payment',
			'option'    => $request,
		];
		@$_is_debug && $request_option[ 'is_debug' ] = true;
		// DEBUG
		// var_dump( $request_option );
		$result = $this->api_request( $request_option );
		// DEBUG
		// var_dump( $result );
		// DUMP
		$payment_api->dump( [ 'var' => [ 'response' => $result ]]);
		if( @$result[ 'status' ] === false ) { return( $result ); }
		if( ! @$result ) {
			$result = [
				'status'         => false,
				'status_message' => 'Невозможно отправить запрос',
			];
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
		list( $request_status, $state, $result ) = $this->_payout_status_handler( $response );
		// DEBUG
		// var_dump( $request_status, $state, $result ); exit;
		// request
		$request_id = @$response[ 'request_id' ];
		if( $request_status && $state == 'success' ) {
			if( !$request_id ) {
				$result = [
					'status'         => 'error',
					'status_message' => 'Неверный ответ: отсутствует request_id',
				];
				return( $result );
			} else {
				$request_option =  [ 'request_id' => $request_id ] + $options;
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
		$operation_data = [
			'operation_id'   => $operation_id,
			'provider_force' => @$_provider_force,
			'provider_name'  => 'yandexmoney',
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

	public function _payout_process( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! @$_request_id ) {
			$result = [
				'status'         => 'processing',
				'status_message' => 'Ошибка: отсутствует request_id',
			];
			return( [ null, $result ] );
		}
		$payment_api = &$this->payment_api;
		$request = [
			'request_id' => $_request_id,
		];
		// test
		if( $this->is_test() ) {
			$request += [
				'test_payment' => 'true',
				'test_result'  => @$_test_result2 ?: 'success',
				// 'test_result'  => 'payment_refused',
				// 'test_result'  => 'not_enough_funds',
			];
		}
		// process
		$request_option = [
			'method_id' => 'process-payment',
			'option'    => $request,
		];
		@$_is_debug && $request_option[ 'is_debug' ] = true;
		// DEBUG
		// var_dump( 'process:', $request_option );
		// DUMP
		$payment_api->dump( [ 'var' => [ 'process request' => $request_option ]]);
		$result = $this->api_request( $request_option );
		// DEBUG
		// var_dump( 'process:', $result ); exit;
		// DUMP
		$payment_api->dump( [ 'var' => [ 'process response' => $result ]]);
		if( @$result[ 'status' ] === false ) { return( [ null, $result ] ); }
		if( ! @$result ) {
			$result = [
				'status'         => false,
				'status_message' => 'Невозможно отправить запрос',
			];
			return( [ null, $result ] );
		}
		@list( $status, $response ) = $result;
		if( !@$response ) {
			$result = [
				'status'         => false,
				'status_message' => 'Невозможно декодировать ответ: '. var_export( $response, true ),
			];
			return( [ null, $result ] );
		}
		// transform reverse
		foreach( $this->_api_transform_reverse as $from => $to ) {
			if( $from != $to && isset( $response[ $from ] ) ) {
				$response[ $to ] = $response[ $from ];
				unset( $response[ $from ] );
			}
		}
		// DEBUG state
		// $response[ 'state' ] = 'in_progress';
		// result
		$result = $this->_payout_status_handler( $response );
		return( $result );
	}

	public function _payout_status_handler( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$request_status = false;
		$status_name    = false;
		$status_message = null;
		$result = [
			'status'         => &$status_name,
			'status_message' => &$status_message,
		];
		$state = &$_state;
		$error = &$_error;
		// DEBUG state
		// $state = 'refused'; $error = 'error1';
		switch( $state ) {
			// success
			case 'success':
				$status         = 'success';
				$status_message = 'Выполнено';
				$request_status = true;
				break;
			// in_progress
			case 'in_progress':
				$status         = 'in_progress';
				$status_message = 'В процессе';
				$request_status = true;
				break;
			// error
			case 'refused':
				$status         = $error;
				$status_message = 'Отказано';
				$request_status = true;
				break;
			default:
				$status         = 'unknown';
				$status_message = 'Ошибка: '. $state;
				break;
		}
		@$status_message && $_message = $status_message;
		// check status
		if( $status != 'unknown' ) {
			list( $status_name, $status_message ) = $this->_state( $status
				, $this->_payout_status
				, $this->_payout_status_message
			);
			if( !$status_name ) {
				$status = 'error';
				list( $status_name, $status_message ) = $this->_state( $status
					, $this->_payout_status
					, $this->_payout_status_message
				);
			}
		}
		$status_name == 'error' || $status_name == 'external error'
			&& $request_status = false;
		// add error_description
		$status_name == 'error' && $status == 'unknown' && $_error_description
			&& $status_message .= ' ('. $_error_description . ')';
		// result
		$state = $status_name ?: $status;
		$status_message = @$status_message ?: $state;
		return( [ $request_status, $state, $result ] );
	}

}
