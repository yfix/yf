<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_ecommpay extends yf_payment_api__provider_remote {

	public $URL              = 'https://terminal.ecommpay.com/';
	public $URL_TEST         = 'https://terminal-sandbox.ecommpay.com/';
	public $KEY_PUBLIC       = null;  // site id
	public $KEY_PRIVATE      = null;  // salt

	public $IS_DEPOSITION = true;
	// public $IS_PAYMENT    = true;

	public $URL_API          = 'https://gate.ecommpay.com/card/json/';
	public $URL_API_TEST     = 'https://gate-sandbox.ecommpay.com/card/json/';

	public $method_allow = array(
		'payment' => array(
			'pay_card' => array(
				'title'       => 'EcommPay',
				'icon'        => 'ecommpay',
				'amount_min'  => 100,
				'fee'         => 0, // 0.1%
				'currency' => array(
					'RUB' => array(
						'currency_id' => 'RUB',
						'active'      => true,
					),
				),
				'field' => array(
					'b_name',
					'b_card_or_acc',
					'amt',
					'ccy',
					'details',
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
		'amount'                     => 'amount',                     // + Numeric Сумма к выплате в валюте сайта
		'currency'                   => 'currency',                   // - Enum Валюта
		'operation_id'               => 'external_id',                // + String Идентификатор заказа в системе продавца
		'transaction_id'             => 'transaction_id',             // ? Numeric ID успешного платежа по той банковской карте, на которую нужно сделать выплату.
		'force_disable_callback'     => 'force_disable_callback',     // - Enum Отключить оповещения (callback) об операции. Допустимые значения - 1 (да, отключить), 0 (нет, высылать оповещения)
		'first_callback_delay'       => 'first_callback_delay',       // - Numeric Задержка перед отправкой первого оповещения
		'card'                       => 'card',                       // ? Numeric(13,19) Номер банковской карт, на которую совершается выплата.
		'title'                      => 'comment',                    // + String(4096) Комментарий к запросу example_comment
		'sender_first_name'          => 'sender_first_name',          // + String(255) Имя пользователя
		'sender_last_name'           => 'sender_last_name',           // + String(255) Фамилия пользователя
		'sender_middle_name'         => 'sender_middle_name',         // + String(255) Отчество пользователя
		'sender_passport_number'     => 'sender_passport_number',     // + String(255) Серия и номер паспорта пользователя
		'sender_passport_issue_date' => 'sender_passport_issue_date', // + String(255) Дата выдачи паспорта: 2002-01-31
		'sender_passport_issued_by'  => 'sender_passport_issued_by',  // + String(255) Орган, выдавший паспорт
		'sender_phone'               => 'sender_phone',               // + String(11) Контактный телефон пользователя.
		'sender_birthdate'           => 'sender_birthdate',           // + Date Дата рождения пользователя: 1980-01-31
		'sender_address'             => 'sender_address',             // + String(255) Адрес пользователя
		'sender_city'                => 'sender_city',                // ? String(255) Город пользователя.
		'sender_postindex'           => 'sender_postindex',           // ? String(255) Почтовый индекс пользователя.
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
		'EcommPay',
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
		$this->url_result = url( '/api/payment/provider?name=ecommpay&operation=response' );
		$this->url_server = url( '/api/payment/provider?name=ecommpay&operation=response&server=true' );
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
// $payment_api->dump();
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
// $payment_api->dump( array( 'var' => $result ));
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
// $payment_api->dump( array( 'var' => $result ));
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
// $payment_api->dump( array( 'var' => 'type: ' . $state ));
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
		$method  = &$this->method_allow;
		if( empty( $method[ 'payment' ][ $_method_id ] )
			|| empty( $method[ 'payment' ][ $_method_id ][ 'currency' ] )
		) { return( null ); }
		$currency = $method[ 'payment' ][ $_method_id ][ 'currency' ];
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
		$method  = &$this->method_allow;
		$key = 'fee';
		if( empty( $method[ 'payment' ][ $_method_id ] )
			|| empty( $method[ 'payment' ][ $_method_id ][ $key ] )
		) { return( null ); }
		$result = $method[ 'payment' ][ $_method_id ][ $key ];
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
			'status_message' => 'Поплнение через сервис: EcommPay',
		);
		return( $result );
	}

	public function api_request( $method, $options ) {
		if( !$this->ENABLE ) { return( null ); }
		$api_method_allow = $this->method_allow[ 'payment' ][ $method ];
		if( !is_array( $api_method_allow ) ) { return( null ); }
		$payment_api = &$this->payment_api;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
// var_dump( $method, $options );
// return;
		// transform
		foreach( $this->_xml_transform as $from => $to ) {
			$f = &${ '_'.$from };
			$t = &${ '_'.$to   };
			if( isset( $f ) ) { $t = $f; unset( ${ '_'.$from } ); }
		}
		// default
		$_amt  = number_format( $_amt, 2, '.', '' );
		$_ccy  = $payment_api->_default( array( $_ccy, $this->currency_default ) );
		$_wait = $_wait ?: $this->_api_request_timeout;
		$_test = $payment_api->_default( array( $_test, $this->TEST_MODE ) );
		// $_test = (int)$_test;
		foreach( $api_method_allow[ 'field' ] as $name ) {
			$value = &${ '_'.$name };
			if( !isset( $value ) ) {
				$result = array(
					'status'         => false,
					'status_message' => 'Отсутствуют данные запроса',
				);
				return( $result );
			}
		}
		// build xml
		$xml_request = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>'
			.'<request version="1.0">'
			.'</request>'
		);
		$xml_merchant = $xml_request->addChild( 'merchant' );
		$xml_data     = $xml_request->addChild( 'data'     );
		// oper, wait, test
		$xml_data->addChild( 'oper', 'cmt' );
		$xml_data->addChild( 'wait', $_wait );
		$xml_data->addChild( 'test', $_test );
		// payment
		$xml_payment = $xml_data->addChild( 'payment' );
		// payment id
		isset( $_payment_id ) && $xml_payment->addAttribute( 'id', $_payment_id );
		// data
		$data = '';
		foreach( $api_method_allow[ 'field' ] as $name ) {
			$value = ${ '_'.$name };
			$value = htmlentities( $value, ENT_COMPAT | ENT_XML1, 'UTF-8', $double_encode = false );
			$prop = $xml_payment->addChild( 'prop' );
			$prop->addAttribute( 'name',  $name  );
			$prop->addAttribute( 'value', $value );
		}
		// signature
		$key_public  = $this->KEY_PUBLIC;
		$data = '';
		foreach( $xml_data->children() as $_xml ) { $data .= $_xml->asXML(); }
		$signature = $this->api->str_to_sign( $data );
		// merchant
		$xml_merchant->addChild( 'id',        $key_public );
		$xml_merchant->addChild( 'signature', $signature   );
		// request
		$data = $xml_request->asXML();
		$result = $this->_api_request( $method, $data );
		list( $status, $response ) = $result;
		if( !$status ) { return( $result ); }
		libxml_use_internal_errors( true );
		$xml_response = simplexml_load_string( $response );
// debug
// ini_set( 'html_errors', 0 );
// var_dump( $response, $xml_response );
		// error?
		$error = libxml_get_errors();
		if( $error ) {
			libxml_clear_errors();
			$result = array(
				'status'         => null,
				'status_message' => 'Ошибка ответа: неверная структура данных',
			);
			return( $result );
		}
		if( $xml_response->getName() == 'error' ) {
			$result = array(
				'status'         => null,
				'status_message' => 'Ошибка ответа: неверные данные - ' . (string)$xml_response,
			);
			return( $result );
		}
		// ----- check response
		// key public - merchant
		$value = $key_public;
		$r_value = (string)$xml_response->merchant->id;
		if( $value != $r_value ) {
			$result = array(
				'status'         => null,
				'status_message' => 'Ошибка ответа: неверный публичный ключ (merchant)',
			);
			return( $result );
		}
		// signature
		$data = '';
		foreach( $xml_response->data->children() as $_xml ) { $data .= $_xml->asXML(); }
		$value = $this->api->str_to_sign( $data );
		$r_value = (string)$xml_response->merchant->signature;
		if( $value != $r_value ) {
			$result = array(
				'status'         => null,
				'status_message' => 'Ошибка ответа: неверный подпись (signature)',
			);
			return( $result );
		}
		// payment
		$xml_response_payment = $xml_response->data->payment->attributes();
		// id
		$value = $_payment_id;
		$r_value = (string)$xml_response_payment->id;
		if( $value != $r_value ) {
			$result = array(
				'status'         => null,
				'status_message' => 'Ошибка ответа: неверный номер операции (operation_id)',
			);
			return( $result );
		}
		// amt
		$value = $_amt;
		$r_value = (string)$xml_response_payment->amt;
		if( $value != $r_value ) {
			$result = array(
				'status'         => null,
				'status_message' => 'Ошибка ответа: неверная сумма (amt)',
			);
			return( $result );
		}
		// ccy
		$value = $_ccy;
		$r_value = (string)$xml_response_payment->ccy;
		if( $value != $r_value ) {
			$result = array(
				'status'         => null,
				'status_message' => 'Ошибка ответа: неверная валюта (ccy)',
			);
			return( $result );
		}
		// state
		$status         = (bool)$xml_response_payment->state;
		$status_message = (string)$xml_response_payment->message;
		if( $status ) {
			if( $status_message == 'payment added to the queue' ) {
				$status_message = 'Платеж добавлен в очередь';
			} else {
				$status_message = 'Платеж выполнен успешно';
			}
		} else {
			$status_message = 'Платеж забракован';
		}
		return( array( $status, $status_message ) );
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
		if( empty( $account_result ) ) { $status = false; }
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
		$result = $payment_api->operation_update( array(
			'operation_id'    => $operation_id,
			'balance'         => $account[ 'balance' ],
			'datetime_update' => $sql_datetime,
			'options'         => $operation_options,
		));
		if( !$_result[ 'status' ] ) {
			db()->rollback();
			return( $_result );
		}
		db()->commit();
		$result = array(
			'status'         => true,
			'status_message' => t( 'Заявка на вывод средств принята' ),
		);
		return( $result );
	}

}
