<?php

class yf_payment_test {

	protected $options = null;

	public $api = array(
		// 'Privat24'    => true,
		// 'LiqPay'      => true,
		'Interkassa'   => true,
		'WebMoney'    => true,
		// 'Ecommpay'    => true,
		'PerfectMoney' => true,
		// 'YandexMoney'  => true,
	);

	public $payin = array(
		// 'Privat24'       => true,
		// 'LiqPay'         => true,
		// 'Interkassa'     => true,
		'WebMoney'       => true,
		'Ecommpay'       => true,
		'PerfectMoney'   => true,
		// 'YandexMoney'    => true,
	);

	public $payout = array(
		// 'Privat24'    => true,
		// 'LiqPay'      => true,
		'Interkassa'   => true,
		// 'WebMoney'    => true,
		'Ecommpay'     => true,
		'PerfectMoney' => true,
		// 'YandexMoney'  => true,
	);

	public function _init() {
		if( !( defined( 'PAYMENT_TEST' ) && PAYMENT_TEST )
			&& !( defined( 'TEST_MODE' ) && TEST_MODE ) ) {
			$message = 'Service Unavailable';
			$header = 'Status: 503 Service Unavailable';
			$code = 503;
			http_response_code( $code );
			header( $header );
			die( $message );
		}
		// error_reporting( E_ALL );
	}

	protected function _init_options() {
		$_ = &$this->options;
		$_ = array(
			'url_result'   => url( '/@object/@action', array( 'test_mode' => 1 ) ),
			'url_server'   => url( '/@object/@action', array( 'test_mode' => 1, 'server' => true ) ),
			'operation_id' => @$_GET[ 'operation_id' ] ?: '_1',
			'amount'       => @$_GET[ 'amount' ] ?: '0.01',
		);
		return( $_ );
	}

	// ************* fast
	// protected function _fast_mail() {
		// $this->mail();
	// }

	// protected function _fast_api() {
		// $this->api();
	// }

	// protected function _fast_payout() {
		// $this->payout();
	// }

	protected function _fast_currency_rate() {
		$this->currency_rate();
	}

	protected function _fast_number() {
		$this->number();
	}

	// protected function _fast_payin() {
		// $this->payin();
	// }

	protected function _fast_sign() {
		$this->sign();
	}

	protected function _fast_ip_check() {
		$this->ip_check();
	}

	protected function _fast_js_cors() {
		$this->js_cors();
	}

	// ************* test api
	public function transaction() {
		$payment_api = _class( 'payment_api' );
		// var
		$result = array();
		$operation_id = @$_GET[ 'operation_id' ];
		$data = array( 'operation_id' => $operation_id );
		// start
		$result[1][ 'level'  ] = $payment_api->transaction_isolation();

		$result[2][ 'start'  ] = $payment_api->transaction_start( $data );
		$result[2][ 'level'  ] = $payment_api->transaction_isolation();
		if( $operation_id ) {
			$operation = $payment_api->operation( array(
				'operation_id' => $operation_id,
			));
			$result[2][ 'operation'  ] = $operation;
		}
		$result[2][ 'commit' ] = $payment_api->transaction_commit();

		$result[3][ 'start'    ] = $payment_api->transaction_start();
		$result[3][ 'rollback' ] = $payment_api->transaction_rollback();

		$result[4][ 'start'    ] = $payment_api->transaction_start();
		$result[4][ 'commit 1' ] = $payment_api->transaction_commit();
		$result[4][ 'commit 2' ] = $payment_api->transaction_commit();

		$result[5][ 'start'      ] = $payment_api->transaction_start();
		$result[5][ 'rollback 1' ] = $payment_api->transaction_rollback();
		$result[5][ 'rollback 2' ] = $payment_api->transaction_rollback();

		$result[6][ 'start'    ] = $payment_api->transaction_start();
		$result[6][ 'rollback' ] = $payment_api->transaction_rollback();
		$result[6][ 'commit'   ] = $payment_api->transaction_commit();

		$result[7][ 'start'    ] = $payment_api->transaction_start();
		$result[7][ 'commit'   ] = $payment_api->transaction_commit();
		$result[7][ 'rollback' ] = $payment_api->transaction_rollback();

		$result[8][ 'level'    ] = $payment_api->transaction_isolation();
		// out
		$php = var_export( $result, true );
		$result = $this->_add_panel(  array(
			'header'    => 'Transaction DB',
			'php'       => $php,
			'is_action' => true,
		));
		$this->_render( $result );
	}

	public function mail() {
		$mail_tpl = array(
			'payin_success' => array(
				'type'     => 'payin',
				'status'   => 'success',
				'is_user'  => true,
				'is_admin' => true,
			),
			'payin_refused' => array(
				'type'     => 'payin',
				'status'   => 'refused',
				'is_user'  => true,
				'is_admin' => true,
			),

			'payout_success' => array(
				'type'     => 'payout',
				'status'   => 'success',
				'is_user'  => true
			),
			'payout_refused' => array(
				'type'     => 'payout',
				'status'   => 'refused',
				'is_user'  => true
			),
			'payout_request' => array(
				'type'     => 'payout',
				'status'   => 'request',
				'is_user'  => true,
				'is_admin' => true,
			),
			'payout_confirmation' => array(
				'type'     => 'payout',
				'status'   => 'confirmation',
				'is_user'  => true,
				'is_admin' => true,
			),
		);
		// exec
		$form = '';
		if( @$_GET[ 'tpl' ] || ( @$_GET[ 'type' ] && @$_GET[ 'status' ] ) ) {
			// vars
			$payment_api = _class( 'payment_api' );
			// prepare
			$tpl     = @$_GET[ 'tpl' ];
			$type    = @$_GET[ 'type' ];
			$status  = @$_GET[ 'status' ];
			$user_id = @$_GET[ 'user_id' ] ?: 1;
			$is_admin = !empty( $_GET[ 'is_admin' ] );
			$result = $payment_api->mail( array(
				'tpl'      => $tpl,
				'type'     => $type,
				'status'   => $status,
				'force'    => true,
				'user_id'  => $user_id,
				'is_admin' => $is_admin,
				'data'     => array(
					'operation_id' => 1,
					'amount'       => 1.1,
					'code'         => 123,
				),
			));
			$form .= var_export( array(
				'tpl'    => $tpl,
				'result' => $result,
			), true );
		} else { $form = 'Выберите шаблон'; }
		// actions
		$action = array();
		foreach( $mail_tpl as $tpl => $item ) {
			if( empty( $item ) ) { continue; }
			// uri
			$uri = array();
			$uri[] = 'tpl='. $tpl;
			// user
			if( @$item[ 'is_user' ] ) {
				$_uri = '?'. implode( '&', $uri );
				$link = url_user( '/@object/@action'. $_uri );
				$a = sprintf( '<a href="%s" class="btn btn-default">%s</a>', $link, $tpl );
				$action[] = $a;
			}
			// admin
			if( @$item[ 'is_admin' ] ) {
				$uri[] = 'is_admin='. true;
				$_uri = '?'. implode( '&', $uri );
				$link = url_user( '/@object/@action'. $_uri );
				$a = sprintf( '<a href="%s" class="btn btn-default">%s</a>', $link, $tpl .'_admin' );
				$action[] = $a;
			}
			// uri
			$uri = array();
			$uri[] = 'type='.   $item[ 'type'   ];
			$uri[] = 'status='. $item[ 'status' ];
			// user
			if( @$item[ 'is_user' ] ) {
				$_uri = '?'. implode( '&', $uri );
				$link = url_user( '/@object/@action'. $_uri );
				$a = sprintf( '<a href="%s" class="btn btn-default">by %s - %s</a>', $link, $item[ 'type' ], $item[ 'status' ] );
				$action[] = $a;
			}
			// admin
			if( @$item[ 'is_admin' ] ) {
				$uri[] = 'is_admin='. true;
				$_uri = '?'. implode( '&', $uri );
				$link = url_user( '/@object/@action'. $_uri );
				$a = sprintf( '<a href="%s" class="btn btn-default">by %s, %s - admin</a>', $link, $item[ 'type' ], $item[ 'status' ] );
				$action[] = $a;
			}
		}
		$result = $this->_add_panel(  array(
			'header' => 'Payment mail',
			'form'   => $form,
			'action' => $action,
		));
		$this->_render( $result );
	}

