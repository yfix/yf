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
		'PAYMENT_AMOUNT'    => 'amount',
		'PAYMENT_UNITS'     => 'currency',
		'SUGGESTED_MEMO'    => 'title',
		'PAYMENT_ID'        => 'operation_id',
		'PAYMENT_BATCH_NUM' => 'provider_operation_id',
	);

	public $_status = array(
		'success' => 'success',
		'fail'    => 'refused',
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
		// $_operation_id = (int)$_GET[ 'operation_id' ];
		$operation_id = (int)$_POST[ 'PAYMENT_ID' ];
		// check status
		$state = @$_GET[ 'status' ];
		list( $status_name, $status_message ) = $this->_state( $state );
		// START DUMP
		$payment_api->dump( array( 'name' => 'PerfectMoney', 'operation_id' => (int)$operation_id ));
		if( !$is_server ) {
			$result = array(
				'status'         => $state == 'success',
				'status_message' => 'Операция выполнена',
			);
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
		$response = $_POST;
		// response POST:
		$signature = $response[ $this->HASH_KEY ];
		// check signature
		if( empty( $signature ) && !$test_mode ) {
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
