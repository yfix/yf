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
	protected static $_pcntl_is_ttl        = true;
	protected static $_pcntl_ttl_rnd       = 5; // +/- 5%
	protected static $_pcntl_ttl_value     = 24 * 60 * 60; // 1 day, sec
	protected static $_pcntl_ttl           = 0;
	// limit: actions count
	protected static $_pcntl_is_limit_action = true;
	protected static $_pcntl_limit_action    = 100000;
	// counters
	protected static $_pcntl_ts_start       = 0;
	protected static $_pcntl_count_action   = 0;
	// options
	protected static $_pcntl_is_exit      = true;
	protected static $_pcntl_is_silent    = false;
	// signal handler
	protected static $_pcntl_is_terminate           = false;
	protected static $_pcntl_is_terminate_by_signal = false;
	// vars
	protected static $_pcntl_vars = [
		'is_ttl', 'ttl_rnd', 'ttl_value',
		'is_limit_action', 'limit_action',
		'is_exit', 'is_silent',
	];

	public static $LOG0_PATH = 'php://stdout';
	public static $LOG1_PATH = 'php://stderr';
	public static $log0_file = null;
	public static $log1_file = null;

	function _init( $options = null ) {
		self::_pcntl_init( $options );
	}

	static function _pcntl_init( $options = null ) {
		if( is_console() ) {
			// import options
			is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
			// var
			$ts                     = time();
			$is_ttl                 = &self::$_pcntl_is_ttl;
			$ttl_rnd                = &self::$_pcntl_ttl_rnd;
			$ttl_value              = &self::$_pcntl_ttl_value;
			$ttl                    = &self::$_pcntl_ttl;
			$is_limit_action        = &self::$_pcntl_is_limit_action;
			$limit_action           = &self::$_pcntl_limit_action;
			$ts_start               = &self::$_pcntl_ts_start;
			$count_action           = &self::$_pcntl_count_action;
			$is_exit                = &self::$_pcntl_is_exit;
			$is_terminate           = &self::$_pcntl_is_terminate;
			$is_terminate_by_signal = &self::$_pcntl_is_terminate_by_signal;
			$is_silent              = &self::$_pcntl_is_silent;
			$vars                   = &self::$_pcntl_vars;
			// vars init
			foreach( $vars as $n ) {
				$_v = &${ '_'. $n };
				if( !isset( $_v ) ) { continue; }
				${ $n } = $_v;
			}
			// start init
			$ttl                    = 0;
			$ts_start               = $ts;
			$count_action           = 0;
			$is_terminate           = false;
			$is_terminate_by_signal = false;
			// ttl
			if( $is_ttl ) {
				if( $ttl_rnd > 0 ) {
					$d   = $ttl_value * $ttl_rnd / 100;
					$t1  = $ttl_value - $d;
					$t2  = $ttl_value + $d;
					$ttl = mt_rand( $t1, $t2 );
					( $ttl_value > 0 && $ttl < 1 ) && $ttl = 1;
				} else {
					$ttl = $ttl_value;
				}
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

	static function _pcntl_signal_init( $options = null ) {
		if( is_console() ) {
			self::_pcntl_init( $options );
			$is_ttl          = &self::$_pcntl_is_ttl;
			$ttl             = &self::$_pcntl_ttl;
			$is_limit_action = &self::$_pcntl_is_limit_action;
			$limit_action    = &self::$_pcntl_limit_action;
			$is_silent       = &self::$_pcntl_is_silent;
			// signal
			if( !$is_silent ) {
				echo 'Process signal SIGINT, SIGTERM' .PHP_EOL;
			}
			pcntl_signal( SIGINT,  __CLASS__ .'::_pcntl_signal' );
			pcntl_signal( SIGTERM, __CLASS__ .'::_pcntl_signal' );
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

	static function _pcntl_signal( $signo = null, $signinfo = null ) {
		// var
		$is_terminate_by_signal = &self::$_pcntl_is_terminate_by_signal;
		$is_silent              = &self::$_pcntl_is_silent;
		// signal
		$signo = (int)$signo;
		switch( $signo ) {
			case SIGINT:
			case SIGTERM:
				$is_terminate_by_signal = true;
				break;
			default:
				if( !$is_silent ) {
					echo 'Signal not handled: '. $signo .PHP_EOL;
				}
		}
	}

	static function _pcntl_is_silent( $options = null ) {
		return( self::$_pcntl_is_silent );
	}

	static function _pcntl_is_terminate_by_signal( $options = null ) {
		return( self::$_pcntl_is_terminate_by_signal );
	}

	static function _pcntl_is_terminate( $options = null ) {
		return( self::$_pcntl_is_terminate );
	}

	static function _pcntl_dispatch( $options = null ) {
		$result = false;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( is_console() ) {
			// var
			$ts                     = time();
			$is_ttl                 = &self::$_pcntl_is_ttl;
			$ttl                    = &self::$_pcntl_ttl;
			$is_limit_action        = &self::$_pcntl_is_limit_action;
			$limit_action           = &self::$_pcntl_limit_action;
			$ts_start               = &self::$_pcntl_ts_start;
			$count_action           = &self::$_pcntl_count_action;
			$is_exit                = &self::$_pcntl_is_exit;
			$is_terminate           = &self::$_pcntl_is_terminate;
			$is_terminate_by_signal = &self::$_pcntl_is_terminate_by_signal;
			$is_silent              = &self::$_pcntl_is_silent;
			$count_action++;
			// signal
			$__is_exit   = isset( $_is_exit   ) ? $_is_exit   : $is_exit;
			$__is_silent = isset( $_is_silent ) ? $_is_silent : $is_silent;
			if( ! @$__is_silent ) {
				echo PHP_EOL;
			}
			$message_head = 'Going to terminate';
			$message_type = [];
			// if( ! @$__is_silent ) {
				// echo 'Process action: #'. $count_action .PHP_EOL;
			// }
			// signal
			pcntl_signal_dispatch();
			if( $is_terminate_by_signal ) {
				$is_terminate = true;
				$message_type[] = 'signal';
			}
			// ttl
			if( $is_ttl ) {
				$_ttl = $ts - $ts_start;
				if( $_ttl >= $ttl ) {
					$is_terminate = true;
					$message_type[] = 'limit ttl';
				}
			}
			// action
			if( $is_limit_action && $count_action >= $limit_action ) {
				$is_terminate = true;
				$message_type[] = 'limit action';
			}
			// exit
			if( $is_terminate ) {
				$result = true;
				if( ! @$__is_silent ) {
					$message = $message_head;
					if( $message_type ) {
						$message .= ' by '. implode( ', ', $message_type );
					}
					if( $message ) {
						echo $message .PHP_EOL;
					}
				}
				if( @$__is_exit ) {
					exit( 0 );
				}
			}
		}
		return( $result );
	}

	static function _pcntl_log( $options = null, $type = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// message
		if( is_string( $options ) ) { $_message = $options; }
		if( ! @$_message ) { return; }
		// file type
		@$_type && $type = $_type;
		$type = in_array( $type, array( 'log', 'error' ) ) ? $type : 'log';
		// file
		if( $type == 'log' ) {
			$path = &self::$LOG0_PATH;
			$file = &self::$log0_file;
		} else {
			$path = &self::$LOG1_PATH;
			$file = &self::$log1_file;
		}
		if( ! $file ) { $file = fopen( $path, 'a' ); }
		// prepare: datetime, message
		list( $msec, $ts ) = explode( ' ', microtime() );
		$datetime = date( 'Y-m-d H-i-s', $ts );
		$message = sprintf( '[%s %3d] %s%s', $datetime, (int)( $msec*1000 ), $_message, PHP_EOL );
		fwrite( $file, $message );
	}

	static function _pcntl_dump( $message = null, $var = null ) {
		if( !@$message && !@$var ) { return( false ); }
		!@$message && $message = '';
		$log = $var ? ' - '. var_export( $var, true ) : '';
		self::_pcntl_log( $message . $log );
		return( true );
	}

	static function _pcntl_error( $message = null ) {
		if( !@$message ) { return( false ); }
		$title = 'Error: ';
		self::_pcntl_log( $title . $message . PHP_EOL, 'error' );
		return( true );
	}

	static function _pcntl_fatal( $message = null, $code = -1 ) {
		if( !@$message ) { return( false ); }
		$title = 'Fatal: ';
		self::_pcntl_log( $title . $message . PHP_EOL, 'error' );
		exit( $code );
		return( true );
	}

	// cmd test
	public function pcntl_test() {
		if( is_console() ) {
			// var
			$is_terminate = &self::$_pcntl_is_terminate;
			$is_silent    = &self::$_pcntl_is_silent;
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
				// if( self::_pcntl_dispatch() ) {
				$is_terminate = self::_pcntl_dispatch(); if( $is_terminate ) {
					// if( self::_is_terminate([ 'is_exit' => false, 'is_silent' => true ]) ) {
					echo 'break loop' .PHP_EOL;
					break;
				}
			}
			echo 'exit' .PHP_EOL;
		}
	}

}
