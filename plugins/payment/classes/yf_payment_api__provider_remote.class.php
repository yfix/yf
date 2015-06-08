<?php

if( !function_exists( 'array_replace_recursive' ) ) {
	trigger_error( 'Not exists function "array_replace_recursive ( PHP 5 >= 5.3.0 )"', E_USER_ERROR );
}

class yf_payment_api__provider_remote {

	public $ENABLE    = null;
	public $TEST_MODE = null;

	public $URL         = null;
	public $KEY_PUBLIC  = null;
	public $KEY_PRIVATE = null;

	public $URL_API      = null;
	public $URL_API_TEST = null;
	public $API_KEY_PUBLIC  = null;
	public $API_KEY_PRIVATE = null;

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
		'processing'  => 'Обработка: ',
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
		$name    = @$status[ $value ] ?: null;
		$message = @$status_message[ $name ] ?: @$status_message[ $value ] ?: null;
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

	public function option_transform( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		foreach( (array)$_transform as $from => $to ) {
			if( isset( $_option[ $from ] ) && $from != $to ) {
				$_option[ $to ] = $_option[ $from ];
				unset( $_option[ $from ] );
			}
		}
	}

	public function api_url( $options = null, $request_options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( !empty( $_url ) ) {
			$result = $_url;
		} elseif( $this->is_test( $options ) && !empty( $this->URL_API_TEST ) ) {
			$result = $this->URL_API_TEST;
		} else {
			$result = $this->URL_API;
		}
		if( is_array( $_uri ) ) {
			// prepare uri
			$uri = array();
			foreach( $_uri as $id => $value ) {
				if( is_string( $value ) && strpos( $value, '$' ) !== false ) {
					$v = str_replace( '$', '', $value );
					$v = @$request_options[ $v ];
					if( is_null( $v ) ) {
						$result = array(
							'status'         => false,
							'status_message' => 'error api url: required option '. $value,
						);
						return( $result );
					}
					$value = $v;
				}
				$uri[ $id ] = $value;
			}
			$result = str_replace( array_keys( $uri ), array_values( $uri ), $result );
		}
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
		if( $status[ 'name' ] != 'in_progress' && $status[ 'name' ] != 'processing' ) {
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
		// method
		$method = $this->api_method( array(
			'type'      => @$payway,
			'method_id' => @$_method_id,
		));
		if( empty( $method ) ) { return( $this->result_success() ); }
		// validation options
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

	public function api_authorization( $method = null ) {
		$result = null;
		if( ! @$method[ 'is_authorization' ] ) { return( $result ); }
		$result = array(
			'user'     => $this->API_KEY_PUBLIC,
			'password' => $this->API_KEY_PRIVATE,
		);
		return( $result );
	}

	public function api_method( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$result = null;
		if( !is_array( @$this->method_allow[ $_type ][ $_method_id ] ) ) { return( $result ); }
		$result = $this->method_allow[ $_type ][ $_method_id ];
		return( $result );
	}

	protected function _api_post( $url, $post, $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// options
		$options = array(
			// CURLOPT_URL            =>  $url,
			CURLOPT_RETURNTRANSFER =>  true,
		);
		$header = array();
		if( !empty( $post ) ) {
			if( @$_is_json ) {
				$http_post = json_encode( $post );
			} else {
				$http_post = http_build_query( $post );
			}
			$options += array(
				CURLOPT_POST       => true,
				CURLOPT_POSTFIELDS => $http_post,
			);
		}
		if( !empty( $_user ) && !empty( $_password ) ) {
			$userpwd = $_user;
			!empty( $_password ) && $userpwd .= ':'. $_password;
			$options += array(
				CURLOPT_HTTPAUTH => CURLAUTH_ANY,
				CURLOPT_USERPWD  => $userpwd,
			);
		}
		if( !empty( $_is_json ) ) {
			$header[] = 'Content-Type: application/json; charset=utf-8';
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
		// add header
		if( !empty( $_header ) ) {
			$header = array_merge_recursive( $header, $_header );
		}
		!empty( $header ) && $options += array( CURLOPT_HTTPHEADER => $header );
		// debug request header
		$options += array(
			CURLINFO_HEADER_OUT => true,
		);
		// debug response header
		$options += array(
			CURLOPT_VERBOSE => true,
			CURLOPT_HEADER  => true,
		);
		// exec
		$ch = curl_init( $url );
		curl_setopt_array( $ch, $options );
		$response = curl_exec( $ch );
		// status
		$http_code   = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$http_header = curl_getinfo( $ch, CURLINFO_HEADER_OUT );
		$error_number  = curl_errno( $ch );
		$error_message = curl_error( $ch );
		// DEBUG
		@$_is_debug && var_dump( $url, $options, $http_code, $http_header );
		// exit;
		// response
		$status = null;
		if( $response === false ) {
			$message = sprintf( '[%d] %s', $error_number, $error_message );
			$result = array(
				'status'         => $status,
				'status_message' => 'Ошибка транспорта: ' . $message,
			);
			return( $result );
		}
		// get header, body
		$http_header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
		$header = substr( $response, 0, $http_header_size );
		$body   = substr( $response, $http_header_size );
		// DEBUG
		@$_is_debug && var_dump( $header, $body );
		// exit;
		// http code
		$message = '';
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
		// detect content type of response
		if( empty( $_is_response_raw ) ) {
			$content_type = curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );
			switch( true ) {
			case $content_type == 'application/json' || $_is_response_json:
				$result = @json_decode( $body, true );
				break;
			}
		} else {
			$result = $body;
		}
		// finish
		curl_close( $ch );
		return( array( $status, $result ) );
	}

	public function _api_request( $url, $data, $options = null ) {
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
		if( !@$_provider_force && $provider_name != $_provider_name ) {
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
			( $current_type_name == 'payment'    && $current_status_name == 'processing' )
			||
			( $current_type_name == 'deposition' && $current_status_name != 'success' )
		;
// DEBUG
$payment_api->dump(array( 'var' => array(
	'is_try'              => $is_try,
	'current_type_name'   => $current_type_name,
	'current_status_name' => $current_status_name,
)));
		// prepare
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
				$mail_tpl  = 'payout';
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
				$mail_tpl  = 'payin';
				break;
		}
		if( $is_try ) {
			db()->begin();
			$direction = $operation[ 'direction' ];
			// update account balance
			if( $current_status_id != $new_status_id ) {
				if( $is_update_balance ) {
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
						$message = 'Ошибка при обновлении счета';
						$result = array(
							'status'         => false,
							'status_message' => $message,
						);
						// mail admin
						$tpl = $mail_tpl . '_error';
						$payment_api->mail( array(
							'subject'  => 'Ошибка платежа (id: '. $operation_id . ')',
							'tpl'      => $tpl,
							'user_id'  => $account[ 'user_id' ],
							'is_admin' => true,
							'data'    => array(
								'operation_id' => $operation_id,
								'message'      => $message,
							),
						));
						return( $result );
					}
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
						case 'processing':
							$message = 'Обработка';
							break;
						case 'refused':
						default:
							$message = 'Отклонено';
							break;
					}
					$_response[ 'message' ] = $message;
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
					$tpl = $mail_tpl . '_error';
					$message = 'Ошибка при обновлении операции';
					$payment_api->mail( array(
						'subject'  => 'Ошибка платежа (id: '. $operation_id . ')',
						'tpl'      => $tpl,
						'user_id'  => $account[ 'user_id' ],
						'is_admin' => true,
						'data'    => array(
							'operation_id' => $operation_id,
							'message'      => $message,
						),
					));
					return( $result );
				}
			}
			db()->commit();
			@$_status_message && $status_message = $_status_message;
		} else {
			$message = 'Повторный запрос на выполнение операции';
			$status_message = $message;
			// mail admin
			$tpl = $mail_tpl . '_error';
			$payment_api->mail( array(
				'subject'  => 'Ошибка платежа (id: '. $operation_id . ')',
				'tpl'      => $tpl,
				'user_id'  => $account[ 'user_id' ],
				'is_admin' => true,
				'data'    => array(
					'operation_id' => $operation_id,
					'message'      => $message,
				),
			));
		}
		@$status_message && $status_message .= ': ';
		$status_message .= $operation[ 'title' ] .', сумма: '. $amount;
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
		$method = $this->api_method( array(
			'type'      => 'payout',
			'method_id' => $_method_id,
		));
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
		$method = $this->api_method( array(
			'type'      => 'payout',
			'method_id' => $_method_id,
		));
		$key = 'fee';
		if( empty( $method ) || empty( $method[ $key ] ) ) { return( null ); }
		$result = $method[ $key ];
		if( is_array( $result[ 'out' ] ) ) {
			$result = $result[ 'out' ];
		}
		return( $result );
	}

