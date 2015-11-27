<?php

class yf_payment {

	public $URL_REDIRECT = '/payment';

	public $transition = array(
		'default' => array(
			'title' => true,
		),
		'status' => array(
			'status_message' => true,
		),
		'currency' => array(
			'name'  => true,
			'short' => true,
			'sign'  => true,
		),
	);

	private $_class_path = null;

	public function _init() {
		// class
		$this->_class_path = 'modules/' . __CLASS__ . '/';
	}

	public function show() {
		// import options
		is_array( $_GET ) && extract( $_GET, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// route
		switch( true ) {
			case @$_is_confirmation:
				$result = $this->confirmation();
				break;
			case @$_is_cancel:
				$result = $this->cancel();
				break;
			default:
				$result = js_redirect( '/login_form', false, 'Payment action not exists' );
				break;
		}
		return( $result );
	}

	protected function _operation_form() {
		// import options
		is_array( $_GET  ) && extract( $_GET,  EXTR_PREFIX_ALL | EXTR_REFS, '' );
		is_array( $_POST ) && extract( $_POST, EXTR_PREFIX_ALL | EXTR_REFS, '_' );
		// var
		$api         = _class( 'api'         );
		$payment_api = _class( 'payment_api' );
		$result = array();
		// check input data
		list( $account_id, $account  ) = $payment_api->get_account();
		if( empty( $account_id ) ) { js_redirect( '/login_form', false, 'User id empty' ); }
		// operation
		$operation = $payment_api->operation( array(
			'operation_id' => $_operation_id,
		));
		if( !$operation ) {
			$result = array(
				'status'         => false,
				'status_message' => t( 'Операция отсутствует (id: %operation_id)', array(
					'%operation_id' => $_operation_id,
				)),
			);
			return( $this->_operation_tpl( $result ) );
		}
		// user
		$user_id = main()->USER_ID;
		if( $user_id != $account[ 'user_id' ] ) { return( $api->_reject() ); }
		// import operation
		is_array( $operation ) && extract( $operation, EXTR_PREFIX_ALL | EXTR_REFS, 'o' );
		// prepare data
		$data = array(
			'title'  => $o_title,
			'amount' => $payment_api->money_html( $o_amount ),
		);
		$form = array(
			'code' => @$__code ?: @$_code,
			'action' => url_user( $_SERVER[ 'REQUEST_URI' ] ),
		);
		$result = array(
			'data' => $data,
			'form' => $form,
		);
		return( $result );
	}

	public function confirmation() {
		$result = $this->_operation_form();
		// form handler
		if( @$_POST ) {
			switch( @$_POST[ 'confirmation_action' ] ) {
				case 'ok':
					$result += $this->_confirmation_action_ok();
					break;
				case 'cancel':
					$result += $this->_confirmation_action_cancel();
					break;
			}
		}
		// result
		return( $this->_operation_tpl( $result ) );
	}

	protected function _operation_tpl( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		isset( $_status_message ) && $_status_message = t( $_status_message );
		$result = tpl()->parse( 'payment/confirmation/view', $options );
		return( $result );
	}

	protected function _confirmation_action_ok() {
		// import options
		is_array( $_GET ) && extract( $_GET, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		is_array( $_POST ) && extract( $_POST, EXTR_PREFIX_ALL | EXTR_REFS, 'p' );
		// var
		$payment_api = _class( 'payment_api' );
		$result = $payment_api->confirmation_code_check( array(
			'operation_id' => @$_operation_id,
			'code'         => @$p_code,
		));
		return( $result );
	}

	protected function _confirmation_action_cancel() {
		// import options
		is_array( $_GET ) && extract( $_GET, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$payment_api = _class( 'payment_api' );
		$result = $payment_api->cancel_user( array(
			'operation_id' => @$_operation_id,
		));
		return( $result );
	}

	public function cancel() {
		$result = $this->_operation_form();
		// import options
		is_array( $_GET ) && extract( $_GET, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$payment_api = _class( 'payment_api' );
		$result += $payment_api->cancel_user( array(
			'operation_id' => @$_operation_id,
		));
		// result
		return( $this->_operation_tpl( $result ) );
	}

	// simple route: payment__user_balance->user_balance
	private function _class( $class, $method, $options ) {
		$_path  = $this->_class_path;
		$_class = __CLASS__ . '__' . $class;
		$result = _class_safe( $_class, $_path )->{ $method }( $options );
		return( $result );
	}

	public function _balance_refresh( $request, $options ) {
		$api         = _class( 'api'         );
		$payment_api = _class( 'payment_api' );
		// update account, operation
		list( $account_id,  $account  ) = $payment_api->get_account();
		if( empty( $account_id ) ) { $api->_forbidden(); }
		list( $currency_id, $currency ) = $payment_api->get_currency__by_id( $account );
		$this->t( $currency, 'currency' );
		$response = array(
			'response' => array(
				'balance' => array(
					'account'  => $account,
					'currency' => $currency,
				),
			),
		);
		return( $response );
	}

	public function _balance_recharge( $request, $options ) {
		$api         = _class( 'api'         );
		$payment_api = _class( 'payment_api' );
		$balance     = $payment_api->deposition_user( $request );
		// need user authentication
		if( $balance[ 'status' ] === -1 ) { $api->_forbidden(); }
		// update account, operation
		list( $account_id, $account ) = $payment_api->get_account();
		list( $operation, $count ) = $payment_api->operation( $account );
		$page_per = $payment_api->OPERATION_LIMIT;
		$pages    = ceil( $count / $page_per );
		$response = array(
			'response' => array(
				'balance'   => $balance,
				'payment' => array(
					'account'   => $account,
					'operation' => $operation,
					'operation_pagination' => array(
						'count'    => $count,
						'page_per' => $page_per,
						'pages'    => $pages,
						'page'     => 1,
					),
				),
			),
		);
		return( $response );
	}

	public function _payin( $request, $options ) {
		$result = $this->_balance_recharge( $request, $options );
		return( $result );
	}

	public function _payout( $request, $options ) {
		$api         = _class( 'api'         );
		$payment_api = _class( 'payment_api' );
		// todo
		$request = $request[ 'options' ];
		// security
		// $_request = array();
		// foreach( array( 'amount', 'currency_id', 'provider_id', 'page', 'method_id', 'account', 'name' ) as $key ) {
			// isset( $request[ 'options' ][ $key ] ) && $_request[ $key ] = &$request[ 'options' ][ $key ];
		// }
		$request += array(
			'operation_title' => 'Выплата со счета',
		);
		$payout = $payment_api->payment_user( $request );
		// need user authentication
		if( $payout[ 'status' ] === -1 ) { $api->_forbidden(); }
		// update account, operation
		list( $account_id, $account ) = $payment_api->get_account();
		list( $operation, $count ) = $payment_api->operation( $account );
		$page_per = $payment_api->OPERATION_LIMIT;
		$pages    = ceil( $count / $page_per );
		$response = array(
			'response' => array(
				'payout'  => $payout,
				'payment' => array(
					'account'   => $account,
					'operation' => $operation,
					'operation_pagination' => array(
						'count'    => $count,
						'page_per' => $page_per,
						'pages'    => $pages,
						'page'     => 1,
					),
				),
			),
		);
		return( $response );
	}

	public function _operation( $request, $options ) {
		$api         = _class( 'api'         );
		$payment_api = _class( 'payment_api' );
		// update account, operation
		list( $account_id, $account ) = $payment_api->get_account();
		// need user authentication
		if( empty( $account_id ) ) { $api->_forbidden(); }
		$page = $request[ 'page' ];
		$operation_options = array(
			'account_id' => $account_id,
			'page'       => $page,
			'count'      => $count,
		);
		list( $operation, $count ) = $payment_api->operation( $operation_options );
		$this->t( $operation );
		$page_per = $payment_api->OPERATION_LIMIT;
		$pages    = ceil( $count / $page_per );
		$response = array(
			'response' => array(
				'payment' => array(
					'account'   => $account,
					'operation' => $operation,
					'operation_pagination' => array(
						'count'    => $count,
						'page_per' => $page_per,
						'pages'    => $pages,
						'page'     => $page,
					),
				),
			),
		);
		return( $response );
	}

	public function _cancel( $request, $options ) {
		$api         = _class( 'api'         );
		$payment_api = _class( 'payment_api' );
		$object      = $payment_api->cancel_user( $request );
		// need user authentication
		if( $balance[ 'status' ] === -1 ) { $api->_forbidden(); }
		// update account, operation
		list( $account_id, $account ) = $payment_api->get_account();
		list( $operation, $count ) = $payment_api->operation( $account );
		$page_per = $payment_api->OPERATION_LIMIT;
		$pages    = ceil( $count / $page_per );
		$response = array(
			'response' => array(
				'cancel'  => $object,
				'payment' => array(
					'account'   => $account,
					'operation' => $operation,
					'operation_pagination' => array(
						'count'    => $count,
						'page_per' => $page_per,
						'pages'    => $pages,
						'page'     => 1,
					),
				),
			),
		);
		return( $response );
	}

	public function _api_provider( $request, $options ) {
		$api         = _class( 'api'         );
		$payment_api = _class( 'payment_api' );
		$provider_name = $_GET[ 'name' ];
		if( empty( $provider_name ) ) { return( $api->_reject() ); }
		// check provider
		$object = $payment_api->provider( array(
			'is_service' => true,
			'name'       => $provider_name,
		));
		if( empty( $object ) ) { return( $api->_reject() ); }
		$is_server = !empty( $_GET[ 'server' ] );
		// provider handler
		$provider_class = 'provider_' . $provider_name;
		switch( $_GET[ 'operation' ] ) {
			case 'authorize':
			case 'response':
			case 'server':
			case 'check':
				$method = $_GET[ 'operation' ];
				break;
			default:
				$api->_reject();
				break;
		}
		$result = $payment_api->_class( $provider_class, '_api_' . $method, $request );
		if( @$result[ 'is_raw' ] ) { return( $result ); }
// var_dump( $result );
// exit;
		@list( $status, $status_message ) = array_values( $result );
		if( $is_server ) {
			if( @$status) {
				return( array( 'status' => 'ok' ) );
			} else {
				$api->_error();
			}
		}
		if( defined( 'DEBUG' ) && DEBUG ) {
			var_dump( $result );
			js_redirect( url( $this->URL_REDIRECT ), false, 'payment provider error' );
		} else {
			$api->_redirect( url( $this->URL_REDIRECT ), $status_message );
		}
	}

	public function _api_balance( $request, $options ) {
		// security
		$_request = array();
		foreach( array( 'operation_id', 'amount', 'currency_id', 'provider_id', 'page', 'method_id', 'account', 'name' ) as $key ) {
			isset( $request[ 'options' ][ $key ] ) && $_request[ $key ] = &$request[ 'options' ][ $key ];
		}
		// route
		$api = _class( 'api' );
		switch( $_GET[ 'operation' ] ) {
			case 'refresh':
				$response = $this->_balance_refresh( $_request, $options );
				break;
			case 'recharge':
				$response = $this->_balance_recharge( $_request, $options );
				break;
			case 'payin':
				$response = $this->_payin( $_request, $options );
				break;
			case 'payout':
				$_request = array();
				$response = $this->_payout( $request, $options );
				break;
			case 'operation':
				$response = $this->_operation( $_request, $options );
				break;
			case 'cancel':
				$response = $this->_cancel( $_request, $options );
				break;
			default:
				$api->_reject();
				break;
		}
		return( $response );
	}

	public function user_balance( $options = null ) {
		$result = $this->_class( 'user', 'balance', $options );
		return( $result );
	}

	public function t( &$strs, $set = null, $level = 0 ) {
		if( $level > 1 || !is_array( $strs ) ) { return; }
		!isset( $set ) && $set = 'default';
		if( empty( $this->transition[ $set ] ) ) { return; }
		$transition = $this->transition[ $set ];
		foreach( $strs as $key => &$str ) {
			if( is_array( $str ) ) { $this->t( $str, $set, $level + 1 ); }
			if( empty( $transition[ $key ] ) ) { continue; }
			$str = t( $str );
		}
	}

}
