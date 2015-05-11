<?php

class yf_payment_api__provider {

	public $ENABLE    = null;
	public $TEST_MODE = null;

	public $payment_api = null;

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

	public function is_test( $options = null ) {
		$result = false;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( !empty( $this->TEST_MODE ) || !empty( $_test_mode ) ) { $result = true; }
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

	public function deposition( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		$result = $this->_transaction( $options );
		return( $result );
	}

	public function payment( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		$result = $this->_transaction( $options );
		return( $result );
	}

	protected function _transaction( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		$payment_api    = $this->payment_api;
		$_              = $options;
		$data           = &$_[ 'data'           ];
		$options        = &$_[ 'options'        ];
		$operation_data = &$_[ 'operation_data' ];
		$operation      = &$_[ 'operation'      ];
		// prepare data
		$operation_id = (int)$data[ 'operation_id' ];
		$account_id   = (int)$data[ 'account_id'   ];
		$amount       = $payment_api->_number_float( $data[ 'amount' ] );
		// operation_id
		if( empty( $operation_id ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Не определен код операции',
			);
			return( $result );
		}
		// update account balance
		$sql_datetime = $payment_api->sql_datetime();
		$sql_amount   = $payment_api->_number_mysql( $amount );
		switch( $operation_data[ 'type' ][ 'name' ] ) {
			case 'payment':
				$sql_sign = '-';
				break;
			case 'deposition':
			default:
				$sql_sign = '+';
				break;
		}
		$_data = array(
			'account_id'      => $account_id,
			'datetime_update' => db()->escape_val( $sql_datetime ),
			'balance'         => "( balance $sql_sign $sql_amount )",
		);
		db()->begin();
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
		// get status
		$object = $payment_api->get_status( array( 'name' => 'success' ) );
		list( $payment_status_id, $payment_status ) = $object;
		if( empty( $payment_status_id ) ) {
			db()->rollback();
			return( $object );
		}
		$result[ 'status_message' ] = 'Выполнено: ' . $options[ 'operation_title' ] . ', сумма: ' . $amount;
		if( !empty( $payment_api->currency[ 'short' ] ) ) {
			$result[ 'status_message' ] .= ' ' . $payment_api->currency[ 'short' ];
		}
		// check account
		$account_result = $payment_api->get_account( array( 'account_id' => $account_id ) );
		if( empty( $account_result ) ) { $status = false; }
			list( $account_id, $account ) = $account_result;
		// update operation status
		$_data = array(
			'operation_id'    => $operation_id,
			'status_id'       => $payment_status_id,
			'balance'         => $account[ 'balance' ],
			'datetime_update' => $sql_datetime,
			'datetime_finish' => $sql_datetime,
		);
		$_result = $payment_api->operation_update( $_data );
		if( !$_result[ 'status' ] ) {
			db()->rollback();
			return( $_result );
		}
		db()->commit();
		return( $result );
	}

}
