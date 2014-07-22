<?php

require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_yf_extend_test extends tpl_abstract {
	public function test_extend() {
		$old = tpl()->_custom_patterns;
		tpl()->_custom_patterns = array();
		$this->assertEmpty(tpl()->_custom_patterns);

		$this->assertEquals('{my_new_tpl_func(testme)}', self::_tpl('{my_new_tpl_func(testme)}'));
		tpl()->add_pattern_callback('/\{my_new_tpl_func\(\s*["\']{0,1}([a-z0-9_:\.]+?)["\']{0,1}\s*\)\}/i', function($m, $r, $name, $_this) {
			return '__'.$m[1].'__';
		});
		$this->assertEquals('__testme__', self::_tpl('{my_new_tpl_func(testme)}'));
		tpl()->_custom_patterns = array();
		$this->assertEmpty(tpl()->_custom_patterns);

		$this->assertEquals('{my_new_tpl_func(testme)}', self::_tpl('{my_new_tpl_func(testme)}'));
		tpl()->add_function_callback('my_new_tpl_func', function($m, $r, $name, $_this) {
			return '__'.$m['args'].'__';
		});
		$this->assertEquals('__testme__', self::_tpl('{my_new_tpl_func(testme)}'));

		$this->assertEquals('{my_new_tpl_section(k1=v1;k2=v2)} section_body {/my_new_tpl_section}', self::_tpl('{my_new_tpl_section(k1=v1;k2=v2)} section_body {/my_new_tpl_section}'));
		tpl()->add_section_callback('my_new_tpl_section', function($m, $r, $name, $_this) {
			return '__'.$m['args'].'__'.$m['body'].'__';
		});
		$this->assertEquals('__k1=v1;k2=v2__section_body__', self::_tpl('{my_new_tpl_section(k1=v1;k2=v2)} section_body {/my_new_tpl_section}'));

		tpl()->_custom_patterns = $old;
	}
}