	public function currency_conversion_payout( $options ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$amount = &$_amount;
		// processing
		if( !empty( $_method[ 'is_currency' ] ) ) {
			// currency_id
			$currency_id = $this->get_currency_payout( $_options );
			if( empty( $currency_id ) ) {
				$result = array(
					'status'         => false,
					'status_message' => 'Неизвестная валюта',
				);
				return( $result );
			}
			$payment_api = &$this->payment_api;
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
			$amount = $amount_currency;
			// fee
			if( !empty( $method[ 'is_fee' ] ) ) {
				$fee = $this->get_fee_payout( $_options );
				$amount_currency_total = $payment_api->fee( $amount_currency, $fee );
				$amount = $amount_currency_total;
			}
		}
		$result = array(
			'status'                => true,
			'amount'                => $amount,
			'amount_currency'       => @$amount_currency,
			'amount_currency_total' => @$amount_currency_total,
			'currency_id'           => @$currency_id,
		);
		return( $result );
	}

	public function amount_limit( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( !empty( $_method[ 'amount' ] ) ) {
			$min = @$_method[ 'amount' ][ 'min' ];
			$max = @$_method[ 'amount' ][ 'max' ];
			$status = false;
			switch( true ) {
				case isset( $min ) && $_amount < $min: $status_message = 'больше '. $min; break;
				case isset( $max ) && $_amount > $max: $status_message = 'меньше '. $max;  break;
				default: $status = true; break;
			}
			if( empty( $status ) ) {
					$result = array(
						'status'         => false,
						'status_message' => @sprintf(
							'Сумма %s: %s должна быть %s'
								, $_currency_id
								, $_amount
								, $status_message
						),
					);
					return( $result );
			}
		}
		$result = array( 'status' => true );
		return( $result );
	}

