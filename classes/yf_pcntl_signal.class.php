<?php

/**
* pcntl_signal handler
* static class
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_pcntl_signal {

	// limit: time to live
	static $is_ttl        = true;
	static $ttl_rnd       = 5; // +/- 5%
	static $ttl_value     = 24 * 60 * 60; // 1 day, sec
	static $ttl           = 0;
	// limit: actions count
	static $is_limit_action = true;
	static $limit_action    = 100000;
	// counters
	static $ts_start       = 0;
	static $count_action   = 0;
	// signal handler
	static $is_exit      = true;
	static $is_terminate = false;
	static $is_silent    = false;
	// vars
	static $vars = [
		'is_ttl', 'ttl_rnd', 'ttl_value',
		'is_limit_action', 'limit_action',
		'is_exit', 'is_terminate', 'is_silent',
	];

	function _init( $options = null ) {
		self::__init( $options );
	}

	static function __init( $options = null ) {
		if( is_console() ) {
			// import options
			is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
			// var
			$ts              = time();
			$is_ttl          = &self::$is_ttl;
			$ttl_rnd         = &self::$ttl_rnd;
			$ttl_value       = &self::$ttl_value;
			$ttl             = &self::$ttl;
			$is_limit_action = &self::$is_limit_action;
			$limit_action    = &self::$limit_action;
			$ts_start        = &self::$ts_start;
			$count_action    = &self::$count_action;
			$is_exit         = &self::$is_exit;
			$is_terminate    = &self::$is_terminate;
			$is_silent       = &self::$is_silent;
			$vars            = &self::$vars;
			// vars init
			foreach( $vars as $n ) {
				$_v = &${ '_'. $n };
				if( !isset( $_v ) ) { continue; }
				${ $n } = $_v;
			}
			$ttl          = 0;
			$ts_start     = $ts;
			$count_action = 0;
			// ttl
			if( $is_ttl ) {
				$d   = $ttl_value * $ttl_rnd / 100;
				$t1  = $ttl_value - $d;
				$t2  = $ttl_value + $d;
				$ttl = mt_rand( $t1, $t2 );
				( $ttl_value > 0 && $ttl < 1 ) && $ttl = 1;
			}
			// ini
			ini_set( 'html_errors', 0 );
			error_reporting( E_ALL & ~E_NOTICE );
			if( isset( $_is_error ) && $_is_error ) {
				ini_set( 'display_errors',         true );
				ini_set( 'display_startup_errors', true );
			}
			ini_set( 'default_socket_timeout', -1 );
		}
	}

	static function _signal_init( $options = null ) {
		if( is_console() ) {
			self::__init( $options );
			$is_ttl          = &self::$is_ttl;
			$ttl             = &self::$ttl;
			$is_limit_action = &self::$is_limit_action;
			$limit_action    = &self::$limit_action;
			$is_silent       = &self::$is_silent;
			// signal
			if( !$is_silent ) {
				echo 'Process signal SIGINT, SIGTERM' .PHP_EOL;
			}
			pcntl_signal( SIGINT,  __CLASS__ .'::_signal' );
			pcntl_signal( SIGTERM, __CLASS__ .'::_signal' );
			// ttl
			if( $is_ttl ) {
				if( !$is_silent ) {
					echo 'Process limit ttl: '. $ttl .PHP_EOL;
				}
			}
			// action
			if( $is_limit_action ) {
				if( !$is_silent ) {
					echo 'Process limit action: '. $limit_action .PHP_EOL;
				}
			}
		}
	}

	static function _signal( $signo = null, $signinfo = null ) {
		// var
		$is_terminate = &self::$is_terminate;
		$is_silent    = &self::$is_silent;
		// signal
		$signo = (int)$signo;
		switch( $signo ) {
			case SIGINT:
			case SIGTERM:
				$is_terminate = true;
				break;
			default:
				if( !@$is_silent ) {
					echo 'Signal not handled: '. $signo .PHP_EOL;
				}
		}
	}

	static function _is_terminate( $options = null ) {
		$result = false;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( is_console() ) {
			// var
			$ts              = time();
			$is_ttl          = &self::$is_ttl;
			$ttl             = &self::$ttl;
			$is_limit_action = &self::$is_limit_action;
			$limit_action    = &self::$limit_action;
			$ts_start        = &self::$ts_start;
			$count_action    = &self::$count_action;
			$is_exit         = &self::$is_exit;
			$is_terminate    = &self::$is_terminate;
			$is_silent       = &self::$is_silent;
			$count_action++;
			// signal
			$__is_exit   = isset( $_is_exit   ) ? $_is_exit   : $is_exit;
			$__is_silent = isset( $_is_silent ) ? $_is_silent : $is_silent;
			if( ! @$__is_silent ) {
				echo 'Process action: #'. $count_action .PHP_EOL;
			}
			// signal
			pcntl_signal_dispatch();
			if( $is_terminate ) {
				$result = true;
				if( ! @$__is_silent ) {
					echo 'going to terminate by signal'. PHP_EOL;
				}
				if( @$__is_exit ) {
					exit( 0 );
				}
			}
			// ttl
			if( $is_ttl ) {
				$_ttl = $ts - $ts_start;
				if( $_ttl >= $ttl ) {
					if( ! @$__is_silent ) {
						echo 'going to terminate by limit ttl'. PHP_EOL;
					}
					exit( 0 );
				}
			}
			// count
			if( $is_limit_action && $count_action >= $limit_action ) {
				if( ! @$__is_silent ) {
					echo 'going to terminate by limit action'. PHP_EOL;
				}
				exit( 0 );
			}
		}
		return( $result );
	}

	public function test() {
		if( is_console() ) {
			// var
			$is_exit      = &self::$is_exit;
			$is_terminate = &self::$is_terminate;
			$is_silent    = &self::$is_silent;
			// action
			// $is_exit = false;
			// $is_silent = true;
			echo PHP_EOL;
			$count = 5;
			while( true ) {
				echo 'Long action: '. $count .' sec';
				for( $i = 0; $i < $count; $i++ ) {
					echo '.';
					sleep( 1 );
				}
				echo PHP_EOL;
				// signal handler
				if( self::_is_terminate() ) {
					// self::_is_terminate(); if( $is_terminate ) {
					// if( self::_is_terminate([ 'is_exit' => false, 'is_silent' => true ]) ) {
					echo 'break loop'. PHP_EOL;
					break;
				}
			}
			echo 'exit'. PHP_EOL;
		}
	}

}