	// ************* test api
	public function api() {
		$provider = array(
			// 'Privat24',
			// 'LiqPay',
			'Interkassa',
			'WebMoney',
			// 'Ecommpay',
			'PerfectMoney',
			'YandexMoney',
		);
		$_provider = array();
		foreach( $provider as $item ) {
			if( !empty( $this->api[ $item ] ) ) {
				$_provider[] = $item;
			}
		}
		$result = array();
		$result += $this->_chunk( 'api', $_provider );
		$this->_render( $result );
	}

	protected function _api_Interkassa( $title ) {
		$php     = '';
		$api     = _class( 'payment_api__provider_interkassa' );
		$methods = array(
			'currency'                => true,
			'paysystem-input-payway'  => true,
			'paysystem-output-payway' => true,
			'account'                 => true,
			'checkout'                => true,
			'checkout-b'              => true,
			'purse'                   => true,
			'co-invoice'              => true,
			'co-invoice-id'           => true,
			'withdraw'                => true,
			'withdraw-id'             => true,
			'withdraw-calc'           => true,
			'withdraw-process'        => true,
		);
		$is_action = null;
		if( !@empty( $methods[ $_GET[ 'method' ] ] ) ) {
			$is_action = true;
			$method_id = $_GET[ 'method' ];
			$is_debug  = @$_GET[ 'is_debug' ];
			$options   = array(
				'method_id' => $method_id,
				'is_debug'  => $is_debug,
			);
			$operation_id = @$_GET[ 'operation_id' ] ?: '1';
			$amount = @$_GET[ 'amount' ] ?: 50;
			$card   = @$_GET[ 'card'   ] ?: '5218572211211342';
			switch( true ) {
				case $method_id == 'withdraw-id' || $method_id == 'co-invoice-id':
					if( @$_GET[ 'id' ] > 0 ) {
						$options[ 'id' ] = $_GET[ 'id' ];
					}
					break;
				case $method_id == 'withdraw-calc':
					$options[ 'option' ] = array(
						'amount' => $amount,
						// 'calcKey' => 'ikPayerPrice',
						'calcKey' => 'psPayeeAmount',
						'details' => array(
							'card' => $card,
						),
						'paywayId'  => '52efa902e4ae1a780e000001',
						'purseId'   => '300301404317',
						'title'     => 'Вывод средств: '. $amount . ' грн',
						'paymentNo' => $operation_id,
					);
					break;
				case $method_id == 'withdraw-process':
					$options[ 'option' ] = array(
						'amount' => $amount,
						// 'calcKey' => 'ikPayerPrice',
						'calcKey' => 'psPayeeAmount',
						'details' => array(
							'card' => $card,
						),
						'paywayId'  => '52efa902e4ae1a780e000001',
						'purseId'   => '300301404317',
						'title'     => 'Вывод средств: '. $amount . ' грн',
						'paymentNo' => $operation_id,
					);
					break;
			}
			$php[] = var_export( array(
				'request'   => $options,
			), true );
			$result = $api->api_request( $options );
			$php[] = var_export( array(
				'response'  => $result,
			), true );
		} else { $php = 'Выберите метод'; }
		// actions
		$action = array();
		foreach( $methods as $item => $active ) {
			if( empty( $active ) ) { continue; }
			$link = url_user( '/@object/@action?method='. $item );
			$a = <<<EOS
<a href="$link" class="btn btn-default">$item</a>
EOS;
			$action[] = $a;
		}
		return( array( 'header' => 'api: ', 'php' => $php, 'action' => $action, 'is_action' => $is_action ) );
	}

	protected function _api_PerfectMoney( $title ) {
		$php     = '';
		$api     = _class( 'payment_api__provider_perfectmoney' );
		$methods = array(
			'verify' => true,
			'spend'  => true,
		);
		$is_action = null;
		if( !@empty( $methods[ $_GET[ 'method' ] ] ) ) {
			$is_action = true;
			$method_id = $_GET[ 'method' ];
			$is_debug  = @$_GET[ 'is_debug' ];
			$options   = array(
				'method_id' => $method_id,
				'is_debug'  => $is_debug,
			);
			$operation_id = '_'. ( @$_GET[ 'operation_id' ] ?: '1' );
			$amount  = @$_GET[ 'amount'  ] ?: 0.01;
			$account = @$_GET[ 'account' ] ?: $api->PURSE_ID[ 'USD' ];
			switch( true ) {
				case $method_id == 'verify' || $method_id == 'spend':
					$options[ 'option' ] = array(
						'Amount'        => $amount,
						'Memo'          => 'Вывод средств USD : '. $amount,
						'PAYMENT_ID'    => $operation_id,
						'Payer_Account' => $account,
						'Payee_Account' => $account,
					);
					break;
			}
			$php[] = var_export( array(
				'request' => $options,
			), true );
			$result = $api->api_request( $options );
			$php[] = var_export( array(
				'response'  => $result,
			), true );
		} else { $php = 'Выберите метод'; }
		// actions
		$action = array();
		foreach( $methods as $item => $active ) {
			if( empty( $active ) ) { continue; }
			$link = url_user( '/@object/@action?method='. $item );
			$a = <<<EOS
<a href="$link" class="btn btn-default">$item</a>
EOS;
			$action[] = $a;
		}
		return( array( 'header' => 'api: ', 'php' => $php, 'action' => $action, 'is_action' =>  $is_action ) );
	}


	protected function _api_YandexMoney( $title ) {
		$php     = '';
		$api     = _class( 'payment_api__provider_yandexmoney' );
		$methods = array(
			'revoke'            => true,
			'account-info'      => true,
			'operation-history' => true,
			'operation-details' => true,
			'request-payment'   => true,
		);
		$is_action = null;
		if( !@empty( $methods[ $_GET[ 'method' ] ] ) ) {
			$is_action = true;
			$method_id = $_GET[ 'method' ];
			$is_debug  = @$_GET[ 'is_debug' ];
			$options   = array(
				'method_id' => $method_id,
				'is_debug'  => $is_debug,
			);
			$operation_id = '_'. ( @$_GET[ 'operation_id' ] ?: '1' );
			$amount  = @$_GET[ 'amount'  ] ?: 0.01;
			$account = @$_GET[ 'account' ] ?: $api->PURSE_ID[ 'USD' ];
			switch( true ) {
				case $method_id == 'operation-history':
					$options[ 'option' ] = array(
						'details' => 'true',
					);
					break;
				case $method_id == 'operation-details':
					$options[ 'option' ] = array(
						'operation_id' => '500141181144110002',
					);
					break;
				case $method_id == 'request-payment':
					$options[ 'option' ] = array(
						'pattern_id'   => 'p2p',
						'to'           => '410012771676199',
						'amount'       => '0.01',
						'message'      => 'Test payout',
						'comment'      => 'Test payout: 0.01',
						'test_payment' => 'true',
						'test_result'  => 'success',
					);
					break;
			}
			$php[] = var_export( array(
				'request' => $options,
			), true );
			$result = $api->api_request( $options );
			$php[] = var_export( array(
				'response'  => $result,
			), true );
		} else { $php = 'Выберите метод'; }
		// actions
		$action = array();
		foreach( $methods as $item => $active ) {
			if( empty( $active ) ) { continue; }
			$link = url_user( '/@object/@action?method='. $item );
			$a = <<<EOS
<a href="$link" class="btn btn-default">$item</a>
EOS;
			$action[] = $a;
		}
		return( array( 'header' => 'api: ', 'php' => $php, 'action' => $action, 'is_action' =>  $is_action ) );
	}

