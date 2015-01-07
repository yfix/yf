<?php

class yf_payment_api__provider_administration {

	public $payment_api      = null;
	public $payment_provider = null;

	public function _init() {
		$this->payment_api      = _class( 'payment_api'           );
		$this->payment_provider = _class( 'payment_api__provider' );
	}

	public function payment( $options ) {
		$provider = &$this->payment_provider;
		$result   = $provider->payment( $options );
		return( $result );
	}

	public function deposition( $options ) {
		$provider = &$this->payment_provider;
		$result   = $provider->deposition( $options );
		return( $result );
	}

}
