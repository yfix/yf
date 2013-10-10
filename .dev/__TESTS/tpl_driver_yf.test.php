<?php

define('YF_PATH', dirname(dirname(dirname(__FILE__))).'/');
require YF_PATH.'classes/yf_main.class.php';
new yf_main('user', 1, 0);

function _tpl($stpl_text = '', $replace = array(), $name = '', $params = array()) {
	return tpl()->parse_string($stpl_text, $replace, $name, $params);
}

class tpl_core_test extends PHPUnit_Framework_TestCase {
	public function test_10() {
		$this->assertEquals(YF_PATH, _tpl( '{const("YF_PATH")}' ));
	}
	public function test_11() {
		$this->assertEquals(YF_PATH, _tpl( '{const(\'YF_PATH\')}' ));
	}
	public function test_12() {
		$this->assertEquals(YF_PATH, _tpl( '{const(YF_PATH)}' ));
	}
	public function test_13() {
		$this->assertEquals('{const(WRONG-CONST)}', _tpl( '{const(WRONG-CONST)}' ));
	}
	public function test_14() {
		$this->assertEquals('{const()}', _tpl( '{const()}' ));
	}
	public function test_15() {
		$this->assertEquals(substr(YF_PATH, 0, 8), _tpl( '{eval_code(substr(YF_PATH, 0, 8))}' ));
	}
	public function test_20() {
		$this->assertEquals('val1', _tpl( '{replace1}', array('replace1' => 'val1') ));
	}
	public function test_21() {
		$this->assertEquals('val1', _tpl( '{replace-1}', array('replace-1' => 'val1') ));
	}
	public function test_22() {
		$this->assertEquals('{ replace-1 }', _tpl( '{ replace-1 }', array('replace-1' => 'val1') ));
	}
	public function test_23() {
		$this->assertEquals('<a href="http://google.com/">Google</a>', _tpl( '<a href="{url1}">Google</a>', array('url1' => 'http://google.com/') ));
	}
	public function test_24() {
		$this->assertEquals('http://google.com/http://google.com/http://google.com/', _tpl( '{url1}{url1}{url1}', array('url1' => 'http://google.com/') ));
	}
	public function test_25() {
		$this->assertEquals('<a href="http://yahoo.com/">Google</a>', _tpl( '{catch("url1")}http://yahoo.com/{/catch}<a href="{url1}">Google</a>', array('url1' => 'http://google.com/') ));
	}
	public function test_26() {
		$this->assertEquals('<a href="http://google.com/">Google</a>', _tpl( '{catch("url1")}http://yahoo.com/{/catch}{catch("url1")}http://google.com/{/catch}<a href="{url1}">Google</a>', array('url1' => 'http://google.com/') ));
	}
	public function test_27() {
		$this->assertEquals('<script>function myjs(){ var i = 0 }<script>', _tpl( '<script>function myjs(){ {js-var} }<script>', array('js-var' => 'var i = 0') ));
	}
	public function test_28() {
		$this->assertEquals('{get.test}', _tpl( '{get.test}' ));
	}
	public function test_29() {
		$this->assertEquals('22true33', _tpl( '{catch("mytest1")}22{execute(test,true_for_unittest)}33{/catch}{mytest1}' ));
	}
	public function test_30() {
		$this->assertEquals('<script>function myjs(){ var i = 0 }<script>', _tpl( '{cleanup()}<script>function myjs(){ {js-var} }<script>{/cleanup}', array('js-var' => 'var i = 0') ));
	}
	public function test_31() {
		$this->assertEquals('val1,val2,val3', _tpl( '{sub.key1},{sub.key2},{sub.key3}', array('sub' => array('key1' => 'val1', 'key2' => 'val2', 'key3' => 'val3')) ));
	}
	public function test_50() {
		$this->assertEquals('', _tpl( '{{--STPL COMMENT--}}' ));
	}
	public function test_51() {
		$this->assertEquals('<!---->', _tpl( '<!--{{--STPL COMMENT--}}-->' ));
	}
	public function test_52() {
		$this->assertEquals('TEXT', _tpl( '{{--<!----}}TEXT{{---->--}}' ));
	}
	public function test_60() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" eq "val1")}GOOD{/if}', array('key1' => 'val1') ));
	}
	public function test_61() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" ne "")}GOOD{/if}', array('key1' => 'val1') ));
	}
	public function test_62() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" gt "1")}GOOD{/if}', array('key1' => '2') ));
	}
	public function test_63() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" lt "2")}GOOD{/if}', array('key1' => '1') ));
	}
	public function test_64() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" ge "2")}GOOD{/if}', array('key1' => '2') ));
	}
	public function test_65() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" ge "2")}GOOD{/if}', array('key1' => '3') ));
	}
	public function test_66() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" le "1")}GOOD{/if}', array('key1' => '1') ));
	}
	public function test_67() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" le "1")}GOOD{/if}', array('key1' => '0') ));
	}
	public function test_68() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" ne "")}GOOD{else}BAD{/if}', array('key1' => 'not empty') ));
	}
	public function test_69() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" eq "")}BAD{else}GOOD{/if}', array('key1' => 'not empty') ));
	}
	public function test_70() {
		$this->assertEquals(' GOOD ', _tpl( '{if("key1" eq "")} GOOD {else} BAD {/if}', array('key1' => '') ));
	}
	public function test_71() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" ne "" and "key2" ne "")}GOOD{else}BAD{/if}', array('key1' => '1', 'key2' => '2') ));
	}
	public function test_72() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" ne "" and "key2" ne "")}BAD{else}GOOD{/if}', array('key1' => '', 'key2' => '2') ));
	}
	public function test_73() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" ne "" and "key2" ne "")}BAD{else}GOOD{/if}', array('key1' => '', 'key2' => '') ));
	}
	public function test_74() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" eq "" and "key2" eq "")}GOOD{else}BAD{/if}', array('key1' => '', 'key2' => '') ));
	}
	public function test_75() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" eq "")}{if("key2" eq "")}GOOD{/if}{/if}', array('key1' => '', 'key2' => '') ));
	}
	public function test_76() {
		$this->assertEquals('', _tpl( '{if("key1" eq "")}{if("key2" eq "")}GOOD{/if}{/if}', array('key1' => '1', 'key2' => '2') ));
	}
	public function test_77() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" eq "1")}{if("key2" eq "2")}{if("key3" eq "3")}GOOD{/if}{/if}{/if}', array('key1' => '1', 'key2' => '2', 'key3' => '3') ));
	}
	public function test_78() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" eq "1" and "key2" eq "2" and "key3" eq "3")}GOOD{/if}', array('key1' => '1', 'key2' => '2', 'key3' => '3') ));
	}
	public function test_79() {
		$this->assertEquals('GOOD', _tpl( '{if("key1" eq "1" and "key2" eq "2" and "key3" eq "3" and "key4" eq "4" and "key5" eq "5")}GOOD{/if}', array('key1' => '1', 'key2' => '2', 'key3' => '3', 'key4' => '4', 'key5' => '5') ));
	}
	public function test_80() {
		$this->assertEquals('1111111111', _tpl( '{foreach(10)}1{/foreach}' ));
	}
	public function test_81() {
		$this->assertEquals(' 1  2  3  4 ', _tpl( '{foreach("testarray")} {_val} {/foreach}', array('testarray' => array(1,2,3,4)) ));
	}
	public function test_82() {
		$this->assertEquals(' 0  1  2  3 ', _tpl( '{foreach("testarray")} {_key} {/foreach}', array('testarray' => array(1,2,3,4)) ));
	}
	public function test_83() {
		$this->assertEquals(' 4  4  4  4 ', _tpl( '{foreach("testarray")} {_total} {/foreach}', array('testarray' => array(1,2,3,4)) ));
	}
	public function test_84() {
		$this->assertEquals(' 1  2  3 ', _tpl( '{foreach("testarray")} {_num} {/foreach}', array('testarray' => array(5,6,7)) ));
	}
	public function test_85() {
		$this->assertEquals(' 42  0  0 ', _tpl( '{foreach("testarray")} {if("_first" eq "1")}42{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
	}
	public function test_86() {
		$this->assertEquals(' 0  0  42 ', _tpl( '{foreach("testarray")} {if("_last" eq "1")}42{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
	}
	public function test_87() {
		$this->assertEquals(' 42  0  42 ', _tpl( '{foreach("testarray")} {if("_even" eq "1")}42{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
	}
	public function test_88() {
		$this->assertEquals(' 0  42  0 ', _tpl( '{foreach("testarray")} {if("_odd" eq "1")}42{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
	}
	public function test_89() {
		$this->assertEquals(' 0  42  0 ', _tpl( '{foreach("testarray")} {if("_key" eq "1")}42{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
	}
	public function test_90() {
		$this->assertEquals(' 42  0  0 ', _tpl( '{foreach("testarray")} {if("_val" eq "5")}42{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
	}
	public function test_91() {
		$this->assertEquals(' 0  0  7  0  0 ', _tpl( '{foreach("testarray")} {if("_num" eq "3")}{_val}{else}0{/if} {/foreach}', array('testarray' => array(5,6,7,8,9)) ));
	}
	public function test_92() {
		$this->assertEquals(' 1  1  1 ', _tpl( '{foreach("testarray")} {if("_total" eq "3")}1{else}0{/if} {/foreach}', array('testarray' => array(5,6,7)) ));
	}
	public function test_100() {
		$this->assertEquals(
"1). <small>(key: One)</small>
<b style='color:red;'>First!!!</b><br />
<span style='color: blue;'>name: First<br />, num_items: 4<br /></span>, <br />
2). <small>(key: Two)</small>
<span style='color: green;'>name: Second<br />, num_items: 4<br /></span>, <br />
3). <small>(key: Three)</small>
<span style='color: blue;'>name: Third<br />, num_items: 4<br /></span>, <br />
4). <small>(key: Four)</small>
<span style='color: green;'>name: Fourth<br />, num_items: 4<br /></span>"
		, _tpl( 
"{foreach('test_array_2')}
{_num}). <small>(key: {_key})</small>
{if('_first' eq 1)}<b style='color:red;'>First!!!</b><br />\n{/if}
<span style='{if('_even' eq 1)}color: blue;{/if}{if('_odd' eq 1)}color: green;{/if}'>name: {#.name}<br />, num_items: {_total}<br /></span>{if('_last' ne 1)}, <br />\n{/if}
{/foreach}"
		, array(
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
		) ));
	}
	public function test_101() {
		$this->assertEquals(' name1:21  name2:22  name3:23 ', _tpl( '{foreach("testarray")} {#.name}:{#.age} {/foreach}', array(
			'testarray' => array(
				5 => array('name' => 'name1', 'age' => 21),
				6 => array('name' => 'name2', 'age' => 22),
				7 => array('name' => 'name3', 'age' => 23),
			),
		) ));
	}
	public function test_110() {
		_tpl( 'Hello from include', array(), 'unittest_include' );
		$this->assertEquals('Hello from include', _tpl( '{include("unittest_include")}' ));
	}
	public function test_111() {
		_tpl( 'Inherited var: {key1}', array(), 'unittest_include' );
		$this->assertEquals('Inherited var: val1', _tpl( '{include("unittest_include")}', array('key1' => 'val1') ));
	}
	public function test_112() {
		_tpl( 'Inherited var: {key1}, passed var: {var2}', array(), 'unittest_include' );
		$this->assertEquals('Inherited var: val1, passed var: 42', _tpl( '{include("unittest_include",var2=42)}', array('key1' => 'val1') ));
	}
	public function test_116() {
		_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include' );
		$this->assertEquals('Included: v1 v2 v3 v4', _tpl( '{include("unittest_include",var1=v1;var2=v2;var3=v3;var4=v4)}' ));
	}
	public function test_120() {
		$this->assertEquals('GOOD', _tpl( '{if("sub.key1" eq "val1")}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1')) ));
	}
	public function test_121() {
		$this->assertEquals('GOOD', _tpl( '{if("sub.key1" eq "val1" and "sub.key2" ne "gggg")}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1', 'key2' => 'val2')) ));
	}
	public function test_122() {
		define('MY_TEST_CONST_1', '42');
		$this->assertEquals('GOOD', _tpl( '{if("sub.key1" eq "val1" and "const.MY_TEST_CONST_1" eq "42")}GOOD{else}BAD{/if}', array('sub' => array('key1' => 'val1')) ));
	}
	public function test_123() {
		$this->assertEquals('GOOD', _tpl( '{if("%string" eq "string")}GOOD{else}BAD{/if}' ));
	}
}