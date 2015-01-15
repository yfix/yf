<?php

_class( 'payment_api__provider' );

class yf_payment_api__provider_test extends yf_payment_api__provider {

	public $ENABLE = false;

	public function _init() {
		$allow = defined( 'TEST_MODE' ) && TEST_MODE;
		$allow = $this->allow( $allow );
		if( !$allow ) { return( false ); }
		parent::_init();
	}

}
