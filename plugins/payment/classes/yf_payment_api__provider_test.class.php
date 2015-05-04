<?php

_class( 'payment_api__provider' );

class yf_payment_api__provider_test extends yf_payment_api__provider {

	public $IS_DEPOSITION = true;
	// public $IS_PAYMENT    = true;

	public $service_allow = array(
		'Тест',
	);

	public function _init() {
		if( !$this->ENABLE ) { return( null ); }
		// ( defined( 'TEST_MODE' ) && TEST_MODE ) && $allow = true;
		// $allow = $this->allow( $allow );
		$allow = $this->allow();
		if( !$allow ) { return( false ); }
		parent::_init();
	}

}
