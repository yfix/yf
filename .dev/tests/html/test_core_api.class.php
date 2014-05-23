<?php

class test_core_api {
	function _hook_side_column() {
		$methods = array();
		foreach (get_class_methods($this) as $name) {
			if ($name[0] == '_' || $name == 'show') {
				continue;
			}
			$methods[$name] = array(
				'name'	=> $name,
#				'link'	=> url('/'.__CLASS__.'/'.$name),
				'link'	=> './?object='.__CLASS__.'&action='.$name,
			);
		}
		return _class('html')->navlist($methods);
	}
	function show() {
	}
	function get_classes() {
		$data = array();
		foreach (glob(YF_PATH.'classes/*.class.php') as $v) {
			$name = substr(basename($v), 0, -strlen('.class.php'));
			if (substr($name, 0, strlen(YF_PREFIX)) == YF_PREFIX) {
				$name = substr($name, strlen(YF_PREFIX));
			}
#<a href="https://github.com/yfix/yf"><i class="icon icon-github icon-large"></i></a> 
			$data[$name] = '<a href="./?object='.__CLASS__.'&action=get_methods&id='.$name.'">'.$name.'</a>';
		}
		return _class('html')->li($data);
	}
	function get_methods() {
	}
	function get_modules_user() {
	}
	function get_modules_admin() {
	}
#	function get_submodules_user() {
#	}
#	function get_submodules_admin() {
#	}
}