	protected function _api_WebMoney( $title ) {
		$php     = '';
		$api     = _class( 'payment_api__provider_webmoney' );
		$methods = array(
			'payout_p2p' => true,
			'balance'    => true,
		);
		$is_action = null;
		if( !@empty( $methods[ $_GET[ 'method' ] ] ) ) {
			$is_action = true;
			$method_id = $_GET[ 'method' ];
			$is_debug  = @$_GET[ 'is_debug' ];
			$options   = array(
				'method_id' => $method_id,
				'is_debug'  => $is_debug,
			);
			$operation_id = '_'. ( @$_GET[ 'operation_id' ] ?: '1' );
			$amount  = @$_GET[ 'amount'  ] ?: 0.01;
			$account = @$_GET[ 'purse' ] ?: $api->_purse_by_currency( array( 'currency_id' => 'USD' ) )[ 'id' ];
			switch( true ) {
				case $method_id == 'balance':
					$options[ 'option' ] = array(
						'purse' => $account,
					);
					break;
			}
			$php[] = var_export( array(
				'request' => $options,
			), true );
			$result = $api->api_request( $options );
			$php[] = var_export( array(
				'response'  => $result,
			), true );
		} else { $php = 'Выберите метод'; }
		// actions
		$action = array();
		foreach( $methods as $item => $active ) {
			if( empty( $active ) ) { continue; }
			$link = url_user( '/@object/@action?method='. $item );
			$a = <<<EOS
<a href="$link" class="btn btn-default">$item</a>
EOS;
			$action[] = $a;
		}
		return( array( 'header' => 'api: ', 'php' => $php, 'action' => $action, 'is_action' =>  $is_action ) );
	}

	// ************* test payout
	public function payout() {
		$provider = array(
			// 'Privat24',
			// 'LiqPay',
			'Interkassa',
			// 'WebMoney',
			'Ecommpay',
			'PerfectMoney',
			'YandexMoney',
		);
		$_provider = array();
		foreach( $provider as $item ) {
			if( !empty( $this->payout[ $item ] ) ) {
				$_provider[] = $item;
			}
		}
		$result = array();
		$result += $this->_chunk( 'payout', $_provider );
		$this->_render( $result );
	}

	protected function _payout_Privat24( $title ) {
return( (array)'opss...' );
		$form    = '';
		$api     = _class( 'payment_api__provider_privat24' );
		// pb
		$method = 'pay_pb';
		$options = array(
			// 'wait'         => 1,
			// 'test'         => 1,
			'operation_id' => '_1',
			'amount'       => 0.01,
			// 'currency'     => 'UAH',
			'title'        => 'test',
			'account'      => '5457082390236292',
		);
		$result = $api->api_payout( $method, $options );
		list( $status, $status_message ) = $result;
		$form .= var_export( array(
			'method'   => $method,
			'request'  => $options,
			'response' => $result,
		), true );
		// visa
		$method = 'pay_visa';
		$options = array(
			// 'wait'         => 1,
			// 'test'         => 1,
			'operation_id' => '_1',
			'amount'       => 0.01,
			'currency'     => 'UAH',
			'title'        => 'test',
			'name'         => 'Test Tst',
			'account'      => '4731217103744338',
		);
		$result = $api->api_payout( $method, $options );
		list( $status, $status_message ) = $result;
		$form .= var_export( array(
			'method'   => $method,
			'request'  => $options,
			'response' => $result,
		), true );
		return( array( $form ) );
	}

	protected function _payout_EcommPay( $title ) {
		$payment_api = _class( 'payment_api' );
		$provider_class = $payment_api->provider_class( array(
			'provider_name' => 'ecommpay',
		));
		$php = '';
		$methods = array(
			'pay_card' => true,
			'qiwi'     => true,
		);
		// process
		if( @$methods[ $_GET[ 'method' ] ] ) {
			$method_id = $_GET[ 'method' ];
			$is_debug  = @$_GET[ 'is_debug' ];
			$options   = array(
				'method_id' => $method_id,
				'is_debug'  => $is_debug,
			);
			$operation_id = @$_GET[ 'operation_id' ] ?: '1';
			switch( true ) {
				case $method_id == 'pay_card':
					$options += array(
						// 'wait'         => 1,
						// 'test'         => 1,
						// 'method_id'    => $method_id,
						'operation_id' => $operation_id,
						'amount'       => 0.01,
						// 'currency'     => 'UAH',
						// 'title'        => 'test',
						'account'      => '5555555555554444',
						'sender_first_name'          => 'Test',
						'sender_last_name'           => 'Test',
						'sender_middle_name'         => 'Test',
						'sender_passport_number'     => '4400123456',
						'sender_passport_issue_date' => '2000-11-11',
						'sender_passport_issued_by'  => 'Test test',
						'sender_phone'               => '380612203238',
						'sender_birthdate'           => '1977-11-11',
						'sender_address'             => 'Test 123',
						'sender_city'                => 'Test',
						'sender_postindex'           => '123456',
					);
					break;
				case $method_id == 'qiwi':
					$options += array(
						// 'wait'         => 1,
						// 'test'         => 1,
						// 'method_id'    => $method_id,
						'operation_id' => $operation_id,
						'amount'       => 0.01,
						// 'currency'     => 'UAH',
						// 'title'        => 'test',
						'account_number' => '12345678900',
					);
					break;
			}
			$php[] = var_export( array(
				'method_id' => $method_id,
				'request'   => $options,
			), true );
			$result = $provider_class->api_payout( $options );
			list( $status, $status_message ) = $result;
			$php[] = var_export( array(
				'response' => $result,
			), true );
		} else { $php = 'Выберите метод'; }
		// actions
		$action = array();
		foreach( $methods as $item => $active ) {
			if( empty( $active ) ) { continue; }
			$link = url_user( '/@object/@action?method='. $item );
			$a = <<<EOS
<a href="$link" class="btn btn-default">$item</a>
EOS;
			$action[] = $a;
		}
		return( array( 'header' => 'payout: ', 'php' => $php, 'action' => $action ) );
	}

