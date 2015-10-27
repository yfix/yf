<?php

require_once __DIR__.'/tpl__setup.php';

class tpl_driver_yf_core_test extends tpl_abstract {
	public function return_true($out = '') {
		return $out ? (is_array($out) ? implode(',', $out) : $out) : 'true';
	}
	public function test_const() {
		$this->assertEquals(YF_PATH, self::_tpl( '{const("YF_PATH")}' ));
		$this->assertEquals(YF_PATH, self::_tpl( '{const(\'YF_PATH\')}' ));
		$this->assertEquals(YF_PATH, self::_tpl( '{const(YF_PATH)}' ));
		$this->assertEquals(YF_PATH, self::_tpl( '{const( YF_PATH)}' ));
		$this->assertEquals(YF_PATH, self::_tpl( '{const( YF_PATH )}' ));
		$this->assertEquals(YF_PATH, self::_tpl( '{const( YF_PATH )}' ));
		$this->assertEquals(YF_PATH, self::_tpl( '{const(      YF_PATH         )}' ));
		$this->assertEquals(YF_PATH.YF_PATH.YF_PATH, self::_tpl( '{const(YF_PATH)}{const(YF_PATH)}{const(YF_PATH)}' ));
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
	public function test_execute() {
		$class = get_called_class();
		$method = 'return_true';
		$this->assertEquals('true', self::_tpl( '{execute('.$class.','.$method.')}' ));
		$this->assertEquals('truetrue', self::_tpl( '{execute('.$class.','.$method.')}{execute('.$class.','.$method.')}' ));
		$this->assertEquals('{ execute('.$class.','.$method.')}', self::_tpl( '{ execute('.$class.','.$method.')}' ));
		$this->assertEquals('{execute('.$class.','.$method.') }', self::_tpl( '{execute('.$class.','.$method.') }' ));
		$this->assertEquals('{ execute('.$class.','.$method.') }', self::_tpl( '{ execute('.$class.','.$method.') }' ));
		$this->assertEquals('true', self::_tpl( '{execute( '.$class.','.$method.')}' ));
		$this->assertEquals('true', self::_tpl( '{execute('.$class.','.$method.' )}' ));
		$this->assertEquals('true', self::_tpl( '{execute( '.$class.','.$method.' )}' ));
		$this->assertEquals('true', self::_tpl( '{execute('.$class.' ,'.$method.')}' ));
		$this->assertEquals('true', self::_tpl( '{execute('.$class.', '.$method.')}' ));
		$this->assertEquals('true', self::_tpl( '{execute('.$class.' , '.$method.')}' ));
		$this->assertEquals('true', self::_tpl( '{execute( '.$class.' , '.$method.' )}' ));
		$this->assertEquals('true', self::_tpl( '{execute('.$class.';'.$method.')}' ));
		$this->assertEquals('true', self::_tpl( '{execute( '.$class.';'.$method.' )}' ));
		$this->assertEquals('true', self::_tpl( '{execute( '.$class.' ; '.$method.' )}' ));
		$this->assertEquals('val1', self::_tpl( '{execute('.$class.';'.$method.';param1=val1)}' ));
		$this->assertEquals('val1', self::_tpl( '{execute('.$class.','.$method.',param1=val1)}' ));
		$this->assertEquals('val1', self::_tpl( '{execute( '.$class.' , '.$method.' , param1=val1 )}' ));
		$this->assertEquals('val1,val2', self::_tpl( '{execute('.$class.','.$method.',param1=val1;param2=val2)}' ));
		$this->assertEquals('val1,val2,val3', self::_tpl( '{execute('.$class.','.$method.',param1=val1;param2=val2;param3=val3)}' ));
		$this->assertEquals('val1,val2', self::_tpl( '{execute( '.$class.' , '.$method.' , param1=val1 ; param2=val2 )}' ));
		$this->assertNotEquals('tru', self::_tpl( '{execute('.$class.','.$method.')}' ));

		$bak['object'] = $_GET['object'];
		$bak['action'] = $_GET['action'];

		$_GET['object'] = $class;
		$_GET['action'] = $method;
		$this->assertEquals('true', self::_tpl( '{execute(@object,'.$method.')}' ));
		$this->assertEquals('true', self::_tpl( '{execute(@object,@action)}' ));

		$_GET['object'] = $bak['object'];
		$_GET['action'] = $bak['action'];
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
				<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap{min_ext}.js"></script>
			{/if}
		';
		$this->assertEquals('<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.js"></script>', trim(self::_tpl($tpl_str, array('css_framework' => 'bs2', 'debug_mode' => 1))) );
		$this->assertEquals('<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.js"></script>', trim(self::_tpl($tpl_str, array('css_framework' => 'bs3', 'debug_mode' => 1))) );
		$this->assertEquals('<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>', trim(self::_tpl($tpl_str, array('css_framework' => 'bs2', 'debug_mode' => 0))) );
		$this->assertEquals('<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>', trim(self::_tpl($tpl_str, array('css_framework' => 'bs3', 'debug_mode' => 0))) );
	}
	public function test_cleanup() {
		$this->assertEquals('<script>function myjs(){ var i = 0 }<script>', self::_tpl( '{cleanup()}<script>function myjs(){ {js-var} }<script>{/cleanup}', array('js-var' => 'var i = 0') ));
	}
	public function test_comment() {
		$this->assertEquals('', self::_tpl( '{{--STPL COMMENT--}}' ));
		$this->assertEquals('', self::_tpl( '{{--STPL COMMENT--}}{{--STPL COMMENT--}}' ));
		$this->assertEquals('<!---->', self::_tpl( '<!--{{--STPL COMMENT--}}-->' ));
		$this->assertEquals('TEXT', self::_tpl( '{{--<!----}}TEXT{{---->--}}' ));
		$this->assertEquals('TEXTTEXT', self::_tpl( '{{--<!----}}TEXT{{---->--}}{{--<!----}}TEXT{{---->--}}' ));
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
		$data2 = array(
			5 => array('name' => 'name1', 'age' => 21),
			6 => array('name' => 'name2', 'age' => 22),
			7 => array('name' => 'name3', 'age' => 23),
		);
		$this->assertEquals('1111111111', self::_tpl( '{foreach(10)}1{/foreach}' ));
		$this->assertEquals('111111111122222222221111111111', self::_tpl( '{foreach(10)}1{/foreach}{foreach(10)}2{/foreach}{foreach(10)}1{/foreach}' ));
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
		$this->assertEquals(' name1:21  name2:22  name3:23 ', self::_tpl( '{foreach("testarray")} {#.name}:{#.age} {/foreach}', array('testarray' => $data2) ));
		$this->assertEquals('', self::_tpl( '{foreach( not_existing_key )}{/foreach}' ));
		$this->assertEquals('', self::_tpl( '{foreach( not_existing_key )} {if("_total" eq "3")}1{else}0{/if} {/foreach}' ));
	}
	public function test_if_sub() {
		$this->assertEquals('GOOD', self::_tpl( '{if("sub.key1" eq "val1")}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1')) ));
		$this->assertEquals('GOOD', self::_tpl( '{if("sub.key1" eq "val1" and "sub.key2" ne "gggg")}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1', 'key2' => 'val2')) ));
		$this->assertEquals('GOOD', self::_tpl( '{if( "sub.key1" eq "val1" )}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1')) ));
		$this->assertEquals('GOOD', self::_tpl( '{if(sub.key1 eq val1)}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1')) ));
		$this->assertEquals('GOOD', self::_tpl( '{if( sub.key1 eq val1 )}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1')) ));
	}
	public function test_if_string() {
		$this->assertEquals('GOOD', self::_tpl( '{if("%string" eq "string")}GOOD{else}BAD{/if}' ));
	}
	public function test_if_const() {
		define('MY_TEST_CONST_1', '42');
		define('MY_TEST_CONST_2', '43');
		$this->assertFalse(defined('MY_NOT_EXISTING_CONST'));
		$this->assertEquals('GOOD', self::_tpl( '{if("const.MY_TEST_CONST_1" ne "")}GOOD{else}BAD{/if}' ));
		$this->assertEquals('GOOD', self::_tpl( '{if("const.MY_NOT_EXISTING_CONST" eq "")}GOOD{else}BAD{/if}' ));

		$this->assertEquals('GOOD', self::_tpl( '{if(const.MY_NOT_EXISTING_CONST eq 0)}GOOD{else}BAD{/if}' ));
		$this->assertEquals('GOOD', self::_tpl( '{if_empty(const.MY_NOT_EXISTING_CONST)}GOOD{else}BAD{/if}' ));
		$this->assertEquals('GOOD', self::_tpl( '{if_not_isset(const.MY_NOT_EXISTING_CONST)}GOOD{else}BAD{/if}' ));

		$this->assertEquals('GOOD', self::_tpl( '{if(const.MY_NOT_EXISTING_CONST eq "")}GOOD{else}BAD{/if}' ));
		$this->assertEquals('GOOD', self::_tpl( '{if("const.MY_NOT_EXISTING_CONST" ne "")}BAD{else}GOOD{/if}' ));
		$this->assertEquals('GOOD', self::_tpl( '{if(const.MY_NOT_EXISTING_CONST ne "")}BAD{else}GOOD{/if}' ));
		$this->assertEquals('GOOD', self::_tpl( '{if( const.MY_NOT_EXISTING_CONST ne "" )}BAD{else}GOOD{/if}' ));
		$this->assertEquals('GOOD', self::_tpl( '{if(const.MY_TEST_CONST_1 eq 42 and const.MY_TEST_CONST_2 eq 43)}GOOD{else}BAD{/if}' ));
		$this->assertEquals('GOOD', self::_tpl( '{if(const.MY_TEST_CONST_1 eq 42 and const.MY_NOT_EXISTING_CONST eq "")}GOOD{else}BAD{/if}' ));
		$this->assertEquals('GOOD', self::_tpl( '{if( const.MY_NOT_EXISTING_CONST1 ne 42 and const.MY_NOT_EXISTING_CONST2 eq "" )}GOOD{else}BAD{/if}' ));
	}
	public function test_if_const_and_sub() {
		define('MY_TEST_CONST_1', '42');
		$this->assertEquals('GOOD', self::_tpl( '{if("sub.key1" eq "val1" and "const.MY_TEST_CONST_1" eq "42")}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1')) ));
		$this->assertEquals('GOOD', self::_tpl( '{if("sub.key1" eq "val1" and "const.MY_TEST_CONST_1" eq "43")}BAD{else}GOOD{/if}', array('sub' => array('key1' => 'val1')) ));
		$this->assertEquals('GOOD', self::_tpl( '{if("sub.key1" eq "val1" and "const.MY_NOT_EXISTING_CONST" eq "1")}BAD{else}GOOD{/if}', array('sub' => array('key1' => 'val1')) ));
		$this->assertEquals('GOOD', self::_tpl( '{if("sub.key1" eq "val1" and "sub.key2" ne "1" and "const.MY_TEST_CONST_1" ne "43")}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1', 'key2' => 'val2')) ));
		$this->assertEquals('GOOD', self::_tpl( '{if("sub.key1" ne "val1" or "sub.key2" ne "val2" or "sub.key3" ne "val3")}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1', 'key2' => 'val2')) ));
		$this->assertEquals('GOOD', self::_tpl( '{if("sub.key1" ne "val1" or "sub.key2" ne "val2" or "sub.key3" ne "val3" or "sub.key4" ne "val4" or "sub.key5" ne "val5")}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1', 'key2' => 'val2')) ));
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
		_class('assets')->clean_all();
		$jquery_url = _class('assets')->get_asset('jquery', 'js');
		$this->assertNotEmpty($jquery_url);
		$this->assertEquals('', self::_tpl( '{js()}'.$jquery_url.'{/js}' ));
		$this->assertEquals('', self::_tpl( '{js()} '.$jquery_url.' {/js}' ));
		$this->assertEquals('', self::_tpl( '{js(class=yf_core)}'.$jquery_url.'{/js}' ));
		$this->assertEquals('', self::_tpl( '{js(class=yf_core,other=param)}'.$jquery_url.'{/js}' ));
	}
	public function test_js_complex() {
		_class('assets')->clean_all();

		$jquery_url = _class('assets')->get_asset('jquery', 'js');
		$this->assertNotEmpty($jquery_url);
		self::_tpl( '{js()}'.$jquery_url.'{/js}' );
		$this->assertEquals('<script src="'.$jquery_url.'" type="text/javascript"></script>', _class('assets')->show_js() );
		self::_tpl( '{js()} '.$jquery_url.' {/js}' );
		$this->assertEquals('<script src="'.$jquery_url.'" type="text/javascript"></script>', _class('assets')->show_js() );
		self::_tpl( '{js(class=yf_core)}'.$jquery_url.'{/js}' );
		$this->assertEquals('<script src="'.$jquery_url.'" type="text/javascript" class="yf_core"></script>', _class('assets')->show_js() );
		self::_tpl( '{js(class=yf_core,other=param)}'.$jquery_url.'{/js}' );
		$this->assertEquals('<script src="'.$jquery_url.'" type="text/javascript" class="yf_core"></script>', _class('assets')->show_js() );

		$url = '/my.js';
		self::_tpl( '{js()}'.$url.'{/js}' );
		$this->assertEquals('<script type="text/javascript">'.PHP_EOL.$url.PHP_EOL.'</script>', _class('assets')->show_js() );
		self::_tpl( '{js(type=url)}'.$url.'{/js}' );
		$this->assertEquals('<script src="'.$url.'" type="text/javascript"></script>', _class('assets')->show_js() );

		self::_tpl( '{js()}'.PHP_EOL.'var testtag="<span>";'.PHP_EOL.'{/js}' );
		$this->assertEquals('<script type="text/javascript">'.PHP_EOL.'var testtag="<span>";'.PHP_EOL.'</script>', _class('assets')->show_js() );
	}
	public function test_css() {
		_class('assets')->clean_all();
		$jqueryui_url = _class('assets')->get_asset('jquery-ui', 'css');
		$this->assertNotEmpty($jqueryui_url);
		$this->assertEquals('', self::_tpl( '{css()}'.$jqueryui_url.'{/css}' ));
		$this->assertEquals('', self::_tpl( '{css()} '.$jqueryui_url.' {/css}' ));
		$this->assertEquals('', self::_tpl( '{css(class=yf_core)}'.$jqueryui_url.'{/css}' ));
		$this->assertEquals('', self::_tpl( '{css(class=yf_core,other=param)}'.$jqueryui_url.'{/css}' ));
	}
	public function test_css_complex() {
		_class('assets')->clean_all();
		$jqueryui_url = _class('assets')->get_asset('jquery-ui', 'css');
		$this->assertNotEmpty($jqueryui_url);
		self::_tpl( '{css()}'.$jqueryui_url.'{/css}' );
		$this->assertEquals('<link href="'.$jqueryui_url.'" rel="stylesheet" />', _class('assets')->show_css() );
		self::_tpl( '{css()} '.$jqueryui_url.' {/css}' );
		$this->assertEquals('<link href="'.$jqueryui_url.'" rel="stylesheet" />', _class('assets')->show_css() );
		self::_tpl( '{css(class=yf_core)}'.$jqueryui_url.'{/css}' );
		$this->assertEquals('<link href="'.$jqueryui_url.'" rel="stylesheet" class="yf_core" />', _class('assets')->show_css() );
		self::_tpl( '{css(class=yf_core,other=param)}'.$jqueryui_url.'{/css}' );
		$this->assertEquals('<link href="'.$jqueryui_url.'" rel="stylesheet" class="yf_core" />', _class('assets')->show_css() );
	}
	public function test_assets_js_libs() {
		_class('assets')->clean_all();
		$jquery_url = _class('assets')->get_asset('jquery', 'js');
		$this->assertNotEmpty($jquery_url);
		self::_tpl( '{jquery()} var i = 0; $("#id").on(\'click\', ".sub_selector", function(){ return false; }); {/jquery}' );
		$this->assertEquals(
			'<script src="'.$jquery_url.'" type="text/javascript"></script>'.PHP_EOL.
			'<script type="text/javascript">'.PHP_EOL.'$(function(){'.PHP_EOL.'var i = 0; $("#id").on(\'click\', ".sub_selector", function(){ return false; });'.PHP_EOL.'})'.PHP_EOL.'</script>',
			_class('assets')->show_js()
		);

		_class('assets')->clean_all();
		self::_tpl( '{asset()} jquery {/asset}' );
		$this->assertEquals('<script src="'.$jquery_url.'" type="text/javascript"></script>', _class('assets')->show_js());
	}
	public function test_replace_subarray() {
		$this->assertEquals('', self::_tpl( '{get.test}' ));
		$this->assertEquals('{ get.test }', self::_tpl( '{ get.test }' ));
		$this->assertEquals('val1,val2,val3', self::_tpl( '{sub.key1},{sub.key2},{sub.key3}', array('sub' => array('key1' => 'val1', 'key2' => 'val2', 'key3' => 'val3')) ));
	}
	public function test_avail_arrays() {
		$old = tpl()->_avail_arrays;
		$_GET['mytestvar'] = 'mytestvalue';
		tpl()->_avail_arrays = array('get' => '_GET');

		$this->assertEquals('', self::_tpl( '{get.not_exists}' ));
		$this->assertEquals('_mytestvalue_', self::_tpl( '_{get.mytestvar}_' ));
		$this->assertEquals('good', self::_tpl( '{if(get.mytestvar eq mytestvalue)}good{else}bad{/if}' ));
		$this->assertEquals('good', self::_tpl( '{if(get.mytestvar ne "")}good{else}bad{/if}' ));
		$this->assertEquals('good', self::_tpl( '{if(get.mytestvar ne something_else)}good{else}bad{/if}' ));

		$data = array(
			'k1' => 'v1', 'k2' => 'v2', 'k3' => 'v3',
		);
		$_GET['myarray'] = $data;

		$this->assertEquals(' k1=v1  k2=v2  k3=v3 ', self::_tpl( '{foreach(data)} {_key}={_val} {/foreach}', array('data' => $data) ));
		$this->assertEquals(' k1=v1  k2=v2  k3=v3 ', self::_tpl( '{foreach(data.myarray)} {_key}={_val} {/foreach}', array('data' => array('myarray' => $data)) ));
		$this->assertEquals('', self::_tpl( '{foreach(data.not_exists)} {_key}={_val} {/foreach}', array('data' => array('myarray' => $data)) ));
		$this->assertEquals('k1=v1', self::_tpl( '{foreach(data.myarray)}{if(_key eq k1)}{_key}={_val}{/if}{/foreach}', array('data' => array('myarray' => $data)) ));
		$this->assertEquals('k2=v2', self::_tpl( '{foreach(data.myarray)}{if(_key eq k2)}{_key}={_val}{/if}{/foreach}', array('data' => array('myarray' => $data)) ));
		$this->assertEquals('k3=v3', self::_tpl( '{foreach(data.myarray)}{if(_key eq k3)}{_key}={_val}{/if}{/foreach}', array('data' => array('myarray' => $data)) ));
		$this->assertEquals(' k1=v1  k2=v2  k3=v3 ', self::_tpl( '{foreach(get.myarray)} {_key}={_val} {/foreach}' ));
		$this->assertEquals('', self::_tpl( '{foreach(get.not_exists)} {_key}={_val} {/foreach}' ));
		$this->assertEquals('k1=v1', self::_tpl( '{foreach(get.myarray)}{if(_key eq k1)}{_key}={_val}{/if}{/foreach}' ));
		$this->assertEquals('k2=v2', self::_tpl( '{foreach(get.myarray)}{if(_key eq k2)}{_key}={_val}{/if}{/foreach}' ));
		$this->assertEquals('k3=v3', self::_tpl( '{foreach(get.myarray)}{if(_key eq k3)}{_key}={_val}{/if}{/foreach}' ));

		tpl()->_avail_arrays = $old;
	}
	public function test_foreach_val_array() {
		$data = array('k1' => 'v1', 'k4' => array(1,2,3));
		$this->assertEquals(' k1=v1  k4=1,2,3 ', self::_tpl('{foreach(data)} {_key}={_val} {/foreach}', array('data' => $data)));
	}
	public function test_object_vars() {
		$data = new stdClass();
		$data->key1 = 'val1';
		$data->key2 = 'val2';
		$data->key3 = 'val3';

		$this->assertEquals('val1 val2 val3', self::_tpl('{key1} {key2} {key3}', $data));
		$this->assertEquals('val1', self::_tpl('{data.key1}', array('data' => $data)));
		$this->assertEquals('val1,val2', self::_tpl('{data.key1},{data.key2}', array('data' => $data)));
		$this->assertEquals('val1,val2,val3', self::_tpl('{data.key1},{data.key2},{data.key3}', array('data' => $data)));
		$this->assertEquals('good', self::_tpl('{if(data.key1 eq val1)}good{else}bad{/if}', array('data' => $data)));
		$this->assertEquals('good', self::_tpl('{if(data.key1 ne fsdfsfsd)}good{else}bad{/if}', array('data' => $data)));
		$this->assertEquals(' key1=val1  key2=val2  key3=val3 ', self::_tpl('{foreach(data)} {_key}={_val} {/foreach}', array('data' => $data)));
		$data->key4 = array(1,2,3);
		$this->assertEquals(' key1=val1  key2=val2  key3=val3  key4=1,2,3 ', self::_tpl('{foreach(data)} {_key}={_val} {/foreach}', array('data' => $data)));
	}
	public function test_if_funcs_basic() {
		$data = array('name1' => '', 'name2' => 'something');

		$this->assertEquals('good', self::_tpl('{if_not_ok(name1)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_empty(name1)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_false(name1)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_not_true(name1)}good{/if}', $data));

		$this->assertEquals('good', self::_tpl('{if_ok(name2)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_not_empty(name2)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_true(name2)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_not_false(name2)}good{/if}', $data));

		$this->assertEquals('good ok', self::_tpl('{if_empty(name1)}good{/if} {if_not_empty(name2)}ok{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_not_empty(name2)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_isset(name1)}good{/if}', $data));

		$this->assertEquals('good', self::_tpl('{if_not_isset(name3)}good{/if}', $data));

		$this->assertEquals('good', self::_tpl('{if_empty(data)}good{/if}', array('data' => '')));
		$this->assertEquals('good', self::_tpl('{if_empty(data)}good{/if}', array('data' => array())));
		$this->assertEquals('good', self::_tpl('{if_not_empty(data)}good{/if}', array('data' => $data)));
		$this->assertEquals('good', self::_tpl('{if_empty(data.name1)}good{/if}', array('data' => $data)));
		$this->assertEquals('good', self::_tpl('{if_not_empty(data.name2)}good{/if}', array('data' => $data)));
		$this->assertEquals('good', self::_tpl('{if_not_isset(data.name3)}good{/if}', array('data' => $data)));

		$this->assertEquals('good', self::_tpl('{if_validate:is_natural_no_zero(data)}good{/if}', array('data' => '1234567890')));
		$this->assertEquals('good', self::_tpl('{if_not_validate:alpha_spaces(data)}good{/if}', array('data' => '1234567890')));
		$this->assertEquals('good', self::_tpl('{if_validate:alpha_spaces(data)}good{/if}', array('data' => 'abcd efgh ijkl mnop qrst uvwx yz')));
	}
	public function test_if_funcs_multiple() {
		$data = array('name1' => '', 'name2' => 'something', 'name3' => '', 'name4' => 'other', 'name5' => '', 'name6' => 'gggggg');

		$this->assertEquals('good', self::_tpl('{if_not_ok(name1,name3)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_not_ok(name1,name2)}bad{else}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_empty(name1,name3)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_empty(name1,name2)}bad{else}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_false(name1,name3)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_false(name1,name2)}bad{else}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_not_true(name1,name3,name5)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_not_true(name1,name3,name6)}bad{else}good{/if}', $data));

		$this->assertEquals('good', self::_tpl('{if_ok(name2,name4,name6)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_and_ok(name2,name4,name6)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_or_ok(name1,name2)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_or_ok(name1,name2,name3,name4,name5,name6)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_or_ok(name1,name3,name5)}bad{else}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_not_ok(name1,name2,name3,name4,name5,name6)}bad{else}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_and_not_ok(name1,name2,name3,name4,name5,name6)}bad{else}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_or_not_ok(name1,name2,name3)}good{/if}', $data));

		$this->assertEquals('good ok', self::_tpl('{if_empty(name1,name3,name5)}good{/if} {if_not_empty(name2,name4,name6)}ok{/if}', $data));
		$this->assertEquals('good ok', self::_tpl('{if_and_empty(name1,name3,name5)}good{/if} {if_and_not_empty(name2,name4,name6)}ok{/if}', $data));
		$this->assertEquals('good ok', self::_tpl('{if_or_empty(name1,name3,name5)}good{/if} {if_or_not_empty(name2,name4,name6)}ok{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_not_empty(name2,name4)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_isset(name1,name3,name5)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_isset( name1 )}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_isset( name1, name3, name5 )}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_and_isset(name1,name3,name5)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_or_isset(name1,name333,name555)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_not_isset(name777,name888,name999)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_and_not_isset(name777,name888,name999)}good{/if}', $data));
		$this->assertEquals('good', self::_tpl('{if_or_not_isset(name1,name2,name9999)}good{/if}', $data));
// TODO: add more tests
/*
		$this->assertEquals('good', self::_tpl('{if_empty(data)}good{/if}', array('data' => '')));
		$this->assertEquals('good', self::_tpl('{if_empty(data)}good{/if}', array('data' => array())));
		$this->assertEquals('good', self::_tpl('{if_not_empty(data)}good{/if}', array('data' => $data)));
		$this->assertEquals('good', self::_tpl('{if_empty(data.name1)}good{/if}', array('data' => $data)));
		$this->assertEquals('good', self::_tpl('{if_not_empty(data.name2)}good{/if}', array('data' => $data)));
		$this->assertEquals('good', self::_tpl('{if_not_isset(data.name3)}good{/if}', array('data' => $data)));

		$this->assertEquals('good', self::_tpl('{if_validate:is_natural_no_zero(data)}good{/if}', array('data' => '1234567890')));
		$this->assertEquals('good', self::_tpl('{if_not_validate:alpha_spaces(data)}good{/if}', array('data' => '1234567890')));
		$this->assertEquals('good', self::_tpl('{if_validate:alpha_spaces(data)}good{/if}', array('data' => 'abcd efgh ijkl mnop qrst uvwx yz')));
*/
	}
	public function test_elseif_simple() {
		$data = array('name1' => '', 'name2' => 'something');
		$this->assertEquals('ok', self::_tpl('{if(name1 ne "")}bad{elseif(name2 ne "")}ok{/if}', $data));
		$this->assertEquals('ok', self::_tpl('{if(name2 eq "")}bad{elseif(name1 eq "")}ok{/if}', $data));
	}
	public function test_elseif_funcs() {
		$data = array('name1' => '', 'name2' => 'something');
		$this->assertEquals('ok', self::_tpl('{if_empty(name2)}bad{elseif_empty(name1)}ok{/if}', $data));
		$this->assertEquals('ok', self::_tpl('{if_empty(name2)}bad{elseif_not_ok(name1)}ok{/if}', $data));
		$this->assertEquals('ok', self::_tpl('{if_empty(name2)}bad{elseif_false(name1)}ok{/if}', $data));
		$this->assertEquals('ok', self::_tpl('{if_empty(name2)}bad{elseif_not_true(name1)}ok{/if}', $data));

		$this->assertEquals('ok', self::_tpl('{if_not_empty(name1)}bad{elseif_ok(name2)}ok{/if}', $data));
		$this->assertEquals('ok', self::_tpl('{if_not_empty(name1)}bad{elseif_true(name2)}ok{/if}', $data));
		$this->assertEquals('ok', self::_tpl('{if_not_empty(name1)}bad{elseif_not_empty(name2)}ok{/if}', $data));
		$this->assertEquals('ok', self::_tpl('{if_not_empty(name1)}bad{elseif_not_false(name2)}ok{/if}', $data));
	}
	public function test_elseforeach() {
		$data = array('k1' => 'v1', 'k2' => 'v2');
		$this->assertEquals('no rows', self::_tpl('{foreach(data)} {_key}={_val} {elseforeach}no rows{/foreach}', array()));
		$this->assertEquals(' k1=v1  k2=v2 ', self::_tpl('{foreach(data)} {_key}={_val} {elseforeach}no rows{/foreach}', array('data' => $data)));
		$this->assertEquals('no rows', self::_tpl('{foreach(data.sub)} {_key}={_val} {elseforeach}no rows{/foreach}', array()));
		$this->assertEquals(' k1=v1  k2=v2 ', self::_tpl('{foreach(data.sub)} {_key}={_val} {elseforeach}no rows{/foreach}', array('data' => array('sub' => $data))));
		$this->assertEquals('k1k2 k1k2', self::_tpl('{foreach(data.sub)}{_key}{elseforeach}no rows{/foreach} {foreach(data.sub)}{_key}{elseforeach}no rows{/foreach}', array('data' => array('sub' => $data))));
		$data = array('k1' => ' v1 ', 'k2' => ' v2 ');
	}
	public function test_global_tags() {
		$old = tpl()->_global_tags;
		tpl()->_global_tags = array();
#		$this->assertEquals('{some_global_tag1}', self::_tpl('{some_global_tag1}'));

		tpl()->_global_tags = array(
			'some_global_tag1'	=> ' val1 ',
			'some_global_tag2'	=> ' val2 ',
			'some_global_tag3'	=> ' val3 ',
		);
		$this->assertEquals(' val1 ', self::_tpl('{some_global_tag1}'));
		$this->assertEquals(' val1   val1   val1 ', self::_tpl('{some_global_tag1} {some_global_tag1} {some_global_tag1}'));
		$this->assertEquals(' val1   val2   val3 ', self::_tpl('{some_global_tag1} {some_global_tag2} {some_global_tag3}'));
		$this->assertEquals('val1  val2   val3 ', self::_tpl('{some_global_tag1|trim} {some_global_tag2} {some_global_tag3}'));

		tpl()->_global_tags = $old;
	}
	public function test_deep_vars_avail_arrays() {
		$old = tpl()->_avail_arrays;
		tpl()->_avail_arrays = array('get' => '_GET');
		$_GET['some']['deep']['var']['key'] = 'mytestvalue2';

// TODO
#		$this->assertEquals('mytestvalue2', self::_tpl( '{get.some.deep.var.key}' ));

		tpl()->_avail_arrays = $old;
	}
	public function _callme(array $a) {
		if (!is_array($this->_callme_results)) {
			$this->_callme_results = array();
		}
		$this->_callme_results += $a;
	}
	public function test_exec_last() {
		// Some magick here with DI container, we link to this class :-)
		main()->modules['unittest1'] = $this;
		_class('unittest1')->_callme(array('k3' => 'v3'));
		$this->assertSame(array('k3'=>'v3'), $this->_callme_results);

		$this->_callme_results = array();
		$this->assertEquals(array(), $this->_callme_results);
		$this->assertEquals('', self::_tpl('{execute(unittest1,_callme;k1=v1)}'));
		$this->assertSame(array('k1'=>'v1'), $this->_callme_results);

		$this->_callme_results = array();
		$this->assertEquals('', self::_tpl('{execute(unittest1,_callme;k2=v2)}{execute(unittest1,_callme;k1=v1)}'));
		$this->assertSame(array('k2'=>'v2','k1'=>'v1'), $this->_callme_results);

		// Here we ensure that exec_last will be executed after common execute calls
		$this->_callme_results = array();
		$this->assertEquals('', self::_tpl('{exec_last(unittest1,_callme;k2=v2)}{execute(unittest1,_callme;k1=v1)}'));
		$this->assertSame(array('k1'=>'v1','k2'=>'v2'), $this->_callme_results);

		$this->_callme_results = array();
		$this->assertEquals('', self::_tpl('{execute(unittest1,_callme;k1=v1)}{exec_last(unittest1,_callme;k2=v2)}'));
		$this->assertSame(array('k1'=>'v1','k2'=>'v2'), $this->_callme_results);

		unset(main()->modules['unittest1']);
	}
	public function _callme2($a) {
		if (!is_array($this->_callme2_results)) {
			$this->_callme2_results = array();
		}
		if (is_array($a)) {
			$this->_callme2_results = $a;
		}
		return $this->_callme2_results;
	}
	public function callme2($a) {
		return $this->_callme2($a);
	}
	public function test_foreach_exec() {
		// Some magick here with DI container, we link to this class :-)
		main()->modules['unittest2'] = $this;
		$data = array('k1' => 'v1', 'k2' => 'v2');
		$result = _class('unittest2')->_callme2($data);
		$this->assertSame($result, $data);
		$this->assertSame($result, $this->_callme2_results);

		$this->assertSame(' _k1=v1_  _k2=v2_ ', self::_tpl('{foreach_exec(unittest2,_callme2)} _{_key}={_val}_ {/foreach_exec}'));
		$_GET['object'] = 'unittest2';
		$this->assertSame(' _k1=v1_  _k2=v2_ ', self::_tpl('{foreach_exec(@object,_callme2)} _{_key}={_val}_ {/foreach_exec}'));
		$_GET['action'] = '_callme2';
		$this->assertSame(' _k1=v1_  _k2=v2_ ', self::_tpl('{foreach_exec(@object,@action)} _{_key}={_val}_ {/foreach_exec}'));
		$this->assertSame(' _k1=v1_  _k2=v2_ ', self::_tpl('{foreach_exec(@object,@action)} _{_key}={_val}_ {/foreach_exec}'));
		$this->assertSame(' _k1=v1_  _k2=v2_ ', self::_tpl('{foreach_exec(@object;@action)} _{_key}={_val}_ {/foreach_exec}'));
		$this->assertSame(' _arg1=val1_  _arg2=val2_ ', self::_tpl('{foreach_exec(@object;@action;arg1=val1;arg2=val2)} _{_key}={_val}_ {/foreach_exec}'));
		$this->assertSame(' _arg1=val1_  _arg2=val2_ ', self::_tpl('{foreach_exec(@object; @action; arg1=val1; arg2=val2)} _{_key}={_val}_ {/foreach_exec}'));
		$this->assertSame(' _arg1=val1_  _arg2=val2_ ', self::_tpl('{foreach_exec(unittest2; _callme2; arg1=val1; arg2=val2)} _{_key}={_val}_ {/foreach_exec}'));

		$result = _class('unittest2')->_callme2(array());
		$this->assertSame($result, array());
		$this->assertSame(' no rows ', self::_tpl('{foreach_exec(unittest2,_callme2)} _{_key}={_val}_ {elseforeach} no rows {/foreach_exec}'));

		unset(main()->modules['unittest2']);
	}
	public function test_class_properties() {
/*
		// Some magick here with DI container, we link to this class :-)
		main()->modules['unittest3'] = $this;
		$this->data1 = 'data1_val';
		$this->data2 = ' data2_val ';
		$this->data3 = array('k1' => 'v1', 'k2' => ' v2 ');
		$this->assertSame('data1_val  data2_val ', self::_tpl('{unittest3.data1} {unittest3.data2}'));
		$this->assertSame('DATA1_VAL DATA2_VAL', self::_tpl('{unittest3.data1|strtoupper} {unittest3.data2|trim|strtoupper}'));
		$this->assertSame('DATA1_VAL DATA2_VAL', self::_tpl('{unittest3.data1|strtoupper} {unittest3.data2|trim|strtoupper}{unittest3.data_not_exists}'));
		$this->assertSame('DATA1_VAL DATA2_VAL', self::_tpl('{unittest3.data1|strtoupper} {unittest3.data2|trim|strtoupper}{unittest3.data_not_exists|trim}'));
#		$this->assertSame('v1  v2 ', self::_tpl('{unittest2.data.k1} {unittest2.data.k2}'));
#		$this->assertSame('V1 V2', self::_tpl('{unittest2.data.k1|strtoupper} {unittest2.data.k2|trim}'));
		unset(main()->modules['unittest3']);
*/
	}
}
