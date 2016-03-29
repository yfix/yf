<?php

require_once __DIR__.'/tpl__setup.php';

class tpl_driver_yf_foreach_test extends tpl_abstract {
	public function return_true($out = '') {
		return $out ? (is_array($out) ? implode(',', $out) : $out) : 'true';
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
			'1). <small>(key: One)</small><b style="color:red;">First!!!</b><br /><span style="color: blue;">name: First<br />, num_items: 4<br /></span>, <br />'.
			'2). <small>(key: Two)</small><span style="color: green;">name: Second<br />, num_items: 4<br /></span>, <br />'.
			'3). <small>(key: Three)</small><span style="color: blue;">name: Third<br />, num_items: 4<br /></span>, <br />'.
			'4). <small>(key: Four)</small><span style="color: green;">name: Fourth<br />, num_items: 4<br /></span>'
		, self::_tpl(
			'{foreach(test_array_2)}'.PHP_EOL.
			'{_num}). <small>(key: {_key})</small>{if(_first eq 1)}<b style="color:red;">First!!!</b><br />{/if}'.PHP_EOL.
			'<span style="{if(_even eq 1)}color: blue;{/if}{if(_odd eq 1)}color: green;{/if}">name: {#.name}<br />, num_items: {_total}<br /></span>{if(_last ne 1)}, <br />{/if}'.PHP_EOL.
			'{/foreach}'
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
	public function test_elseforeach() {
		$data = array('k1' => 'v1', 'k2' => 'v2');
		$this->assertEquals('no rows', self::_tpl('{foreach(data)} {_key}={_val} {elseforeach}no rows{/foreach}', array()));
		$this->assertEquals(' k1=v1  k2=v2 ', self::_tpl('{foreach(data)} {_key}={_val} {elseforeach}no rows{/foreach}', array('data' => $data)));
		$this->assertEquals('no rows', self::_tpl('{foreach(data.sub)} {_key}={_val} {elseforeach}no rows{/foreach}', array()));
		$this->assertEquals(' k1=v1  k2=v2 ', self::_tpl('{foreach(data.sub)} {_key}={_val} {elseforeach}no rows{/foreach}', array('data' => array('sub' => $data))));
		$this->assertEquals('k1k2 k1k2', self::_tpl('{foreach(data.sub)}{_key}{elseforeach}no rows{/foreach} {foreach(data.sub)}{_key}{elseforeach}no rows{/foreach}', array('data' => array('sub' => $data))));
		$data = array('k1' => ' v1 ', 'k2' => ' v2 ');
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
	public function test_foreach_and_cond() {
		$bak = $_GET;
		$_GET = ['cat_id' => '5', 'cat_name' => 'five'];
		$data = ['cats' => ['5' => 'five', '6' => 'six']];
		$this->assertEquals(
			'<a href="#" class="active">5:five</a><a href="#">6:six</a>',
			self::_tpl('{foreach(cats)}<a href="#"{if(get.cat_id eq _key)} class="active"{/if}>{_key}:{_val}</a>{/foreach}', $data)
		);
		$this->assertEquals(
			'<a href="#" class="active">5:five</a><a href="#">6:six</a>',
			self::_tpl('{foreach(cats)}<a href="#"{if(get.cat_name eq _val)} class="active"{/if}>{_key}:{_val}</a>{/foreach}', $data)
		);
		$_GET = $bak;
	}
}