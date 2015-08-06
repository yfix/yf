<?php

events()->listen( 'payin.finish', function( $account, $operation ) {
		var_dump( $account, $operation );
		exit;
});
