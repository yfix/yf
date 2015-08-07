<?php

events()->listen( 'payin.finish', function( $account, $operation ) {
		var_dump( $account, $operation );
		exit;
});
events()->listen( 'payout.finish', function( $account, $operation ) {
		var_dump( $account, $operation );
		exit;
});
