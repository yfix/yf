<?php

class yf_payment_api__provider_remote {

	public $ENABLE    = null;
	public $TEST_MODE = null;

	public $URL         = null;
	public $KEY_PUBLIC  = null;
	public $KEY_PRIVATE = null;

	public $URL_API      = null;
	public $URL_API_TEST = null;

	public $method_allow = null;

	public $API_SSL_VERIFY = true;

	public $IS_DEPOSITION    = null;
	public $IS_PAYMENT       = null;

	public $IS_PAYIN_MANUAL  = null;
	public $IS_PAYOUT_MANUAL = null;

	public $service_allow = null;
	public $description   = null;

	public $_status = array();
	public $_status_message = array(
		'success'     => 'Выполнено: ',
		'in_progress' => 'Ожидание: ',
		'refused'     => 'Отклонено: ',
	);

	public $payment_api = null;
	public $api         = null;

	public function _init() {
		if( !$this->ENABLE ) { return( null ); }
		$this->payment_api = _class( 'payment_api' );
		!empty( $this->service_allow ) && $this->description = implode( ', ', $this->service_allow );
	}

	public function allow( $value = null ) {
		$result = &$this->ENABLE;
		if( isset( $value ) ) {
			$value = (bool)$value;
			// init if enable
			if( !$result && $value ) { $this->_init(); }
			$result = $value;
		}
		return( $result );
	}

	protected function _state( $value, $status = null, $status_message = null ) {
		if( !$this->ENABLE ) { return( null ); }
		if( !is_array( $status         ) ) { $status         = &$this->_status;         }
		if( !is_array( $status_message ) ) { $status_message = &$this->_status_message; }
		$name    = isset( $status[ $value ] ) ? $status[ $value ] : null;
		$message = isset( $status_message[ $name ] ) ? $status_message[ $name ] : null;
		return( array( $name, $message ) );
	}

	protected function _ip( $options = null ) {
		if( !empty( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ) {
			$ips = explode( ',', $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] );
			$ip  = reset( $ips );
		} else {
			$ip =
				   $_SERVER[ 'HTTP_CLIENT_IP' ]
				?: $_SERVER[ 'HTTP_X_REAL_IP' ]
				?: $_SERVER[ 'REMOTE_ADDR' ]
			;
		}
		$result = trim( $ip );
		return( $result );
	}

	protected function _check_ip( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// allow ip
		$ip_allow = isset( $_provider_ip_allow ) ? $_provider_ip_allow : $this->provider_ip_allow;
		$ip = isset( $_ip ) ? $_ip : $this->_ip();
		$result = empty( $ip_allow[ $ip ] ) ? false : true;
		return( $result );
	}

	public function is_test( $options = null ) {
		$result = false;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( !empty( $this->TEST_MODE ) || !empty( $_test_mode ) ) { $result = true; }
		return( $result );
	}

	public function api_url( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( $this->is_test( $options ) ) {
			$result = &$this->URL_API_TEST;
		} else {
			$result = &$this->URL_API;
		}
		if( is_array( $_uri ) ) {
			$result = str_replace( array_keys( $_uri ), array_values( $_uri ), $result );
		}
		return( $result );
	}

	public function validate( $options ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// check payment method, type
		if( empty( $_method_id ) || empty( $_type_name ) ) { return( $this->result_success() ); }
		// payway
		$payway = null;
		switch( $_type_name ) {
			case 'deposition': $payway = 'payin';  break;
			case 'payment':    $payway = 'payout'; break;
		}
		if( empty( $payway ) || empty( $this->method_allow[ $payway ][ $_method_id ] ) ) { return( $this->result_success() ); }
		// method
		$method = &$this->method_allow[ $payway ][ $_method_id ];
		if( empty( $method[ 'option_validation' ] ) ) { return( $this->result_success() ); }
		$validation         = $method[ 'option_validation' ];
		$validation_message = @$method[ 'option_validation_message' ];
		$validation_error   = array();
		$validate = _class( 'validate' );
		// validation processor
		foreach( $method[ 'option' ] as $key => $item ) {
			// skip: empty validator
			if( empty( $validation[ $key ] ) ) { continue; }
			// processor
			$value  = trim( ${ '_'. $key } );
			$rules  = $validation[ $key ];
			$result = $validate->_input_is_valid( $value, $rules );
			if( empty( $result ) ) {
				$message = @$validation_message[ $key ] ?: 'Неверное поле';
				$validation_error[ $key ] = t( $message );
			}
		}
		if( empty( $validation_error ) ) { return( $this->result_success() ); }
		return( $this->result_fail( t( 'Неверно заполненные поля для вывода средств, проверьте и повторите запрос.' ), $validation_error ) );
	}