	protected function _payout_Interkassa( $title ) {
		$payment_api = _class( 'payment_api' );
		$provider_class = $payment_api->provider_class( array(
			'provider_name' => 'interkassa',
		));
		$php = '';
		$methods = array(
			'mastercard_p2p_privat_uah' => true,
		);
		$is_action = null;
		// process
		if( @$methods[ $_GET[ 'method' ] ] ) {
			$is_action = true;
			$method_id = $_GET[ 'method' ];
			$is_debug  = @$_GET[ 'is_debug' ];
			$options   = array(
				'method_id' => $method_id,
				'is_debug'  => $is_debug,
			);
			$operation_id = @$_GET[ 'operation_id' ] ?: '1';
			$amount = @$_GET[ 'amount' ] ?: 50;
			$card   = @$_GET[ 'card'   ] ?: '5218572211211342';
			switch( true ) {
				case $method_id == 'mastercard_p2p_privat_uah':
					$options += array(
						'operation_id' => $operation_id,
						'amount'       => $amount,
						// 'currency'     => 'UAH',
						'title'        => 'Вывод средств: '. $amount . ' грн',
						'account'      => $card,
						'provider_force' => true,
					);
					break;
			}
			$php[] = var_export( array(
				'method_id'      => $method_id,
				'request'        => $options,
			), true );
			$result = $provider_class->api_payout( $options );
			list( $status, $status_message ) = $result;
			$php[] = var_export( array(
				'response' => $result,
			), true );
		} else { $php = 'Выберите метод'; }
		// actions
		$action = array();
		foreach( $methods as $item => $active ) {
			if( empty( $active ) ) { continue; }
			$link = url_user( '/@object/@action?method='. $item );
			$a = <<<EOS
<a href="$link" class="btn btn-default">$item</a>
EOS;
			$action[] = $a;
		}
		return( array( 'header' => 'payout: ', 'php' => $php, 'action' => $action, 'is_action' =>  $is_action ) );
	}

	protected function _payout_PerfectMoney( $title ) {
		$payment_api = _class( 'payment_api' );
		$provider_class = $payment_api->provider_class( array(
			'provider_name' => 'perfectmoney',
		));
		$php = '';
		$methods = array(
			'perfectmoney' => true,
		);
		$is_action = null;
		// process
		if( @$methods[ $_GET[ 'method' ] ] ) {
			$is_action = true;
			$method_id = $_GET[ 'method' ];
			$is_debug  = @$_GET[ 'is_debug' ];
			$options   = array(
				'method_id' => $method_id,
				'is_debug'  => $is_debug,
			);
			$operation_id = '_'. ( @$_GET[ 'operation_id' ] ?: '1' );
			$amount  = @$_GET[ 'amount'  ] ?: 0.01;
			$account = @$_GET[ 'account' ] ?: $provider_class->PURSE_ID[ 'USD' ];
			switch( true ) {
				case $method_id == 'perfectmoney':
					$options += array(
						'operation_id' => $operation_id,
						'amount'       => $amount,
						// 'currency'     => 'USD',
						'title'        => 'Вывод средств USD: '. $amount,
						'account'      => $account,
						'provider_force' => true,
					);
					break;
			}
			$php[] = var_export( array(
				'method_id'      => $method_id,
				'request'        => $options,
			), true );
			$result = $provider_class->api_payout( $options );
			list( $status, $status_message ) = $result;
			$php[] = var_export( array(
				'response' => $result,
			), true );
		} else { $php = 'Выберите метод'; }
		// actions
		$action = array();
		foreach( $methods as $item => $active ) {
			if( empty( $active ) ) { continue; }
			$link = url_user( '/@object/@action?method='. $item );
			$a = <<<EOS
<a href="$link" class="btn btn-default">$item</a>
EOS;
			$action[] = $a;
		}
		return( array( 'header' => 'payout: ', 'php' => $php, 'action' => $action, 'is_action' =>  $is_action ) );
	}

	protected function _payout_YandexMoney( $title ) {
		$payment_api = _class( 'payment_api' );
		$provider_class = $payment_api->provider_class( array(
			'provider_name' => 'yandexmoney',
		));
		$php = '';
		$methods = array(
			'yandexmoney_p2p' => true,
		);
		$is_action = null;
		// process
		if( @$methods[ $_GET[ 'method' ] ] ) {
			$is_action = true;
			$method_id = $_GET[ 'method' ];
			$is_debug  = @$_GET[ 'is_debug' ];
			$options   = array(
				'method_id' => $method_id,
				'is_debug'  => $is_debug,
			);
			$operation_id = @$_GET[ 'operation_id' ] ?: 1;
			$amount  = @$_GET[ 'amount'  ] ?: 100;
			$test_result1 = @$_GET[ 'test_result1' ];
			$test_result2 = @$_GET[ 'test_result2' ];
			switch( true ) {
				case $method_id == 'yandexmoney_p2p':
					$options += array(
						'operation_id'   => $operation_id,
						'to'             => '410012771676199',
						'amount'         => $amount,
						'title'          => 'Вывод средств: '. $amount,
						'test_result1'    => $test_result1,
						'test_result2'    => $test_result2,
						'provider_force' => true,
					);
					break;
			}
			$php[] = var_export( array(
				'method_id'      => $method_id,
				'request'        => $options,
			), true );
			$result = $provider_class->api_payout( $options );
			list( $status, $status_message ) = $result;
			$php[] = var_export( array(
				'response' => $result,
			), true );
		} else { $php = 'Выберите метод'; }
		// actions
		$action = array();
		foreach( $methods as $item => $active ) {
			if( empty( $active ) ) { continue; }
			$link = url_user( '/@object/@action?method='. $item );
			$a = <<<EOS
<a href="$link" class="btn btn-default">$item</a>
EOS;
			$action[] = $a;
		}
		return( array( 'header' => 'payout: ', 'php' => $php, 'action' => $action, 'is_action' =>  $is_action ) );
	}

	// ************* test currency_rate
	protected function currency_rate() {
		$result = array();
		$currency__api = _class( 'payment_api__currency' );
		// NBU
		/*
		$data = array (
			0 => array (
				'from' => 'USD',
				'to' => 'UAH',
				'from_value' => '100',
				'to_value' => '2289.8565',
			),
			1 => array (
				'from' => 'EUR',
				'to' => 'UAH',
				'from_value' => '100',
				'to_value' => '2478.0827',
			),
			2 => array (
				'from' => 'RUB',
				'to' => 'UAH',
				'from_value' => '10',
				'to_value' => '4.4490',
			),
		);
		//*/
		$data = $currency__api->load_from_NBU();
		$r = $this->_add_panel( array(
			'header' => 'НБУ',
			'php'    => var_export( $data, true ),
		));
		$result[] = $r;
		// p24
		// $data = $currency__api->load_from_Privat24();
		// $r = $this->_add_panel( array(
			// 'header' => 'Приват24',
			// 'php'    => var_export( $data, true ),
		// ));
		// $result[] = $r;
		// CashExchange
		// $data = $currency__api->load_from_CashExchange();
		/*
		$data = array (
			0 => array (
			'from'       => 'USD',
			'to'         => 'UAH',
			'from_value' => 1,
			'to_value'   => '22.0428',
			),
			1 => array (
			'from'       => 'UAH',
			'to'         => 'USD',
			'from_value' => '24.0503',
			'to_value'   => 1,
			),
			2 => array (
			'from'       => 'EUR',
			'to'         => 'UAH',
			'from_value' => 1,
			'to_value'   => '23.7551',
			),
			3 => array (
			'from'       => 'UAH',
			'to'         => 'EUR',
			'from_value' => '26.1773',
			'to_value'   => 1,
			),
			4 => array (
			'from'       => 'RUB',
			'to'         => 'UAH',
			'from_value' => 1,
			'to_value'   => '0.6029',
			),
			5 => array (
			'from'       => 'UAH',
			'to'         => 'RUB',
			'from_value' => '0.6938',
			'to_value'   => 1,
			),
		); //*/
		// reverse data
		$data = $currency__api->reverse( array(
			'currency_rate' => $data,
		));
		$r = $this->_add_panel( array(
			'header' => 'reverse data',
			'php'    => var_export( $data, true ),
		));
		$result[] = $r;
		// prepare data
		$data = $currency__api->prepare( array(
			'currency_rate' => $data,
		));
		$r = $this->_add_panel( array(
			'header' => 'prepare data',
			'php'    => var_export( $data, true ),
		));
		$result[] = $r;
		// correction rate
		$data = $currency__api->correction( array(
			'currency_rate' => $data,
		));
		$r = $this->_add_panel( array(
			'header' => 'correction rate',
			'php'    => var_export( $data, true ),
		));
		$result[] = $r;
		// update
/*
		main()->init_modules_base();
		main()->init_main_functions();
		main()->init_events();
		main()->init_cache();
		main()->init_files();
		main()->init_db();
		$data = $currency__api->update( array(
			'currency_rate' => $data,
		));
		$r = $this->_add_panel( array(
			'header' => 'update',
			'php'    => var_export( $data, true ),
		));
		$result[] = $r;
 */
		// finish
		$this->_render( $result );
	}

