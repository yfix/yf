<?php

class yf_payment_api__provider {

	public $ENABLE = null;

	public $payment_api = null;

	public function _init() {
		$this->payment_api = _class( 'payment_api' );
	}

	public function allow( $value ) {
		$result = &$this->ENABLE;
		!isset( $value ) && $result = (bool)$value;
		return( $result );
	}

	public function deposition( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		$result = $this->transaction( $options );
		return( $result );
	}

	public function payment( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		$result = $this->transaction( $options );
		return( $result );
	}

	public function transaction( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		$_api           = $this->payment_api;
		$_              = $options;
		$data           = &$_[ 'data'           ];
		$options        = &$_[ 'options'        ];
		$operation_data = &$_[ 'operation_data' ];
		$operation      = &$_[ 'operation'      ];
		// prepare data
		$account_id = (int)$data[ 'account_id' ];
		$amount     = $_api->_number_float( $data[ 'amount' ] );
		// update account balance
		db()->begin();
		$sql_datetime = $_api->sql_datatime();
		$sql_amount = $_api->_number_mysql( $amount );
		switch( $operation_data[ 'type' ][ 'name' ] ) {
			case 'payment':
				$sql_sign = '-';
				break;
			case 'deposition':
			default:
				$sql_sign = '+';
				break;
		}
		$sql_data = array(
			'datetime_update' => db()->escape_val( $sql_datetime ),
			'balance' => "( balance $sql_sign $sql_amount )",
		);
		$status = db()->table( 'payment_account' )
			->where( 'account_id', '=', $account_id )
			// ->update( $sql_data, array( 'escape' => false, 'sql' => true ) );
			->update( $sql_data, array( 'escape' => false ) );
		$result = array(
			'status' => $status,
		);
		// test status
		if( empty( $status ) ) {
			$payment_status_name = 'refused';
			$result[ 'status_message' ] = 'Ошибка: ';
		} else {
			$payment_status_name = 'success';
			$result[ 'status_message' ] = 'Выполнено: ';
		}
		$result[ 'status_message' ] .= $options[ 'operation_title' ] . ', сумма: ' . $amount;
		if( !empty( $_api->currency[ 'short' ] ) ) {
			$result[ 'status_message' ] .= ' ' . $_api->currency[ 'short' ];
		}
		// get payment status
		$payment_status_result = $_api->get_status( array( 'name' => $payment_status_name ) );
		list( $payment_status_id, $payment_status ) = $payment_status_result;
		if( empty( $payment_status_id ) ) {
			db()->rollback();
			return( $payment_status_result );
		}
		// check account
		$account_result = $_api->get_account( array( 'account_id' => $account_id ) );
		if( empty( $account_result ) ) { $status = false; }
			list( $account_id, $account ) = $account_result;
		// update operation status
		$sql_data = array(
			'status_id'       => $payment_status_id,
			'balance'         => $account[ 'balance' ],
			'datetime_update' => $sql_datetime,
			'datetime_finish' => $sql_datetime,
		);
		$operation_id = (int)$data[ 'operation_id' ];
		$status = db()->table( 'payment_operation' )
			->where( 'operation_id', $operation_id )
			->update( $sql_data );
		if( empty( $status ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Ошибка при обновлении операции: ' . $operation_id,
			);
			db()->rollback();
			return( $result );
		}
		db()->commit();
		return( $result );
	}

}
