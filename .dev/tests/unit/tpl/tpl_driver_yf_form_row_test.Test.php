<?php


require_once __DIR__ . '/tpl__setup.php';

class tpl_driver_yf_form_row_test extends tpl_abstract
{
    public static $_er = [];
    public static function setUpBeforeClass() : void
    {
        self::$_er = error_reporting();
        error_reporting(0);
    }
    public static function tearDownAfterClass() : void
    {
        error_reporting(self::$_er);
    }
    public function test_10()
    {
        $html = _class('form2')->tpl_row('password');
        $this->assertEquals($html, self::_tpl('{form_row("password")}'));
        $this->assertEquals($html, self::_tpl('{form_row( "password" )}'));
        $this->assertEquals($html, self::_tpl('{form_row("password" )}'));
        $this->assertEquals($html, self::_tpl('{form_row( "password")}'));
        $this->assertEquals($html, self::_tpl('{form_row( " password")}'));
        $this->assertEquals($html, self::_tpl('{form_row( " password " )}'));
        $this->assertEquals($html, self::_tpl('{form_row(" password ")}'));
        $this->assertEquals($html, self::_tpl('{form_row(   "   password   "  )}'));
        $this->assertEquals($html, self::_tpl('{form_row(	" 	 password  	" 	)}'));
        $this->assertEquals($html, self::_tpl('{form_row("password","")}'));
        $this->assertEquals($html, self::_tpl('{form_row("password","","")}'));
        $this->assertEquals($html, self::_tpl('{form_row("password","","","")}'));
        $this->assertEquals($html, self::_tpl('{form_row("password", "", "", "")}'));
    }
    public function test_11()
    {
        $html = _class('form2')->tpl_row('password');
        $this->assertEquals($html, self::_tpl('{form_row(password)}'));
        $this->assertEquals($html, self::_tpl('{form_row( password )}'));
        $this->assertEquals($html, self::_tpl('{form_row(password )}'));
        $this->assertEquals($html, self::_tpl('{form_row( password)}'));
        $this->assertEquals($html, self::_tpl('{form_row(  password)}'));
        $this->assertEquals($html, self::_tpl('{form_row(  password  )}'));
        $this->assertEquals($html, self::_tpl('{form_row( password )}'));
        $this->assertEquals($html, self::_tpl('{form_row(      password     )}'));
        $this->assertEquals($html, self::_tpl('{form_row(	 	 password  	 	)}'));
        $this->assertEquals($html, self::_tpl('{form_row(password,"")}'));
        $this->assertEquals($html, self::_tpl('{form_row(password,"","")}'));
        $this->assertEquals($html, self::_tpl('{form_row(password,"","","")}'));
        $this->assertEquals($html, self::_tpl('{form_row(password, "", "", "")}'));
    }
    public function test_21()
    {
        $replace = ['password' => '123'];
        $text = _class('form2')->tpl_row('password', $replace);
        $this->assertEquals($text, self::_tpl('{form_row("password")}', $replace));
    }
    public function test_22()
    {
        $replace = ['password' => '123'];
        $text = _class('form2')->tpl_row('password', $replace, 'pswd');
        $this->assertEquals($text, self::_tpl('{form_row("password","pswd")}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row( "password","pswd" )}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row( "password" , "pswd" )}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row(  "password"  ,  "pswd"  )}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row("password", "pswd")}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row( " password " , " pswd " )}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row(" password "," pswd ")}', $replace));
    }
    public function test_23()
    {
        $replace = ['password' => '123'];
        $text = _class('form2')->tpl_row('password', $replace, 'pswd', 'My password');
        $this->assertEquals($text, self::_tpl('{form_row("password","pswd","My password")}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row("password", "pswd","My password")}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row("password", "pswd", "My password")}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row( "password", "pswd", "My password" )}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row( "password" , "pswd" , "My password" )}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row(  "password"  ,  "pswd"  ,  "My password"  )}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row( " password ", " pswd ", " My password " )}', $replace));
    }
    public function test_24()
    {
        $replace = ['name' => 'Hello world'];
        $text = _class('form2')->tpl_row('text', $replace, 'name', 'My name');
        $this->assertEquals($text, self::_tpl('{form_row("text","name","My name")}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row("text", "name", "My name")}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row( "text", "name", "My name")}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row( "text" , "name" , "My name" )}', $replace));
        $this->assertEquals($text, self::_tpl('{form_row(  "text"  ,  "name"  ,  "My name"  )}', $replace));
    }

    public function test_30()
    {
        $replace = ['name' => 'Hello world', 't_password' => 'My password'];
        $text = _class('form2')->tpl_row('text', $replace, 'name', '%t_password');
        $this->assertEquals($text, self::_tpl('{catch("t_password")}My password{/catch}{form_row("text","name","%t_password")}', $replace));
    }

    public function test_31()
    {
        $replace = ['name' => 'Hello world', 't_password' => 'Пароль'];
        $text = _class('form2')->tpl_row('text', $replace, 'name', '%t_password');
        $this->assertEquals($text, self::_tpl('{catch("t_password")}Пароль{/catch}{form_row("text","name","%t_password")}', $replace));
    }

    public function test_40()
    {
        $replace = ['name' => 'Hello world'];
        $text = _class('form2')->tpl_row('text', $replace, 'name', 'Пароль');
        $this->assertEquals($text, self::_tpl('{form_row("text","name","Пароль")}', $replace));
    }
}
