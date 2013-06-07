<?php

function globals_var( $name, $value = NULL ) {
	if( !isset( $name  ) ) { return( NULL ); }
	if( !isset( $value ) ) { return( $GLOBALS[ $name ] ); }
	$GLOBALS[ $name ] = $value;
}

class Config {
	private $_var;
	protected static $__instance;

	function __construct( $var = NULL ) {
		isset( $var )
			and $this->_var = $var
			 or $this->_var = array();
	}
	public function Instance() {
		if( !isset( self::$__instance ) ) {
			$class = __CLASS__;
			self::$__instance = new $class;
		}
		return( self::$__instance );
	}
	public function variable( $name, $value = NULL ) {
		if( !isset( $name  ) ) { return( NULL ); }
		if( !isset( $value ) ) { return( $this->_var[ $name ] ); }
		$this->_var[ $name ] = $value;
	}
}

Config::Instance();
function wrap_class_var( $name, $value = NULL ) {
	return( Config::Instance()->variable( $name, $value ) );
}

$GLOBALS[ 'var' ] = array(
	false => false,
	true  => true,
	1	 => 1,
	'name_string' => 'string_value',
	'name_array'  => array(
		1			  => 1,
		'name_string1' => 'string_value1',
		'name_string2' => 'string_value2',
		'name_string3' => 'string_value3',
	),
);

$total = 1e5;
printf( "Total iterations: %g\n\n", $total );

class test_globals_vs_class extends PHPUnit_Framework_TestCase {

	private function _globals_write() {
		// GLOBALS write
		$msg = 'GLOBALS: write'; $start_ts = microtime( true );
		for( $i = 0; $i < $GLOBALS[ 'total' ]; $i++ ) {
			globals_var( false,	false	 );
			globals_var( true,	 true	  );
			globals_var( 111,	  111	   );
			globals_var( 'string', 'string'  );
			globals_var( 'array',  $GLOBALS[ 'var' ] );
		}
		$finish_ts = microtime( true ); $inteval_ts = $finish_ts - $start_ts; printf( "%s ( %.3f msec )\n", $msg, $inteval_ts );
	}

	private function _globals_write_local() {
		$array_var = $GLOBALS[ 'var' ];
		// GLOBALS write
		$msg = 'GLOBALS: write'; $start_ts = microtime( true );
		for( $i = 0; $i < $GLOBALS[ 'total' ]; $i++ ) {
			globals_var( false,	false	 );
			globals_var( true,	 true	  );
			globals_var( 111,	  111	   );
			globals_var( 'string', 'string'  );
			globals_var( 'array',  $array_var );
		}
		$finish_ts = microtime( true ); $inteval_ts = $finish_ts - $start_ts; printf( "%s ( %.3f msec ) local \n", $msg, $inteval_ts );
	}

	private function _globals_write_stack( $array_var ) {
		// GLOBALS write
		$msg = 'GLOBALS: write'; $start_ts = microtime( true );
		for( $i = 0; $i < $GLOBALS[ 'total' ]; $i++ ) {
			globals_var( false,	false	 );
			globals_var( true,	 true	  );
			globals_var( 111,	  111	   );
			globals_var( 'string', 'string'  );
			globals_var( 'array',  $array_var );
		}
		$finish_ts = microtime( true ); $inteval_ts = $finish_ts - $start_ts; printf( "%s ( %.3f msec ) stack\n", $msg, $inteval_ts );
	}

	private function _globals_read() {
		globals_var( 'array',  $GLOBALS[ 'var' ] );
		// GLOBALS read
		$msg = 'GLOBALS:  read'; $start_ts = microtime( true );
		for( $i = 0; $i < $GLOBALS[ 'total' ]; $i++ ) {
			$a = globals_var( 'array' );
		}
		$finish_ts = microtime( true ); $inteval_ts = $finish_ts - $start_ts; printf( "%s ( %.3f msec )\n", $msg, $inteval_ts );
	}

// ---------------------------------

	private function _class_write() {
		// Class write
		$config = Config::Instance();
		$msg = 'CLASS  : write'; $start_ts = microtime( true );
		for( $i = 0; $i < $GLOBALS[ 'total' ]; $i++ ) {
			$config->variable( false,	false	 );
			$config->variable( true,	 true	  );
			$config->variable( 111,	  111	   );
			$config->variable( 'string', 'string'  );
			$config->variable( 'array',  $GLOBALS[ 'var' ] );
		}
		$finish_ts = microtime( true ); $inteval_ts = $finish_ts - $start_ts; printf( "%s ( %.3f msec )\n", $msg, $inteval_ts );
	}

