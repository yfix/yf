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
		$provider = $payment_api->provider();
		$payment_api->provider_currency( array(
			'provider' => &$provider,
		));
		$status        = $payment_api->status();
		$currencies    = $payment_api->currencies;
		$currency_rate = $payment_api->currency_rate__buy();
		$replace = array(
			'payment'   => json_encode( array(
				'account'              => $account,
				'currency'             => $currency,
				'operation'            => $operation,
				'provider'             => $provider,
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
