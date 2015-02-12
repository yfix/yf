<?php

class yf_payment_api__provider_remote {

	public $ENABLE    = null;
	public $TEST_MODE = null;

	public $URL         = null;
	public $KEY_PUBLIC  = null;
	public $KEY_PRIVATE = null;

	public $service_allow = null;
	public $description   = null;

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

	protected function _state( $value ) {
		if( !$this->ENABLE ) { return( null ); }
		$name    = $this->_status[ $value ];
		$message = $this->_status_message[ $name ];
		return( array( $name, $message ) );
	}

	protected function _api_post( $url, $post ) {
		// curl
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL           , $url  );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_POST          , 1     );
		curl_setopt( $ch, CURLOPT_POSTFIELDS    , $post );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1     );
		$result = curl_exec( $ch );
		curl_close( $ch );
		return( $result );
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
