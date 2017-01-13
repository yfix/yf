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
			// ini
			ini_set( 'html_errors', 0 );
			error_reporting( E_ALL & ~E_NOTICE );
			if( isset( $_is_error ) && $_is_error ) {
				ini_set( 'display_errors',         true );
				ini_set( 'display_startup_errors', true );
			}
			// timeout
			if( isset( $_ttl ) && $_ttl > 0 ) {
				$ttl = (int)$_ttl;
				$r = 5; // 5%
				$d = $ttl * $r / 100;
				$t1 = $ttl - $d;
				$t2 = $ttl + $d;
				$t = mt_rand( $ttl - $d, $ttl + $d );
				( $ttl > 0 && $t < 1 ) && $t = 1;
				echo 'ttl: '. $t .PHP_EOL;
				set_time_limit( $t );
			}
			ini_set( 'default_socket_timeout', -1 );
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
