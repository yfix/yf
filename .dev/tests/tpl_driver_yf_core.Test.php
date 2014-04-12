<?php

require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_yf_core_test extends tpl_abstract {
	public function test_const() {
		$this->assertEquals(YF_PATH, self::_tpl( '{const("YF_PATH")}' ));
		$this->assertEquals(YF_PATH, self::_tpl( '{const(\'YF_PATH\')}' ));
		$this->assertEquals(YF_PATH, self::_tpl( '{const(YF_PATH)}' ));
		$this->assertEquals(YF_PATH, self::_tpl( '{const( YF_PATH)}' ));
		$this->assertEquals(YF_PATH, self::_tpl( '{const( YF_PATH )}' ));
		$this->assertEquals(YF_PATH, self::_tpl( '{const( YF_PATH )}' ));
		$this->assertEquals(YF_PATH, self::_tpl( '{const(      YF_PATH         )}' ));
		$this->assertEquals('{const(WRONG-CONST)}', self::_tpl( '{const(WRONG-CONST)}' ));
		$this->assertEquals('{const( WRONG-CONST)}', self::_tpl( '{const( WRONG-CONST)}' ));
		$this->assertEquals('{const( WRONG-CONST )}', self::_tpl( '{const( WRONG-CONST )}' ));
		$this->assertEquals('{const()}', self::_tpl( '{const()}' ));
		$this->assertEquals('{const( )}', self::_tpl( '{const( )}' ));
	}
	public function test_eval() {
		$this->assertEquals(substr(YF_PATH, 0, 8), self::_tpl( '{eval_code(substr(YF_PATH, 0, 8))}' ));
		$this->assertEquals(substr(YF_PATH, 0, 8), self::_tpl( '{eval_code( substr(YF_PATH, 0, 8))}' ));
		$this->assertEquals(substr(YF_PATH, 0, 8), self::_tpl( '{eval_code(substr(YF_PATH, 0, 8) )}' ));
		$this->assertEquals(substr(YF_PATH, 0, 8), self::_tpl( '{eval_code( substr(YF_PATH, 0, 8) )}' ));
	}
	public function test_replace() {
		$this->assertEquals('val1', self::_tpl( '{replace1}', array('replace1' => 'val1') ));
		$this->assertEquals('val1', self::_tpl( '{replace-1}', array('replace-1' => 'val1') ));
		$this->assertEquals('{ replace-1}', self::_tpl( '{ replace-1}', array('replace-1' => 'val1') ));
		$this->assertEquals('{replace-1 }', self::_tpl( '{replace-1 }', array('replace-1' => 'val1') ));
		$this->assertEquals('{ replace-1 }', self::_tpl( '{ replace-1 }', array('replace-1' => 'val1') ));
		$this->assertEquals('<a href="http://google.com/">Google</a>', self::_tpl( '<a href="{url1}">Google</a>', array('url1' => 'http://google.com/') ));
		$this->assertEquals('http://google.com/http://google.com/http://google.com/', self::_tpl( '{url1}{url1}{url1}', array('url1' => 'http://google.com/') ));
		$this->assertEquals('<a href="http://yahoo.com/">Google</a>', self::_tpl( '{catch("url1")}http://yahoo.com/{/catch}<a href="{url1}">Google</a>', array('url1' => 'http://google.com/') ));
		$this->assertEquals('<a href="http://google.com/">Google</a>', self::_tpl( '{catch("url1")}http://yahoo.com/{/catch}{catch("url1")}http://google.com/{/catch}<a href="{url1}">Google</a>', array('url1' => 'http://google.com/') ));
		$this->assertEquals('<script>function myjs(){ var i = 0 }<script>', self::_tpl( '<script>function myjs(){ {js-var} }<script>', array('js-var' => 'var i = 0') ));
		$this->assertEquals('<script>function myjs(){ var i = 0; if(var > 0) alert("foreach"); }<script>', self::_tpl( '<script>function myjs(){ {js-var}; if(var > 0) alert("foreach"); }<script>', array('js-var' => 'var i = 0') ));
		$this->assertEquals('<script>function myjs(){ var i = 0; { js-var}; }<script>', self::_tpl( '<script>function myjs(){ {js-var}; { js-var}; }<script>', array('js-var' => 'var i = 0') ));
		$this->assertEquals('myjs(){ var i = 0; { js-var}; }', self::_tpl( 'myjs(){ {js-var}; { js-var}; }', array('js-var' => 'var i = 0') ));
		$this->assertEquals('myjs(){ var i = 0; {js-var }; }', self::_tpl( 'myjs(){ {js-var}; {js-var }; }', array('js-var' => 'var i = 0') ));
		$this->assertEquals('myjs(){ var i = 0; { js-var }; }', self::_tpl( 'myjs(){ {js-var}; { js-var }; }', array('js-var' => 'var i = 0') ));
	}
	public function test_replace_subarray() {
		$this->assertEquals('{get.test}', self::_tpl( '{get.test}' ));
		$this->assertEquals('{ get.test }', self::_tpl( '{ get.test }' ));
		$this->assertEquals('val1,val2,val3', self::_tpl( '{sub.key1},{sub.key2},{sub.key3}', array('sub' => array('key1' => 'val1', 'key2' => 'val2', 'key3' => 'val3')) ));
	}
	public function test_execute() {
		$this->assertEquals('true', self::_tpl( '{execute(test,true_for_unittest)}' ));
		$this->assertEquals('{ execute(test,true_for_unittest)}', self::_tpl( '{ execute(test,true_for_unittest)}' ));
		$this->assertEquals('{execute(test,true_for_unittest) }', self::_tpl( '{execute(test,true_for_unittest) }' ));
		$this->assertEquals('{ execute(test,true_for_unittest) }', self::_tpl( '{ execute(test,true_for_unittest) }' ));
		$this->assertEquals('true', self::_tpl( '{execute( test,true_for_unittest)}' ));
		$this->assertEquals('true', self::_tpl( '{execute(test,true_for_unittest )}' ));
		$this->assertEquals('true', self::_tpl( '{execute( test,true_for_unittest )}' ));
		$this->assertEquals('true', self::_tpl( '{execute(test ,true_for_unittest)}' ));
		$this->assertEquals('true', self::_tpl( '{execute(test, true_for_unittest)}' ));
		$this->assertEquals('true', self::_tpl( '{execute(test , true_for_unittest)}' ));
		$this->assertEquals('true', self::_tpl( '{execute( test , true_for_unittest )}' ));
		$this->assertEquals('true', self::_tpl( '{execute(test;true_for_unittest)}' ));
		$this->assertEquals('true', self::_tpl( '{execute( test;true_for_unittest )}' ));
		$this->assertEquals('true', self::_tpl( '{execute( test ; true_for_unittest )}' ));
		$this->assertEquals('true', self::_tpl( '{execute(test;true_for_unittest;param1=val1)}' ));
		$this->assertEquals('true', self::_tpl( '{execute(test,true_for_unittest,param1=val1)}' ));
		$this->assertEquals('true', self::_tpl( '{execute( test , true_for_unittest , param1=val1 )}' ));
		$this->assertEquals('true', self::_tpl( '{execute(test,true_for_unittest,param1=val1;param2=val2)}' ));
		$this->assertEquals('true', self::_tpl( '{execute(test,true_for_unittest,param1=val1;param2=val2;param3=val3)}' ));
		$this->assertEquals('true', self::_tpl( '{execute( test , true_for_unittest , param1=val1 ; param2=val2 )}' ));
		$this->assertNotEquals('tru', self::_tpl( '{execute(test,true_for_unittest)}' ));
	}
	public function test_catch() {
		$this->assertEquals('  __true__', self::_tpl( '{catch( mytest1 )}{execute(test,true_for_unittest)}{/catch}  __{mytest1}__' ));
		$this->assertEquals('  __true__', self::_tpl( '{catch(mytest1)}{execute(test,true_for_unittest)}{/catch}  __{mytest1}__' ));
		$this->assertEquals('  __true__', self::_tpl( '{catch("mytest1")}{execute(test,true_for_unittest)}{/catch}  __{mytest1}__' ));
		$this->assertEquals('22true33', self::_tpl( '{catch("mytest1")}22{execute(test,true_for_unittest)}33{/catch}{mytest1}' ));
		$this->assertEquals('22true33', self::_tpl( '{catch( "mytest1" )}22{execute(test,true_for_unittest)}33{/catch}{mytest1}' ));
		$this->assertEquals('22true33', self::_tpl( '{catch( mytest1 )}22{execute(test,true_for_unittest)}33{/catch}{mytest1}' ));
	}
	public function test_catch_complex() {
		$tpl_str = '
			{catch(min_ext)}{if(debug_mode eq 0)}.min{/if}{/catch}
			{if(css_framework eq "bs2" or css_framework eq "")}
				<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap{min_ext}.js"></script>
			{else}
				<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/3.1.0/js/bootstrap{min_ext}.js"></script>
			{/if}
		';
		$this->assertEquals('<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.js"></script>'
			, trim(self::_tpl($tpl_str, array('css_framework' => 'bs2', 'debug_mode' => 1))) );
		$this->assertEquals('<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/3.1.0/js/bootstrap.js"></script>'
			, trim(self::_tpl($tpl_str, array('css_framework' => 'bs3', 'debug_mode' => 1))) );
		$this->assertEquals('<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>'
			, trim(self::_tpl($tpl_str, array('css_framework' => 'bs2', 'debug_mode' => 0))) );
		$this->assertEquals('<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/3.1.0/js/bootstrap.min.js"></script>'
			, trim(self::_tpl($tpl_str, array('css_framework' => 'bs3', 'debug_mode' => 0))) );
	}
	public function test_cleanup() {
		$this->assertEquals('<script>function myjs(){ var i = 0 }<script>', self::_tpl( '{cleanup()}<script>function myjs(){ {js-var} }<script>{/cleanup}', array('js-var' => 'var i = 0') ));
	}
	public function test_comment() {
		$this->assertEquals('', self::_tpl( '{{--STPL COMMENT--}}' ));
		$this->assertEquals('<!---->', self::_tpl( '<!--{{--STPL COMMENT--}}-->' ));
		$this->assertEquals('TEXT', self::_tpl( '{{--<!----}}TEXT{{---->--}}' ));
	}
	public function test_if() {
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" eq "val1")}GOOD{/if}', array('key1' => 'val1') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" ne "")}GOOD{/if}', array('key1' => 'val1') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" gt "1")}GOOD{/if}', array('key1' => '2') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" lt "2")}GOOD{/if}', array('key1' => '1') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" ge "2")}GOOD{/if}', array('key1' => '2') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" ge "2")}GOOD{/if}', array('key1' => '3') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" le "1")}GOOD{/if}', array('key1' => '1') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" le "1")}GOOD{/if}', array('key1' => '0') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" ne "")}GOOD{else}BAD{/if}', array('key1' => 'not empty') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" eq "")}BAD{else}GOOD{/if}', array('key1' => 'not empty') ));
		$this->assertEquals(' GOOD ', self::_tpl( '{if("key1" eq "")} GOOD {else} BAD {/if}', array('key1' => '') ));
	}
	public function test_if_several() {
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" ne "" and "key2" ne "")}GOOD{else}BAD{/if}', array('key1' => '1', 'key2' => '2') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" ne "" and "key2" ne "")}BAD{else}GOOD{/if}', array('key1' => '', 'key2' => '2') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" ne "" and "key2" ne "")}BAD{else}GOOD{/if}', array('key1' => '', 'key2' => '') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" eq "" and "key2" eq "")}GOOD{else}BAD{/if}', array('key1' => '', 'key2' => '') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" eq "")}{if("key2" eq "")}GOOD{/if}{/if}', array('key1' => '', 'key2' => '') ));
		$this->assertEquals('', self::_tpl( '{if("key1" eq "")}{if("key2" eq "")}GOOD{/if}{/if}', array('key1' => '1', 'key2' => '2') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" eq "1")}{if("key2" eq "2")}{if("key3" eq "3")}GOOD{/if}{/if}{/if}', array('key1' => '1', 'key2' => '2', 'key3' => '3') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" eq "1" and "key2" eq "2" and "key3" eq "3")}GOOD{/if}', array('key1' => '1', 'key2' => '2', 'key3' => '3') ));
		$this->assertEquals('GOOD', self::_tpl( '{if("key1" eq "1" and "key2" eq "2" and "key3" eq "3" and "key4" eq "4" and "key5" eq "5")}GOOD{/if}', array('key1' => '1', 'key2' => '2', 'key3' => '3', 'key4' => '4', 'key5' => '5') ));
	}
	public function test_foreach() {
		$this->assertEquals('1111111111', self::_tpl( '{foreach(10)}1{/foreach}' ));
		$this->assertEquals(' 1  2  3  4 ', self::_tpl( '{foreach("testarray")} {_val} {/foreach}', array('testarray' => array(1,2,3,4)) ));
		$this->assertEquals(' 0  1  2  3 ', self::_tpl( '{foreach("testarray")} {_key} {/foreach}', array('testarray' => array(1,2,3,4)) ));
		$this->assertEquals(' 4  4  4  4 ', self::_tpl( '{foreach("testarray")} {_total} {/foreach}', array('testarray' => array(1,2,3,4)) ));
		$this->assertEquals(' 1  2  3 ', self::_tpl( '{foreach("testarray")} {_num} {/foreach}', array('testarray' => array(5,6,7)) ));
		$this->assertEquals(' 42  0  0 ', self::_tpl( '{foreach("testarray")} {if("_first" eq "1")}42{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
		$this->assertEquals(' 0  0  42 ', self::_tpl( '{foreach("testarray")} {if("_last" eq "1")}42{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
		$this->assertEquals(' 42  0  42 ', self::_tpl( '{foreach("testarray")} {if("_even" eq "1")}42{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
		$this->assertEquals(' 0  42  0 ', self::_tpl( '{foreach("testarray")} {if("_odd" eq "1")}42{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
		$this->assertEquals(' 0  42  0 ', self::_tpl( '{foreach("testarray")} {if("_key" eq "1")}42{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
		$this->assertEquals(' 42  0  0 ', self::_tpl( '{foreach("testarray")} {if("_val" eq "5")}42{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
		$this->assertEquals(' 0  0  7  0  0 ', self::_tpl( '{foreach("testarray")} {if("_num" eq "3")}{_val}{else}0{/if} {/foreach}', array('testarray' => array(5,6,7,8,9)) ));
		$this->assertEquals(' 1  1  1 ', self::_tpl( '{foreach("testarray")} {if("_total" eq "3")}1{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
		$this->assertEquals(' 1  1  1 ', self::_tpl( '{foreach( "testarray" )} {if("_total" eq "3")}1{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
		$this->assertEquals(' 1  1  1 ', self::_tpl( '{foreach(\'testarray\')} {if("_total" eq "3")}1{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
		$this->assertEquals(' 1  1  1 ', self::_tpl( '{foreach( \'testarray\' )} {if("_total" eq "3")}1{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
		$this->assertEquals(' 1  1  1 ', self::_tpl( '{foreach(testarray)} {if("_total" eq "3")}1{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
		$this->assertEquals(' 1  1  1 ', self::_tpl( '{foreach( testarray )} {if("_total" eq "3")}1{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
		$this->assertEquals(' name1:21  name2:22  name3:23 ', self::_tpl( '{foreach("testarray")} {#.name}:{#.age} {/foreach}', array(
			'testarray' => array(
				5 => array('name' => 'name1', 'age' => 21),
				6 => array('name' => 'name2', 'age' => 22),
				7 => array('name' => 'name3', 'age' => 23),
			),
		) ));
	}
	public function test_if_sub() {
		$this->assertEquals('GOOD', self::_tpl( '{if("sub.key1" eq "val1")}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1')) ));
		$this->assertEquals('GOOD', self::_tpl( '{if("sub.key1" eq "val1" and "sub.key2" ne "gggg")}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1', 'key2' => 'val2')) ));
		$this->assertEquals('GOOD', self::_tpl( '{if( "sub.key1" eq "val1" )}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1')) ));
		$this->assertEquals('GOOD', self::_tpl( '{if(sub.key1 eq val1)}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1')) ));
		$this->assertEquals('GOOD', self::_tpl( '{if( sub.key1 eq val1 )}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1')) ));
	}
	public function test_if_const_and_sub() {
		define('MY_TEST_CONST_1', '42');
		$this->assertEquals('GOOD', self::_tpl( '{if("sub.key1" eq "val1" and "const.MY_TEST_CONST_1" eq "42")}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1')) ));
		$this->assertEquals('GOOD', self::_tpl( '{if("sub.key1" eq "val1" and "const.MY_NOT_EXISTING_CONST" eq "1")}BAD{else}GOOD{/if}', array('sub' => array('key1' => 'val1')) ));
	}
	public function test_if_string() {
		$this->assertEquals('GOOD', self::_tpl( '{if("%string" eq "string")}GOOD{else}BAD{/if}' ));
	}
	public function test_if_const() {
		define('MY_TEST_CONST_1', '42');
		$this->assertEquals('GOOD', self::_tpl( '{if("const.MY_TEST_CONST_1" ne "")}GOOD{else}BAD{/if}' ));
		$this->assertEquals('GOOD', self::_tpl( '{if("const.MY_NOT_EXISTING_CONST" eq "")}GOOD{else}BAD{/if}' ));
		$this->assertEquals('GOOD', self::_tpl( '{if(const.MY_NOT_EXISTING_CONST eq "")}GOOD{else}BAD{/if}' ));
		$this->assertEquals('GOOD', self::_tpl( '{if("const.MY_NOT_EXISTING_CONST" ne "")}BAD{else}GOOD{/if}' ));
		$this->assertEquals('GOOD', self::_tpl( '{if(const.MY_NOT_EXISTING_CONST ne "")}BAD{else}GOOD{/if}' ));
		$this->assertEquals('GOOD', self::_tpl( '{if( const.MY_NOT_EXISTING_CONST ne "" )}BAD{else}GOOD{/if}' ));
	}
	public function test_module_conf() {
		module_conf('main', 'unit_var1', 'unit_val');
		$this->assertEquals('unit_val', self::_tpl( '{module_conf("main","unit_var1")}' ));
		$this->assertEquals('unit_val', self::_tpl( '{module_conf("main", "unit_var1")}' ));
		$this->assertEquals('unit_val', self::_tpl( '{module_conf( "main", "unit_var1" )}' ));
		$this->assertEquals('unit_val', self::_tpl( '{module_conf("main",unit_var1)}' ));
		$this->assertEquals('unit_val', self::_tpl( '{module_conf( "main", unit_var1)}' ));
		$this->assertEquals('unit_val', self::_tpl( '{module_conf(main,"unit_var1")}' ));
		$this->assertEquals('unit_val', self::_tpl( '{module_conf( main, unit_var1 )}' ));
		$this->assertEquals('unit_val', self::_tpl( '{module_conf(main,unit_var1)}' ));
	}
	public function test_if_module_conf() {
		module_conf('main', 'unit_var2', '5');
		$this->assertEquals(' ok ', self::_tpl( '{if("module_conf.main.unit_var2" eq "5")} ok {/if}' ));
		$this->assertEquals(' ok ', self::_tpl( '{if( "module_conf.main.unit_var2" eq "5" )} ok {/if}' ));
		$this->assertEquals(' ok ', self::_tpl( '{if( module_conf.main.unit_var2 eq 5 )} ok {/if}' ));
		$this->assertEquals(' ok ', self::_tpl( '{if(module_conf.main.unit_var2 eq 5)} ok {/if}' ));
	}
	public function test_conf() {
		conf('unit_test_conf_item1', 'unit_val');
		$this->assertEquals('unit_val', self::_tpl( '{conf( "unit_test_conf_item1" )}' ));
		$this->assertEquals('unit_val', self::_tpl( '{conf("unit_test_conf_item1" )}' ));
		$this->assertEquals('unit_val', self::_tpl( '{conf( unit_test_conf_item1 )}' ));
		$this->assertEquals('unit_val', self::_tpl( '{conf(unit_test_conf_item1)}' ));
	}
	public function test_if_conf() {
		conf('unit_test_conf_item2', '6');
		$this->assertEquals(' ok ', self::_tpl( '{if("conf.unit_test_conf_item2" eq "6")} ok {/if}' ));
		$this->assertEquals(' ok ', self::_tpl( '{if( "conf.unit_test_conf_item2" eq "6" )} ok {/if}' ));
		$this->assertEquals(' ok ', self::_tpl( '{if( conf.unit_test_conf_item2 eq 6 )} ok {/if}' ));
		$this->assertEquals(' ok ', self::_tpl( '{if(conf.unit_test_conf_item2 eq 6)} ok {/if}' ));
	}
	public function test_if_conf_complex() {
		module_conf('main', 'unit_var2', '5');
		conf('unit_test_conf_item2', '6');
		$this->assertEquals(' ok ', self::_tpl( '{if(conf.unit_test_conf_item2 eq "6" and module_conf.main.unit_var2 eq "5")} ok {/if}' ));
		$this->assertEquals(' ok ', self::_tpl( '{if(conf.unit_test_conf_item2 eq 6 and module_conf.main.unit_var2 eq 5)} ok {/if}' ));
	}
	public function test_var_array() {
		$this->assertEquals('val1 val2', self::_tpl( '{sub.key1} {sub.key2}', array('sub' => array('key1' => 'val1', 'key2' => 'val2')) ));
	}
	public function test_var_modifier() {
		$this->assertEquals('val1', self::_tpl( '{key1|trim}', array('key1' => ' val1 ') ));
		$this->assertEquals('val1', self::_tpl( '{key1|trim|urlencode}', array('key1' => ' val1 ') ));
		$this->assertEquals('val+1', self::_tpl( '{key1|trim|urlencode}', array('key1' => ' val 1 ') ));
		$this->assertEquals('val%201', self::_tpl( '{key1|trim|rawurlencode}', array('key1' => ' val 1 ') ));
		$this->assertEquals('val1 val2', self::_tpl( '{sub.key1|trim} {sub.key2|urlencode}', array('sub' => array('key1' => ' val1 ', 'key2' => 'val2')) ));
	}
	public function test_foreach_and_var_modifier() {
		$a = array( array('key1' => ' val11 '), array('key1' => ' val21 '), );
		$this->assertEquals('+val11++val21+', self::_tpl( '{foreach("testarray")}+{#.key1|trim}+{/foreach}', array('testarray' => $a) ));
	}
	public function test_complex_foreach() {
		$data = array(
			'test_array_1'  => array('One', 'Two', 'Three', 'Four'),
			'test_array_2'  => array(
				'One'   => array('name'  => 'First'),
				'Two'   => array('name'  => 'Second'),
				'Three' => array('name'  => 'Third'),
				'Four'  => array('name'  => 'Fourth'),
			),
			'cond_1' => 1,
			'cond_2' => 2,
			'cond_3' => 2,
		);
		$this->assertEquals(
'1). <small>(key: One)</small><b style="color:red;">First!!!</b><br /><span style="color: blue;">name: First<br />, num_items: 4<br /></span>, <br />
2). <small>(key: Two)</small><span style="color: green;">name: Second<br />, num_items: 4<br /></span>, <br />
3). <small>(key: Three)</small><span style="color: blue;">name: Third<br />, num_items: 4<br /></span>, <br />
4). <small>(key: Four)</small><span style="color: green;">name: Fourth<br />, num_items: 4<br /></span>
'		, self::_tpl( 
'{foreach(test_array_2)}
{_num}). <small>(key: {_key})</small>{if(_first eq 1)}<b style="color:red;">First!!!</b><br />{/if}
<span style="{if(_even eq 1)}color: blue;{/if}{if(_odd eq 1)}color: green;{/if}">name: {#.name}<br />, num_items: {_total}<br /></span>{if(_last ne 1)}, <br />{/if}'.PHP_EOL.'
{/foreach}'
		, $data) );
	}
	public function test_complex_foreach2() {
		$data = array(
			'test_array'  => array(
				'One'   => array('name'  => 'First'),
				'Two'   => array('name'  => 'Second'),
				'Three' => array('name'  => 'Third'),
				'Four'  => array('name'  => 'Fourth'),
			),
			'cond_name'	=> 'Third',
			'cond_array'=> array('mykey' => 'Third'),
		);
		$this->assertEquals('ok', self::_tpl('{foreach("test_array")}{if(#.name eq #cond_name)}ok{/if}{/foreach}', $data) );
		$this->assertEquals('ok', self::_tpl('{catch(mycond)}{cond_array.mykey}{/catch}{foreach("test_array")}{if(#.name eq #mycond)}ok{/if}{/foreach}', $data) );
	}
	public function test_js() {
		$this->assertEquals('', self::_tpl( '{js()}//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.js{/js}' ));
		$this->assertEquals('', self::_tpl( '{js()} //ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.js {/js}' ));
		$this->assertEquals('', self::_tpl( '{js(class=yf_core)}//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.js{/js}' ));
		$this->assertEquals('', self::_tpl( '{js(class=yf_core,other=param)}//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.js{/js}' ));
	}
	public function test_js_complex() {
		self::_tpl( '{js()}//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.js{/js}' );
		$this->assertEquals('<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.js" type="text/javascript"></script>', _class('core_js')->show() );
		self::_tpl( '{js()} //ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.js {/js}' );
		$this->assertEquals('<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.js" type="text/javascript"></script>', _class('core_js')->show() );
		self::_tpl( '{js(class=yf_core)}//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.js{/js}' );
		$this->assertEquals('<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.js" type="text/javascript" class="yf_core"></script>', _class('core_js')->show() );
		self::_tpl( '{js(class=yf_core,other=param)}//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.js{/js}' );
		$this->assertEquals('<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.js" type="text/javascript" class="yf_core"></script>', _class('core_js')->show() );
	}
	public function test_css() {
		$this->assertEquals('', self::_tpl( '{css()}//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/css/jquery-ui.min.css{/css}' ));
		$this->assertEquals('', self::_tpl( '{css()} //cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/css/jquery-ui.min.css {/css}' ));
		$this->assertEquals('', self::_tpl( '{css(class=yf_core)}//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/css/jquery-ui.min.css{/css}' ));
		$this->assertEquals('', self::_tpl( '{css(class=yf_core,other=param)}//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/css/jquery-ui.min.css{/css}' ));
	}
	public function test_css_complex() {
		self::_tpl( '{css()}//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/css/jquery-ui.min.css{/css}' );
		$this->assertEquals('<link href="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/css/jquery-ui.min.css" rel="stylesheet" />', _class('core_css')->show() );
		self::_tpl( '{css()} //cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/css/jquery-ui.min.css {/css}' );
		$this->assertEquals('<link href="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/css/jquery-ui.min.css" rel="stylesheet" />', _class('core_css')->show() );
		self::_tpl( '{css(class=yf_core)}//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/css/jquery-ui.min.css{/css}' );
		$this->assertEquals('<link href="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/css/jquery-ui.min.css" rel="stylesheet" class="yf_core" />', _class('core_css')->show() );
		self::_tpl( '{css(class=yf_core,other=param)}//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/css/jquery-ui.min.css{/css}' );
		$this->assertEquals('<link href="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/css/jquery-ui.min.css" rel="stylesheet" class="yf_core" />', _class('core_css')->show() );
	}
}