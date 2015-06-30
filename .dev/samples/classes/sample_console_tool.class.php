<?php

class sample_console_tool {

	/***/
	function _init() {
		_class('core_api')->add_syntax_highlighter();
	}

	/***/
	function _hook_side_column() {
		$items = array();
		$url = url('/@object');
		$methods = $this->_get_console_commands();
		$sample_methods = get_class_methods($this);
		sort($methods);
		foreach ((array)$sample_methods as $name) {
			if (in_array($name, $methods)) {
				continue;
			}
			$methods[] = $name;
		}
		foreach ((array)$methods as $name) {
			if ($name == 'show' || substr($name, 0, 1) == '_') {
				continue;
			}
			$items[] = array(
				'name'	=> $name. (!in_array($name, $sample_methods) ? ' <sup class="text-error text-danger"><small>TODO</small></sup>' : ''),
				'link'	=> url('/@object/@action/'.$name),
			);
		}
		return _class('html')->navlist($items);
	}

	/***/
	function show() {
// TODO
	}

	/**
	*/
	function _get_console_commands() {
		$cmds = array();
		$subfolder = 'commands/';
		$prefix_project = 'console_';
		$prefix_framework = 'yf_'.$prefix_project;
		$ext = '.class.php';
		$globs = array(
			'project_app'			=> APP_PATH. $subfolder. $prefix_project. '*'. $ext,
			'project_app_plugins'	=> APP_PATH. 'plugins/*/'. $subfolder. $prefix_project. '*'. $ext,
			'project_plugins'		=> PROJECT_PATH. 'plugins/*/'. $subfolder. $prefix_project. '*'. $ext,
			'project_main'			=> PROJECT_PATH. $subfolder. $prefix_project. '*'. $ext,
			'framework_plugins'		=> YF_PATH. 'plugins/*/'. $subfolder. $prefix_framework. '*'. $ext,
			'framework_main'		=> YF_PATH. '.dev/console/'. $subfolder. $prefix_framework. '*'. $ext,
		);
		foreach ($globs as $gname => $glob) {
			foreach (glob($glob) as $path) {
				$name = '';
				$file = basename($path);
				$inside_project = false;
				if (strpos($file, $prefix_framework) === 0) {
					$name = substr($file, strlen($prefix_framework), -strlen($ext));
				} elseif (strpos($file, $prefix_project) === 0) {
					$name = substr($file, strlen($prefix_project), -strlen($ext));
					$inside_project = true;
				}
				if ($name && !isset($cmds[$name])) {
					$cmds[$name] = $name;
				}
			}
		}
		return $cmds;
	}
}