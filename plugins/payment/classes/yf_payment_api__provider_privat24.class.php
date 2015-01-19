<?php

// _class( 'payment_api__provider' );

// class yf_payment_api__provider_privat24 extends yf_payment_api__provider {

class yf_payment_api__provider_privat24 {

	public $ENABLE = true;

	public $payment_api = null;
	public $api         = null;

	public $URL         = 'https://api.privatbank.ua/p24api/ishop';
	public $PUBLIC_KEY  = null; // merchant
	public $PRIVATE_KEY = null; // pass

	public $TEST_MODE   = null;

	public $_options_transform = array(
		'amount'       => 'amt',
		'currency'     => 'ccy',
		'title'        => 'details',
		'description'  => 'ext_details',
		'order_id'     => 'order',
		'operation_id' => 'order',
		'url_result'   => 'return_url',
		'url_server'   => 'server_url',
		'public_key'   => 'merchant',
	);

	public $_options_transform_reverse = array(
		'amt'         => 'amount',
		'ccy'         => 'currency',
		'details'     => 'title',
		'ext_details' => 'description',
		'order'       => 'operation_id',
		'merchant'    => 'public_key',
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
	);

	public $fee = 2; // 2%

	public $url_result = null;
	public $url_server = null;

	public function _init() {
		$this->payment_api = _class( 'payment_api' );
		// load api
		require_once( __DIR__ . '/payment_provider/privat24/Privat24.php' );
		$this->api = new Privat24( $this->PUBLIC_KEY, $this->PRIVATE_KEY );
		$this->url_result = url( '/api/payment/provider?name=privat24&operation=response' );
		$this->url_server = url( '/api/payment/provider?name=privat24&operation=response&server=true' );
		// parent
		// parent::_init();
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
		$_[ 'amt' ] = number_format( $_[ 'amt' ], 2, '.', '' );
		empty( $_[ 'merchant'   ] ) && $_[ 'merchant'   ] = $this->PUBLIC_KEY;
		empty( $_[ 'pay_way'    ] ) && $_[ 'pay_way'    ] = 'privat24';
		if( empty( $_[ 'return_url' ] ) ) {
			$_[ 'return_url' ] = $this->url_result
				. '&operation_id=' . (int)$options[ 'operation_id' ];
		}
		if( empty( $_[ 'server_url' ] ) ) {
			$_[ 'server_url' ] = $this->url_server
				. '&operation_id=' . (int)$options[ 'operation_id' ];
		}
		if( empty( $_[ 'amt' ] ) || empty( $_[ 'merchant' ] ) ) { $_ = null; }
		return( $_ );
	}