	// ************* test currency_rate
	public function currency_rate_current() {
		$result = array();
		$payment_api = _class( 'payment_api' );
		// UNT
		$currency_id = 'UNT';
		$buy  = $payment_api->currency_rate__buy();
		$sell = $payment_api->currency_rate__sell();
		$r = $this->_add_panel( array(
			'header' => $currency_id,
			'php'    => var_export( array(
				'buy'  => $buy,
				'sell' => $sell,
			), true ),
		));
		$result[] = $r;
		// USD
		$currency_id = 'USD';
		$buy  = $payment_api->currency_rate__buy( array( 'currency_id' => $currency_id ));
		$sell = $payment_api->currency_rate__sell(array( 'currency_id' => $currency_id ));
		$r = $this->_add_panel( array(
			'header' => $currency_id,
			'php'    => var_export( array(
				'buy'  => $buy,
				'sell' => $sell,
			), true ),
		));
		$result[] = $r;
		// finish
		$this->_render( $result );
	}

	// ************* test number
	protected function number() {
		$value = 12345.1234;
		$payment_api = _class( 'payment_api' );
		$result = $payment_api->money_format( array(
			'format' => 'html',
			'sign'   => true,
			'nbsp'   => true,
			'value'  => $value,
		));
		$result .= '<br>'. $payment_api->money_html( array(
			'value'  => $value,
		));
		$result .= '<br>'. $payment_api->money_html( $value );
		echo( $result );
	}

	// ************* test provider
	public function payin() {
		if( !empty( $_POST ) ) {
			$payment_api = _class( 'payment_api' );
			$dump = $payment_api->dump();
			if( empty( $_GET[ 'server' ] ) ) {
				$result = $this->_add_panel( array(
					'header' => 'Тест ответа: ',
					'php'    => $dump,
				));
				$this->_render( $result );
			}
			exit();
		}
		$this->_init_options();
		$provider = array(
			'Privat24',
			'LiqPay',
			'Interkassa',
			'YandexMoney',
			'WebMoney',
			'Ecommpay',
			'PerfectMoney',
		);
		$_provider = array();
		foreach( $provider as $item ) {
			if( !empty( $this->payin[ $item ] ) ) {
				$_provider[] = $item;
			}
		}
		$result = array();
		$result += $this->_chunk( 'payin', $_provider );
		$this->_render( $result );
	}

	protected function sign() {
		$provider = array(
			'Privat24',
			'LiqPay',
			'Interkassa',
			'WebMoney',
			'PerfectMoney',
			// 'YandexMoney',
		);
		$result = array();
		$result += $this->_chunk( 'sign', $provider );
		$this->_render( $result );
	}

	protected function ip_check() {
		$api = _class( 'payment_api__provider_remote' );
		$api->ENABLE = true;
		// $api = _class( 'api' );
		$ips = array(
			'212.118.48.1',
			'212.118.48.2',
			'212.118.48.3',
			'212.118.49.1',
			'212.119.49.1',
		);
		$allow = array(
			'212.118.48.1'    => true,
			'212.118.48.2'    => true,
			'212.118.48.*'    => true,
			'212.118.48.0/24' => true,
			'212.118.*.*'     => false,
		);
		$result = array();
		$ip = $api->_ip();
		$result[] = 'ip: '.       var_export( $ip, true );
		$result[] = 'ip allow: '. var_export( $allow, true );
		foreach( $ips as $ip ) {
			$r = $api->_check_ip( array(
				'ip'        => $ip,
				'ip_filter' => $allow,
			));
			$result[] = $ip .': '. var_export( $r, true );
		}
		$result = $this->_add_panel( array( 'header' => 'IP check', 'php' => $result ) );
		return( $this->_render( $result ) );
	}

	public function _api_js_cors() {
		// check origin
		header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
		header('Access-Control-Allow-Credentials: true');
		// all origin
		// header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, PATCH, DELETE');
		header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Content-Range, Content-Disposition');
		return( array(
			'cors'   => 'ok',
			'get'    => $_GET,
			'post'   => $_POST,
			'server' => $_SERVER,
		));
	}

	protected function js_cors() {
		$api = _class( 'api' );
echo <<<'EOS'
<!DOCTYPE html>
<html ng-app="myApp">
	<head>
		<meta charset="utf-8">
		<title>JS CORS</title>
	</head>
	<body ng-controller="MainCtrl">
		Cross-Origin Resource Sharing<br/>

		<button ng-click="get()">GET</button>
		<br/>
		Result : {{resultGet|json}}
		<hr/>
		<input ng-model="value"/><button ng-click="post(movie)">POST</button>
		<br/>
		Result : {{resultPost|json}}
		<hr/>

		<script src="http://code.angularjs.org/1.4.8/angular.min.js"></script>

		<script>
			var app = angular.module('myApp', []);
			app.config(function($httpProvider) {
				//Enable cross domain calls
				$httpProvider.defaults.useXDomain = true;
				//Remove the header containing XMLHttpRequest used to identify ajax call
				//that would prevent CORS from working
				delete $httpProvider.defaults.headers.common['X-Requested-With'];
			});
			app.controller('MainCtrl', function($scope, $http) {
				var url_api = 'http://rocky.dev/api/payment_test/js_cors';
				$scope.get = function() {
					$http.post(url_api)
					.success(function(result) {
						console.log(result);
						$scope.resultGet = result;
					})
					.error(function(result) {
						$scope.resultGet = result;
						console.log("error");
					});
				};
				$scope.post = function(value) {
					$http.post(url_api, { value: value })
					.success(function(result) {
						console.log(result);
						$scope.resultPost = result;
					})
					.error(function(result) {
						$scope.resultPost = result;
						console.log("error");
					});
				};
			});
		</script>

	</body>
</html>
EOS;
		exit;
	}

	// *************** payin

