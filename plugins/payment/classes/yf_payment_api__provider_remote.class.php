<?php

class yf_payment_api__provider_remote {

	public $ENABLE    = null;
	public $TEST_MODE = null;

	public $URL         = null;
	public $KEY_PUBLIC  = null;
	public $KEY_PRIVATE = null;

	public $API_SSL_VERIFY = true;

	public $IS_DEPOSITION = null;
	public $IS_PAYMENT    = null;

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
		$this->payment_api = _class( 'payment_api' );
		!empty( $this->service_allow ) && $this->description = implode( ', ', $this->service_allow );
	}

	public function allow( $value ) {
		$result = &$this->ENABLE;
		isset( $value ) && $result = (bool)$value;
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

	protected function _api_post( $url, $post ) {
		// options
		$options = array(
			// CURLOPT_URL            =>  $url,
			CURLOPT_POST           =>  true,
			CURLOPT_POSTFIELDS     =>  $post,
			CURLOPT_RETURNTRANSFER =>  true,
		);
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
		// debug
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

	protected function _api_request( $uri, $data ) {
		$url    = $this->URL . $uri;
		$result = $this->_api_post( $url, $data );
		return( $result );
	}

	protected function _api_deposition( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		$result = $this->_api_transaction( $options );
		return( $result );
	}

	protected function _api_payment( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		$result = $this->_api_transaction( $options );
		return( $result );
	}

	protected function _api_transaction( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// vars
		$payment_api = $this->payment_api;
		// get success status
		$object = $payment_api->get_status( array( 'name' => 'success' ) );
		list( $payment_status_success_id, $payment_success_status ) = $object;
		if( empty( $payment_status_success_id ) ) { return( $object ); }
		// get currency status
		$object = $payment_api->get_status( array( 'name' => $_payment_status_name ) );
		list( $_payment_status_id, $payment_status ) = $object;
		if( empty( $_payment_status_id ) ) { return( $object ); }
		// get operation options
		$operation_id = (int)$_response[ 'operation_id' ];
		if( empty( $operation_id ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Не определен код операции',
			);
			return( $result );
		}
		$operation = $payment_api->operation( array(
			'operation_id' => $operation_id,
			'test'=> true
		));
		if( empty( $operation ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Операция отсутствует: ' . $operation_id,
			);
			return( $result );
		}
		$operation_options = $operation[ 'options' ];
		// check operation options
		if( empty( $operation_options[ 'request' ] ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Отсутствуют опции операции',
			);
			return( $result );
		}
		$request = reset( $operation_options[ 'request' ] );
		$operation_data = $request[ 'data' ];
			$user_id         = (int)$operation_data[ 'user_id'      ];
			$_operation_id   = (int)$operation_data[ 'operation_id' ];
			$account_id      = (int)$operation_data[ 'account_id'   ];
			$provider_id     = (int)$operation_data[ 'provider_id'  ];
			$amount          = $payment_api->_number_float( $operation_data[ 'amount' ] );
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
		$provider      = reset( $object );
		$provider_name = $provider[ 'name' ];
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
		// update
		$sql_amount   = $payment_api->_number_mysql( $amount );
		$sql_datetime = $payment_api->sql_datatime();
		$balance      = null;
		$payment_status_id = (int)$operation[ 'status_id' ];
		if( $payment_status_success_id != $payment_status_id ) {
			db()->begin();
			if( $payment_status_id != $_payment_status_id && $_payment_status_name == 'success' ) {
				// update account
				$_data = array(
					'account_id'      => $account_id,
					'datetime_update' => db()->escape_val( $sql_datetime ),
					'balance'         => '( balance + ' . $sql_amount . ' )',
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
				// get balance
				$object = $payment_api->get_account__by_id( array( 'account_id' => $account_id, 'force' => true ) );
				list( $account_id, $account ) = $object;
				$balance = $account[ 'balance' ];
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
				'status_id'       => $_payment_status_id,
				'datetime_update' => $sql_datetime,
				'options'         => $data,
			);
			$balance && ( $data += array(
				'balance'         => $balance,
				'datetime_finish' => $sql_datetime,
			));
			// save options
			$result = $payment_api->operation_update( $data );
			if( !$result[ 'status' ] ) {
				db()->rollback();
				return( $result );
			}
			db()->commit();
			$status_message = $_status_message;
		} else {
			$status_message = 'Выполнено повторно: ';
		}
		$status_message .= $_response[ 'title' ] . ', сумма: ' . $amount;
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
