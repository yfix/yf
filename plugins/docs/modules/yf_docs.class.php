<?php

class yf_docs {

	/**
	* Catch all methods calls
	*/
	function _module_action_handler($name) {
		if (method_exists($this, $name)) {
			return $this->$name();
		} else {
			$_GET['id'] = $name;
			return $this->view();
		}
	}

	/**
	*/
	function _init() {
		_class('core_api')->add_syntax_highlighter();

		$this->docs_dir = YF_PATH.'.dev/docs/en/';

		tpl()->add_pattern_callback('/\{github\(\s*["\']{0,1}([a-z0-9_:\.]+?)["\']{0,1}\s*\)\}/i', function($m, $r, $name, $_this) {
			$body = trim($m[1]);
// TODO: find correct line
// TODO: find correct path
			if (false !== strpos($body, '.')) {
				list($class, $method) = explode('.', $body);
				$line = 100;
				$path = 'classes/yf_'.$class.'.php#L'.$line;
			} else {
				$line = 100;
				// Function, maybe inside common_funcs...
				$path = 'share/functions/yf_common_funcs.php#L'.$line;
			}
			return '<a href="https://github.com/yfix/yf'.$path.'" class="btn btn-mini btn-xs btn-primary pull-right"><i class="icon icon-github icon-large"></i></a>';
		});
	}

	/***/
	function view() {
		$name = preg_replace('~[^a-z0-9_-]+~ims', '', $_GET['id']);
		if ($name) {
			$f = $this->docs_dir. $name. '.stpl';
			if (file_exists($f)) {
				return '<section class="page-contents">'.tpl()->parse_string(file_get_contents($f), $replace, 'doc_'.$name).'</section>';
			}
		}
		return _e('Not found');
	}

	/***/
	function show() {
		if ($_GET['id']) {
			return $this->view();
		}
		foreach (glob($this->docs_dir.'*.stpl') as $path) {
			$f = basename($path);
			$name = substr($f, 0, -strlen('.stpl'));
			$body[] = '<li><a href="./?object='.$_GET['object'].'&action=show&id='.$name.'">'.$name.'</a></li>';
		}
		return implode(PHP_EOL, $body);
	}

	/***/
	function _hook_side_column() {
		$items = array();
		$url = process_url('./?object='.$_GET['object']);
		foreach ((array)glob(PROJECT_PATH.'modules/*.class.php') as $cls) {
			$cls = basename($cls);
			if ($cls == __CLASS__) {
				continue;
			}
			$name = substr($cls, 0, -strlen('.class.php'));
			$items[] = '<li><a href="./?object='.$name.'"><i class="icon-chevron-right"></i> '.t($name).'</a></li>';
		}
		return '<div class="bs-docs-sidebar"><ul class="nav nav-list bs-docs-sidenav">'.implode(PHP_EOL, $items).'</ul></div>';
	}
}