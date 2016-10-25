
<?php

class yf_payment_handler {

	public function balance_refresh( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( @$_user_id < 1 ) { return( null ); }
		$user_id = (int)$_user_id;
		$api         = _class( 'api'         );
		$payment     = module( 'payment'     );
		$payment_api = _class( 'payment_api' );
		// get account by user_id
		list( $account_id,  $account  ) = $payment_api->get_account([ 'user_id' => $user_id ]);
		if( empty( $account_id ) ) { return( null ); }
		list( $currency_id, $currency ) = $payment_api->get_currency__by_id( $account );
		$payment->t( $currency, 'currency' );
		$result = [
			'account'  => $account,
			'currency' => $currency,
		];
		return( $result );
	}

}

