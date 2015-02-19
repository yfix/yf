<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_liqpay extends yf_payment_api__provider_remote {

	public $URL         = 'https://www.liqpay.com/api/pay';
	public $KEY_PUBLIC  = null;
	public $KEY_PRIVATE = null;

	public $IS_DEPOSITION = true;
	// public $IS_PAYMENT    = true;

	public $_options_transform = array(
		'title'        => 'description',
		'operation_id' => 'order_id',
		'url_result'   => 'result_url',
		'url_server'   => 'server_url',
		'key_public'   => 'public_key',
		'test'         => 'sandbox',
		'test_mode'    => 'sandbox',
	);

	public $_options_transform_reverse = array(
		'description' => 'title',
		'order_id'    => 'operation_id',
		'public_key'  => 'key_public',
	);

	public $_status = array(
		'success'     => 'success',
		'wait_secure' => 'in_progress',
		'failure'     => 'refused',
	);

	public $currency_default = 'UAH';
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

	public $fee = 2.75; // 2.75%

	public $service_allow = array(
		'Visa',
		'MasterCard',
		'LiqPay',
		'Наличные через терминал ПриватБанк',
	);

	public $url_result = null;
	public $url_server = null;

	public function _init() {
		$this->payment_api = _class( 'payment_api' );
		// load api
		require_once( __DIR__ . '/payment_provider/liqpay/LiqPay.php' );
		$this->api = new LiqPay( $this->KEY_PUBLIC, $this->KEY_PRIVATE );
		$this->url_result = url( '/api/payment/provider?name=liqpay&operation=response' );
		$this->url_server = url( '/api/payment/provider?name=liqpay&operation=response&server=true' );
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

	public function signature( $options, $is_request = true ) {
		$result = $this->api->signature( $options, $is_request );
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
		// default
		$_[ 'amount' ] = number_format( $_[ 'amount' ], 2, '.', '' );
		empty( $_[ 'public_key' ] ) && $_[ 'public_key' ] = $this->key( 'public' );
		empty( $_[ 'pay_way'    ] ) && $_[ 'pay_way'    ] = 'card,delayed';
		if( empty( $_[ 'result_url' ] ) ) {
			$_[ 'result_url' ] = $this->url_result
				. '&operation_id=' . (int)$options[ 'operation_id' ];
		}
		if( empty( $_[ 'server_url' ] ) ) {
			$_[ 'server_url' ] = $this->url_server
				. '&operation_id=' . (int)$options[ 'operation_id' ];
		}
		if( empty( $_[ 'amount' ] ) || empty( $_[ 'public_key' ] ) ) { $_ = null; }
		if( !empty( $this->TEST_MODE ) || !empty( $_[ 'sandbox' ] ) ) { $_[ 'sandbox' ] = '1'; }
		return( $_ );
	}

	public function _form( $data, $options = null ) {
		if( empty( $data ) ) { return( null ); }
		$_ = &$options;
		$is_array = (bool)$_[ 'is_array' ];
		$form_options = $this->_form_options( $data );
		$signature    = $this->signature( $form_options, $is_request = true );
		if( empty( $signature ) ) { return( null ); }
		$form_options[ 'signature' ] = $signature;
		$url = &$this->URL;
		$result = array();
		if( $is_array ) {
			$result[ 'url' ] = $url;
		} else {
			$result[] = '<form id="_js_provider_liqpay_form" method="post" accept-charset="utf-8" action="' . $url . '" class="display: none;">';
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

	public function _api_check( $request = null ) {
		$payment_api = $this->payment_api;
		$is_server = !empty( $_GET[ 'server' ] );
		if( $is_server ) { return( null ); }
		$result = null;
		$operation_id = (int)$_GET[ 'operation_id' ];
		// get response
/*
			$response = array (
				'result' => 'ok',
				'payment_id' => 47209168,
				'status' => 'sandbox',
				'amount' => 20.33,
				'currency' => 'UAH',
				'order_id' => '71',
				'liqpay_order_id' => '4570u1419609068385644',
				'description' => 'Поплнение счета (LiqPay)',
			);
//*/
		$_response = (array)$this->api->api( 'payment/status', array(
			'order_id' => $operation_id,
		));
		// chech response
		if( empty( $_response ) || $_response[ 'result' ] != 'ok' ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Ошибка при проверке статуса операции',
			);
			return( $result );
		}
		// update operation
		$response = $this->_response_parse( $_response );
		// check status
		// success, failure, wait_secure, sandbox
		$state = $response[ 'status' ];
		if( $this->TEST_MODE && $state == 'sandbox' ) { $state = 'success'; }
		list( $payment_status_name, $status_message ) = $this->_state( $state );
		// update account, operation data
		$result = $this->_api_deposition( array(
			'provider_name'       => 'liqpay',
			'response'            => $response,
			'payment_status_name' => $payment_status_name,
			'status_message'      => $status_message,
		));
		return( $result );
	}

	public function _api_response( $request = null ) {
		$is_server = !empty( $_GET[ 'server' ] );
		if( $is_server ) {
			$name = 'server';
		} else {
			$name = 'check';
		}
		$result = $this->{ '_api_' . $name }( $request );
		return( $result );
	}

	public function _api_server( $request = null ) {
		$payment_api = $this->payment_api;
		$is_server = !empty( $_GET[ 'server' ] );
		if( !$is_server ) { return( null ); }
		$result = null;
		$operation_id = (int)$_GET[ 'operation_id' ];
		// get response
/*
		$_POST = array (
			'signature'           => '7GVdRWffi28gwdypt7HsvDKMV+8=',
			'receiver_commission' => '0.00',
			'sender_phone'        => '380679041321',
			'transaction_id'      => '47410158',
			'status'              => 'sandbox',
			'liqpay_order_id'     => '4570u1419855885119185',
			'order_id'            => '_5',
			'type'                => 'buy',
			'description'         => 'Поплнение счета (LiqPay): 0.10 грн.',
			'currency'            => 'UAH',
			'amount'              => '0.10',
			'public_key'          => 'i20715277130',
			'version'             => '2',
		);
// */
		// check response signature
		$signature = $_POST[ 'signature' ];
		if( empty( $signature ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Пустая подпись',
			);
			return( $result );
		}
		// calc signature
		$_signature = $this->signature( $_POST, $is_request = false );
		if( $signature != $_signature ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверная подпись',
			);
			return( $result );
		}
		// update operation
		$response = $this->_response_parse( $_POST );
		// check public key (merchant)
		$public_key = $response[ 'key_public' ];
		$_public_key = $this->key( 'public' );
		if( $public_key != $_public_key ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный ключ (merchant)',
			);
			return( $result );
		}
		// check status
		// success, failure, wait_secure, sandbox
		$state = $response[ 'status' ];
		if( $this->TEST_MODE && $state == 'sandbox' ) { $state = 'success'; }
		list( $payment_status_name, $status_message ) = $this->_state( $state );
		// update account, operation data
		$result = $this->_api_deposition( array(
			'provider_name'       => 'liqpay',
			'response'            => $response,
			'payment_status_name' => $payment_status_name,
			'status_message'      => $status_message,
		));
		return( $result );
	}

	public function _response_parse( $response ) {
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
			// 'description'  => $operation_id,
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
			'status_message' => 'Поплнение через сервис: LiqPay',
		);
		return( $result );
	}

}
