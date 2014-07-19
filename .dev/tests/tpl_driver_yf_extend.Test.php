<?php

require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_yf_extend_test extends tpl_abstract {
	public function test_url() {
#		tpl()->add_pattern_callback('/\{github\(\s*["\']{0,1}([a-z0-9_:\.]+?)["\']{0,1}\s*\)\}/i', function($m, $r, $name, $_this) {
#			return _class('core_api')->get_github_link($m[1]);
#		});
#		$this->assertEquals('', self::_tpl(''));
	}
}