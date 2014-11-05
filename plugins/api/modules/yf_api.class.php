<?php

/***
 *  module: api
 *
 *    info: external api interface by example: ajax, rpc, etc
 * support: json, jsonp
 *     url:
 *        /api/@object/@action?value=10
 *        /api/@object?action=@action&value=10
 *        ./?object=api&action=@object&id=@action&value=10
 *
 * description: call class or module with method prefix '_api_'
 *
 *     example: handler user message
 *         url: /api/user/message
 *       class:
 *              class user {
 *                  _init() {
 *                  ...
 *                  }
 *                  _api_message() {
 *                      // handler here
 *                      // get user id, store message, etc
 *                  }
 *                  ...
 *              }
 */

class yf_api {

	public $is_post = null;
	public $is_json = null;
	public $request = null;

	function _init() {
		$class  = $_GET[ 'action' ];
		$method = $_GET[ 'id' ];
		// override
		$class == 'show' && $class  = $_REQUEST[ 'object' ];
		!$method && $method = $_REQUEST[ 'action' ];
		$this->_call( $class, null, $method );
	}

	protected function _parse_request() {
		$is_post = &$this->is_post;
		$is_json = &$this->is_json;
		$request = &$this->request;
			$is_post = input()->is_post();
		if( $is_post ) {
			$request = json_decode( file_get_contents( 'php://input' ), true );
			$request && $is_json = true;
		}
		return( $request );
	}

	protected function _reject() {
		header( 'Status: 503 Service Unavailable' );
		die( 'Service Unavailable' );
	}

	protected function _firewall( $class = null, $class_path = null, $method = null, $options = array() ) {
		$_method = '_api_' . $method;
		// try module
		$_class  = module_safe( $class );
		$_status = method_exists( $_class, $_method );
		if( !$_status ) {
			// try class
			$_class  = _class_safe( $class, $class_path );
			$_status = method_exists( $_class, $_method );
		}
		if( !$_status ) { $this->_reject(); }
		$request = $this->_parse_request();
		return( $_class->$_method( $request, $options ) );
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
