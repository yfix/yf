<?php

require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_yf_include_test extends tpl_abstract {
	public function test_inherit_var() {
		self::_tpl( 'Inherited var: {key1}', array(), 'unittest_include2' );
		$this->assertEquals('Inherited var: val1', self::_tpl( '{include("unittest_include2")}', array('key1' => 'val1') ));
	}
	public function test_inherit_var_inline() {
		self::_tpl( 'Inherited var: {key1}, passed var: {var2}', array(), 'unittest_include3' );
		$this->assertEquals('Inherited var: val1, passed var: 42', self::_tpl( '{include("unittest_include3",var2=42)}', array('key1' => 'val1') ));
	}
	public function test_params_syntax() {
		self::_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include("unittest_include4",var1=v1;var2=v2;var3=v3;var4=v4)}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( "unittest_include4",var1=v1;var2=v2;var3=v3;var4=v4)}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( "unittest_include4" ,var1=v1;var2=v2;var3=v3;var4=v4 )}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( unittest_include4 , var1=v1;var2=v2;var3=v3;var4=v4 )}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( unittest_include4, var1=v1;var2=v2;var3=v3;var4=v4 )}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( "unittest_include4" , var1=v1 ;var2=v2 ;var3=v3 ;var4=v4 )}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( "unittest_include4" , var1=v1; var2=v2; var3=v3; var4=v4 )}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( "unittest_include4" , var1=v1 ; var2=v2 ; var3=v3 ; var4=v4 )}' ));
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( unittest_include4 , var1 = v1 ; var2 = v2 ; var3 = v3 ; var4 = v4 )}' ));
	}
	public function test_simple_syntax() {
		self::_tpl( 'Hello from include', array(), 'unittest_include_simple' );
		$this->assertEquals('Hello from include', self::_tpl( '{include( "unittest_include_simple")}' ));
		$this->assertEquals('Hello from include', self::_tpl( '{include("unittest_include_simple" )}' ));
		$this->assertEquals('Hello from include', self::_tpl( '{include( "unittest_include_simple" )}' ));
		$this->assertEquals('Hello from include', self::_tpl( '{include( unittest_include_simple)}' ));
		$this->assertEquals('Hello from include', self::_tpl( '{include(unittest_include_simple )}' ));
		$this->assertEquals('Hello from include', self::_tpl( '{include( unittest_include_simple )}' ));
	}
	public function test_include_and_catch() {
		self::_tpl( '{cond1}|{cond2}', array(), 'unittest_include_catch' );
		$this->assertEquals('__ok__|', self::_tpl( '{catch(cond1)}{if(k1 eq 5)}__ok__{/if}{/catch}{catch(cond2)}{if(k2 eq 4)}__ok2__{/if}{/catch}{include("unittest_include_catch")}', array('k1' => '5') ));
		$this->assertEquals('__ok__|', self::_tpl( '{catch(cond1)}{if("k1" eq "5")}__ok__{/if}{/catch}{catch(cond2)}{if("k2" eq "4")}__ok2__{/if}{/catch}{cond1}|{cond2}', array('k1' => '5') ));
	}
	public function test_multi_include() {
		self::_tpl( 'Hello1', array(), 'unittest_include1' );
		self::_tpl( 'Hello2', array(), 'unittest_include2' );
		self::_tpl( 'Hello3', array(), 'unittest_include3' );
		$this->assertEquals('Hello1 Hello1 Hello1', self::_tpl( '{include("unittest_include1")} {include("unittest_include1")} {include("unittest_include1")}' ));
		$this->assertEquals('Hello1 Hello2 Hello3', self::_tpl( '{include("unittest_include1")} {include("unittest_include2")} {include("unittest_include3")}' ));
		$this->assertEquals('Hello1 Hello2 Hello1 Hello2', self::_tpl( '{include("unittest_include1")} {include("unittest_include2")} {include("unittest_include1")} {include("unittest_include2")}' ));
	}
}