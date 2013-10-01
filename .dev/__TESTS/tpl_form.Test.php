<?php  

define("YF_PATH", dirname(dirname(dirname(__FILE__)))."/");
require YF_PATH."classes/yf_main.class.php";
new yf_main("user", 1, 0);

function _tpl($stpl_text = "", $replace = array(), $name = "") {
	return tpl()->parse_string($stpl_text, $replace, $name);
}
#function _form_row() { }
#	tpl_row($type = 'input', $replace = array(), $name, $desc = '', $extra = array()) {
#	=> '_class("form2")->tpl_row(\'$1\',$replace,\'$3\',\'$5\',\'$7\')',

class tpl_form_test extends PHPUnit_Framework_TestCase {
	public function test_10() {
		$html = _class('form2')->tpl_row('password');
		$this->assertEquals( $html, _tpl( '{form_row("password")}' ) );
		$this->assertEquals( $html, _tpl( '{form_row( "password" )}' ) );
		$this->assertEquals( $html, _tpl( '{form_row("password" )}' ) );
		$this->assertEquals( $html, _tpl( '{form_row( "password")}' ) );
		$this->assertEquals( $html, _tpl( '{form_row( " password")}' ) );
		$this->assertEquals( $html, _tpl( '{form_row( " password " )}' ) );
		$this->assertEquals( $html, _tpl( '{form_row(" password ")}' ) );
		$this->assertEquals( $html, _tpl( '{form_row(   "   password   "  )}' ) );
		$this->assertEquals( $html, _tpl( '{form_row(	" 	 password  	" 	)}' ) );
	}
	public function test_21() {
		$replace = array('password' => '123');
		$text = _class('form2')->tpl_row('password', $replace);
		$this->assertEquals( $text, _tpl( '{form_row("password")}', $replace ) );
	}
	public function test_22() {
		$replace = array('password' => '123');
		$text = _class('form2')->tpl_row('password', $replace, 'pswd');
		$this->assertEquals( $text, _tpl( '{form_row("password","pswd")}', $replace ) );
		$this->assertEquals( $text, _tpl( '{form_row( "password","pswd" )}', $replace ) );
		$this->assertEquals( $text, _tpl( '{form_row( "password" , "pswd" )}', $replace ) );
		$this->assertEquals( $text, _tpl( '{form_row(  "password"  ,  "pswd"  )}', $replace ) );
		$this->assertEquals( $text, _tpl( '{form_row("password", "pswd")}', $replace ) );
#		$this->assertEquals( $text, _tpl( '{form_row( " password " , " pswd " )}', $replace ) );
#		$this->assertEquals( $text, _tpl( '{form_row(" password "," pswd ")}', $replace ) );
	}
	public function test_23() {
		$replace = array('password' => '123');
		$text = _class('form2')->tpl_row('password', $replace, 'pswd', 'My password');
		$this->assertEquals( $text, _tpl( '{form_row("password","pswd","My password")}', $replace ) );
		$this->assertEquals( $text, _tpl( '{form_row("password", "pswd","My password")}', $replace ) );
		$this->assertEquals( $text, _tpl( '{form_row("password", "pswd", "My password")}', $replace ) );
		$this->assertEquals( $text, _tpl( '{form_row( "password", "pswd", "My password" )}', $replace ) );
		$this->assertEquals( $text, _tpl( '{form_row( "password" , "pswd" , "My password" )}', $replace ) );
		$this->assertEquals( $text, _tpl( '{form_row(  "password"  ,  "pswd"  ,  "My password"  )}', $replace ) );
#		$this->assertEquals( $text, _tpl( '{form_row( " password ", " pswd ", " My password " )}', $replace ) );
	}
	public function test_24() {
		$replace = array('name' => 'Hello world');
		$text = _class('form2')->tpl_row('text', $replace, 'name', 'My name');
		$this->assertEquals( $text, _tpl( '{form_row("text","name","My name")}', $replace ) );
		$this->assertEquals( $text, _tpl( '{form_row("text", "name", "My name")}', $replace ) );
		$this->assertEquals( $text, _tpl( '{form_row( "text", "name", "My name")}', $replace ) );
		$this->assertEquals( $text, _tpl( '{form_row( "text" , "name" , "My name" )}', $replace ) );
		$this->assertEquals( $text, _tpl( '{form_row(  "text"  ,  "name"  ,  "My name"  )}', $replace ) );
	}
}