	private function _class_write_local() {
		$array_var = $GLOBALS[ 'var' ];
		// Class write
		$config = Config::Instance();
		$msg = 'CLASS  : write'; $start_ts = microtime( true );
		for( $i = 0; $i < $GLOBALS[ 'total' ]; $i++ ) {
			$config->variable( false,	false	 );
			$config->variable( true,	 true	  );
			$config->variable( 111,	  111	   );
			$config->variable( 'string', 'string'  );
			$config->variable( 'array',  $array_var );
		}
		$finish_ts = microtime( true ); $inteval_ts = $finish_ts - $start_ts; printf( "%s ( %.3f msec ) local\n", $msg, $inteval_ts );
	}

	private function _class_write_stack( $array_var ) {
		// Class write
		$config = Config::Instance();
		$msg = 'CLASS  : write'; $start_ts = microtime( true );
		for( $i = 0; $i < $GLOBALS[ 'total' ]; $i++ ) {
			$config->variable( false,	false	 );
			$config->variable( true,	 true	  );
			$config->variable( 111,	  111	   );
			$config->variable( 'string', 'string'  );
			$config->variable( 'array',  $array_var );
		}
		$finish_ts = microtime( true ); $inteval_ts = $finish_ts - $start_ts; printf( "%s ( %.3f msec ) stack\n", $msg, $inteval_ts );
	}

	private function _class_read() {
		// Class write
		$config = Config::Instance();
		$msg = 'CLASS  :  read'; $start_ts = microtime( true );
		for( $i = 0; $i < $GLOBALS[ 'total' ]; $i++ ) {
			$a = $config->variable( 'array' );
		}
		$finish_ts = microtime( true ); $inteval_ts = $finish_ts - $start_ts; printf( "%s ( %.3f msec )\n", $msg, $inteval_ts );
	}

	private function _class_wrap_write() {
		// Class write
		$msg = 'CLASS  : write'; $start_ts = microtime( true );
		for( $i = 0; $i < $GLOBALS[ 'total' ]; $i++ ) {
			wrap_class_var( false,	false	 );
			wrap_class_var( true,	 true	  );
			wrap_class_var( 111,	  111	   );
			wrap_class_var( 'string', 'string'  );
			wrap_class_var( 'array',  $GLOBALS[ 'var' ] );
		}
		$finish_ts = microtime( true ); $inteval_ts = $finish_ts - $start_ts; printf( "%s ( %.3f msec ) wrap\n", $msg, $inteval_ts );
	}

	private function _class_wrap_read() {
		// Class write
		$msg = 'CLASS  :  read'; $start_ts = microtime( true );
		for( $i = 0; $i < $GLOBALS[ 'total' ]; $i++ ) {
			$a = wrap_class_var( 'array' );
		}
		$finish_ts = microtime( true ); $inteval_ts = $finish_ts - $start_ts; printf( "%s ( %.3f msec ) wrap\n", $msg, $inteval_ts );
	}

////////////////////////////////////////////////////////////////////////////////

	public function test_globals_write() {
		$this->_globals_write();
		$this->assertEquals( globals_var( 'array' ), $GLOBALS[ 'var' ] );
	}

	public function test_globals_write_stack() {
		$this->_globals_write_stack( $GLOBALS[ 'var' ] );
		$this->assertEquals( globals_var( 'array' ), $GLOBALS[ 'var' ] );
	}

	public function test_globals_write_local() {
		$this->_globals_write_local();
		$this->assertEquals( globals_var( 'array' ), $GLOBALS[ 'var' ] );
	}

	public function test_globals_read() {
		$this->_globals_read();
		$this->assertEquals( globals_var( 'array' ), $GLOBALS[ 'var' ] );
		echo "\n";
	}

// ---------------------------------

	public function test_class_write() {
		$config = Config::Instance();
		$this->_class_write();
		$this->assertEquals( $config->variable( 'array' ), $GLOBALS[ 'var' ] );
	}

	public function test_class_write_stack() {
		$config = Config::Instance();
		$this->_class_write_stack( $GLOBALS[ 'var' ] );
		$this->assertEquals( $config->variable( 'array' ), $GLOBALS[ 'var' ] );
	}

	public function test_class_write_local() {
		$config = Config::Instance();
		$this->_class_write_local();
		$this->assertEquals( $config->variable( 'array' ), $GLOBALS[ 'var' ] );
	}

	public function test_class_wrap_write() {
		$config = Config::Instance();
		$this->_class_wrap_write();
		$this->assertEquals( $config->variable( 'array' ), $GLOBALS[ 'var' ] );
	}

	public function test_class_read() {
		$config = Config::Instance();
		$this->_class_read();
		$this->assertEquals( $config->variable( 'array' ), $GLOBALS[ 'var' ] );
	}

	public function test_class_wrap_read() {
		$config = Config::Instance();
		$this->_class_wrap_read();
		$this->assertEquals( $config->variable( 'array' ), $GLOBALS[ 'var' ] );
	}

}
