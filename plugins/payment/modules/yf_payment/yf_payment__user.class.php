<?php

class yf_payment__user {

	public function _init() {
	}

	public function balance( $options ) {
		if( empty( main()->USER_ID ) ) { js_redirect( '/', false, 'User id empty' ); }
		$payment_api = _class( 'payment_api' );
		list( $account_id,  $account  ) = $payment_api->get_account();
		list( $currency_id, $currency ) = $payment_api->get_currency__by_id( $account );
		list( $operation, $count ) = $payment_api->operation( $account );
		$page_per = $payment_api->OPERATION_LIMIT;
		$pages    = ceil( $count / $page_per );
		// provider
		$providers = $payment_api->provider( array(
			'all' => true,
		));
		$payment_api->provider_options( $providers, array(
			'IS_DEPOSITION', 'IS_PAYMENT',
			'fee', 'currency_allow', 'description',
		));
		$provider_user = $payment_api->provider();
		$provider = array();
		foreach( (array)$provider_user as &$item ) {
			$provider_id = (int)$item[ 'provider_id' ];
			$_provider   = &$providers[ $provider_id ];
			$_provider[ '_IS_DEPOSITION' ] && $provider[ 'deposition' ][] = $provider_id;
			$_provider[ '_IS_PAYMENT'    ] && $provider[ 'payment'    ][] = $provider_id;
		}
		// misc
		$status        = $payment_api->status();
		$currencies    = $payment_api->currencies;
		$currency_rate = $payment_api->currency_rate__buy();
		$replace = array(
			'payment'   => json_encode( array(
				'account'              => $account,
				'currency'             => $currency,
				'operation'            => $operation,
				'provider'             => $provider,
				'providers'            => $providers,
				'status'               => $status,
				'currencies'           => $currencies,
				'currency_rate'        => $currency_rate,
				'operation_pagination' => array(
					'count'    => $count,
					'page_per' => $page_per,
					'pages'    => $pages,
					'page'     => 1,
				),
			), JSON_NUMERIC_CHECK ),
		);
		// tpl
		$result  = '';
		$result .= tpl()->parse( 'payment/user/balance_ctrl', $replace );
		$result .= tpl()->parse( 'payment/user/balance_form', $replace );
		return( $result );
	}

}
