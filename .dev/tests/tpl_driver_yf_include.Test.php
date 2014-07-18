<?php

require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_yf_include_test extends tpl_abstract {
	public function test_inherit_var() {
		$name = 'unittest/'.__CLASS__.'/'.__FUNCTION__;
		self::_tpl( 'Inherited var: {key1}', array(), $name.'_1' );
		$this->assertEquals('Inherited var: val1', self::_tpl( '{include("'.$name.'_1")}', array('key1' => 'val1') ));
	}
	public function test_inherit_var_inline() {
		$name = 'unittest/'.__CLASS__.'/'.__FUNCTION__;
		self::_tpl( 'Inherited var: {key1}, passed var: {var2}', array(), $name.'_3' );
		$this->assertEquals('Inherited var: val1, passed var: 42', self::_tpl( '{include("'.$name.'_3",var2=42)}', array('key1' => 'val1') ));
	}
	public function test_params_syntax() {
		$name = 'unittest/'.__CLASS__.'/'.__FUNCTION__;
		self::_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), $name.'_4' );
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include("'.$name.'_4",var1=v1;var2=v2;var3=v3;var4=v4)}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( "'.$name.'_4",var1=v1;var2=v2;var3=v3;var4=v4)}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( "'.$name.'_4" ,var1=v1;var2=v2;var3=v3;var4=v4 )}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( '.$name.'_4 , var1=v1;var2=v2;var3=v3;var4=v4 )}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( '.$name.'_4, var1=v1;var2=v2;var3=v3;var4=v4 )}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( "'.$name.'_4" , var1=v1 ;var2=v2 ;var3=v3 ;var4=v4 )}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( "'.$name.'_4" , var1=v1; var2=v2; var3=v3; var4=v4 )}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( "'.$name.'_4" , var1=v1 ; var2=v2 ; var3=v3 ; var4=v4 )}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( '.$name.'_4 , var1 = v1 ; var2 = v2 ; var3 = v3 ; var4 = v4 )}' ));
	}
	public function test_simple_syntax() {
		$name = 'unittest/'.__CLASS__.'/'.__FUNCTION__;
		self::_tpl( 'Hello from include', array(), $name.'_simple' );
		$this->assertEquals('Hello from include', self::_tpl( '{include( "'.$name.'_simple")}' ));
		$this->assertEquals('Hello from include', self::_tpl( '{include("'.$name.'_simple" )}' ));
		$this->assertEquals('Hello from include', self::_tpl( '{include( "'.$name.'_simple" )}' ));
		$this->assertEquals('Hello from include', self::_tpl( '{include( '.$name.'_simple)}' ));
		$this->assertEquals('Hello from include', self::_tpl( '{include('.$name.'_simple )}' ));
		$this->assertEquals('Hello from include', self::_tpl( '{include( '.$name.'_simple )}' ));

		$this->assertEquals('{include()}', self::_tpl( '{include()}' ));
	}
	public function test_include_and_catch() {
		$name = 'unittest/'.__CLASS__.'/'.__FUNCTION__;
		self::_tpl( '{cond1}|{cond2}', array(), $name.'_catch' );
		$this->assertEquals('__ok__|', self::_tpl( '{catch(cond1)}{if(k1 eq 5)}__ok__{/if}{/catch}{catch(cond2)}{if(k2 eq 4)}__ok2__{/if}{/catch}{include("'.$name.'_catch")}', array('k1' => '5') ));
		$this->assertEquals('__ok__|', self::_tpl( '{catch(cond1)}{if("k1" eq "5")}__ok__{/if}{/catch}{catch(cond2)}{if("k2" eq "4")}__ok2__{/if}{/catch}{cond1}|{cond2}', array('k1' => '5') ));
	}
	public function test_multi_include() {
		$name = 'unittest/'.__CLASS__.'/'.__FUNCTION__;
		self::_tpl( 'Hello1', array(), $name.'_1' );
		self::_tpl( 'Hello2', array(), $name.'_2' );
		self::_tpl( 'Hello3', array(), $name.'_3' );
		$this->assertEquals('Hello1 Hello1 Hello1', self::_tpl( '{include("'.$name.'_1")} {include("'.$name.'_1")} {include("'.$name.'_1")}' ));
		$this->assertEquals('Hello1 Hello2 Hello3', self::_tpl( '{include("'.$name.'_1")} {include("'.$name.'_2")} {include("'.$name.'_3")}' ));
		$this->assertEquals('Hello1 Hello2 Hello1 Hello2', self::_tpl( '{include("'.$name.'_1")} {include("'.$name.'_2")} {include("'.$name.'_1")} {include("'.$name.'_2")}' ));
	}
	public function test_include_if_exists() {
		$name = 'unittest/'.__CLASS__.'/'.__FUNCTION__;
		self::_tpl( 'Hello1', array(), $name.'_1' );
		$this->assertEquals('Hello1  ', self::_tpl( '{include_if_exists("'.$name.'_1")} {include_if_exists("'.$name.'_2")} {include_if_exists("'.$name.'_3")}' ));
	}
}