	protected function _payin_privat24( $title ) {
		$options = $this->_options( $title );
		$api     = _class( 'payment_api__provider_privat24' );
		$form    = $api->_form( $options );
		$submit  = '<img src="https://www.privat24.ua/img/logo_big2.png" />';
		return( array( $form, $submit ) );
	}

	protected function _payin_liqpay( $title ) {
		$options = $this->_options( $title );
		$api = _class( 'payment_api__provider_liqpay' );
		$form = $api->_form( $options );
		$submit = '<img src="//static.liqpay.com/buttons/p1ru.radius.png" />';
		return( array( $form, $submit ) );
	}

	protected function _payin_interkassa( $title ) {
		$options = $this->_options( $title );
		$api     = _class( 'payment_api__provider_interkassa' );
		$is_key_private = (bool)$_GET[ 'is_key_private' ];
		if( $is_key_private ) {
			$key      = $api->key( 'private'      );
			$key_test = $api->key( 'private_test' );
			$api->key( 'private',      $key );
			$api->key( 'private_test', $key );
		}
		$form    = $api->_form( $options );
		$submit  = '<img src="https://www.interkassa.com/img/ik_logo/Logo-RU-300dpi.png" />';
		return( array( $form, $submit ) );
	}

	protected function _payin_yandexmoney( $title ) {
		$options = $this->_options( $title );
		$_ = &$options;
		// fix
		unset( $_[ 'url_result' ], $_[ 'url_server' ], $_[ 'currency' ] );
		$_[ 'amount' ] = '0.5';
		$_[ 'description' ] = $_[ 'title' ];
		// form
		$api     = _class( 'payment_api__provider_yandexmoney' );
		$form    = $api->_form( $options );
/*
		$form =  <<<EOS
<form id="_js_provider_yandexmoney_form" method="post" accept-charset="utf-8" action="${url}" class="display: none;">
<input type="hidden" name="test_payment" value="true" />
<input type="hidden" name="test_result" value="success" />
	<input type="hidden" name="receiver" value="${key_public}" />
	<input type="hidden" name="comment" value="test.dev" />
	<input type="hidden" name="short-dest" value="Пополнение счета" />
	<input type="hidden" name="formcomment" value="test.dev: Пополнение счета" />
	<input type="hidden" name="label" value="_1" />
	<input type="hidden" name="quickpay-form" value="shop" />
	<input type="hidden" name="targets" value="Пополнение счета" />
	<input type="hidden" name="sum" value="0.2" />
	<input type="hidden" name="paymentType" value="PC" />
	<input type="hidden" name="need-flo" value="false" />
	<input type="hidden" name="need-email" value="false" />
	<input type="hidden" name="need-phone" value="false" />
	<input type="hidden" name="need-address" value="false" />
</form>
EOS;
 */
		$submit  = '<img src="http://money.yandex.ru/b/blocks/full-site/_/uDftMQXR8pQKYR9NT08a17NSQ64.svg" />';
		return( array( $form, $submit ) );
	}

	protected function _payin_WebMoney( $title ) {
		$url         = 'https://merchant.webmoney.ru/lmi/payment.asp';
		$title = 'Пополнение счета';
		$title = base64_encode( $title );
		// $title = iconv( 'utf-8', 'windows-1251', $title );
		$url_result  = url_user( '/payment_test/provider?status=result'  );
		$url_success = url_user( '/payment_test/provider?status=success' );
		$url_fail    = url_user( '/payment_test/provider?status=fail'    );
		$options = $this->_options( $title );
		$api     = _class( 'payment_api__provider_webmoney' );
		$form    = $api->_form( $options );
		$submit  = '<img src="http://wiki.webmoney.ru/images/wm/logo-wm.png" />';
		return( array( $form, $submit ) );
	}

	protected function _payin_ecommpay( $title ) {
		$options = $this->_options( $title );
		$api     = _class( 'payment_api__provider_ecommpay' );
		$options[ 'currency' ] = 'USD';
		$options[ 'payment_group_id' ] = '1';
		$form    = $api->_form( $options );
		$submit  = '<img src="https://ecommpay.com/wp-content/uploads/2014/11/logo-prefinal-blue3.png" />';
		return( array( $form, $submit ) );
	}

	protected function _payin_perfectmoney( $title ) {
		$options = $this->_options( $title );
		$api     = _class( 'payment_api__provider_perfectmoney' );
		$options[ 'currency' ] = 'USD';
		$form    = $api->_form( $options );
		$submit  = '<img src="https://perfectmoney.is/img/logo3.png" />';
		return( array( $form, $submit ) );
	}


	// *************** signature

	protected function _sign_privat24() {
		$form = '';
		$api  = _class( 'payment_api__provider_privat24' );
		// test signature
		$api->key( 'public',  '104702'                           );
		$api->key( 'private', 'Z1LubP64rkt4e6Uw2kmPKDoCvobz9R9n' );
		// request
		$method = 'request';
		$data = array(
			'amt'         => '10.20',
			'ccy'         => 'USD',
			'details'     => 'Пополнение счета (Приват24)',
			'ext_details' => 3345,
			'order'       => 3345,
			'merchant'    => '104702',
			'pay_way'     => 'privat24',
			'return_url'  => 'http://spori.dev/api/payment/provider?name=privat24&operation=response&operation_id=3345',
			'server_url'  => 'http://spori.dev/api/payment/provider?name=privat24&operation=response&server=true&operation_id=3345',
			'signature'   => '8595eea657f3ccf8f83e5662f547154502586f60',
		);
		$sign = $api->signature( $data, $is_request = true );
		$status = 'fail';
		$sign == $data[ 'signature' ] && $status = 'ok';
		$form .= "\nsignature $method: $status";
		// response
		$method = 'response';
		$data = 'amt=16.00&ccy=UAH&details=Поплнение счета (Приват 24)&ext_details=3#71#9#3#16&pay_way=privat24&order=71&merchant=104702&state=test&date=171214180311&ref=test payment&payCountry=UA';
		$signature = '585b0c173ec36300a5ff77f6cbd9f195492f0c0d';
		$sign = $api->signature( $data, $is_request = false );
		$status = 'fail';
		$sign == $signature && $status = 'ok';
		$form .= "\nsignature $method: $status";
		return( array( 'php' => $form ) );
	}

	protected function _sign_liqpay() {
		$form = '';
		$api  = _class( 'payment_api__provider_liqpay' );
		// test signature
		$api->key( 'public',  'i20715277130'                             );
		$api->key( 'private', 'a0LBAPAJ2UbSSo3xxybT6gZoslPgQra30S7bCQzp' );
		// request
		$method = 'request';
		$data = array(
			'amount'      => '10.28',
			'currency'    => 'USD',
			'description' => 'Пополнение счета (LiqPay)',
			'order_id'    => 3338,
			'public_key'  => 'i20715277130',
			'pay_way'     => 'card,delayed',
			'result_url'  => 'http://spori.dev/api/payment/provider?name=liqpay&operation=response&operation_id=3338',
			'server_url'  => 'http://spori.dev/api/payment/provider?name=liqpay&operation=response&server=true&operation_id=3338',
			'sandbox'     => '1',
			'signature'   => 'wAVbeMSXbTmutsnZip7nuMiRWgo=',
		);
		$sign = $api->signature( $data, $is_request = true );
		$status = 'fail';
		$sign == $data[ 'signature' ] && $status = 'ok';
		$form .= "\nsignature $method: $status";
		// response
		$method = 'response';
		$data = array (
			'signature'           => '7GVdRWffi28gwdypt7HsvDKMV+8=',
			'receiver_commission' => '0.00',
			'sender_phone'        => '380679041321',
			'transaction_id'      => '47410158',
			'status'              => 'sandbox',
			'liqpay_order_id'     => '4570u1419855885119185',
			'order_id'            => '_5',
			'type'                => 'buy',
			'description'         => 'Поплнение счета (LiqPay): 0.10 грн.',
			'currency'            => 'UAH',
			'amount'              => '0.10',
			'public_key'          => 'i20715277130',
			'version'             => '2',
		);
		$sign = $api->signature( $data, $is_request = false );
		$status = 'fail';
		$sign == $data[ 'signature' ] && $status = 'ok';
		$form .= "\nsignature $method: $status";
		return( array( 'php' => $form ) );
	}

