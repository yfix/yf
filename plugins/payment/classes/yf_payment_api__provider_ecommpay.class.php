<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_ecommpay extends yf_payment_api__provider_remote {

	public $URL              = 'https://terminal.ecommpay.com/';
	public $URL_TEST         = 'https://terminal-sandbox.ecommpay.com/';
	public $KEY_PUBLIC       = null;  // site id
	public $KEY_PRIVATE      = null;  // salt

	// public $IS_DEPOSITION = true;
	// public $IS_PAYMENT    = true;

	public $URL_API          = 'https://gate.ecommpay.com/card/json/';
	public $URL_API_TEST     = 'https://gate-sandbox.ecommpay.com/card/json/';

	public $method_allow = array(
		'order' => array(
			'payin' => array(
				'ecommpay',
				'card',
				'qiwi',
				'c24',
				'comepay',
			),
			'payout' => array(
				'pay_card'
			),
		),
		'payin' => array(
			'ecommpay' => array(
				'title'       => 'Visa, MasterCard',
				'icon'        => 'ecommpay',
				'amount_min'  => 100,
				'fee'         => 0, // 0.1%
				'currency' => array(
					'USD' => array(
						'currency_id' => 'USD',
						'active'      => true,
					),
				),
			),
			'card' => array(
				'title'       => 'Visa, MasterCard',
				'icon'        => 'visa-mastercard',
				'option' => array(
					'payment_group_id' => 1,
					// 'followup'         => 1,
				),
				'amount_min'  => 100,
				'fee'         => 0, // 0.1%
				'currency' => array(
					'USD' => array(
						'currency_id' => 'USD',
						'active'      => true,
					),
				),
			),
			'qiwi' => array(
				'title'       => 'Qiwi',
				'icon'        => 'qiwi',
				'option' => array(
					// 'payment_group_id'         => 6,
					// 'followup'                 => 1,
					// 'phone'                    => '380679041321',
					// 'external_payment_type_id' => 'qw',
				),
				'amount_min'  => 100,
				'fee'         => 0, // 0.1%
				'currency' => array(
					'USD' => array(
						'currency_id' => 'USD',
						'active'      => true,
					),
				),
			),
			'c24' => array(
				'title'       => 'C24',
				'icon'        => 'c24',
				'option' => array(
					// 'payment_group_id'         => 28,
					// 'followup'                 => 0,
					// 'external_payment_type_id' => 'qw',
				),
				'amount_min'  => 100,
				'fee'         => 0, // 0.1%
				'currency' => array(
					'USD' => array(
						'currency_id' => 'USD',
						'active'      => true,
					),
				),
			),
			'comepay' => array(
				'title'       => 'Comepay',
				'icon'        => 'comepay',
				'option' => array(
					// 'payment_group_id'         => 29,
					// 'followup'                 => 0,
					// 'external_payment_type_id' => 'qw',
				),
				'amount_min'  => 100,
				'fee'         => 0, // 0.1%
				'currency' => array(
					'USD' => array(
						'currency_id' => 'USD',
						'active'      => true,
					),
				),
			),
		),
		'payout' => array(
			'pay_card' => array(
				'title'       => 'Visa, MasterCard',
				'icon'        => 'visa-mastercard',
				'action'      => 'payout',
				'amount_min'  => 100,
				'fee'         => 0, // 0.1%
				'currency' => array(
					'RUB' => array(
						'currency_id' => 'RUB',
						'active'      => true,
					),
				),
				'field' => array(
					'action',
					'site_id',
					'amount',
					'currency',
					'external_id',
					// 'customer_ip',
					'comment',
					'card',
					'sender_first_name',
					'sender_last_name',
					'sender_middle_name',
					'sender_passport_number',
					'sender_passport_issue_date',
					'sender_passport_issued_by',
					'sender_phone',
					'sender_birthdate',
					'sender_address',
					'sender_city',
					'sender_postindex',
				),
				'option' => array(
					'card'                       => 'Номер карты',
					'sender_first_name'          => 'Имя',
					'sender_last_name'           => 'Фамилия',
					'sender_middle_name'         => 'Отчество',
					'sender_passport_number'     => 'Серия и номер паспорта',
					'sender_passport_issue_date' => 'Дата выдачи паспорта',
					'sender_passport_issued_by'  => 'Орган, выдавший паспорт',
					'sender_phone'               => 'Контактный телефон',
					'sender_birthdate'           => 'Дата рождения',
					'sender_address'             => 'Адрес',
					'sender_city'                => 'Город',
					'sender_postindex'           => 'Почтовый индекс',
				),
			),
		),
	);

	public $_api_transform = array(
		// '+' - required
		// '-' - not required
		// '?' - may be by conditions
		// 'amount'                 => 'amount',                     // + Numeric Сумма к выплате в валюте сайта
		'currency'               => 'currency',                   // - Enum Валюта
		'operation_id'           => 'external_id',                // + String Идентификатор заказа в системе продавца
		'transaction_id'         => 'transaction_id',             // ? Numeric ID успешного платежа по той банковской карте, на которую нужно сделать выплату.
		'force_disable_callback' => 'force_disable_callback',     // - Enum Отключить оповещения (callback) об операции. Допустимые значения - 1 (да, отключить), 0 (нет, высылать оповещения)
		'first_callback_delay'   => 'first_callback_delay',       // - Numeric Задержка перед отправкой первого оповещения
		'account'                => 'card',                       // ? Numeric(13,19) Номер банковской карт, на которую совершается выплата.
		'title'                  => 'comment',                    // + String(4096) Комментарий к запросу example_comment
		'first_name'          => 'sender_first_name',          // + String(255) Имя пользователя
		'last_name'           => 'sender_last_name',           // + String(255) Фамилия пользователя
		'middle_name'         => 'sender_middle_name',         // + String(255) Отчество пользователя
		'passport_number'     => 'sender_passport_number',     // + String(255) Серия и номер паспорта пользователя
		'passport_issue_date' => 'sender_passport_issue_date', // + String(255) Дата выдачи паспорта: 2002-01-31
		'passport_issued_by'  => 'sender_passport_issued_by',  // + String(255) Орган, выдавший паспорт
		'phone'               => 'sender_phone',               // + String(11) Контактный телефон пользователя.
		'birthdate'           => 'sender_birthdate',           // + Date Дата рождения пользователя: 1980-01-31
		'address'             => 'sender_address',             // + String(255) Адрес пользователя
		'city'                => 'sender_city',                // ? String(255) Город пользователя.
		'postindex'           => 'sender_postindex',           // ? String(255) Почтовый индекс пользователя.
	);

	public $_api_transform_reverse = array(
		'external_id' => 'operation_id',
		'code'        => 'state',
	);

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

	public $_status_response = array(
		'1'  => 'success',
		'2'  => 'refused',
	);
	public $_status_server = array(
		'1'  => 'in_progress', // initiated
		'2'  => 'in_progress', // external processing
		'3'  => 'in_progress', // awaiting confirmation
		'4'  => 'success',     // success
		'5'  => 'refused',     // void
		'6'  => 'refused',     // processor decline
		'7'  => 'refused',     // fraudstop decline
		'8'  => 'refused',     // mpi decline
		'9'  => 'refused',
		'10' => 'refused',     // system failure
		'11' => 'refused',     // unsupported protocol operation
		'12' => 'refused',     // protocol configuration error
		'13' => 'refused',     // transaction is expired
		'14' => 'refused',     // transaction rejected by user
		'15' => 'refused',     // internal decline
	);

	public $_type_server = array(
		// deposition         payout
		// 1 (authorization)  4  (void)
		// 3 (purchase)       5  (refund)
		// 6 (rebill)         11 (payout)
		'1'  => 'deposition',     // authorization Авторизация
		// '2'  => 'payout', // confirm Подтверждение авторизации
		'3'  => 'deposition',     // purchase Прямое списание
		'4'  => 'payout', // void Отмена авторизации
		'5'  => 'payout', // refund Возврат
		'6'  => 'deposition',     // rebill Рекуррентный платеж
		// '7'  => 'payout', // chargeback Опротестование платежа
		// '8'  => 'payout', // complete3ds Завершение платежа 3ds
		// '9'  => 'payout',
		// '10' => 'payout',
		'11' => 'payout', // payout Выплата
	);

	public $currency_default = 'USD';
	public $currency_allow = array(
		'USD' => array(
			'currency_id' => 'USD',
			'active'      => true,
		),
		/* 'EUR' => array(
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
		), */
	);

	// public $fee = 5; // 5%

	public $service_allow = array(
		'Visa, MasterCard',
	);

	public $provider_ip_allow = array(
		'78.140.183.154' => true,
		'204.26.61.98'   => true,
	);

	public $url_result = null;
	public $url_server = null;

	public function _init() {
		if( !$this->ENABLE ) { return( null ); }
		// load api
		require_once( __DIR__ . '/payment_provider/ecommpay/EcommPay.php' );
		$this->api = new EcommPay( $this->KEY_PUBLIC, $this->KEY_PRIVATE );
		$this->url_result = url_user( '/api/payment/provider?name=ecommpay&operation=response' );
		$this->url_server = url_user( '/api/payment/provider?name=ecommpay&operation=response&server=true' );
		// translation
		$strs = &$this->method_allow[ 'payout' ][ 'pay_card' ][ 'option' ];
		foreach( $strs as $key => &$str ) { $str = t( $str ); }
		// payout default option
		$user_id = main()->USER_ID;
		if( $user_id ) {
			$user = user( $user_id );
			$option_default = &$this->method_allow[ 'payout' ][ 'pay_card' ][ 'option_default' ];
			$option_default = array(
				'card'                       => $user[ 'card' ],
				'sender_first_name'          => $user[ 'first_name' ],
				'sender_last_name'           => $user[ 'last_name' ],
				'sender_middle_name'         => $user[ 'middle_name' ] ?: $user[ 'patronymic' ],
				'sender_passport_number'     => $user[ 'passport_num' ],
				'sender_passport_issue_date' => $user[ 'passport_issue_date' ],
				'sender_passport_issued_by'  => $user[ 'passport_issued_by' ] ?: $user[ 'passport_released' ],
				'sender_phone'               => $user[ 'phone' ],
				'sender_birthdate'           => $user[ 'birthdate' ] ?: $user[ 'birth_date' ],
				'sender_address'             => $user[ 'address' ] ?: $user[ 'address2' ],
				'sender_city'                => $user[ 'city' ] ?: $user[ 'city2' ],
				'sender_postindex'           => $user[ 'zip_code' ] ?: $user[ 'zip_code2' ],
			);
		}
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

	public function _amount( $amount, $currency, $is_request = true ) {
		if( !$this->ENABLE ) { return( null ); }
		$currency_id = $this->get_currency( array(
			'currency_id' => $currency,
		));
		if( $currency_id != $currency ) { return( null ); }
		$payment_api = $this->payment_api;
		list( $_currency_id, $currency ) = $payment_api->get_currency__by_id( array(
			'currency_id' => $currency_id,
		));
		if( empty( $_currency_id ) ) {
			// $result = array(
				// 'status'         => false,
				// 'status_message' => 'Неизвестная валюта',
			// );
			return( null );
		}
		$units = pow( 10, $currency[ 'minor_units' ] );
		if( $is_request ) {
			$result = (int)( $amount * $units );
		} else {
			$result = (float)$amount / $units;
		}
		return( $result );
	}

	public function _amount_payout( $amount, $currency, $is_request = true ) {
		if( !$this->ENABLE ) { return( null ); }
		$payment_api = $this->payment_api;
		list( $_currency_id, $currency ) = $payment_api->get_currency__by_id( array(
			'currency_id' => $currency_id,
		));
		if( empty( $_currency_id ) ) { return( null ); }
		$units = pow( 10, $currency[ 'minor_units' ] );
		if( $is_request ) {
			$result = (int)( $amount * $units );
		} else {
			$result = (float)$amount / $units;
		}
		return( $result );
	}

	public function _form_options( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		$_ = $options;
		// transform
		foreach ((array)$this->_options_transform as $from => $to ) {
			if( isset( $_[ $from ] ) && $from != $to ) {
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
		empty( $_[ 'language' ] ) && $_[ 'language' ] = 'ru';
		// amount
		$_[ 'amount' ] = $this->_amount( $_[ 'amount' ], $_[ 'currency' ], $is_request = true );
		// site id
		empty( $_[ 'site_id' ] ) && $_[ 'site_id' ] = $this->KEY_PUBLIC;
		if( empty( $_[ 'amount' ] ) || empty( $_[ 'site_id' ] ) ) { $_ = null; }
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
		if( empty( $form_options ) ) { return( null ); }
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
			$result[] = '<form id="_js_provider_ecommpay_form" method="post" accept-charset="utf-8" action="' . $url . '" class="display: none;">';
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
// DEBUG
$payment_api->dump();
		$test_mode = &$this->TEST_MODE;
		$is_server = !empty( $_GET[ 'server' ] );
		$result = null;
		// check operation
		$operation_id = (int)$_GET[ 'operation_id' ];
		// TEST DATA
		/*
		$_POST = array (
			// notification url
			'type_id'               => '3',
			'status_id'             => '4',
			'transaction_id'        => '36876',
			'external_id'           => '4488',
			'acquirer_id'           => '5522470251c72',
			'payment_type_id'       => '2',
			'site_id'               => '2415',
			'amount'                => '1100',
			'currency'              => 'USD',
			'real_amount'           => '1100',
			'real_currency'         => 'USD',
			'extended_info_enabled' => '1',
			'customer_purse'        => '555555...4444',
			'completed_at'          => '2015-04-06T08:42:42+00:00',
			'processor_date'        => '2015-04-06T08:42:42+00:00',
			'source_type'           => '2',
			'holder_name'           => 'AA AA',
			'expiry_date'           => '11/16',
			'authcode'              => '3O0X9R',
			'recurring_allowed'     => '0',
			'recurring_valid_thru'  => '',
			'processor_id'          => '1',
			'processor_code'        => '00',
			'processor_message'     => 'SUCCESS',
			'signature'             => '4d6d96a20e8e0864a464703ec33b399d0ab4c176',
			// success_url
			'site_id'         => '2415',
			'payment_type_id' => '2',
			'transaction_id'  => '36886',
			'external_id'     => '4497',
			'description'     => 'Пополнение счета',
			'amount'          => '200',
			'currency'        => 'USD',
			'real_amount'     => '200',
			'real_currency'   => 'USD',
			'language'        => 'ru',
			'sign'            => 'e21f3eb88ea9de9b10a8d9371e32757d4e31a6cc',
			'signature'       => 'a4565dac9ae8333a9da92ac514e5053fcc07d14f',
			'type'            => '1',
		); // */
		// response
		$response = $_POST;
		// check signature
		isset( $response[ 'signature' ] ) && $signature = $response[ 'signature' ];
		// check signature
		if( empty( $signature ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Пустая подпись',
			);
// DEBUG
$payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		$signature_options = $response;
		$_signature = $this->signature( $signature_options );
// DEBUG
// var_dump( $response, $signature, $signature_options, 'calc: ', $_signature );
// exit;
		if( $signature != $_signature ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверная подпись',
			);
// DEBUG
$payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		// user success or fail
		if( !$is_server ) {
			// check status
			$state = isset( $response[ 'type' ] ) && $response[ 'type' ] == '1' ? true : false;
			$status = isset( $_GET[ 'status' ] )
				&& $_GET[ 'status' ] == 'success'
				&& $state
				? true : false;
			$status_message = $status ? 'Операция выполнена успешно' : 'Операция не выполнена';
			$result = array(
				'status'         => $status,
				'status_message' => $status_message,
			);
			return( $result );
		}
		// server notification
		// check ip
		$ip_allow = $this->_check_ip();
		if( !$ip_allow ) {
			$payment_api->dump( array( 'var' => 'ip not allow' ));
			return( null );
		}
		// update operation
		$_response = $this->_response_parse( $response );
		// check public key (site_id)
		$key_public = $_response[ 'key_public' ];
		$_key_public = $this->key( 'public' );
		if( $key_public != $_key_public ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный ключ (site_id)',
			);
// DEBUG
$payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		// check status
		$state = $_response[ 'status_id' ];
		$status = $this->_status_server;
		list( $payment_status_name, $status_message ) = $this->_state( $state, $status );
		// deposition or payout
		$state = $_response[ 'type_id' ];
		$status = $this->_type_server;
		list( $payment_type ) = $this->_state( $state, $status );
		if( empty( $payment_type ) ) {
// DEBUG
$payment_api->dump( array( 'var' => 'type: ' . $state ));
			return( null );
		}
		// amount
		// $_response[ 'amount' ] = $this->_amount( $_response[ 'amount' ], $_response[ 'currency' ], $is_request = false );
		// update account, operation data
		$result = $this->{ '_api_' . $payment_type }( array(
			'provider_name'       => 'ecommpay',
			'response'            => $_response,
			'payment_status_name' => $payment_status_name,
			'status_message'      => $status_message,
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
		$allow   = &$this->currency_allow;
		$default = $this->currency_default;
		// check: allow currency_id
		$id     = $_[ 'currency_id' ];
		$result = $default;
		if( isset( $allow[ $id ] ) && $allow[ $id ][ 'active' ] ) {
			$result = $id;
		}
		return( $result );
	}

	public function get_currency_payout( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$method = $this->api_method_payout( $_method_id );
		$key = 'currency';
		if( empty( $method ) || empty( $method[ $key ] ) ) { return( null ); }
		$currency = $method[ $key ];
		if( empty( $_currency ) || empty( $currency[ $_currency ] ) ) {
			$default = reset( $currency );
			$result = $default[ 'currency_id' ];
		} else {
			$result = $currency[ 'currency_id' ];
		}
		return( $result );
	}

	public function get_fee_payout( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$method = $this->api_method_payout( $_method_id );
		$key = 'fee';
		if( empty( $method ) || empty( $method[ $key ] ) ) { return( null ); }
		$result = $method[ $key ];
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
		// add options
		if( !empty( $options[ 'method_id' ] ) && !empty( $this->method_allow[ 'payin' ][ $options[ 'method_id' ] ] ) ) {
			$method_id = &$options[ 'method_id' ];
			$method = &$this->method_allow[ 'payin' ][ $method_id ];
			if( !empty( $method[ 'option' ] ) ) {
				$form_options += $method[ 'option' ];
			}
		}
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
			'status_message' => 'Поплнение через сервис: EcommPay',
		);
		return( $result );
	}

	public function api_request( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// method
		$method_option = $this->api_method_payout( $_method_id );
		if( empty( $method_option ) ) {
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
		$_amount = $this->_amount_payout( $_amount, $_currency, $is_request = true );
		!isset( $_site_id ) && $_site_id = $this->key( 'public' );
		!isset( $_comment ) && $_comment = t( 'Вывод средств (id: ' . $_external_id . ')' );
		!isset( $_action  ) && $_action = $method_option[ 'action' ];
		is_string( $_sender_phone ) && $_sender_phone = str_replace( array( ' ', '-', '+', ), '', $_sender_phone );
		// check required
		$request = array();
		foreach( $method_option[ 'field' ] as $key ) {
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
		$url  = $this->api_url( $options );
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
		$status = false;
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
					$status         = true;
					$status_message = 'Выполнено';
				} else {
					$status_message = 'Выполнено, но сумма или код операции не совпадают';
				}
				break;
			// in progress
			case 50:
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
				$status_message = 'Неверный данные запроса';
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
		// handle response
		// switch( $operation_status_name ) {
			// case 'success':
				// $this->_payout_success( array( 'operation_id' => $_operation_id ) );
				// break;
			// case 'refused':
				// $this->_payout_refused( array( 'operation_id' => $_operation_id ) );
				// break;
		// }
// DEBUG
// var_dump( $url, $request, $data_json );
// exit;
		return( $result );
	}

	public function _update_status( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// check
		if( empty( $_name ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Статус операции не определен',
			);
			return( $result );
		}
		if( empty( $_operation_id ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Не определен код операции',
			);
			return( $result );
		}
		// var
		$payment_api = $this->payment_api;
		// operation
		$operation = $payment_api->operation( array( 'operation_id' => $_operation_id ) );
		if( empty( $operation ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Операция отсутствует: ' . $_operation_id,
			);
			return( $result );
		}
		// update status only in_progress
		$object = $payment_api->get_status( array( 'status_id' => $operation[ 'status_id' ] ) );
		list( $status_id, $status ) = $object;
		if( empty( $status_id ) ) { return( $object ); }
		if( $status[ 'name' ] != 'in_progress' ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Операция уже обработана: ' . $_operation_id,
			);
			return( $result );
		}
		// progress
		$object = $payment_api->get_status( array( 'name' => $_name ) );
		list( $status_id, $status ) = $object;
		if( empty( $status_id ) ) { return( $object ); }
		// prepare
		$sql_datetime = $payment_api->sql_datetime();
		$data = array(
			'operation_id'    => $_operation_id,
			'status_id'       => $status_id,
			'datetime_update' => $sql_datetime,
		);
		!empty( $_is_finish ) && $data[ 'datetime_finish' ] = $sql_datetime;
		$result = $payment_api->operation_update( $data );
		return( $result );
	}

	public function _payout_success( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// progress
		$result = $this->_update_status( array(
			'operation_id' => $_operation_id,
			'name'         => 'success',
			'is_finish'    => true,
		));
		return( $result );
	}

	public function _payout_refused( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// progress
		db()->begin();
		$result = $this->_payout_amount_revert( $_operation_id );
			if( empty( $result[ 'status' ] ) ) { db()->rollback(); return( $result ); }
		$result = $this->_payout_balance_update( $_operation_id );
			if( empty( $result[ 'status' ] ) ) { db()->rollback(); return( $result ); }
		$result = $this->_update_status( array(
			'operation_id' => $_operation_id,
			'name'         => 'refused',
			'is_finish'    => true,
		));
			if( empty( $result[ 'status' ] ) ) { db()->rollback(); return( $result ); }
		db()->commit();
		return( $result );
	}

	public function _payout_amount_revert( $operation_id ) {
		$payment_api = $this->payment_api;
		// operation
		$operation = $payment_api->operation( array( 'operation_id' => $operation_id ) );
		if( empty( $operation ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Операция отсутствует: ' . $operation_id,
			);
			return( $result );
		}
		// amount revert
		$account_id = $operation[ 'account_id' ];
		$amount     = $operation[ 'amount' ];
		// update account
		$sql_amount   = $payment_api->_number_mysql( $amount );
		$sql_datetime = $payment_api->sql_datetime();
		$data = array(
			'account_id'      => $account_id,
			'datetime_update' => db()->escape_val( $sql_datetime ),
			'balance'         => '( balance + ' . $sql_amount . ' )',
		);
		$result = $payment_api->balance_update( $data, array( 'is_escape' => false ) );
		return( $result );
	}

	public function _payout_balance_update( $operation_id ) {
		$payment_api = $this->payment_api;
		// operation
		$operation = $payment_api->operation( array( 'operation_id' => $operation_id ) );
		if( empty( $operation ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Операция отсутствует: ' . $operation_id,
			);
			return( $result );
		}
		$account_id = $operation[ 'account_id' ];
		// update balance
		$object = $payment_api->get_account__by_id( array( 'account_id' => $account_id, 'force' => true ) );
		if( empty( $object ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Ошибка при обновлении счет',
			);
			return( $result );
		}
		list( $account_id, $account ) = $object;
		$balance = $account[ 'balance' ];
		// prepare
		$sql_datetime = $payment_api->sql_datetime();
		$data = array(
			'operation_id'    => $operation_id,
			'balance'         => $balance,
			'datetime_update' => $sql_datetime,
		);
		$result = $payment_api->operation_update( $data );
		return( $result );
	}

	public function validate( $options ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// type: deposition, payment, etc
		if( empty( $_type_name ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неизвестный тип операции',
			);
			return( $result );
		}
		switch( $_type_name ) {
			case 'payment':
				return( $this->validate_payment( $options ) );
				break;
		}
		return( $this->result_success() );
	}

	public function validate_payment( $options ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// todo: real validation on type
		if( empty( $_method_id ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Отсутствует метод вывода средств',
			);
			return( $result );
		}
		if( empty( $this->method_allow[ 'payout' ][ $_method_id ] ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Метод вывода средств не найден: '. $_method_id,
			);
			return( $result );
		}
		$method = &$this->method_allow[ 'payout' ][ $_method_id ];
		foreach( $method[ 'option' ] as $key => $item ) {
			if( empty( trim( ${ '_'. $key } ) ) ) {
				return( $this->result_fail( 'Отсутствует обязательное поле запроса: '. $item ) );
			}
		}
		return( $this->result_success() );
	}

	public function payment( $options ) {
		if( !$this->ENABLE ) { return( null ); }
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
		$currency_id    = $this->get_currency_payout( $options );
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
		$fee = $this->get_fee_payout( $options );
		$amount_currency_total = $payment_api->fee( $amount_currency, $fee );
		// check balance
		$account_result = $payment_api->get_account( array( 'account_id' => $account_id ) );
		if( empty( $account_result ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Ошибка при проверке, баланса',
			);
			return( $result );
		}
		list( $account_id, $account ) = $account_result;
		$balance = $account[ 'balance' ];
		if( $amount > $balance ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Недостаточно средств на счету',
			);
			return( $result );
		}
		// update account balance
		db()->begin();
		$sql_datetime = $operation_data[ 'sql_datetime' ];
		$sql_amount   = $payment_api->_number_mysql( $amount );
		$_data = array(
			'account_id'      => $account_id,
			'datetime_update' => db()->escape_val( $sql_datetime ),
			'balance'         => "( balance - $sql_amount )",
		);
		$_result = $payment_api->balance_update( $_data, array( 'is_escape' => false ) );
		if( !$_result[ 'status' ] ) {
			db()->rollback();
			$result = array(
				'status'         => false,
				'status_message' => 'Ошибка при обновлении счета',
			);
			return( $result );
		}
		$result = array(
			'status' => true,
		);
		// check account
		$account_result = $payment_api->get_account( array( 'account_id' => $account_id ) );
		if( empty( $account_result ) ) {
			db()->rollback();
			$result = array(
				'status'         => false,
				'status_message' => 'Ошибка при получении, баланса',
			);
			return( $result );
		}
		list( $account_id, $account ) = $account_result;
		// prepare
		// save options
		$data = array(
			'fee'         => $fee,
			'currency_id' => $currency_id,
			'amount'      => $amount_currency_total,
		);
		$operation_options = array(
			'request' => array( array(
				'options'  => $options,
				'data'     => $data,
				'datetime' => $operation_data[ 'sql_datetime' ],
			))
		);
		$operation_update_data = array(
			'operation_id'    => $operation_id,
			'balance'         => $account[ 'balance' ],
			'datetime_update' => $sql_datetime,
			'options'         => $operation_options,
		);
		$result = $payment_api->operation_update( $operation_update_data );
		if( !$result[ 'status' ] ) {
			db()->rollback();
			return( $result );
		}
		db()->commit();
		$result = array(
			'status'         => true,
			'status_message' => t( 'Заявка на вывод средств принята' ),
		);
		return( $result );
	}

}
