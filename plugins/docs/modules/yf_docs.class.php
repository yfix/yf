<?php

class yf_docs {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Catch all methods calls
	*/
	public function _module_action_handler($name) {
		if (method_exists($this, $name)) {
			return $this->$name();
		} else {
			$_GET['id'] = $name;
			return $this->view();
		}
	}

	/**
	*/
	public function _init() {
		_class('core_api')->add_syntax_highlighter();

		$this->docs_dir = YF_PATH.'.dev/docs/';
		$this->demo_dir = YF_PATH.'.dev/demo/';

		tpl()->add_function_callback('github', function($m, $r, $name, $_this) {
			return _class('core_api')->get_github_link($m[1]);
		});
	}

	/***/
	public function show() {
		if ($_GET['id']) {
			return $this->misc();
		}
		$a = array();
		foreach (get_class_methods($this) as $m) {
			if ($m[0] === '_' || $m === __FUNCTION__) {
				continue;
			}
			$a[$m] = '<h4><a href="'.url('/@object/'.$m).'">'.ucfirst($m).'</a></h4>';
		}
		return implode(PHP_EOL, $a);
	}

	/***/
	public function assets() {
		return _class('sample_assets', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function services() {
		return _class('sample_services', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function form() {
		return _class('sample_form', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function table() {
		return _class('sample_table', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function html() {
		return _class('sample_html', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function common() {
		return _class('sample_common', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function main() {
		return _class('sample_main', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function dir() {
		return _class('sample_dir', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function utils() {
		return _class('sample_utils', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function aliases() {
		return _class('sample_aliases', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function functions() {
		return _class('sample_functions', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function console_tool() {
		return _class('sample_console_tool', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function db() {
		return _class('sample_db', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function db_query_builder() {
		return _class('sample_db_query_builder', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function db_utils() {
		return _class('sample_db_utils', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function db_migrator() {
		return _class('sample_db_migrator', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function model() {
		return _class('sample_model', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function core_api() {
		return _class('sample_core_api', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function demo() {
		return _class('sample_demo', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function misc() {
		return _class('sample_misc', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function _github_link($path = '') {
		if (!strlen($path)) {
			return '';
		}
		$path = realpath($path);
		$yf_path = realpath(YF_PATH);
		$yf_len = strlen($yf_path);
		if (substr($path, 0, $yf_len) === $yf_path) {
			$path = substr($path, $yf_len);
		}
		$link = 'https://github.com/yfix/yf/tree/master/'. trim($path, '/');
		return '<i class="fa fa-github fa-lg"></i> <a href="'.$link.'">'.$link.'</a>';
	}

	/***/
	public function _hook_side_column() {
		$custom_class_name = 'sample_'.$_GET['action'];
		$custom_obj = _class_safe($custom_class_name);
		$hook_name = __FUNCTION__;
		// Try to load side column hook from subclass
		if ($_GET['action'] && is_object($custom_obj) && method_exists($custom_obj, $hook_name)) {
			// class should be instantinated with full path before this
			$custom = $custom_obj->$hook_name();
			if ($custom) {
				return $custom;
			}
		}
		$url = url('/@object');
		$names = array();

		$ext = '.class.php';
		$ext_len = strlen($ext);
		$globs = array(
			'yf_dev_form2'		=> YF_PATH.'.dev/samples/form2/*'.$ext,
			'yf_dev_table2'		=> YF_PATH.'.dev/samples/table2/*'.$ext,
			'yf_dev_classes'	=> YF_PATH.'.dev/samples/classes/*'.$ext,
#			'app'		=> APP_PATH.'modules/*'.$ext,
#			'project'	=> PROJECT_PATH.'modules/*'.$ext,
		);
		$names = array();
		foreach ($globs as $glob) {
			foreach (glob($glob) as $cls) {
				$cls = basename($cls);
				if ($cls == __CLASS__) {
					continue;
				}
				$name = substr($cls, 0, -$ext_len);
				$names[$name] = $name;
			}
		}
		$links = array();
		foreach ($names as $name) {
			$url = '/';
			if (substr($name, 0, strlen('table2_')) === 'table2_') {
				$url = '/@object/table/'. $name;
			} elseif (substr($name, 0, strlen('form2_')) === 'form2_') {
				$url = '/@object/form/'. $name;
			} elseif (substr($name, 0, strlen('sample_html')) === 'sample_html') {
				$url = '/@object/html/';
			} else {
				$url = '/@object/misc/'. $name;
			}
			$links[url($url)] = t($name);
		}
		return html()->navlist($links);
	}

	/***/
	public function _sample_navbar() {
		return _class('form2_navbar', YF_PATH.'.dev/samples/form2/')->show($source = false);
	}
}
