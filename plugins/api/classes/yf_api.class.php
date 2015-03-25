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

	public $JSON_VULNERABILITY_PROTECTION = true;

	public $class   = null;
	public $method  = null;
	public $is_post = null;
	public $is_json = null;
	public $request = null;

	function _init() {
		// ob_start();
		$class   = &$this->class;
		$method  = &$this->method;
		$is_post = &$this->is_post;
		// setup
		$class  = $_GET[ 'action' ];
		$method = $_GET[ 'id'     ];
		$is_post = isset( $_POST );
		// override
		$class == 'show' && $class = $_REQUEST[ 'object' ];
		!$method && $method = $_REQUEST[ 'action' ];
		$this->_call( $class, null, $method );
	}

	public function _parse_request() {
		$is_post = &$this->is_post;
		$is_json = &$this->is_json;
		$request = &$this->request;
		if( $is_post ) {
			$request = file_get_contents( 'php://input' );
			$request = json_decode( file_get_contents( 'php://input' ), true );
			$request && $is_json = true;
		}
		return( $request );
	}

	// usage if user_id < 1
	public function _forbidden() {
		$this->_reject( 'Forbidden', 'Status: 403 Forbidden', 403 );
	}

	public function _reject( $message = 'Service Unavailable', $header = 'Status: 503 Service Unavailable', $code = 503 ) {
		if( function_exists( 'http_response_code' ) ) { http_response_code( $code ); } // PHP 5 >= 5.4.0
		header( $header );
		header('Content-Type: text/html; charset=utf-8');
		$this->_send( $message );
	}

	public function _redirect( $url, $message = null ) {
		$code     = 302;
		$status   = '302 Found';
		$header   = 'Status: ' . $status;
		// $header   = ( $_SERVER['SERVER_PROTOCOL'] ?: 'HTTP/1.1' ) . ' ' . $status;
		$url      = $url ?: url( '/' );
		$location = 'Location: ' . $url;
		if( function_exists( 'http_response_code' ) ) { http_response_code( $code ); } // PHP 5 >= 5.4.0
		header( $header   );
		header( $location );
		header('Content-Type: text/html; charset=utf-8');
		$this->_send( $message );
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
		if( function_exists( 'http_response_code' ) ) { http_response_code( 200 ); } // PHP 5 >= 5.4.0
		header( 'Status: 200' );
		header( "Content-Type: application/$type; charset=utf-8" );
		$this->_send( $response );
	}

	protected function _send( $response = null ) {
		// $error = ob_get_contents();
		// ob_end_clean();
		if( !empty( $this->JSON_VULNERABILITY_PROTECTION ) ) { echo( ")]}',\n" ); }
		if( isset( $response ) ) { echo( $response ); }
		// if( isset( $error    ) ) { echo( "\n,([{\n $error" ); }
		exit;
	}

}