	protected function _sign_interkassa() {
		$form = '';
		$api  = _class( 'payment_api__provider_interkassa' );
		// test signature
		$api->key( 'private',      'xXceiJgnFURU0lq9' );
		$api->key( 'private_test', 'xXceiJgnFURU0lq9' );
		// md5
		$ik = array(
			'ik_co_id' => '54be5909bf4efc7f6b8ab8f5',
			'ik_pm_no' => 'ID_4233',
			'ik_am'    => '100.00',
			'ik_cur'   => 'USD',
			'ik_desc'  => 'Event Description',
			'ik_sign'  => '6NSxzOTqMWxxupZo6tpQKg==',
		);
		$method = 'md5';
		$api->hash_method( $method );
		$sign = $api->signature( $ik );
		$status = 'fail';
		$sign == $ik[ 'ik_sign' ] && $status = 'ok';
		$form .= "\nsignature $method: $status";
		// sha256
		$ik = array(
			'ik_co_id' => '54be5909bf4efc7f6b8ab8f5',
			'ik_pm_no' => 'ID_4233',
			'ik_am'    => '100.00',
			'ik_cur'   => 'USD',
			'ik_desc'  => 'Event Description',
			'ik_sign'  => 'Z9srOHmyWMMJTj204V9pga5BXqo13LVHyxCgNaG6xwU=',
		);
		$method = 'sha256';
		$api->hash_method( $method );
		$sign = $api->signature( $ik );
		$status = 'fail';
		$sign == $ik[ 'ik_sign' ] && $status = 'ok';
		$form .= "\nsignature $method: $status";
		return( array( 'php' => $form ) );
	}

	protected function _sign_webmoney() {
		$form = '';
		$api  = _class( 'payment_api__provider_webmoney' );
		// test signature
		$api->key( 'private',      'hqbGLbbdg1IGSCwB30AG' );
		// sha256
		$data = array(
			'LMI_PAYEE_PURSE'      => 'Z272631242756',
			'LMI_PAYMENT_AMOUNT'   => '10.01',
			'LMI_PAYMENT_NO'       => '1234',
			'LMI_MODE'             => '1',
			'LMI_SYS_INVS_NO'      => '489',
			'LMI_SYS_TRANS_NO'     => '969',
			'LMI_SYS_TRANS_DATE'   => '20150327 18:19:05',
			'LMI_PAYER_PURSE'      => 'Z272631242756',
			'LMI_PAYER_WM'         => '352775132080',
			'LMI_PAYER_COUNTRYID'  => 'UA',
			'LMI_PAYER_PCOUNTRYID' => 'UA',
			'LMI_PAYER_IP'         => '46.46.72.161',
			'LMI_HASH'             => '4464B86459FD00EE0AF9373F844213EF6219A317EB5A43E95F5290A925C087B6',
			'LMI_PAYMENT_DESC'     => 'Пополнение счета',
			'LMI_LANG'             => 'ru-RU',
			'LMI_DBLCHK'           => 'ENUM',
		);
		$method = 'sha256';
		$api->hash_method( $method );
		$sign = $api->signature( $data );
		$status = 'fail';
		$sign == $data[ 'LMI_HASH' ] && $status = 'ok';
		$form .= "\nsignature $method: $status";
		return( array( 'php' => $form ) );
	}

	protected function _sign_PerfectMoney() {
		$form = '';
		$api  = _class( 'payment_api__provider_perfectmoney' );
		// test signature
		$api->key( 'private', "ohboyi'msogood1" );
		// with PAYMENT_ID
		$data = array(
			'PAYMENT_ID'        => 'AB-123',
			'PAYEE_ACCOUNT'     => 'U123456',
			'PAYMENT_AMOUNT'    => '300.00',
			'PAYMENT_UNITS'     => 'USD',
			'PAYMENT_BATCH_NUM' => '789012',
			'PAYER_ACCOUNT'     => 'U456789',
			'TIMESTAMPGMT'      => '876543210',
		);
		$v2_hash = '1CC09524986EDC51F7BEA9E6973F5187';
		$sign = $api->signature( $data );
		$status = 'fail';
		$sign == $v2_hash && $status = 'ok';
		$form .= "\nsignature with operation_id: $status";
		$status == 'fail' && $form .= "\n$v2_hash != $sign";
		// without PAYMENT_ID
		$data = array(
			// 'PAYMENT_ID'        => 'AB-123',
			'PAYEE_ACCOUNT'     => 'U123456',
			'PAYMENT_AMOUNT'    => '300.00',
			'PAYMENT_UNITS'     => 'USD',
			'PAYMENT_BATCH_NUM' => '789012',
			'PAYER_ACCOUNT'     => 'U456789',
			'TIMESTAMPGMT'      => '876543210',
		);
		$v2_hash = 'CA3708D5766BD2414719FFE744D2C5CC';
		$sign = $api->signature( $data );
		$status = 'fail';
		$sign == $v2_hash && $status = 'ok';
		$form .= "\nsignature without operation_id: $status";
		$status == 'fail' && $form .= "\n$v2_hash != $sign";
		return( array( 'php' => $form ) );
	}

