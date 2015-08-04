<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_perfectmoney extends yf_payment_api__provider_remote {

	public $URL              = 'https://perfectmoney.is/api/step1.asp';
	public $KEY_PUBLIC       = null;     // id
	public $KEY_PRIVATE      = null;     // secret key

	public $PAYEE_NAME       = null;
	public $PURSE_ID         = null;     // purse_id by currency_id
/* example for project_conf.php:
	public $PURSE_ID         = array(    // purse_id by currency_id
		'UAH' => '...',
	);
*/
	public $PURSE_UNITS      = array(
		'USD' => array( 'decimals' => 2 ),
		'EUR' => array( 'decimals' => 2 ),
		'OAU' => array( 'decimals' => 0 ),
	);

	public $method_allow = array(
		'order' => array(
			'payin' => array(
				'perfectmoney',
			),
		),
		'payin' => array(
			'perfectmoney' => array(
				'title'       => 'Perfect Money',
				'icon'        => 'perfectmoney',
				'currency' => array(
					'USD' => array(
						'currency_id' => 'USD',
						'active'      => true,
					),
					// 'EUR' => array(
						// 'currency_id' => 'EUR',
						// 'active'      => true,
					// ),
				),
			),
		),
	);

	public $_options_transform = array(
		'amount'       => 'PAYMENT_AMOUNT',
		'currency'     => 'PAYMENT_UNITS',
		'title'        => 'SUGGESTED_MEMO',
		'operation_id' => 'PAYMENT_ID',
	);

	public $_options_transform_reverse = array(
		'PAYMENT_AMOUNT' => 'amount',
		'PAYMENT_UNITS'  => 'currency',
		'SUGGESTED_MEMO' => 'title',
		'PAYMENT_ID'     => 'operation_id',
	);

	public $_status = array(
		'success'    => 'success',
		'new'        => 'processing',
		'waitAccept' => 'processing',
		'process'    => 'processing',
		'fail'       => 'refused',
		'canceled'   => 'refused',
	);

	public $_payin_status = array(
		//     status          description                  финальный
		2  => 'processing', // Ожидает оплаты             - Нет
		3  => 'processing', // Обрабатывается             - Нет
		4  => 'processing', // В процессе возврата        - Нет
		5  => 'expired',    // Просрочен                  - Да
		7  => 'success',    // Зачислен                   - Да
		8  => 'refused',    // Отменен платежной системой - Да
		9  => 'refused',    // Возвращен                  - Да
	);

	public $_payin_status_message = array(
		2  => 'Ожидает оплаты',
		3  => 'Обрабатывается',
		4  => 'В процессе возврата',
		5  => 'Просрочен',
		7  => 'Зачислен',
		8  => 'Отменен платежной системой',
		9  => 'Возвращен',
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
	);

	public $service_allow = array(
		'Perfect Money',
	);

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
		$this->key( 'public',       $this->KEY_PUBLIC       );
		$this->key( 'private',      $this->KEY_PRIVATE      );
		$this->key( 'private_test', $this->KEY_PRIVATE_TEST );
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
			$_[ 'SUGGESTED_MEMO_NOCHANGE' ] = 1;
		}
		// url
		if( !empty( $_[ 'url_result' ] ) ) {
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
		if( !empty( $_[ 'url_server' ] ) ) {
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
		$is_array = (bool)$_[ 'is_array' ];
		$form_options = $this->_form_options( $data );
		$url = &$this->URL;
		$result = array();
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
		$test_mode = &$this->TEST_MODE;
		$is_server = !empty( $_GET[ 'server' ] );
		$result = null;
		// check operation
		$operation_id = (int)$_GET[ 'operation_id' ];
		// START DUMP
		$payment_api->dump( array( 'name' => 'Interkassa', 'operation_id' => (int)$operation_id ));
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
		$response = $_POST;
		// response POST:
		$signature = $response[ 'ik_sign' ];
		// check signature
		if( !$test_mode && empty( $signature ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Пустая подпись',
			);
			// DUMP
			$payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		$_signature = $this->signature( $response, false );
		if( !( $test_mode && empty( $signature ) ) && $signature != $_signature ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверная подпись',
			);
			// DUMP
			$payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		// update operation
		$_response = $this->_response_parse( $response );
		// check public key (ik_co_id)
		$key_public = $_response[ 'key_public' ];
		$_key_public = $this->key( 'public' );
		if( $key_public != $_key_public ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный ключ (ik_co_id)',
			);
			return( $result );
		}
		// check status
		$state = $_response[ 'ik_inv_st' ];
		list( $status_name, $status_message ) = $this->_state( $state );
		// test
		// $_response[ 'operation_id' ] = '3304';
		// update account, operation data
		$result = $this->_api_deposition( array(
			'provider_name'  => 'interkassa',
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
		$request_option = array(
			'method_id' => 'checkout',
			'header'    => array(
				'Ik-Api-Account-Id: '. $account_id,
			),
		);
		$result = $this->api_request( $request_option );
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
// DEBUG
// var_dump( $url, $request, $request_option );
// exit;
		// add options
		!empty( $method[ 'option' ] ) && $request = array_merge_recursive(
			$request, $method[ 'option' ]
		);
		// url
		$object = $this->api_url( $method, $options );
		if( isset( $object[ 'status' ] ) && $object[ 'status' ] === false ) { return( $object ); }
		$url = $object;
		// request options
		$request_option = array();
		@$_is_debug && $request_option[ 'is_debug' ] = true;
			// api authorization
			$_request_option = $this->api_authorization( $method );
			is_array( $_request_option ) && $request_option = array_merge_recursive( $request_option, $_request_option );
			// api account
			$_request_option = $this->api_account( $method );
			is_array( $_request_option ) && $request_option = array_merge_recursive( $request_option, $_request_option );
			// header
			is_array( $_header ) && $request_option = array_merge_recursive( $request_option, array( 'header' => $_header ) );
		// test
		if( $this->is_test() ) {
			switch( $_method_id ) {
				case 'withdraw-process':
					$request[ 'action' ] = 'calc';
					break;
			}
		}
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
		// amount currency conversion
		$amount = $_amount;
		$result = $this->currency_conversion_payout( array(
			'options' => $options,
			'method'  => $method,
			'amount'  => &$amount,
		));
		if( empty( $result[ 'status' ] ) ) { return( $result ); }
		$amount_currency       = $result[ 'amount_currency' ];
		$amount_currency_total = $result[ 'amount_currency_total' ];
		$currency_id           = $result[ 'currency_id' ];
		// amount min/max
		$result = $this->amount_limit( array(
			'amount'      => $amount,
			'currency_id' => $currency_id,
			'method'      => $method,
		));
		if( empty( $result[ 'status' ] ) ) { return( $result ); }
		// default
		$amount = @$method[ 'is_fee' ] ? $amount_currency_total : $amount_currency;
		// request
		$request = array();
		@$method[ 'request_option' ] && $request = $method[ 'request_option' ];
		// add common fields
		!@$request[ 'purseId' ] && $request[ 'purseId' ] = $this->PURSE_ID[ $currency_id ];
		if( ! @$request[ 'purseId' ] ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Требуется настройка кошелька',
			);
			return( $result );
		}
		$request[ 'amount'       ] = $amount;
		$request[ 'operation_id' ] = $operation_id;
		// transform
		$this->option_transform( array(
			'option'    => &$request,
			'transform' => $this->_api_transform,
		));
		// add details
		$request[ 'details' ] = array();
		$request_details = $options;
		$this->option_transform( array(
			'option'    => &$request_details,
			'transform' => $this->_api_transform,
		));
		foreach( $method[ 'field' ] as $key ) {
			$value = &$request_details[ $key ];
			if( !isset( $value ) ) {
				$result = array(
					'status'         => false,
					'status_message' => 'Отсутствуют данные запроса: '. $key,
				);
				return( $result );
			}
			$request[ 'details' ][ $key ] = &$request_details[ $key ];
		}
// DEBUG
// var_dump( $request );
		// START DUMP
		$payment_api->dump( array( 'name' => 'Interkassa', 'operation_id' => $operation_id,
			'var' => array( 'request' => $request )
		));
		// update processing
		$sql_datetime = $payment_api->sql_datetime();
		$operation_options = array(
			'processing' => array( array(
				'provider_name' => 'interkassa',
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
			'method_id' => 'withdraw-process',
			'option'    => $request,
			'is_debug'  => @$_is_debug,
		);
		$result = $this->api_request( $request_option );
		// DUMP
		$payment_api->dump( array( 'var' => array( 'response' => $result )));
		if( empty( $result ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Невозможно отправить запрос',
			);
			return( $result );
		}
		@list( $status, $response ) = $result;
		// DEBUG
		/*
		$this->is_test() && $response = array (
			'status' => 'ok',
			'code' => 0,
			'data' =>
			array (
				'withdraw' => array (
					'state'         => '4',
					'state'         => '1',
					'result'        => '0',
					'stateName'     => 'success',
					'purseId'       => '300301404317',
					'accountId'     => '5534b0f13b1eaf67738b456a',
					'coId'          => '5534b12f3b1eaf07728b4569',
					'paymentNo'     => '1',
					'paywayId'      => '52efa902e4ae1a780e000001',
					'paywayPurseId' => '52efa952e4ae1a3008000003',
					'payerWriteoff' => 2063.3299999999999,
					'payeeReceive'  => 2063.3299999999999,
					'ikFee'         => 0,
					'ikPrice'       => 2063.3299999999999,
					'ikPsPrice'     => 2063.3299999999999,
					'psFeeIn'       => 0,
					'psFeeOut'      => 30.329999999999998,
					'psCost'        => 22.329999999999998,
					'ikIncome'      => 8,
					'psAmount'      => 2033,
					'psValue'       => 2033,
					'psPrice'       => 2055.3299999999999,
					'psCurRate'     => 1,
				),
				'transaction' => array (
					'payerPurseId' => '300301404317',
					'payerBalance' => 5714.3199999999997,
					'payeePurseId' => '304403706200',
					'payeeBalance' => 179616920.39289999,
					'payerAmount' => 2063.3299999999999,
					'payerPrice' => 2063.3299999999999,
					'payerFee' => 0,
					'payerExchFee' => 0,
					'payeeAmount' => 2063.3299999999999,
					'payeeFee' => 0,
					'payeePrice' => 2063.3299999999999,
					'exchRate' => 1,
				),
			),
			'message' => 'Success',
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
		$result = array(
			'status'         => &$status_name,
			'status_message' => &$status_message,
		);
		$status_name         = false;
		$status_message = null;
		$state = (int)$response[ 'state' ];
		switch( $state ) {
			// success
			case 0:
				$status_name    = true;
				$status_message = 'Выполнено';
				break;
			// processing
			case 1106:
				$status_message = 'Не хватает средств';
				break;
			default:
				$status_message = 'Ошибка: '. $response[ 'message' ];
				break;
		}
		@$status_message && $response[ 'message' ] = $status_message;
		if( !$status_name ) { return( $result ); }
		// data
		$data = &$response[ 'data' ][ 'withdraw' ];
		if( !is_array( $data ) ) {
			$status_name    = false;
			$status_message = 'Невозможно декодировать ответ: '. var_export( $response, true );
			return( $result );
		}
		$data[ '_transaction' ] = &$response[ 'data' ][ 'transaction' ];
		// test mode
		$this->is_test() && $data += array (
			'state' => 1,
			'id'    => 401040, // need real interkassa operation id
		);
		// check status
		$state = (int)$data[ 'state' ];
		list( $status_name, $status_message ) = $this->_state( $state
			, $this->_payout_status
			, $this->_payout_status_message
		);
		$status_message = @$status_message ?: @$data[ 'stateName' ];
		// update account, operation data
		$payment_type = 'payment';
		$operation_data = array(
			'operation_id'   => $operation_id,
			'provider_force' => @$_provider_force,
			'provider_name'  => 'interkassa',
			'state'          => $state,
			'status_name'    => $status_name,
			'status_message' => $status_message,
			'payment_type'   => $payment_type,
			'response'       => $data,
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
