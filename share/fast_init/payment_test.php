<?php

return function() {
	$module = 'payment_test';
	$method = '_fast_init';
	$class = module_safe( $module );
	if( !method_exists( $class, $method ) ) { return( null ); }
	$result = $class->$method();
	return( $result );
};
