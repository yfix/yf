<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_interkassa extends yf_payment_api__provider_remote {

	public $URL              = 'https://sci.interkassa.com/';
	public $KEY_PUBLIC       = null;  // Checkout ID, Идентификатор кассы
	public $KEY_PRIVATE      = null;  // secret key
	public $KEY_PRIVATE_TEST = null;  // secret key for test
	public $HASH_METHOD      = 'md5'; // signature hash method: md5, sha256

	public $IS_DEPOSITION = true;
	// public $IS_PAYMENT    = true;

	public $URL_API          = 'https://api.interkassa.com/v1/%method';
	public $API_ACCOUNT      = null; // api header: Ik-Api-Account-Id

	public $method_allow = array(
		'order' => array(
			'payin' => array(
				'interkassa',
			),
			'payout' => array(
				'visa_p2p_privat_uah',
			),
		),
		'api' => array(
			// Список используемых в системе валют и курсов
			'currency' => array(
				'uri' => array(
					'%method' => 'currency',
				),
				// 'option' => array(
					// 'active' => true,
				// ),
			),
			// Список платежных направлений на ввод, включенных в системе ИК
			'paysystem-input-payway' => array(
				'uri' => array(
					'%method' => 'paysystem-input-payway',
				),
			),
			// Список платежных направлений на вывод, включенных в системе ИК
			'paysystem-output-payway' => array(
				'uri' => array(
					'%method' => 'paysystem-output-payway',
				),
			),
			// Список аккаунтов, доступных пользователю
			'account' => array(
				'is_authorization' => true,
				'uri' => array(
					'%method' => 'account',
				),
			),
			// Список касс, привязанных к аккаунту
			'checkout' => array(
				'is_authorization' => true,
				'is_api_account'   => true,
				'uri' => array(
					'%method' => 'checkout',
				),
			),
			// Список бизнес касс, привязанных к аккаунту
			'checkout-b' => array(
				'is_authorization' => true,
				'is_handler'       => 'checkout_b',
			),
			// Список кошельков, привязанных к аккаунту, с их параметрами
			'purse' => array(
				'is_authorization' => true,
				'is_api_account'   => true,
				'uri' => array(
					'%method' => 'purse',
				),
			),
			// Позволяет получить выгрузку платежей
			'co-invoice' => array(
				'is_authorization' => true,
				'is_api_account'   => true,
				'uri' => array(
					'%method' => 'co-invoice',
				),
			),
			// GET
			// - список осуществленных выводов
			// - информацию по конкретному выводу
			// POST
			// - создать новый вывод в системе
			'withdraw' => array(
				'is_authorization' => true,
				'is_api_account'   => true,
				'uri' => array(
					'%method' => 'withdraw',
				),
			),
			'withdraw-id' => array(
				'is_authorization' => true,
				'is_api_account'   => true,
				'url' => 'https://api.interkassa.com/v1/%method/%id',
				'uri' => array(
					'%method' => 'withdraw',
					'%id'     => '$id',
				),
			),
			'withdraw-calc' => array(
				'is_authorization' => true,
				'is_api_account'   => true,
				'uri' => array(
					'%method' => 'withdraw',
				),
				'option' => array(
					'action' => 'calc',
				),
			),
			'withdraw-process' => array(
				'is_authorization' => true,
				'is_api_account'   => true,
				'uri' => array(
					'%method' => 'withdraw',
				),
				'option' => array(
					// 'action' => 'process',
					'action' => 'calc',
				),
			),
		),
		'payin' => array(
			'interkassa' => array(
				'title'       => 'Visa, MasterCard',
				'icon'        => 'interkassa',
				'amount_min'  => 100,
				'fee'         => 0, // 0.1%
				'currency' => array(
					// 'USD' => array(
						// 'currency_id' => 'USD',
						// 'active'      => true,
					// ),
					// 'EUR' => array(
						// 'currency_id' => 'EUR',
						// 'active'      => true,
					// ),
					'UAH' => array(
						'currency_id' => 'UAH',
						'active'      => true,
					),
					// 'RUB' => array(
						// 'currency_id' => 'RUB',
						// 'active'      => true,
					// ),
				),
			),
		),
		'payout' => array(
			'visa_p2p_privat_uah' => array(
				'title'      => 'Visa (Privat24, UAH)',
				'icon'       => 'visa',
				'uri'        => array(
					'%method' => 'withdraw',
				),
				'action'     => 'visa_p2p_privat_uah',
				'amount'     => array(
					'min' => 50,
					'max' => 10000,
				),
				'_fee' => array(
					'out' => array(
						'rt'  => 1,
						'fix' => 10,
					),
				),
				'currency' => array(
					'UAH' => array(
						'currency_id' => 'UAH',
						'is_int'      => true,
						'active'      => true,
					),
				),
				'field' => array(
					'card',
				),
				'order' => array(
					'card',
				),
				'option' => array(
					'card' => 'Номер карты',
				),
				'option_validation_js' => array(
					'card'                       => array(
						'type'      => 'text',
						'required'  => true,
						'minlength' => 13,
						'maxlength' => 16,
						// 'pattern'   => '^\d+$',
						'pattern'   => '^4((40588)|(14949)|(32339)|(32334)|(32338)|(32340)|(40535)|(73117)|(73121)|(13051)|(40509)|(24600)|(62708)|(76065)|(17649)|(32337)|(62705)|(14943)|(14961)|(14962)|(32575)|(58121)|(58122)|(14939)|(14960)|(24657)|(34156)|(32335)|(23396)|(73118)|(32336)|(40129)|(76339)|(14963)|(73114)|(04030)|(58120)|(10653))[0-9]{7}(?:[0-9]{3})?$',
					),
				),
				'option_validation' => array(
					'card' => 'required|length[13,16]|regex:~^4((40588)\|(14949)\|(32339)\|(32334)\|(32338)\|(32340)\|(40535)\|(73117)\|(73121)\|(13051)\|(40509)\|(24600)\|(62708)\|(76065)\|(17649)\|(32337)\|(62705)\|(14943)\|(14961)\|(14962)\|(32575)\|(58121)\|(58122)\|(14939)\|(14960)\|(24657)\|(34156)\|(32335)\|(23396)\|(73118)\|(32336)\|(40129)\|(76339)\|(14963)\|(73114)\|(04030)\|(58120)\|(10653))[0-9]{7}(?:[0-9]{3})?$~',
				),
				'option_validation_message' => array(
					'card' => 'обязательное поле от 13 до 16 цифр',
				),
			),
		),
	);

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

	public $_status = array(
		'success'    => 'success',
		'new'        => 'in_progress',
		'waitAccept' => 'in_progress',
		'process'    => 'in_progress',
		'fail'       => 'refused',
		'canceled'   => 'refused',
	);

	public $currency_default = 'USD';
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
		'Visa',
		'Mastercard',
		// 'WebMoney',
		// 'LiqPay',
		// 'Privat24',
		// 'Yandex.Money',
		// 'Единый кошелек',
		// 'PerfectMoney',
		// 'Почта России',
		// 'Юнистрим',
		// 'Салоны связи',
		// 'Альфаклик (Альфабанк)',
		// 'Anelik',
		// 'ЛИДЕР',
		// 'Qiwi Кошелек',
		// 'Украинский банк',
		// 'Российский банк',
		// 'Терминалы России',
		// 'Терминалы Украины',
		// 'Тестовая платежная система',
		// 'Салоны связи «Альт-телеком»',
		// 'SWIFT Банковский перевод',
		// 'Интернет-банк «Связной Банк»',
		// 'Салоны связи «Форвард Мобайл»',
		// 'Интернет-банк «PSB-Retail» («Промсвязьбанк»)',
		// 'Сбербанк ОнЛ@йн',
		// 'OKPay',
		// 'Payeer',
		// 'Салоны связи «Диксис»',
		// 'Салоны связи «Евросеть»',
		// 'Салоны связи «Связной»',
		// 'Салоны связи «Цифроград»',
		// 'Салоны связи «Сотовый мир»',
	);

	public $url_result = null;
	public $url_server = null;

	public function _init() {
		if( !$this->ENABLE ) { return( null ); }
		$this->payment_api = _class( 'payment_api' );
		// load api
		require_once( __DIR__ . '/payment_provider/interkassa/Interkassa.php' );
		$this->api = new Interkassa( $this->KEY_PUBLIC, $this->KEY_PRIVATE, $this->KEY_PRIVATE_TEST, $this->HASH_METHOD, $this->TEST_MODE );
		$this->url_result = url_user( '/api/payment/provider?name=interkassa&operation=response' );
		$this->url_server = url_user( '/api/payment/provider?name=interkassa&operation=response&server=true' );
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
		$this->key( 'public',       $this->KEY_PUBLIC       );
		$this->key( 'private',      $this->KEY_PRIVATE      );
		$this->key( 'private_test', $this->KEY_PRIVATE_TEST );
	}

	public function hash_method( $value = null ) {
		if( !$this->ENABLE ) { return( null ); }
		$value = $this->api->hash_method( $value );
		return( $value );
	}

	public function hash_method_reset() {
		if( !$this->ENABLE ) { return( null ); }
		$this->api->hash_method( $this->HASH_METHOD );
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
		// url
		if( !empty( $_[ 'url_result' ] )
			|| empty( $_[ 'ik_suc_uc' ] )
			|| empty( $_[ 'ik_pnd_u'  ] )
			|| empty( $_[ 'ik_fal_u'  ] )
		) {
			$url = $this->_url( $options );
			$_[ 'ik_suc_u' ] = $url . '&status=success';
			$_[ 'ik_pnd_u' ] = $url . '&status=pending';
			$_[ 'ik_fal_u' ] = $url . '&status=fail';
			$_[ 'ik_suc_m' ] = 'post';
			$_[ 'ik_pnd_m' ] = 'post';
			$_[ 'ik_fal_m' ] = 'post';
			unset( $_[ 'url_result' ] );
		}
		if( !empty( $_[ 'url_server' ] ) || empty( $_[ 'ik_ia_u' ] ) ) {
			$url_server = $this->_url( $options, $is_server = true );
			$_[ 'ik_ia_u' ] = $url_server . '&status=interaction';
			$_[ 'ik_ia_m' ] = 'post';
			unset( $_[ 'url_server' ] );
		}
		// default
		$_[ 'ik_am' ] = number_format( $_[ 'ik_am' ], 2, '.', '' );
		empty( $_[ 'ik_co_id'   ] ) && $_[ 'ik_co_id'   ] = $this->KEY_PUBLIC;
		if( empty( $_[ 'ik_am' ] ) || empty( $_[ 'ik_co_id' ] ) ) { $_ = null; }
		if( !empty( $this->TEST_MODE ) || !empty( $_[ 'test_mode' ] ) ) {
			unset( $_[ 'test_mode' ] );
			$_[ 'ik_act'    ] = 'payway';
			$_[ 'ik_pw_via' ] = 'test_interkassa_test_xts';
		}
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
		if( !$this->ENABLE ) { return( null ); }
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
		list( $status_name, $status_message ) = $this->_state( $state );
		// test
		// $response[ 'operation_id' ] = '3304';
		// update account, operation data
		$result = $this->_api_deposition( array(
			'provider_name'  => 'interkassa',
			'response'       => $response,
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
		return( $_ );
	}

	public function get_currency( $options ) {
		if( !$this->ENABLE ) { return( null ); }
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

	public function api_request__checkout_b( $options = null ) {
		// get business account_id
		list( $status, $account ) = $this->api_request( 'account' );
		if( empty( $status )
			|| !empty( $account[ 'code' ] )
			|| empty( $account[ 'data' ] )
		) {
			$result = array(
				'status'         => false,
				'status_message' => 'Ошибка при запросе бизнес счета',
			);
			return( $result );
		}
		// find business account_id
		$account_id = null;
		foreach( $account[ 'data' ] as $id => $item ) {
			if( @$item[ 'tp' ] == 'b' ) {
				$account_id = $item[ '_id' ];
				break;
			}
		}
		// get business account
		$request_options = array(
			'method_id' => 'checkout',
			'header'    => array(
				'Ik-Api-Account-Id: '. $account_id,
			),
		);
		$result = $this->api_request( $request_options );
		return( $result );
	}

	public function api_account( $options = null ) {
		// var
		$account_id = @$this->API_ACCOUNT;
		if( empty( $account_id ) ) { return( null ); }
		// business account id
		$result = array(
			'header'    => array(
				'Ik-Api-Account-Id: '. $account_id,
			),
		);
		return( $result );
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
		!empty( $_option ) && $request = $_option;
		// add options
		!empty( $method[ 'option' ] ) && $request = array_merge_recursive(
			$request, $method[ 'option' ]
		);
		// url
		$object = $this->api_url( $method, $options );
		var_dump( $object );
		if( @$object[ 'status' ] === false ) { return( $object ); }
		$url = $object;
		// request options
		$request_options = array();
		@$_is_debug && $request_options[ 'is_debug' ] = true;
			// api authorization
			$_request_options = $this->api_authorization( $method );
			is_array( $_request_options ) && $request_options = array_merge_recursive( $request_options, $_request_options );
			// api account
			$_request_options = $this->api_account( $method );
			is_array( $_request_options ) && $request_options = array_merge_recursive( $request_options, $_request_options );
			// header
			is_array( $_header ) && $request_options = array_merge_recursive( $request_options, array( 'header' => $_header ) );
		// request
// DEBUG
// var_dump( $url, $request, $request_options );
// exit;
		$result = $this->_api_request( $url, $request, $request_options );
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
		// currency conversion
		$amount_currency = $payment_api->currency_conversion( array(
			'conversion_type' => 'sell',
			'currency_id'     => $currency_id,
			'amount'          => $_amount,
		));
		if( empty( $amount_currency ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Невозможно произвести конвертацию валют',
			);
			return( $result );
		}
		// fee
		$fee = $this->get_fee_payout( $options );
		$amount_currency_total = $payment_api->fee( $amount_currency, $fee );
		// transform
		foreach( $this->_api_transform as $from => $to ) {
			$_from = '_'.$from;
			$_to   = '_'.$to;
			$f = &${ $_from };
			$t = &${ $_to   };
			if( isset( $f ) && $_from != $_to ) { $t = $f; unset( ${ $_from } ); }
		}
		// default
		// $_currency = $currency_id;
		// $_amount = $this->_amount_payout( $amount_currency_total, $_currency, $is_request = true );
		$_amount = $this->_amount_payout( $_amount, $_currency_id, $method, $is_request = true );
		!isset( $_site_id ) && $_site_id = $this->key( 'public' );
		!isset( $_comment ) && $_comment = t( 'Вывод средств (id: ' . $_external_id . ')' );
		!isset( $_action  ) && $_action = $method[ 'action' ];
		is_string( $_sender_phone ) && $_sender_phone = str_replace( array( ' ', '-', '+', ), '', $_sender_phone );
		// check required
		$request = array();
		foreach( $method[ 'field' ] as $key ) {
			$value = &${ '_'.$key };
			if( !isset( $value ) ) {
				$result = array(
					'status'         => false,
					'status_message' => 'Отсутствуют данные запроса: '. $key,
				);
				continue;
				// return( $result );
			}
			$request[ $key ] = &${ '_'.$key };
		}
		// signature
		$signature = $this->api->signature( $request );
		if( empty( $signature ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Ошибка генерации подписи',
			);
			return( $result );
		}
		$request[ 'signature' ] = $signature;
// DEBUG
// var_dump( $request );
$payment_api->dump( array( 'var' => $request ));
		// request
		// url
		$object  = $this->api_url( $method, $options );
		if( @$object[ 'status' ] === false ) { return( $object ); }
		$url = $object;
		$data = http_build_query( $request );
		$result = $this->_api_request( $url, $data );
// DEBUG
$payment_api->dump( array( 'var' => $result ));
// var_dump( $result );
		if( empty( $result ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Невозможно отправить запрос',
			);
			return( $result );
		}
		list( $status, $response ) = $result;
		// DEBUG
		/*
		$response = '
			{
			"code"              : 0,
			"message"           : "Success.",
			"acquirer_id"       : "552e1df177a9e",
			"transaction_id"    : "42169",
			"processor_id"      : "1",
			"processor_code"    : "00",
			"processor_message" : "SUCCESS",
			"amount"            : "89",
			"currency"          : "RUB",
			"real_amount"       : "89",
			"real_currency"     : "RUB",
			"external_id"       : "24563",
			"authcode"          : "6Y8A0C"
			}
		'; //*/
		$response = json_decode( $response, true );
// DEBUG
// var_dump( $result, $response );
		if( is_null( $response ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Невозможно декодировать ответа',
			);
			return( $result );
		}
		// result
		$result = array(
			'status'         => &$status,
			'status_message' => &$status_message,
		);
		$status = 'refused';
		// transform reverse
		foreach( $this->_api_transform_reverse as $from => $to ) {
			if( $from != $to && isset( $response[ $from ] ) ) {
				$response[ $to ] = $response[ $from ];
				unset( $response[ $from ] );
			}
		}
		$operation_status_name = 'refused';
		$state = (int)$response[ 'state' ];
		switch( $state ) {
			// success
			case 0:
				$operation_status_name = 'success';
				if( $response[ 'amount' ] == $_amount
					&& $response[ 'operation_id' ] == $operation_id
				) {
					$status         = 'success';
					$status_message = 'Выполнено';
				} else {
					$status         = 'in_progress';
					$status_message = 'Выполнено, но сумма или код операции не совпадают';
				}
				break;
			// in progress
			case 50:
				$status         = 'in_progress';
				$status_message = 'В процессе';
				break;
			// fails...
			case 2:
				$status_message = 'Доступ запрещен';
				break;
			case 101:
				$status_message = 'Неверный номер карты ' . $request[ 'card' ];
				break;
			case 126:
				$status_message = 'Неверный номер телефона ' . $request[ 'sender_phone' ];
				break;
			case 421:
				$status_message = 'Неверные данные запроса';
				break;
			case 113:
				$status_message = 'Выплата отключена';
				break;
			case 908:
				$status_message = 'Выплата уже произведена ранее';
				break;
			default:
				$status_message = 'Ошибка при выполнении ('. $state .' - '. $response[ 'message' ] .')';
				break;
		}
		$status_message = $_comment .' - '. $status_message;
		// save response
		empty( $response[ 'message' ] ) && $response[ 'message' ] = $status_message;
		$sql_datetime = $payment_api->sql_datetime();
		$operation_options = array(
			'response' => array( array(
				'data'     => $response,
				'datetime' => $sql_datetime,
			))
		);
		$operation_update_data = array(
			'operation_id'    => $operation_id,
			'datetime_update' => $sql_datetime,
			'options'         => $operation_options,
		);
		$payment_api->operation_update( $operation_update_data );
		return( $result );
	}

	public function deposition( $options ) {
		if( !$this->ENABLE ) { return( null ); }
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
