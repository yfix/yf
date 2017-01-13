<?php

/**
* pcntl_signal handler
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_pcntl_signal {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	// signal handler
	static $is_exit      = true;
	static $is_terminate = false;
	static $is_silent    = false;
	function _init( $options = null ) {
		self::__init( $options );
	}
	public static function __init( $options = null ) {
		if( is_console() ) {
			// import options
			is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
			// var
			$is_exit      = &self::$is_exit;
			$is_terminate = &self::$is_terminate;
			$is_silent    = &self::$is_silent;
			// var init
			isset( $_is_exit      ) && $is_exit      = $_is_exit;
			isset( $_is_terminate ) && $is_terminate = $_is_terminate;
			isset( $_is_silent    ) && $is_silent    = $_is_silent;
			var_dump( 'init', $is_exit, $is_terminate, $is_silent );
		}
	}
	public static function _signal_init( $options = null ) {
		if( is_console() ) {
			self::__init( $options );
			// signal
			pcntl_signal( SIGTERM, __CLASS__ .'::_signal' );
			pcntl_signal( SIGINT,  __CLASS__ .'::_signal' );
		}
	}
	public static function _signal( $signo = null, $signinfo = null ) {
		// var
		$is_terminate = &self::$is_terminate;
		$is_silent    = &self::$is_silent;
		// signal
		$signo = (int)$signo;
		switch( $signo ) {
			case SIGTERM:
			case SIGINT:
				$is_terminate = true;
				break;
			default:
				if( !@$is_silent ) {
					echo 'Signal not handled: '. $signo .PHP_EOL;
				}
		}
	}
	public static function _is_terminate( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$is_exit      = &self::$is_exit;
		$is_terminate = &self::$is_terminate;
		$is_silent    = &self::$is_silent;
		// signal
		$__is_exit   = isset( $_is_exit   ) ? $_is_exit   : $is_exit;
		$__is_silent = isset( $_is_silent ) ? $_is_silent : $is_silent;
		$result = false;
		if( is_console() ) {
			pcntl_signal_dispatch();
			if( $is_terminate ) {
				$result = true;
				if( ! @$__is_silent ) {
					echo 'going to terminate'. PHP_EOL;
				}
				if( @$__is_exit ) {
					exit( 0 );
				}
			}
		}
		return( $result );
	}

	public static function test() {
		// var
		$is_exit      = &self::$is_exit;
		$is_terminate = &self::$is_terminate;
		$is_silent    = &self::$is_silent;
		// action
		// $is_exit = false;
		// $is_silent = true;
		echo PHP_EOL;
		$count = 5;
		while( true ){
			// signal handler
			if( self::_is_terminate() ) {
			// self::_is_terminate(); if( $is_terminate ) {
			// if( self::_is_terminate([ 'is_exit' => false, 'is_silent' => true ]) ) {
				echo 'break loop'. PHP_EOL;
				break;
			}
			echo 'long action: '. $count .' sec';
			for( $i = 0; $i < $count; $i++ ) {
				echo '.';
				sleep( 1 );
			}
			echo PHP_EOL;
		}
		echo 'exit'. PHP_EOL;
	}

}
