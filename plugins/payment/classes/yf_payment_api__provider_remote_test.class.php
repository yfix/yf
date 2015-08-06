<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_remote_test extends yf_payment_api__provider_remote {

	public $IS_DEPOSITION = true;
	public $IS_PAYMENT    = true;

	public $service_allow = array(
		'Remote test',
	);

	public $currency_default = 'USD';
	public $currency_allow = array(
		'USD' => array(
			'currency_id' => 'USD',
			'active'      => true,
		),
	);

	public $_status = array(
		'0' => 'success',
		'1' => 'processing',
		'2' => 'refused',
	);

	public function _init() {
		if( !$this->ENABLE ) { return( null ); }
		$allow = $this->allow();
		if( !$allow ) { return( false ); }
		parent::_init();
	}

	public function _api_response( $options ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$operation_id  = $_data[ 'operation_id' ];
		$provider_name = $_provider[ 'name' ];
		$payment_type  = $_options[ 'type_name' ];
		$state         = 0;
		$status        = 'success';
		$datetime      = $_data[ 'datetime_update' ];
		// status
		list( $status_name, $status_message ) = $this->_state( $state );
		// response
		$response = array(
			'operation_id' => $operation_id,
		);
		$operation_data = array(
			'operation_id'   => $operation_id,
			'provider_name'  => $provider_name,
			'state'          => $state,
			'status_name'    => $status_name,
			'status_message' => $status_message,
			'payment_type'   => $payment_type,
			'response'       => $response,
		);
		$result = $this->{ '_api_' . $payment_type }( $operation_data );
		return( $result );
	}

	public function deposition( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		$result = $this->_api_response( $options );
		return( $result );
	}

	public function payment( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		$result = $this->_api_response( $options );
		return( $result );
	}

}