	public function _form( $data, $options = null ) {
		if( empty( $data ) ) { return( null ); }
		$_ = &$options;
		$is_array = (bool)$_[ 'is_array' ];
		$form_options = $this->_form_options( $data );
		$signature    = $this->api->cnb_signature( $form_options );
		if( empty( $signature ) ) { return( null ); }
		$form_options[ 'signature' ] = $signature;
		$url = &$this->URL;
		$result = array();
		if( $is_array ) {
			$result[ 'url' ] = $url;
		} else {
			$result[] = '<form id="_js_provider_privat24_form" method="POST" accept-charset="utf-8" action="' . $url . '" class="display: none;">';
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
		$payment_api = $this->payment_api;
		$is_server = !empty( $_GET[ 'server' ] );
		$result = null;
		// check operation
		$operation_id = (int)$_GET[ 'operation_id' ];
		// response POST:
		$payment   = $_POST[ 'payment'   ];
		$signature = $_POST[ 'signature' ];
		// test data
		// $payment = 'amt=16.00&ccy=UAH&details=Поплнение счета (Приват 24)&ext_details=3#71#9#3#16&pay_way=privat24&order=71&merchant=104702&state=test&date=171214180311&ref=test payment&payCountry=UA';
		// $signature = '585b0c173ec36300a5ff77f6cbd9f195492f0c0d';
		// check signature
		if( empty( $signature ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Пустая подпись',
			);
			return( $result );
		}
		$_signature = $this->api->string_to_sign( $payment );
		if( $signature != $_signature ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверная подпись',
			);
			return( $result );
		}
		// update operation
		$response = $this->_response_parse( $payment );
		// check public key (merchant)
		$public_key = $response[ 'public_key' ];
		$_public_key = $this->PUBLIC_KEY;
		if( $public_key != $_public_key ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный ключ (merchant)',
			);
			return( $result );
		}
		// check status
		// ok, fail, test, wait
		$state = $response[ 'state' ];
		if( $this->TEST_MODE && $state == 'test' ) { $state = 'ok'; }
		$payment_status_name = 'success';
		switch( $state ) {
			case 'ok':
				$payment_status_name = 'success';
				$status_message      = 'Выполнено: ';
				break;
			case 'wait':
				$payment_status_name = 'in_progress';
				$status_message      = 'Ожидание: ';
				break;
			case 'fail':
			default:
				$payment_status_name = 'refused';
				$status_message      = 'Отклонено: ';
				break;
		}
		// get status
		$object = $payment_api->get_status( array( 'name' => 'success' ) );
		if( empty( $object ) ) { return( $object ); }
		list( $payment_status_success_id, $payment_success_status ) = $object;
		// get currency status
		$object = $payment_api->get_status( array( 'name' => $payment_status_name ) );
		if( empty( $object ) ) { return( $object ); }
		list( $payment_status_id, $payment_status ) = $object;
		// get deposition options
		// list( $user_id, $operation_id, $account_id, $provider_id, $amount ) = explode( '#', $response[ 'description' ] );
		$operation_id = (int)$response[ 'operation_id' ];
		$operation = db()->table( 'payment_operation' )
			->where( 'operation_id', $operation_id )
			->get();
		$operation_options = json_decode( $operation[ 'options' ], JSON_NUMERIC_CHECK );
		// check operation options
		if( empty( $operation_options[ 'request' ] ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Отсутствуют опции операции',
			);
			return( $result );
		}
		$request = current( $operation_options[ 'request' ] );
		$operation_data = $request[ 'data' ];
			$user_id         = (int)$operation_data[ 'user_id'      ];
			$_operation_id   = (int)$operation_data[ 'operation_id' ];
			$account_id      = (int)$operation_data[ 'account_id'   ];
			$provider_id     = (int)$operation_data[ 'provider_id'  ];
			$currency_id     = $operation_data[ 'currency_id'  ];
			$amount          = $payment_api->_number_float( $operation_data[ 'amount' ] );
			$amount_currency = $payment_api->_number_float( $operation_data[ 'amount_currency' ] );
		// check operation_id
		if( $operation_id != $_operation_id ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный код операции',
			);
			return( $result );
		}
		// check provider
		$object = $payment_api->provider( array( 'provider_id' => $provider_id ) );
		if( empty( $object ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный провайдер',
			);
			return( $result );
		}
		$provider      = current( $object );
		$provider_name = $provider[ 'name' ];
		if( $provider_name != 'privat24' ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Провайдер не совпадает (privat24)',
			);
			return( $result );
		}
		// check account
		$object = $payment_api->get_account__by_id( array( 'account_id' => $account_id, ) );
		if( empty( $object ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный счет',
			);
			return( $result );
		}
		list( $account_id, $account ) = $object;
		// update
		$sql_amount   = $payment_api->_number_mysql( $amount );
		$sql_datetime = $payment_api->sql_datatime();
		$balance      = null;
		$_payment_status_id = (int)$operation[ 'status_id' ];
		if( $payment_status_success_id != $_payment_status_id ) {
			db()->begin();
			if( $payment_status_id != $_payment_status_id && $payment_status_name == 'success' ) {
				// update account
				$sql_data = array(
					'datetime_update' => db()->escape_val( $sql_datetime ),
					'balance'         => '( balance + ' . $sql_amount . ' )',
				);
				$sql_status = db()->table( 'payment_account' )
					->where( 'account_id', $account_id )
					->order_by( 'account_id' )
					->update( $sql_data, array( 'escape' => false ) );
				if( empty( $sql_status ) ) {
					db()->rollback();
					$result = array(
						'status'         => false,
						'status_message' => 'Ошибка при обновлении счета',
					);
					return( $result );
				}
				// get balance
				$object = $payment_api->get_account__by_id( array( 'account_id' => $account_id, 'force' => true ) );
				list( $account_id, $account ) = $object;
				$balance = $account[ 'balance' ];
			}
			// update operation
			$sql_data = array(
				'status_id'       => $payment_status_id,
				'datetime_update' => $sql_datetime,
			);
			$balance && $sql_data += array(
				'balance'         => $balance,
				'datetime_finish' => $sql_datetime,
			);
			// save options
			$operation_options[ 'response' ][] = array(
				'data'     => $response,
				'datetime' => $sql_datetime,
			);
			$sql_data[ 'options' ] = _es( json_encode( $operation_options ) );
			$sql_status = db()->table( 'payment_operation' )
				->where( 'operation_id', $operation_id )
				->update( $sql_data );
			if( empty( $sql_status ) ) {
				$result = array(
					'status'         => false,
					'status_message' => 'Ошибка при обновлении операции: ' . $operation_id,
				);
				db()->rollback();
				return( $result );
			}
			db()->commit();
		} else {
			$status_message = 'Выполнено повторно: ';
		}
		$status_message .= $response[ 'title' ] . ', сумма: ' . $amount;
		if( !empty( $payment_api->currency[ 'short' ] ) ) {
			$status_message .= ' ' . $payment_api->currency[ 'short' ];
		}
		$result = array(
			'status'         => true,
			'status_message' => $status_message,
		);
		return( $result );
	}

	public function _response_parse( $response ) {
		$options = explode( '&', $response );
		$_ = array();
		foreach( (array)$options as $option ) {
			list( $key, $value ) = explode( '=', $option );
			$_[ $key ] = $value;
		}
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
		$default = $this->default;
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
		if( empty( $amount_currency_total ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Невозможно произвести начисление комисси',
			);
			return( $result );
		}
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
		$operation = db()->table( 'payment_operation' )
			->where( 'operation_id', $operation_id )
			->get();
		$operation_options = (array)json_decode( $operation[ 'options' ], JSON_NUMERIC_CHECK );
		$operation_options[ 'request' ][] = array(
			'data'     => $form_data,
			'form'     => $form_options,
			'datetime' => $operation_data[ 'sql_datetime' ],
		);
		$sql_data = array(
			'options' => _es( json_encode( $operation_options ) ),
		);
		$sql_status = db()->table( 'payment_operation' )
			->where( 'operation_id', $operation_id )
			->update( $sql_data );
		if( empty( $sql_status ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Ошибка при обновлении операции: ' . $operation_id,
			);
			return( $result );
		}
		$result = array(
			'form'           => $form,
			'status'         => true,
			'status_message' => 'Поплнение через сервис: Приват24',
		);
		return( $result );
	}

}
