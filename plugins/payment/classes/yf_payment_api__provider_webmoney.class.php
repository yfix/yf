<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_webmoney extends yf_payment_api__provider_remote {

	// номер перевода в системе учета отправителя; любое целое число без знака (целое число > 0; максимально 2^31 - 1), должно быть уникальным в пределах WMID, который подписывает запрос.
	// Два перевода с одним и тем же tranid с одного WMID (даже с разных кошельков) осуществить невозможно.
	// Уникальность значения tranid контролируется в интервале не менее одного года.
	// shop id: 21-47483648 -  1-20 or 0, null
	//                        21 - test
	public $SHOP_ID     = null;

	public $URL         = 'https://merchant.webmoney.ru/lmi/payment.asp';
	public $KEY_PUBLIC  = null; // purse_id
	public $KEY_PRIVATE = null; // secret key
	public $HASH_METHOD = 'sha256'; // signature hash method: md5, sha256; sign - not support (need payee key on server - no good idea)

	// public $fee = 0.5; // PC = 0.5%, AC = 2%

	public $URL_API = 'https://w3s.wmtransfer.com/asp/%method';
	public $API_REDIRECT_URI = null;

	public $method_allow = [
		'order' => [
			'payin' => [
				'webmoney',
			],
			'payout' => [
				'p2p_wmz',
			],
		],
		'payin' => [
			'webmoney' => [
				'title' => 'WebMoney',
				'icon'  => 'webmoney',
				// 'fee'         => 2, // 0.1%
				'currency' => [
					'USD' => [
						'currency_id' => 'USD',
						'active'      => true,
					],
				],
			],
		],
		'api' => [
		// XML intefaces:
			// X2 - Перевод средств с одного кошелька на другой
			'p2p' => [
				'is_handler' => 'X2',
				'uri' => [
					'%method' => 'XMLTransCert.asp',
				],
			],
			// X9 - Получение информации о балансе на кошельках
			'balance' => [
				'is_handler' => 'X9',
				'uri' => [
					'%method' => 'XMLPursesCert.asp',
				],
			],
		],
		'payout' => [
			'p2p_wmz' => [
				'title' => 'WebMoney WMZ',
				'icon'  => 'webmoney',
				'amount' => [
					'min' => 5,
					'max' => 200,
				],
				// 'is_fee' => true,
				'fee' => [
					'out' => [
						// 'rt'  => 0.01,
						// 'fix' => 0.01,
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
					'tranid',
					'pursesrc',
					'pursedest',
					'amount',
					'desc',
				],
				'field' => [
					'purse',
				],
				'order' => [
					'purse',
				],
				'option' => [
					'purse' => 'Кошелек',
				],
				'option_validation_js' => [
					'purse' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 13,
						'maxlength' => 13,
						// 'pattern'   => '^\d+$',
						'pattern'   => '^Z\d{12}$',
					],
				],
				'option_validation' => [
					'purse' => 'required|length[13,13]|regex:~^Z\d{12}$~',
				],
				'option_validation_message' => [
					'purse' => 'обязательное поле Z и 12 цифр',
				],
			],
		],
	];

	// WebMoney root ssl certificate
	public $CA = null;

	// WebMoney WebPro client ssl certificate
	public $SSL = null;

	public $_api_transform = [
		'title'           => 'message',
	];

	public $_api_transform_reverse = [
		'status'          => 'state',
		'contract_amount' => 'amount',
		'payment_id'      => 'external_id',
	];

	public $_api_X = [
		'title'           => 'desc',
	];

	public $_api_X_reverse = [
		'tranid'          => 'operation_id',
	];

	public $_options_transform = [
		'amount'       => 'LMI_PAYMENT_AMOUNT',
		'title'        => 'LMI_PAYMENT_DESC',
		'operation_id' => 'LMI_PAYMENT_NO',
		'key_public'   => 'LMI_PAYEE_PURSE', // Z111111111111, E111111111111
	];

	public $_options_transform_reverse = [
		'LMI_PAYMENT_AMOUNT' => 'amount',
		'LMI_PAYMENT_DESC'   => 'title',
		'LMI_PAYMENT_NO'     => 'operation_id',
		'LMI_PAYEE_PURSE'    => 'key_public',
		'LMI_MODE'           => 'test',
		'LMI_HASH'           => 'signature',
	];

	public $_status = [
		'success' => 'success',
		'wait'    => 'in_progress',
		'fail'    => 'refused',
	];

	public $_payout_status = [
		'0'    => 'success',
		// in_progress
		'unknown' => 'in_progress',
		'-100' => 'in_progress',
		'-110' => 'in_progress',
		'-1'   => 'in_progress',
		'-2'   => 'in_progress',
		'-3'   => 'in_progress',
		'-4'   => 'in_progress',
		'-5'   => 'in_progress',
		'-6'   => 'in_progress',
		'-7'   => 'in_progress',
		'-8'   => 'in_progress',
		'-9'   => 'in_progress',
		'-10'  => 'in_progress',
		'-11'  => 'in_progress',
		'-12'  => 'in_progress',
		'-14'  => 'in_progress',
		'-15'  => 'in_progress',
		'102'  => 'in_progress',
		'103'  => 'in_progress',
		'110'  => 'in_progress',
		'111'  => 'in_progress',
		'4'    => 'in_progress',
		'15'   => 'in_progress',
		'19'   => 'in_progress',
		'23'   => 'in_progress',
		'5'    => 'in_progress',
		'6'    => 'in_progress',
		'7'    => 'in_progress',
		'11'   => 'in_progress',
		'13'   => 'in_progress',
		'17'   => 'in_progress',
		'18'   => 'in_progress',
		'20'   => 'in_progress',
		'21'   => 'in_progress',
		'22'   => 'in_progress',
		'25'   => 'in_progress',
		'26'   => 'in_progress',
		'29'   => 'in_progress',
		'30'   => 'in_progress',
		'32'   => 'in_progress',
		'34'   => 'in_progress',
		'35'   => 'in_progress',
		'58'   => 'in_progress',
		'72'   => 'in_progress',
		'73'   => 'in_progress',
		'74'   => 'in_progress',
		// refused
		// error
	];

	public $_payout_status_message = [
		'success' => 'Платеж проведен.',
		// in_progress
		'unknown' => 'Отсутствуют статус ответ',
		'-100' => 'общая ошибка при разборе команды. неверный формат команды.',
		'-110' => 'запросы отсылаются не с того IP адреса, который указан при регистрации данного интерфейса в Технической поддержке.',
		'-1'   => 'неверное значение поля w3s.request/reqn',
		'-2'   => 'неверное значение поля w3s.request/sign',
		'-3'   => 'неверное значение поля w3s.request/trans/tranid',
		'-4'   => 'неверное значение поля w3s.request/trans/pursesrc',
		'-5'   => 'неверное значение поля w3s.request/trans/pursedest',
		'-6'   => 'неверное значение поля w3s.request/trans/amount',
		'-7'   => 'неверное значение поля w3s.request/trans/desc',
		'-8'   => 'слишком длинное поле w3s.request/trans/pcode',
		'-9'   => 'поле w3s.request/trans/pcode не должно быть пустым если w3s.request/trans/period > 0',
		'-10'  => 'поле w3s.request/trans/pcode должно быть пустым если w3s.request/trans/period = 0',
		'-11'  => 'неверное значение поля w3s.request/trans/wminvid',
		'-12'  => 'идентификатор переданный в поле w3s.request/wmid не зарегистрирован',
		'-14'  => 'проверка подписи не прошла',
		'-15'  => 'неверное значение поля w3s.request/wmid',
		'102'  => 'не выполнено условие постоянного увеличения значения параметра w3s.request/reqn',
		'103'  => 'транзакция с таким значением поля w3s.request/trans/tranid уже выполнялась',
		'110'  => 'нет доступа к интерфейсу',
		'111'  => 'попытка перевода с кошелька не принадлежащего WMID, которым подписывается запрос; при этом доверие не установлено.',
		'4'    => 'внутренняя ошибка при создании транзакции',
		'15'   => 'внутренняя ошибка при создании транзакции',
		'19'   => 'внутренняя ошибка при создании транзакции',
		'23'   => 'внутренняя ошибка при создании транзакции',
		'5'    => 'идентификатор отправителя не найден',
		'6'    => 'корреспондент не найден',
		'7'    => 'кошелек получателя не найден',
		'11'   => 'кошелек отправителя не найден',
		'13'   => 'сумма транзакции должна быть больше нуля',
		'17'   => 'недостаточно денег в кошельке для выполнения операции',
		'18'   => 'указанная транзакция (wmtransid) не найдена, возникает, например, когда указанная к возврату и завершению операция с протекцией уже завершена или возвращена',
		'20'   => 'указанный для завершения транзакции с протекцией код протекции неверен',
		'21'   => 'счет, по которому совершается оплата не найден',
		'22'   => 'по указанному счету оплата с протекцией не возможна',
		'25'   => 'время действия оплачиваемого счета закончилось',
		'26'   => 'в операции должны участвовать разные кошельки',
		'29'   => 'типы кошельков отличаются',
		'30'   => 'кошелек не поддерживает прямой перевод (например для кредитных кошельков C или D)',
		'32'   => 'плательщику необходимо заполнить персональную информацию на сайте Центра Аттестации',
		'34'   => 'плательщику необходимо заполнить персональную информацию на сайте Центра Аттестации',
		'35'   => 'плательщик не авторизован корреспондентом для выполнения данной операции',
		'58'   => 'превышен лимит средств на кошельках получателя',
		'72'   => 'Обслуживание на вывод средств в WME временно приостановлено, ознакомьтесь с требованиями Гаранта по идентификации',
		'73'   => 'Обслуживание получателя средств в WME временно приостановлено, ознакомьтесь с требованиями Гаранта по идентификации',
		'74'   => 'Обслуживание получателя средств в WME временно приостановлено, ознакомьтесь с требованиями Гаранта по идентификации',
		// refused
		// error
	];

	public $currency_default = 'USD';
	public $currency_allow = [
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
	];

	public $purse = null;
	public $purse_by_currency = [
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
	];

	// public $fee = 0.1; // 2%

	public $ip_filter = [
		'212.118.48.0/24',
		'212.158.173.0/24',
		'91.200.28.0/24',
		'91.227.52.0/24',
	];

	public $service_allow = [
		'WebMoney',
	];

	public $url_result = null;
	public $url_server = null;

	public function _init() {
		if( !$this->ENABLE ) { return( null ); }
		// default
		$purse = $this->_purse_by_currency([ 'is_key' => false ]);
		if( $purse[ 'status' ] === false ) { throw new InvalidArgumentException( $purse[ 'status_message' ] ); }
		// load api
		$provider_path = __DIR__ . '/payment_provider/webmoney';
		// CA: https://wiki.wmtransfer.com/projects/webmoney/wiki/WebMoney_root_certificate
		$this->CA = $provider_path . '/WebMoneyCA.pem';
		require_once( $provider_path .  '/WebMoney.php' );
		$this->api = new WebMoney( $purse[ 'id' ], $purse[ 'key' ], $purse[ 'hash_method' ] );
		$this->url_result = url_user( '/api/payment/provider?name=webmoney&operation=response' );
		$this->url_server = url_user( '/api/payment/provider?name=webmoney&operation=response&server=true' );
		// DEBUG
		$is_test = $this->is_test();
		if( $is_test ) {
			$this->SHOP_ID = 21;
		}
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
			$result = [
				'status'         => false,
				'status_message' => 'Неизвестный код валюты',
			];
			return( $result );
		}
		// purse
		$purse = &$this->purse_by_currency;
		if( ! @$purse[ $currency_id ] ) {
			$result = [
				'status'         => false,
				'status_message' => 'Неизвестная валюта',
			];
			return( $result );
		}
		if( ! @$purse[ $currency_id ][ 'active' ] ) {
			$result = [
				'status'         => false,
				'status_message' => 'Валюта не активна',
			];
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
		// START DUMP
		$payment_api = $this->payment_api;
		$payment_api->dump([ 'name' => 'WebMoney', 'operation_id' => @(int)$_[ 'data' ][ 'operation_id' ] ]);
		$is_array = (bool)$_[ 'is_array' ];
		$form_options = $this->_form_options( $data );
		// DUMP
		$payment_api->dump([ 'var' => $form_options ]);
		$url = $this->URL;
		$result = [];
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
		$operation = $payment_api->operation( [
			'operation_id' => $operation_id,
		]);
		if( empty( $operation ) ) { return( null ); }
		return( $operation );
	}

	public function __api_response__check( $operation_id, $response ) {
		if( !$this->ENABLE ) { return( null ); }
		$payment_api = $this->payment_api;
		// check response options
		$operation = $this->_get_operation( $response );
		if( !is_array( $operation[ 'options' ][ 'request' ][0][ 'data' ] ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Неверный ответ: отсутствуют данные операции',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
		$request = @$operation[ 'options' ][ 'request' ][0][ 'data' ];
		// check operation_id
		if( @$request[ 'operation_id' ] != @$response[ 'operation_id' ] ) {
			$result = [
				'status'         => false,
				'status_message' => 'Неверный ответ: operation_id',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
		// check amount
		$fail_amount = (empty($request[ 'amount_currency' ]) || empty($response[ 'amount' ]) ) ? true : false;
		if(!$fail_amount) {
			$request_amount = floatval($request['amount_currency']);
			$response_amount = floatval($response['amount']);
		}
		if($fail_amount || abs($response_amount-$request_amount)>0.001){
			$result = [
				'status'         => false,
				'status_message' => 'Неверный ответ: amount',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
		// check payee purse
		$purse = $this->_purse_by_currency( $request );
		if( @$response[ 'key_public' ] != @$purse[ 'id' ] ) {
			$result = [
				'status'         => false,
				'status_message' => 'Неверный ответ: payee purse',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
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
		$operation_options = [
			'response' => [ [
				'data'     => $response,
				'datetime' => $sql_datetime,
			]],
		];
		$operation_update_data = [
			'operation_id'    => $operation_id,
			'options'         => $operation_options,
		];
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
			$result = [
				'status'         => false,
				'status_message' => 'Пустая подпись',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
		$_signature = $this->signature( $response, $is_request = false );
		if( $signature != $_signature ) {
			$result = [
				'status'         => false,
				'status_message' => 'Неверная подпись',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
		// save options
		$sql_datetime = $payment_api->sql_datetime();
		$operation_options = [
			'response' => [ [
				'data'     => $_response,
				'datetime' => $sql_datetime,
			]]
		];
		$result = $payment_api->operation_update( [
			'operation_id' => $operation_id,
			'options'      => $operation_options,
		]);
		if( !$result[ 'status' ] ) { return( $result ); }
		$result = [
			'status'         => true,
			'status_message' => 'Поплнение через сервис: WebMoney',
		];
		return( $result );
	}

	public function __api_response__success( $operation_id, $response ) {
		if( !$this->ENABLE ) { return( null ); }
		$payment_api = $this->payment_api;
		if( empty( $response[ 'LMI_SYS_INVS_NO' ] )
			|| empty( $response[ 'LMI_SYS_TRANS_NO' ] )
			|| empty( $response[ 'LMI_SYS_TRANS_DATE' ] )
		) {
			$result = [
				'status'         => false,
				'status_message' => 'Неверный ответ: отсутствуют данные транзакции',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
		// check response options
		$_response = $this->_response_parse( $response );
		$operation = $this->_get_operation( $_response );
		if( !is_array( $operation[ 'options' ][ 'response' ][0][ 'data' ] ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Неверный ответ: отсутствуют данные операции',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
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
			$result = [
				'status'         => false,
				'status_message' => 'Неверный ответ: данные операции не совпадают',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
		return( true );
	}

	public function __api_response__fail( $response ) {
		if( !$this->ENABLE ) { return( null ); }
		$payment_api = $this->payment_api;
		$result = [
			'status'         => false,
			'status_message' => 'Отказано в транзакции',
		];
		// DUMP
		$payment_api->dump([ 'var' => $result ]);
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
		$payment_api->dump( [ 'name' => 'WebMoney', 'operation_id' => (int)$operation_id ]);
		// check ip
		if( $is_server ) {
			$ip_allow = $this->_check_ip();
			if( $ip_allow === false ) {
				// DUMP
				$payment_api->dump( [ 'var' => 'ip not allow' ]);
				return( null );
			}
		}
		// response
		$response = @$_POST;
		// prerequest is empty
		if( ! @$response ) {
			// DUMP
			$payment_api->dump([ 'var' => [ 'PREREQUEST' => 'is empty' ]]);
			$result = [ 'is_raw' => true, 'OK' ];
			return( $result );
		}
		// prerequest
		if( @$response[ 'LMI_PREREQUEST' ] ) {
			// DUMP
			$payment_api->dump([ 'var' => [ 'PREREQUEST' => 'YES' ]]);
			$result = $this->__api_response__prerequest( $operation_id, $response );
			$state = ( $result === true ? 'YES' : 'NO' );
			$result = [ 'is_raw' => true, $state ];
			return( $result );
		}
		$_response = $this->_response_parse( $response );
		// check operation_id
		if( $operation_id != (int)$_response[ 'operation_id' ] ) {
			$result = [
				'status'         => false,
				'status_message' => 'Неверный код операции',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
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
		$result = $this->_api_deposition( [
			'provider_name'  => 'webmoney',
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
		if( !empty( $_[ 'title' ] ) ) {
			$_[ 'title' ] = iconv( 'Windows-1251', 'UTF-8', $_[ 'title' ] );
		}
		return( $_ );
	}

	public function _array2xml( $items = null ) {
		// init xml
		$xml = $this->_xml();
		// add items
		$this->_xml_add( $xml, $items );
		// to string
		$result = $xml->asXML();
		// desc
		$result = iconv( 'UTF-8', 'Windows-1251', $result );
		// $lines = explode( "\n", $result, 2 );
		// if( preg_match( '/^\<\?xml/', $lines[0] ) ) {
			// $result = $lines[ 1 ];
		// }
		// $result = trim( $result );
		return( $result );
	}

	public function _xml_add( &$xml, $items ) {
		if( !( @$xml instanceof SimpleXMLElement ) || !@$items ) { return( null ); }
		foreach( $items as $key => $value ) {
			if( is_array( $value ) ) {
					$node = $xml->addChild( $key );
					$this->_xml_add( $node, $value );
			} else {
				$xml->addChild( $key, htmlspecialchars( $value ) );
			}
		}
		return( true );
	}

	public function _xml( $options = null ) {
		$result = new SimpleXMLElement( '<w3s.request></w3s.request>' );
		$result->addChild( 'reqn', $this->api_request_xml__reqn() );
		return( $result );
	}

	public function api_request_xml__reqn( $options = null ) {
		list( $msec, $sec ) = explode( ' ', substr( microtime(), 2 ) );
		$result = $sec . substr($msec, 0, 5);
		return( $result );
	}

	public function api_request__X_fields( &$data, &$fields, $options ) {
		foreach( $fields as $field ) {
			if( isset( $options[ $field ] ) ) {
				$data[ $field ] = $options[ $field ];
			}
		}
		// amount
		if( isset( $data[ 'amount' ] ) ) {
			// X2: незначащие нули в конце и точка, если число целое, должны отсутствовать
			//     например: 10.50 - не верно, 10.5 - верно, 9. - не верно, 9 - верно
			$payment_api = $this->payment_api;
			$amount = $payment_api->_number_api( $data[ 'amount' ] );
			if( strpos( $amount, '.' ) !== false ) {
				$data[ 'amount' ] = rtrim( $amount, '.0' );
			}
		}
	}

	public function api_request__X9( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! @$_method ) {
			$result = [
				'status'         => false,
				'status_message' => 'Метод запроса не задан',
			];
			return( $result );
		}
		// data
		$options[ 'option' ] = [
			'getpurses' => [
				'wmid' => @$_option[ 'wmid' ],
			],
		];
		// send
		$result = $this->api_request_send( $options );
		return( $result );
	}

	public function api_request__X2( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! @$_method ) {
			$result = [
				'status'         => false,
				'status_message' => 'Метод запроса не задан',
			];
			return( $result );
		}
		// data
		$data = [
			'tranid'    => 0,
			'period'    => 0,
			'wminvid'   => 0,
			'onlyauth'  => 1,
		];
		$fields = [
			'tranid',
			'pursesrc',
			'pursedest',
			'amount',
			'desc',
		];
		$this->api_request__X_fields( $data, $fields, @$_option );
		$options[ 'option' ] = [ 'trans' => $data ];
		// send
		$result = $this->api_request_send( $options );
		return( $result );
	}

	public function api_request( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		if( is_string( $options ) ) { $_method_id = $options; }
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// method
		if( !@$_method ) {
			$_method = $this->api_method( [
				'type'      => 'api',
				'method_id' => @$_method_id,
			]);
		}
		$method = $_method;
		if( !@$method ) {
			$result = [
				'status'         => false,
				'status_message' => 'Метод запроса не найден',
			];
			return( $result );
		}
		$options[ 'method' ] = $method;
		// method handler
		if( @$method[ 'is_handler' ] ) {
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
		$result = $this->api_request_send( $options );
		return( $result );
	}

	public function api_request_send( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! @$_method ) {
			$result = [
				'status'         => false,
				'status_message' => 'Метод запроса не задан',
			];
			return( $result );
		}
		// request
		$request = [];
		if( is_string( @$_option ) ) {
			$request = $_option;
		} elseif( is_array( @$_option ) ) {
			$request = $this->_array2xml( $_option );
		}
		// url
		$object = $this->api_url( $_method, $options );
		if( isset( $object[ 'status' ] ) && $object[ 'status' ] === false ) { return( $object ); }
		$url = $object;
		// request options
		$request_option = [];
		@$_is_debug && $request_option[ 'is_debug' ] = true;
		// CA
		$request_option[ 'CA' ] = $this->CA;
		// authorization: client crt/key (WebPro)
		$request_option[ 'SSLCERT' ] = $this->SSL[ 'crt' ];
		$request_option[ 'SSLKEY'  ] = $this->SSL[ 'key' ];
		// request
// DEBUG
// var_dump( $url, $_option, $request, $request_option );
// exit;
		$request_option[ 'is_request_raw' ] = true;
		$result = $this->_api_request( $url, $request, $request_option );
		return( $result );
	}

	public function api_request__X_tranid( $operation_id = null ) {
		if( !@$this->SHOP_ID || !@$operation_id ) { return( $operation_id ); }
		$result = sprintf( '%02d%08d', $this->SHOP_ID, $operation_id );
		return( $result );
	}

	public function api_request__X_tranid_reverse( $operation_id = null ) {
		if( !@$this->SHOP_ID || !@$operation_id ) { return( $operation_id ); }
		$result = (int)substr( $operation_id, 2 );
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
		// purse
		$object = $this->_purse_by_currency([ 'currency_id' => $currency_id ]);
		if( $object[ 'status' ] === false ) { return( $object ); }
		$purse = $object;
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
		$request[ 'tranid'         ] = $this->api_request__X_tranid( $operation_id );
		// amount
		$this->is_test() && $amount = '0.01';
		$request[ 'amount' ] = $amount;
		// pursesrc
		$request[ 'pursesrc' ] = $purse[ 'id' ];
		// pursedest
		@$_purse && $request[ 'pursedest' ] = $_purse;
		// title
		@$_title           && $request[ 'title' ] = $_title;
		@$_operation_title && $request[ 'title' ] = $_operation_title;
		// transform
		$this->option_transform( [
			'option'    => &$request,
			'transform' => $this->_api_X,
		]);
		// add fields
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
// var_dump( $options, $request );
// exit;
		// START DUMP
		$payment_api->dump( [ 'name' => 'WebMoney', 'operation_id' => $operation_id,
			'var' => [ 'request' => $request ]
		]);
		// update processing
		$sql_datetime = $payment_api->sql_datetime();
		$operation_options = [
			'processing' => [ [
				'provider_name' => 'webmoney',
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
			'method_id' => 'p2p',
			'option'    => $request,
		];
		@$_is_debug && $request_option[ 'is_debug' ] = true;
// DEBUG
// var_dump( $request_option );
		$result = $this->api_request( $request_option );
// DEBUG
// var_dump( $request, $result );
// exit;
		// DUMP
		$payment_api->dump( [ 'var' => [ 'response' => $result ]]);
		if( isset( $result[ 'status' ] ) && $result[ 'status' ] == false ) { return( $result ); }
		if( ! @$result ) {
			$result = [
				'status'         => false,
				'status_message' => 'Невозможно отправить запрос',
			];
			return( $result );
		}
		@list( $status, $response ) = $result;
		if( !@$response || !( @$response instanceof SimpleXMLElement ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Невозможно декодировать ответ: '. var_export( $response, true ),
			];
			return( $result );
		}
// DEBUG
// var_dump( $request, $result, (array)$response );
// exit;
		// transform reverse
		/*
		foreach( $this->_api_X_reverse as $from => $to ) {
			if( $from != $to && isset( $response[ $from ] ) ) {
				$response[ $to ] = $response[ $from ];
				unset( $response[ $from ] );
			}
		}
		*/
		// result
		list( $request_status, $state, $result ) = $this->_payout_status_handler( $response );
// DEBUG
// var_dump( $request_status, $state, $result ); exit;
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
			'provider_name'  => 'webmoney',
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

	public function _payout_status_handler( $xml ) {
		if( !$this->ENABLE ) { return( null ); }
		$request_status = false;
		$status_name    = false;
		$status_message = null;
		$result = [
			'status'         => &$status_name,
			'status_message' => &$status_message,
		];
		$state = null;
		$error = null;
		if( property_exists( $xml, 'retval' ) ) {
			$state =    (int)$xml->retval;
			$error = (string)$xml->retdesc;
		}
		$status = $state;
		switch( true ) {
			// success
			case $state === 0:
				$request_status = true;
				break;
			// unknown
			case is_null( $state ):
				$status         = 'unknown';
				$request_status = null;
				break;
			// error
			default:
				$request_status = true;
				break;
		}
// DEBUG
// var_dump( 'state:', $state, $error, $status ); exit;
		// check status
		list( $status_name, $status_message ) = $this->_state( $status
			, $this->_payout_status
			, $this->_payout_status_message
		);
		if( !$status_name ) {
			$status_name    = 'in_progress';
			$status_message = $error ?: 'Неизвестный код ошибки: '. $state;
		}
// DEBUG
// var_dump( 'state:', $state, $status, $status_name, $status_message ); exit;
		// result
		$state = $status_name ?: $status;
		$status_message = @$status_message ?: $state;
		return( [ $request_status, $state, $result ] );
	}

}
