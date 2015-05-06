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
		return( $result );
	}

	public function validate( $options = null ) {
		return( $this->result_success() );
	}

	public function result_success() {
		return( array( 'status' => true ) );
	}

	public function result_fail( $message ) {
		return( array( 'status' => false, 'status_message' => $message ) );
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
			case 200:
				$status = true;
				break;
			case 400:
				$message = 'неверный запрос';
				break;
			case 401:
				$message = 'неавторизован';
				break;
			case 403:
				$message = 'доступ ограничен';
				break;
			case 404:
				$message = 'неверный адрес';
				break;
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
		// response operation id
		$operation_id = (int)$_response[ 'operation_id' ];
		if( empty( $operation_id ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Не определен код операции',
			);
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
			return( $result );
		}
		$operation_options = $operation[ 'options' ];
		// operation request options
		if( !is_array( $operation_options[ 'request' ] ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Отсутствуют опции операции',
			);
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
			return( $result );
		}
		// get in_progress status
		$object = $payment_api->get_status( array( 'name' => 'in_progress' ) );
		list( $payment_status_in_progress_id, $payment_in_progress_status ) = $object;
		if( empty( $payment_status_in_progress_id ) ) { return( $object ); }
		// get current status
		$object = $payment_api->get_status( array( 'name' => $_status_name ) );
		list( $_status_id, $_status ) = $object;
		if( empty( $_status_id ) ) { return( $object ); }
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
		$current_status_id = (int)$operation[ 'status_id' ];
		// start update
		if( $payment_status_in_progress_id == $current_status_id ) {
			db()->begin();
			$direction = $operation[ 'direction' ];
			$is_manual = null;
			$is_payin  = null;
			$is_payout = null;
			$is_update = null;
			switch( $direction ) {
				case 'out':
					$is_payout = true;
					$is_manual = $this->IS_PAYOUT_MANUAL;
					// revert amount
					if( !$is_manual && $_status_name == 'refused' ) {
						$is_update = true;
						$sql_sign  = '+';
					}
					$mail_tpl  = 'payment';
					break;
				case 'in':
					$is_payin  = true;
					$is_manual = $this->IS_PAYIN_MANUAL;
					// add amount
					if( !$is_manual && $_status_name == 'success' ) {
						$is_update = true;
						$sql_sign  = '+';
					}
					$mail_tpl  = 'payout';
					break;
			}
			// update account balance
			if( $is_update && $current_status_id != $_status_id ) {
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
				$tpl = $mail_tpl . '_'. $_status_name;
				$payment_api->mail( array(
					'tpl'     => $tpl,
					'user_id' => $account[ 'user_id' ],
					'admin'   => true,
					'data'    => array(
						'operation_id' => $operation_id,
						'amount'       => $amount,
					),
				));
				// get balance
				$object = $payment_api->get_account__by_id( array( 'account_id' => $account_id, 'force' => true ) );
				list( $account_id, $account ) = $object;
				$balance = $account[ 'balance' ];
			}
			if( $is_update ) {
				// update operation
				$data = array(
					'response' => array( array(
						'data'     => $_response,
						'datetime' => $sql_datetime,
					))
				);
				$data = array(
					'operation_id'    => $operation_id,
					'status_id'       => $_status_id,
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

}