	protected function _sign_YandexMoney() {
		$form = '';
		$api  = _class( 'payment_api__provider_yandexmoney' );
/*
		// test signature
		$api->key( 'private', '01234567890ABCDEF01234567890' );
		// https notification
		$data = array(
			'operation_id'      => '441361714955017004',
			'notification_type' => 'card-incoming',
			'datetime'          => '2013-12-26T08:28:34Z',
			'sha1_hash'         => 'ac13833bd6ba9eff1fa9e4bed76f3d6ebb57f6c0',
			'sender'            => '',
			'codepro'           => 'false',
			'currency'          => '643',
			'amount'            => '98.00',
			'withdraw_amount'   => '100.00',
			'label'             => 'ML23045',
			'lastname'          => 'Иванов',
			'firstname'         => 'Петр',
			'fathersname'       => 'Сидорович',
			'zip'               => '195123',
			'city'              => 'Санкт-Петербург',
			'street'            => 'Денежная',
			'building'          => '12',
			'suite'             => '12',
			'flat'              => '12',
			'phone'             => '',
			'email'             => '',
		);
		$hash = $data[ 'sha1_hash' ];
		$sign = $api->signature( $data );
		$status = 'fail';
		$sign == $hash && $status = 'ok';
		$form .= "\nsignature over https: $status";
		$status == 'fail' && $form .= "\n$hash != $sign";
		// http notification
		$data = array(
			'operation_id'      => '441361714955017004',
			'notification_type' => 'card-incoming',
			'datetime'          => '2013-12-26T08:28:34Z',
			'sha1_hash'         => 'ac13833bd6ba9eff1fa9e4bed76f3d6ebb57f6c0',
			'codepro'           => 'false',
			'currency'          => '643',
			'amount'            => '98.00',
			'withdraw_amount'   => '100.00',
			'label'             => 'ML23045',
		);
		$hash = $data[ 'sha1_hash' ];
		$sign = $api->signature( $data );
		$status = 'fail';
		$sign == $hash && $status = 'ok';
		$form .= "\nsignature over http: $status";
		$status == 'fail' && $form .= "\n$hash != $sign";
 */
		// without label
		$api->key( 'private', '01234567890ABCDEF01234567890' );
		$data = array(
			'notification_type' => 'p2p-incoming',
			'operation_id'      => '1234567',
			'amount'            => '300.00',
			'currency'          => '643',
			'datetime'          => '2011-07-01T09:00:00.000+04:00',
			'sender'            => '41001XXXXXXXX',
			'codepro'           => 'false',
			'sha1_hash'         => '090a8e7ebb6982a7ad76f4c0f0fa5665d741aafa',
			'withdraw_amount'   => '100.00',
		);
		$hash = $data[ 'sha1_hash' ];
		$sign = $api->signature( $data );
		$status = 'fail';
		$sign == $hash && $status = 'ok';
		$form .= "\nsignature without label: $status";
		$status == 'fail' && $form .= "\n$hash != $sign";
		// with label
		$data[ 'label'     ] = 'YM.label.12345';
		$data[ 'sha1_hash' ] = 'a2ee4a9195f4a90e893cff4f62eeba0b662321f9';
		$hash = $data[ 'sha1_hash' ];
		$sign = $api->signature( $data );
		$status = 'fail';
		$sign == $hash && $status = 'ok';
		$form .= "\nsignature with label: $status";
		$status == 'fail' && $form .= "\n$hash != $sign";
		return( array( 'php' => $form ) );
	}

	// ********************************* util

	// ************* fast init actions
	public function _fast_init() {
		main()->init_files();
		$this->_fast_init_route();
	}

	protected function _fast_init_route() {
		$url = parse_url( $_SERVER[ 'REQUEST_URI' ] );
		$path = explode( '/', $url[ 'path' ] );
		$object = $path[ 1 ];
		$method = $path[ 2 ];
		$_GET[ 'object' ] = $object;
		$_GET[ 'action' ] = $method;
		!empty( $method ) && $method = '_fast_' . $method;
		if( !method_exists( __CLASS__, $method ) ) { return( null ); }
		$this->$method();
		exit;
	}

	protected function _options( $title, $options = null ) {
		$_ = &$this->options;
		is_array( $_ ) && extract( $_, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$result = (array)$options + array(
			'amount'       => $_amount,
			'currency'     => 'UAH',
			'operation_id' => $_operation_id,
			'title'        => 'Пополнение счета',
			'description'  => "Пополнение счета ({$title}): {$_amount} грн.",
			'url_result'   => $_url_result,
			'url_server'   => $_url_server,
		);
		return( $result );
	}

	protected function _chunk( $name, $title ) {
		if( is_array( $title ) ) {
			$chunks = &$title;
			$result = array();
			foreach( $chunks as $item ) {
				$result[ $item ] = $this->_chunk( $name, $item );
			}
			return( $result );
		}
		$method = "_{$name}_{$title}";
		if( !method_exists( __CLASS__, $method ) ) {
			throw new BadMethodCallException( 'Method not exists: ' . $method );
		}
		$data = $this->$method( $title );
		if( $name == 'sign' ) {
			$data[ 'lang' ] = 'http';
		}
		$data += array(
			'name'  => $name,
			'title' => $title,
		);
		isset( $data[ 0 ] ) && $data += array( 'form'   => $data[ 0 ] );
		isset( $data[ 1 ] ) && $data += array( 'submit' => $data[ 1 ] );
		$result = $this->_add_chunk( $data );
		return( $result );
	}

	protected function _add_chunk( $options = null ) {
		empty( $options[ 'header' ] ) && $options[ 'header' ] = 'Провайдер: ';
		$result = $this->_add_panel( $options );
		return( $result );
	}

	protected function _add_panel( $options ) {
		static $count = 1;
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// action
		$action = null;
		if( !empty( $_action ) ) {
			$action = implode( ' ', (array)$_action );
		}
		// data
		if( @$_php ) { $data = is_array( $_php ) ? implode( "\n", $_php ) : $_php; $lang = 'php'; }
		elseif( @$_form ) { $data = $_form; $lang = 'html'; }
		$html_data = array();
		foreach( (array)$data as $item ) {
			$html_data[] = htmlentities( trim( $item ) );
		}
		$data = implode( '<hr>', $html_data );
		// lang
		$lang = $_lang ?: $lang ?: 'html';
		$expanded    = 'true';
		$expanded_in = 'in';
		if( is_string( $_submit ) || is_string( $action ) ) {
			$expanded    = 'false';
			!@$_is_action && $expanded_in = '';
			// form
			$form = '';
			$name = strtolower( $_title );
			@$_form && $form = <<<EOS
			<div class="form">
				$_form
			</div>
			<div class="submit">
				<a onclick="document.getElementById( '_js_provider_{$name}_form' ).submit();">
					$_submit
				</a>
			</div>
EOS;
			$footer = '';
			( $form || $action ) && $footer = <<<EOS
		<div class="panel-footer">
			$form
			$action
		</div>
EOS;
		}
		$result = <<<EOS
	<div class="panel panel-info">
		<div class="panel-heading" role="tab" id="heading{$count}">
			<a data-toggle="collapse" data-parent="#accordion" href="#collapse{$count}" aria-expanded="{$expanded}" aria-controls="collapse{$count}">
				<h4 class="panel-title">
					{$_header}{$_title}
				</h4>
			</a>
		</div>
		<div id="collapse{$count}" class="panel-collapse collapse {$expanded_in}" role="tabpanel" aria-labelledby="heading{$count}">
			<div class="panel-body">
				<pre><code class="lang-{$_lang}">{$data}</code></pre>
			</div>
		</div>
		$footer
	</div>
EOS;
		$count++;
		return( $result );
	}

	protected function _render( $result ) {
		$asset_class = _class('assets');
		$asset_class->clean_all();
		$asset_class->ADD_IS_DIRECT_OUT = true;
		$asset = asset( 'bootstrap3' );
		$result = implode( "\n", (array)$result );
		$hl_style = 'default';
		// $hl_style = 'solarized_dark';
echo <<<EOS
<!DOCTYPE html>
<head>
	<meta charset="utf-8">
	<title>Payment test</title>
	{$asset}
	<!--
	style: default, solarized_dark, zenburn, railscasts, ...
	  see: https://github.com/isagalaev/highlight.js/tree/master/src/styles
	-->
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.4/styles/{$hl_style}.min.css">
	<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.4/highlight.min.js"></script>
	<script>
	hljs.configure({
		tabReplace  : '    ', // 4 spaces
	})
	hljs.initHighlightingOnLoad();
	</script>
	<style>
		.form {
			display : none;
		}
		.submit a {
			cursor  : pointer;
			height  : 30px;
			display : block;
		}
		.submit img {
			height  : 100%;
		}
	</style>
</head>
<body>
	<div class="container-fluid"><div class="row">
		<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
$result
		</div>
	</div></div>
</body>
</html>
EOS;
		exit;
	}

}
