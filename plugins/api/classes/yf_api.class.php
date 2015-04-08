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

	// 403 Forbidden
	// usage if user_id < 1
	public function _forbidden() {
		$this->_reject( 403 );
	}

	// 500 Internal Server Error
	public function _error() {
		$this->_reject( 500 );
	}

	// 503 Service Unavailable
	public function _reject( $code = 503 ) {
		list( $protocol, $code, $status ) = $this->_send_http_status( $code );
		$this->_send_http_type();
		$this->_send_http_content( $status );
	}

	// 301 Moved Permanently
	// 302 Moved Temporarily
	// 302 Found
	public function _redirect( $url, $message = null ) {
		list( $protocol, $code, $status ) = $this->_send_http_status( 302 );
		// location
		$url      = $url ?: url( '/' );
		$location = 'Location: ' . $url;
		header( $location );
		// message
		$this->_send_http_type();
		$this->_send_http_content( $message );
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
		list( $protocol, $code, $status ) = $this->_send_http_status( 200 );
		$this->_send_http_type( $type );
		$this->_send_http_content( $response );
	}

	protected function _send_http_status( $code = null, $status = null ) {
		$code     = (int)$code;
		$protocol = null;
		$status   = null;
		if( $code > 0 ) {
			// send http code
			if( function_exists( 'http_response_code' ) ) { http_response_code( $code ); } // PHP >= 5.4.0
			// protocol detect
			$protocol = 'HTTP/1.1';
			if( function_exists( 'php_sapi_name' ) ) { // PHP >= 4.1.0
				$type = php_sapi_name();
				substr( $type, 0, 3 ) == 'cgi' && $protocol = 'Status:';
			} else {
				isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) && $protocol = $_SERVER[ 'SERVER_PROTOCOL' ];
			}
			$header = array();
			$header[] = $protocol;
			// status default
			if( empty( $status ) ) {
				switch( $code ) {
					case 200: $status = 'OK';                    break;
					case 301: $status = 'Moved Permanently';     break;
					case 302: $status = 'Moved Temporarily';     break;
					case 403: $status = 'Forbidden';             break;
					case 500: $status = 'Internal Server Error'; break;
					case 503: $status = 'Service Unavailable';   break;
				}
			}
			// code
			$header[] = $code;
			// status
			!empty( $status ) && $header[] = $status;
			// send http status
			$header = implode( ' ', $header );
			header( $header );
		}
		return( array( $protocol, $code, $status ) );
	}

	protected function _send_http_type( $type = null, $charset = null ) {
		empty( $type    ) && $type    = 'html';
		empty( $charset ) && $charset = 'utf-8';
		switch( $type ) {
			case 'json':
			case 'javascript':
				$content_type = 'application/' . $type;
				break;
			case 'plain':
			case 'html':
				$content_type = 'text/' . $type;
				break;
			default:
				$content_type = 'text/html';
				break;
		}
		$header = 'Content-Type: '. $content_type .'; charset='. $charset;
		header( $header );
	}

	protected function _send_http_content( $response = null ) {
		// $error = ob_get_contents();
		// ob_end_clean();
		if( !empty( $this->JSON_VULNERABILITY_PROTECTION ) ) { echo( ")]}',\n" ); }
		if( isset( $response ) ) { echo( $response ); }
		// if( isset( $error    ) ) { echo( "\n,([{\n $error" ); }
		exit;
	}

}
