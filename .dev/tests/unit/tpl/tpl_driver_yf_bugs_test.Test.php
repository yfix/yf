<?php

require_once __DIR__ . '/tpl__setup.php';

class tpl_driver_yf_bugs_test extends tpl_abstract
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
    public function test_bug_01()
    {
        $this->assertEquals('#description ', self::_tpl('#description {execute(main,_show_block123123)}', ['description' => 'test']));
    }
    public function test_bug_02()
    {
        $this->assertEquals(' {} ', self::_tpl(' {} ', ['' => '']));
    }
    public function test_bug_03()
    {
        $a = ['quantity' => 10, 'active' => 1];
        $this->assertEquals(' ok ', self::_tpl('{if("quantity" gt 0)} ok {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if("active" ne 0)} ok {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if("quantity" gt "0" and "active" ne "0")} ok {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if(quantity gt 0 and active ne 0)} ok {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if("quantity" gt 0 and active ne 0)} ok {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if("quantity" gt 0 and "active" ne 0)} ok {/if}', $a));

        $a = ['quantity' => 10, 'active' => 0];
        $this->assertEquals('', self::_tpl('{if("quantity" gt 0 and "active" ne 0)} ok {/if}', $a));
        $a = ['quantity' => 0, 'active' => 0];
        $this->assertEquals('', self::_tpl('{if("quantity" gt 0 and "active" ne 0)} ok {/if}', $a));
    }
    public function test_bug_04()
    {
        conf('unit_test_conf_item1', '5');
        $this->assertEquals('5', conf('unit_test_conf_item1'));
        conf('unit_test_conf_item2', '6');
        $this->assertEquals('6', conf('unit_test_conf_item2'));
        module_conf('main', 'unit_var1', '4');
        $this->assertEquals('4', module_conf('main', 'unit_var1'));
        module_conf('main', 'unit_var2', '5');
        $this->assertEquals('5', module_conf('main', 'unit_var2'));

        $this->assertEquals(' ok ', self::_tpl('{if(conf.unit_test_conf_item1 eq "5" and conf.unit_test_conf_item2 eq "6")} ok {/if}'));
        $this->assertEquals(' ok ', self::_tpl('{if(conf.unit_test_conf_item1 eq 5 and conf.unit_test_conf_item2 eq 6)} ok {/if}'));
        $this->assertEquals(' ok ', self::_tpl('{if(conf.unit_test_conf_item1 eq 1 or conf.unit_test_conf_item2 eq 6)} ok {/if}'));
        $this->assertEquals(' ok ', self::_tpl('{if(conf.unit_test_conf_item1 ne 1 or conf.unit_test_conf_item2 ne 1)} ok {/if}'));

        $this->assertEquals(' ok ', self::_tpl('{if(conf.unit_test_conf_item2 eq "6" and module_conf.main.unit_var2 eq "5")} ok {/if}'));
        $this->assertEquals(' ok ', self::_tpl('{if(conf.unit_test_conf_item2 eq 6 and module_conf.main.unit_var2 eq 5)} ok {/if}'));
    }
    public function test_bug_05()
    {
        $this->assertEquals('.min', self::_tpl('{if_false(debug_mode)}.min{/if}', ['debug_mode' => 0]));

        $tpl_str = '{catch(min_ext)}{if_false(debug_mode)}.min{/if}{/catch}{min_ext}';
        $this->assertEquals('.min', self::_tpl($tpl_str, ['debug_mode' => 0]));
        $this->assertEquals('', self::_tpl($tpl_str, ['debug_mode' => 1]));
    }
    public function test_bug_06()
    {
        $tpl_str = '
			{catch(min_ext)}{if_false(debug_mode)}.min{/if}{/catch}
			{if(css_framework eq "bs2" or css_framework eq "")}
				<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap{min_ext}.js"></script>
			{else}
				<script src="//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap{min_ext}.js"></script>
			{/if}
		';
        $this->assertEquals('<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.js"></script>', trim(self::_tpl($tpl_str, ['css_framework' => 'bs2', 'debug_mode' => 1])));
        $this->assertEquals('<script src="//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.js"></script>', trim(self::_tpl($tpl_str, ['css_framework' => 'bs3', 'debug_mode' => 1])));
        $this->assertEquals('<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>', trim(self::_tpl($tpl_str, ['css_framework' => 'bs2', 'debug_mode' => 0])));
        $this->assertEquals('<script src="//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>', trim(self::_tpl($tpl_str, ['css_framework' => 'bs3', 'debug_mode' => 0])));
    }
    public function test_bug_07()
    {
        self::_tpl('Hello1', [], 'unittest_include1');
        self::_tpl('Hello2', [], 'unittest_include2');
        self::_tpl('Hello3', [], 'unittest_include3');
        $this->assertEquals('Hello1 Hello1 Hello1', self::_tpl('{include("unittest_include1")} {include("unittest_include1")} {include("unittest_include1")}'));
    }
    public function test_bug_08()
    {
        $this->assertEquals('', self::_tpl('{foreach(0)}</ul>{/foreach}'));
        $this->assertEquals('</ul>', self::_tpl('{foreach(1)}</ul>{/foreach}'));
        $this->assertEquals('</ul></ul>', self::_tpl('{foreach(2)}</ul>{/foreach}'));
        $this->assertEquals('</ul></ul></ul>', self::_tpl('{foreach(3)}</ul>{/foreach}'));
        $this->assertEquals('</ul></ul></ul>', self::_tpl('{foreach( 3 )}</ul>{/foreach}'));
        $this->assertEquals(str_repeat('</ul>', 100), self::_tpl('{foreach(100)}</ul>{/foreach}'));

        $this->assertEquals('', self::_tpl('{foreach(next_level_diff)}</ul>{/foreach}'));
        $this->assertEquals('', self::_tpl('{foreach(next_level_diff)}</ul>{/foreach}', ['next_level_diff' => 0]));
        $this->assertEquals('</ul>', self::_tpl('{foreach(next_level_diff)}</ul>{/foreach}', ['next_level_diff' => 1]));
        $this->assertEquals('</ul></ul>', self::_tpl('{foreach(next_level_diff)}</ul>{/foreach}', ['next_level_diff' => 2]));
        $this->assertEquals('</ul></ul></ul>', self::_tpl('{foreach("next_level_diff")}</ul>{/foreach}', ['next_level_diff' => 3]));
        $this->assertEquals('</ul></ul></ul>', self::_tpl('{foreach( "next_level_diff" )}</ul>{/foreach}', ['next_level_diff' => 3]));
        $this->assertEquals('</ul></ul></ul>', self::_tpl('{foreach(next_level_diff)}</ul>{/foreach}', ['next_level_diff' => 3]));
        $this->assertEquals(str_repeat('</ul>', 100), self::_tpl('{foreach(next_level_diff)}</ul>{/foreach}', ['next_level_diff' => 100]));
        $this->assertEquals('</ul></ul></ul>', self::_tpl('{foreach(data.next_level_diff)}</ul>{/foreach}', ['data' => ['next_level_diff' => 3]]));
    }
    public function test_bug_09()
    {
        $this->assertEquals('{foreach()}1{/foreach}', self::_tpl('{foreach()}1{/foreach}'));
        $this->assertEquals('', self::_tpl('{foreach(items)}1{/foreach}'));
        $this->assertEquals('', self::_tpl('{foreach(items)}1{/foreach}', ['items' => 0]));
        $this->assertEquals('', self::_tpl('{foreach(items)}1{/foreach}', ['items' => []]));
        $this->assertEquals('111', self::_tpl('{foreach(items)}1{/foreach}', ['items' => 3]));
        $this->assertEquals('111', self::_tpl('{foreach(items)}1{/foreach}', ['items' => [0, 1, 2]]));
        $this->assertEquals('111', self::_tpl('{foreach(items)}1{/foreach}', ['items' => [1, 2, 3]]));
        $this->assertEquals('111', self::_tpl('{foreach(items)}1{/foreach}', ['items' => range(1, 3)]));
        $this->assertEquals('111', self::_tpl('{foreach(items)}1{/foreach}', ['items' => [['k' => 'v'], ['k' => 'v'], ['k' => 'v']]]));

        $this->assertEquals('111,222', self::_tpl('{foreach(items)}1{/foreach},{foreach(items)}2{/foreach}', ['items' => range(1, 3)]));
        $this->assertEquals('111,222,333', self::_tpl('{foreach(items)}1{/foreach},{foreach(items)}2{/foreach},{foreach(items)}3{/foreach}', ['items' => range(1, 3)]));
        $this->assertEquals('1111,2222,3333', self::_tpl('{foreach(items)}1{/foreach},{foreach(items)}2{/foreach},{foreach(items)}3{/foreach}', ['items' => range(1, 4)]));
        $this->assertEquals('1111,2222,1111', self::_tpl('{foreach(items)}1{/foreach},{foreach(items)}2{/foreach},{foreach(items)}1{/foreach}', ['items' => range(1, 4)]));
    }
    public function test_bug_10()
    {
        $data = [
            ['k1' => 'v1'],
            ['k1' => 'v2'],
            ['k2' => 'v22'],
            ['k3' => 'v33'],
        ];
        $this->assertEquals(' 0=v1 _v1_  1=v2 _v2_  2=v22   3=v33  ', self::_tpl('{foreach(data)} {_key}={_val} {if(#.k1 ne "")}_{#.k1}_{/if} {/foreach}', ['data' => $data]));
    }
    public function test_bug_11()
    {
        $this->assertEquals('{form}', self::_tpl('{form}', []));
        $data = ['form' => form(['name' => 'val'])->text('name')];
        $this->assertNotEquals('{form}', self::_tpl('{form}', $data));
        $this->assertGreaterThan(100, strlen(self::_tpl('{form}', $data)));

        $data = new stdClass();
        $data->key1 = 'val1';
        $data->form = form()->text('name');

        //		$this->assertEquals('', self::_tpl('{data.form}', array()));
        $this->assertEquals('{data.form}', self::_tpl('{data.form}', []));
        $this->assertNotEquals('{data.form}', self::_tpl('{data.form}', ['data' => $data]));
        $this->assertGreaterThan(100, strlen(self::_tpl('{data.form}', ['data' => $data])));
    }
    public function test_bug_12()
    {
        $a = ['var1' => 10, 'var2' => 1];
        $this->assertEquals(' ok ', self::_tpl('{if("var1" gt "var2")} ok {else} ko {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if(var1 gt var2)} ok {else} ko {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if("var1" lt "var2")} ko {else} ok {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if(var1 lt var2)} ko {else} ok {/if}', $a));
        $a = ['var1' => '10', 'var2' => '1'];
        $this->assertEquals(' ok ', self::_tpl('{if("var1" gt "var2")} ok {else} ko {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if(var1 gt var2)} ok {else} ko {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if("var1" lt "var2")} ko {else} ok {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if(var1 lt var2)} ko {else} ok {/if}', $a));
        $a = ['var1' => '10000', 'var2' => '2300'];
        $this->assertEquals(' ok ', self::_tpl('{if("var1" gt "var2")} ok {else} ko {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if(var1 gt var2)} ok {else} ko {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if("var1" lt "var2")} ko {else} ok {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if(var1 lt var2)} ko {else} ok {/if}', $a));
        $a = ['var1' => '10000.00', 'var2' => '2300.00'];
        $this->assertEquals(' ok ', self::_tpl('{if("var1" gt "var2")} ok {else} ko {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if(var1 gt var2)} ok {else} ko {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if("var1" lt "var2")} ko {else} ok {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if(var1 lt var2)} ko {else} ok {/if}', $a));
    }
    public function test_bug_13()
    {
        $a = ['var1' => 10, 'var2' => 10];
        $this->assertEquals(' ok ', self::_tpl('{if(var3 eq "")} ok {else} ko {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if(var3 ne "")} ko {else} ok {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if(var1 eq var2)} ok {else} ko {/if}', $a));
        $this->assertEquals(' ok ', self::_tpl('{if(var1 ne "")} ok {else} ko {/if}', $a));
    }
    public function test_bug_14()
    {
        $this->assertFalse(defined('MY_NOT_EXISTING_CONST'));
        $this->assertEquals('GOOD', self::_tpl('{if(const.MY_NOT_EXISTING_CONST eq 0)}GOOD{else}BAD{/if}'));
        $this->assertEquals('GOOD', self::_tpl('{if_empty(const.MY_NOT_EXISTING_CONST)}GOOD{else}BAD{/if}'));
        $this->assertEquals('GOOD', self::_tpl('{if_not_isset(const.MY_NOT_EXISTING_CONST)}GOOD{else}BAD{/if}'));
    }
}
