<?php

$_user_modules_methods = _class('user_modules', 'admin_modules/')->_get_methods(array('private' => '1'));
foreach ((array)$_user_modules_methods as $module_name => $module_methods) {
	foreach ((array)$module_methods as $method_name) {
		if (substr($method_name, 0, 8) != '_widget_') {
			continue;
		}
		$data[$module_name][$method_name] = _class($module_name, 'modules/')->$method_name(array('describe' => '1'));
	}
}
