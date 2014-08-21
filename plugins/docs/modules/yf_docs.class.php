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

		tpl()->add_function_callback('github', function($m, $r, $name, $_this) {
			return _class('core_api')->get_github_link($m[1]);
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
			$data[++$i] = array(
				'name'	=> $name,
				'link'	=> './?object='.$_GET['object'].'&action=show&id='.$name,
			);
		}
		return _class('html')->tree($data, array('draggable' => false));
	}

	/***/
	function _hook_side_column() {
		$items = array();
		$url = process_url('./?object='.$_GET['object']);
		foreach (array_merge((array)glob(APP_PATH.'modules/*.class.php'),(array)glob(PROJECT_PATH.'modules/*.class.php')) as $cls) {
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