	public function result_success() {
		return( array( 'status' => true ) );
	}

	public function result_fail( $message, $options = null ) {
		return( array( 'status' => false, 'status_message' => $message, 'options' => $options ) );
	}

	public function api_method_payin( $name ) {
		$result = null;
		if(
			empty( $name )
			|| empty( $this->method_allow )
			|| empty( $this->method_allow[ 'payin' ] )
			|| empty( $this->method_allow[ 'payin' ][ $name ] )
			|| !is_array( $this->method_allow[ 'payin' ][ $name ] )
		) {
			return( $result );
		}
		$result = $this->method_allow[ 'payin' ][ $name ];
		return( $result );
	}

	public function api_method_payout( $name ) {
		$result = null;
		if(
			empty( $name )
			|| empty( $this->method_allow )
			|| empty( $this->method_allow[ 'payout' ] )
			|| empty( $this->method_allow[ 'payout' ][ $name ] )
			|| !is_array( $this->method_allow[ 'payout' ][ $name ] )
		) {
			return( $result );
		}
		$result = $this->method_allow[ 'payout' ][ $name ];
		return( $result );
	}

	protected function _api_post( $url, $post, $options = true ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// options
		$options = array(
			// CURLOPT_URL            =>  $url,
			CURLOPT_POST           =>  true,
			CURLOPT_POSTFIELDS     =>  $post,
			CURLOPT_RETURNTRANSFER =>  true,
		);
		if( !empty( $_is_json ) ) {
			$options += array(
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json; charset=utf-8'
				),
			);
		}
		if( $this->API_SSL_VERIFY && strpos( $url, 'https' ) !== false ) {
			$options += array(
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_CAINFO         => __DIR__ . '/ca.pem',
			);
		} else {
			$options += array(
				CURLOPT_SSL_VERIFYPEER => false,
			);
		}
		// exec
		$ch = curl_init( $url );
		curl_setopt_array( $ch, $options );
		$result    = curl_exec( $ch );
		// status
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$error_number  = curl_errno( $ch );
		$error_message = curl_error( $ch );
		curl_close( $ch );
// DEBUG
// var_dump( $url, $options, $result, $http_code );
// exit;
		// result
		$status = null;
		if( $result === false ) {
			$message = sprintf( '[%d] %s', $error_number, $error_message );
			$result = array(
				'status'         => $status,
				'status_message' => 'Ошибка транспорта: ' . $message,
			);
			return( $result );
		}
		switch( $http_code ) {
			case 200: $status = true;                break;
			case 400: $message = 'неверный запрос';  break;
			case 401: $message = 'неавторизован';    break;
			case 403: $message = 'доступ ограничен'; break;
			case 404: $message = 'неверный адрес';   break;
			default:
				if( $http_code >= 500 ) {
					$message = 'ошибка сервера';
				}
				break;
		}
		if( $http_code != 200 ) {
			$result = sprintf( 'Ошибка транспорта: [%d] %s', $http_code, $message );
		}
		return( array( $status, $result ) );
	}

	public function _api_request( $url, $data, $options = array() ) {
		if( !$this->ENABLE ) { return( null ); }
		$result = $this->_api_post( $url, $data, $options );
		return( $result );
	}

	public function _api_deposition( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		$result = $this->_api_transaction( $options );
		return( $result );
	}

	public function _api_payment( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		$result = $this->_api_transaction( $options );
		return( $result );
	}

	public function _api_transaction( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// vars
		$payment_api = $this->payment_api;
// DEBUG
$payment_api->dump(array( 'var' => array(
	'transaction' => $options,
)));
		// response operation id
		$operation_id = (int)$_response[ 'operation_id' ];
		if( empty( $operation_id ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Не определен код операции',
			);
// DEBUG
// $payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		// exists operation
		$operation = $payment_api->operation( array(
			'operation_id' => $operation_id,
		));
		if( empty( $operation ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Операция отсутствует: ' . $operation_id,
			);
// DEBUG
// $payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		$operation_options = $operation[ 'options' ];
		// operation request options
		if( !is_array( $operation_options[ 'request' ] ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Отсутствуют опции операции',
			);
// DEBUG
// $payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		// request data
		$request = reset( $operation_options[ 'request' ] );
		$request_data = $request[ 'data' ];
		// operation options
		$_operation_id = (int)$operation[ 'operation_id' ];
		$account_id    = (int)$operation[ 'account_id'   ];
		$provider_id   = (int)$operation[ 'provider_id'  ];
		$amount        = $payment_api->_number_float( $operation[ 'amount' ] );
		// check request/response operation_id
		if( $operation_id != $_operation_id ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный код операции',
			);
// DEBUG
// $payment_api->dump(array( 'var' => $result ));
			return( $result );
		}
		// get current status
		$new_status_name = $_status_name;
// DEBUG
// $payment_api->dump(array( 'var' => array(
	// 'new_status_name' => $new_status_name,
// )));
		$object = $payment_api->get_status( array( 'name' => $new_status_name ) );
		list( $new_status_id, $new_status ) = $object;
		if( empty( $new_status_id ) ) { return( $object ); }
		// check request provider
		$object = $payment_api->provider( array(
			'is_service'  => true,
			'provider_id' => $provider_id,
		));
		if( empty( $object ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неверный провайдер',
			);
			return( $result );
		}
		$provider      = reset( $object );
		$provider_name = $provider[ 'name' ];
		// check request/response provider
		if( $provider_name != $_provider_name ) {
			$result = array(
				'status'         => false,
				'status_message' => "Провайдер не совпадает ($_provider_name)",
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
		// sql options
		$sql_amount   = $payment_api->_number_mysql( $amount );
		$sql_datetime = $payment_api->sql_datetime();
		$balance      = null;
		// get current status_name
		$current_status_id = (int)$operation[ 'status_id' ];
// DEBUG
// $payment_api->dump(array( 'var' => array(
	// 'current_status_id' => $current_status_id,
// )));
		$object = $payment_api->get_status( array( 'status_id' => $current_status_id ) );
		list( $current_status_id, $current_status ) = $object;
		if( empty( $current_status_id ) ) { return( $object ); }
		$current_status_name = $current_status[ 'name' ];
		// get payment type
		$current_type_id = (int)$operation[ 'type_id' ];
// DEBUG
// $payment_api->dump(array( 'var' => array(
	// 'current_type_id' => $current_type_id,
// )));
		$object = $payment_api->get_type( array( 'type_id' => $current_type_id ) );
		list( $current_type_id, $current_type ) = $object;
		if( empty( $current_type_id ) ) { return( $object ); }
		$current_type_name = $current_type[ 'name' ];
		// start update
		$is_try =
			( $current_type_name == 'payment'    && $current_status_name == 'in_progress' )
			||
			( $current_type_name == 'deposition' && $current_status_name != 'success' )
		;
// DEBUG
$payment_api->dump(array( 'var' => array(
	'is_try'              => $is_try,
	'current_type_name'   => $current_type_name,
	'current_status_name' => $current_status_name,
)));
		if( $is_try ) {
			db()->begin();
			$direction = $operation[ 'direction' ];
			$is_manual = null;
			$is_payin  = null;
			$is_payout = null;
			$is_update_balance = null;
			$is_update_status  = null;
			switch( $current_type_name ) {
				case 'payment':
					$is_payout = true;
					$is_manual = $this->IS_PAYOUT_MANUAL;
					// revert amount
					if( !$is_manual ) {
						$is_update_status = true;
						if( $new_status_name == 'refused' ) {
							$is_update_balance = true;
							$sql_sign  = '+';
						}
					}
					$mail_tpl  = 'payment';
					break;
				case 'deposition':
					$is_payin  = true;
					$is_manual = $this->IS_PAYIN_MANUAL;
					// add amount
					if( !$is_manual ) {
						$is_update_status = true;
						if( $new_status_name == 'success' ) {
							$is_update_balance = true;
							$sql_sign  = '+';
						}
					}
					$mail_tpl  = 'payout';
					break;
			}
			// update account balance
			if( $is_update_balance && $current_status_id != $new_status_id ) {
// DEBUG
$payment_api->dump(array( 'var' => array(
	'is_update_balance' => $is_update_balance,
)));
				// update account
				$_data = array(
					'account_id'      => $account_id,
					'datetime_update' => db()->escape_val( $sql_datetime ),
					'balance'         => "( balance $sql_sign $sql_amount )",
				);
				$_result = $payment_api->balance_update( $_data, array( 'is_escape' => false ) );
				if( !$_result[ 'status' ] ) {
					db()->rollback();
					$result = array(
						'status'         => false,
						'status_message' => 'Ошибка при обновлении счета',
					);
					// mail admin
					$tpl = $mail_tpl . '_refused';
					$payment_api->mail( array(
						'subject'  => 'DB error: payment account update error, id operation: '. $account_id,
						'tpl'      => $tpl,
						'user_id'  => $account[ 'user_id' ],
						'is_admin' => true,
						'data'    => array(
							'operation_id' => $operation_id,
							'amount'       => $amount,
						),
					));
					return( $result );
				}
				// mail
				$tpl = $mail_tpl . '_'. $new_status_name;
				$payment_api->mail( array(
					'tpl'     => $tpl,
					'user_id' => $account[ 'user_id' ],
					'admin'   => true,
					'data'    => array(
						'operation_id' => $operation_id,
						'amount'       => $amount,
					),
				));
			}
			if( $is_update_status ) {
// DEBUG
$payment_api->dump(array( 'var' => array(
	'is_update_status' => $is_update_status,
)));
				// get balance
				$object = $payment_api->get_account__by_id( array( 'account_id' => $account_id, 'force' => true ) );
				list( $account_id, $account ) = $object;
				$balance = $account[ 'balance' ];
				// save response
				if( empty( $_response[ 'message' ] ) ) {
					switch( $new_status_name ) {
					case 'success':
						$message = 'Выполнено';
						break;
					case 'in_progress':
						$message = 'В процессе';
						break;
					case 'refused':
					default:
						$message = 'Отклонено';
						break;
					}
					$_response[ 'message' ] = $message;
					// code...
				}
				// update operation
				$data = array(
					'response' => array( array(
						'data'     => $_response,
						'datetime' => $sql_datetime,
					))
				);
				$data = array(
					'operation_id'    => $operation_id,
					'status_id'       => $new_status_id,
					'datetime_update' => $sql_datetime,
					'options'         => $data,
				);
				$balance && ( $data += array(
					'balance'         => $balance,
					'datetime_finish' => $sql_datetime,
				));
				$result = $payment_api->operation_update( $data );
				if( !$result[ 'status' ] ) {
					db()->rollback();
					// mail admin
					$tpl = $mail_tpl . '_refused';
					$payment_api->mail( array(
						'subject'  => 'DB error: payment operation update error, id operation: '. $operation_id,
						'tpl'      => $tpl,
						'user_id'  => $account[ 'user_id' ],
						'is_admin' => true,
						'data'    => array(
							'operation_id' => $operation_id,
							'amount'       => $amount,
						),
					));
					return( $result );
				}
			}
			db()->commit();
			$status_message = $_status_message;
		} else {
			$status_message = 'Выполнено повторно: ';
			// mail admin
			$tpl = $mail_tpl . '_success';
			$payment_api->mail( array(
				'subject'  => 'Payment operation notification again, id operation: '. $operation_id,
				'tpl'      => $tpl,
				'user_id'  => $account[ 'user_id' ],
				'is_admin' => true,
				'data'    => array(
					'operation_id' => $operation_id,
					'amount'       => $amount,
				),
			));
		}
		$status_message .= $operation[ 'title' ] . ', сумма: ' . $amount;
		if( !empty( $payment_api->currency[ 'short' ] ) ) {
			$status_message .= ' ' . $payment_api->currency[ 'short' ];
		}
		$result = array(
			'status'         => true,
			'status_message' => $status_message,
		);
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
		ini_set( 'html_errors', 0 );
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
			'status_message' => t( 'Заявка на ввод средств принята' ),
		);
		return( $result );
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
		$request_data  = array(
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
		$operation_options = array(
			'request' => array( array(
				'options'  => $options,
				'data'     => $request_data,
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
