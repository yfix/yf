<?php

/***
 * api
 *
 *    info: external api interface by example: ajax, rpc, etc
 * support: json, jsonp
 * example:
 *        /api/@object/@action?value=10
 *        /api/@object?action=@action&value=10
 *        ./?object=api&action=@object&id=@action&value=10
 */

class yf_api {

	function _init() {
		$class  = $_GET[ 'action' ];
		$method = $_GET[ 'id' ];
		// override
		$class == 'show' && $class  = $_REQUEST[ 'object' ];
		!$method && $method = $_REQUEST[ 'action' ];
		$this->_call( $class, null, $method );
	}

	// api call
	protected function _reject() {
		header( 'Status: 503 Service Unavailable' );
		die( 'Service Unavailable' );
	}

	protected function _firewall( $class = null, $class_path = null, $method = null, $options = array() ) {
		$_method = '_api_' . $method;
		// try class
		$_class  = _class_safe( $class, $class_path );
		$_status = method_exists( $_class, $_method );
		// try module
		if( !$_status ) {
			$_class  = module_safe( $class );
			$_status = method_exists( $_class, $_method );
		}
		if( !$_status ) { $this->_reject(); }
		return( $_class->$_method( $options ) );
	}

	protected function _call( $class = null, $class_path = null, $method = null, $options = array() ) {
		main()->NO_GRAPHICS = true;
		$result = $this->_firewall( $class, $class_path, $method, $options );
		$json = json_encode( $result );
		$response = &$json;
		// check jsonp
		$type = 'json';
		if( isset( $_GET[ 'callback' ] ) ) {
			$jsonp_callback = $_GET[ 'callback' ];
			$response = '/**/ ' . $jsonp_callback . '(' . $json . ');';
			$type = 'javascript';
		}
		header( "Content-Type: application/$type; charset=UTF-8" );
		echo( $response );
		// without debug info
		exit( 0 );
	}

}
