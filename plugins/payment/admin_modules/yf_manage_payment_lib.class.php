<?php

class yf_manage_payment_lib {

	public $css_by_status = array(
		'processing'  => 'text-warning',
		'in_progress' => 'text-warning',
		'success'     => 'text-success',
		'expired'     => 'text-danger',
		'refused'     => 'text-danger',
		'cancelled'   => 'text-danger',
	);

	function _init() {
	}

	function css_by_status( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$css = &$this->css_by_status;
		$result = @$css[ $_status_name ] ?: '';
		return( $result );
	}

}
