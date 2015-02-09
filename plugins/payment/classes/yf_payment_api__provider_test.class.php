<?php

_class( 'payment_api__provider' );

class yf_payment_api__provider_test extends yf_payment_api__provider {

	public $ENABLE    = null;
	public $TEST_MODE = null;

	public function _init() {
		( defined( 'TEST_MODE' ) && TEST_MODE ) && $allow = true;
		$allow = $this->allow( $allow );
		if( !$allow ) { return( false ); }
		parent::_init();
	}

}
