<?php  

define("YF_PATH", dirname(dirname(dirname(__FILE__)))."/");
require YF_PATH."classes/yf_main.class.php";
new yf_main("user", 1, 0);

function _tpl($stpl_text = "", $replace = array(), $name = "") {
	return tpl()->parse_string($stpl_text, $replace, $name);
}

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
		$this->assertEquals( $html, _tpl( '{form_row("password","")}' ) );
		$this->assertEquals( $html, _tpl( '{form_row("password","","")}' ) );
		$this->assertEquals( $html, _tpl( '{form_row("password","","","")}' ) );
		$this->assertEquals( $html, _tpl( '{form_row("password", "", "", "")}' ) );
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
		$this->assertEquals( $text, _tpl( '{form_row( " password " , " pswd " )}', $replace ) );
		$this->assertEquals( $text, _tpl( '{form_row(" password "," pswd ")}', $replace ) );
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
		$this->assertEquals( $text, _tpl( '{form_row( " password ", " pswd ", " My password " )}', $replace ) );
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

	public function test_30() {
		$replace = array('name' => 'Hello world', 't_password' => 'My password');
		$text = _class('form2')->tpl_row('text', $replace, 'name', '%t_password');
		$this->assertEquals( $text, _tpl( '{catch("t_password")}My password{/catch}{form_row("text","name","%t_password")}', $replace ) );
	}

	public function test_31() {
		$replace = array('name' => 'Hello world', 't_password' => 'Пароль');
		$text = _class('form2')->tpl_row('text', $replace, 'name', '%t_password');
		$this->assertEquals( $text, _tpl( '{catch("t_password")}Пароль{/catch}{form_row("text","name","%t_password")}', $replace ) );
	}

	public function test_40() {
		$replace = array('name' => 'Hello world');
		$text = _class('form2')->tpl_row('text', $replace, 'name', 'Пароль');
		$this->assertEquals( $text, _tpl( '{form_row("text","name","Пароль")}', $replace ) );
	}
}