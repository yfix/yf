<?php

class yf_payment__user {

	public function _init() {
	}

	public function balance( $options ) {
		$payment_api = _class( 'payment_api' );
		list( $account_id,  $account  ) = $payment_api->get_account();
		list( $currency_id, $currency ) = $payment_api->get_currency__by_id( $account );
		$operation     = $payment_api->operation( $account );
		$provider      = $payment_api->provider();
		$status        = $payment_api->status();
		$currencies    = $payment_api->currencies;
		$currency_rate = $payment_api->currency_rate__buy();
		$replace = array(
			'payment'   => json_encode( array(
				'account'       => $account,
				'currency'      => $currency,
				'operation'     => $operation,
				'provider'      => $provider,
				'status'        => $status,
				'currencies'    => $currencies,
				'currency_rate' => $currency_rate,
				'operation_pagination' => array( 'page' => 1, 'page_per' => $payment_api->OPERATION_LIMIT ),
			), JSON_NUMERIC_CHECK ),
		);
		// tpl
		$result  = '';
		$result .= tpl()->parse( 'payment/user/balance_ctrl', $replace );
		$result .= tpl()->parse( 'payment/user/balance_form', $replace );
		return( $result );
	}

}