	public function _amount_payout( $amount, $currency_id, $method, $is_request = true ) {
		if( !$this->ENABLE ) { return( null ); }
		empty( $currency_id ) && $currency_id = $this->currency_default;
		if( empty( $currency_id ) ) { return( null ); }
		$payment_api = $this->payment_api;
		list( $_currency_id, $currency ) = $payment_api->get_currency__by_id( array(
			'currency_id' => $currency_id,
		));
		if( empty( $_currency_id ) ) { return( null ); }
		// use conversion to integer by minor_units
		$is_int = false;
		if( !empty( $method )
			&& !empty( $method[ 'currency' ] )
			&& !empty( $method[ 'currency' ][ $_currency_id ] )
			&& !empty( $method[ 'currency' ][ $_currency_id ][ 'is_int' ] )
		) {
			$is_int = true;
		}
		if( $is_int ) {
			$units = pow( 10, $currency[ 'minor_units' ] );
			if( $is_request ) {
				$result = (int)( $amount * $units );
			} else {
				$result = (float)$amount / $units;
			}
		} else {
			$result = $amount;
		}
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
		$method = $this->api_method( array(
			'type'      => 'payin',
			'method_id' => @$options[ 'method_id' ],
		));
		!empty( $method[ 'option' ] ) && $form_options += $method[ 'option' ];
		// form
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
