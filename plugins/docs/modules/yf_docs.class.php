<?php

class yf_docs {

	private $whats_new = [
		'demo',
	];

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
			return _class('core_api')->get_github_link($m[2]);
		});
		tpl()->add_function_callback('self_source', function($m, $r, $name, $_this) {
#			return _class('core_api')->get_method_source($m[1], $m[2]);
		});
	}

	/***/
	public function show() {
		$methods = [];
		foreach (get_class_methods($this) as $m) {
			if ($m[0] === '_' || $m === __FUNCTION__) {
				continue;
			}
			$methods[$m] = $m;
		}
		$id = $_GET['id'];
		if ($id) {
			$func = in_array($id, $methods) ? $id : 'misc';
			return $this->$func();
		}
		$a = [];
		foreach ($methods as $m) {
			$a[$m] = '<h4><a href="'.url('/@object/'.$m).'">'. ucfirst($m). '</a>'. (in_array($m, $this->whats_new) ? ' <sup class="text-success"><small>NEW</small></sup>' : ''). '</h4>';
		}
		ksort($a);
		return implode(PHP_EOL, $a);
	}

	/***/
	public function _show_for($obj, $id = '') {
		$id = $id ?: $_GET['id'];
		$action = $_GET['action'];
		if (preg_match('~^[a-z0-9_]+$~ims', $id)) {
			$only_method = strtolower($id);
		}
		$methods = [];
		foreach(get_class_methods($obj) as $name) {
			if ($name == 'show' || substr($name, 0, 1) == '_') {
				continue;
			}
			$methods[$name] = $name;
		}
		sort($methods);
		if (!$only_method) {
			$only_method = current($methods);
		}
		$url = url('/@object');
		foreach ((array)$methods as $name) {
			if ($only_method && $only_method !== $name) {
				continue;
			}
			$self_source	= _class('core_api')->get_method_source($obj, $name);
			$target_source	= _class('core_api')->get_method_source(_class('html'), $name);
			$target_docs	= _class('core_api')->get_method_docs('html', $name);

			$items[] = 
				'<div id="head_'.$name.'" class="panel">
	                <div class="panel-heading">
						<h1 class="panel-title">
							<a href="'.url('/@object/@action/'.$name).'">'.$name.'</a>
							<div class="pull-right">
								<button class="btn btn-primary btn-xs" data-toggle="collapse" data-target="#func_self_source_'.$name.'"><i class="fa fa-file-text-o"></i> '.$name.'()</button> '
								.($target_source['source'] ? ' <button class="btn btn-primary btn-xs" data-toggle="collapse" data-target="#func_target_source_'.$name.'"><i class="fa fa-file-text-o"></i> _class("'.$action.'")-&gt;'.$name.'()</button> ' : '')
								._class('core_api')->get_github_link($action.'.'.$name)
								.($target_docs ? ' <button class="btn btn-primary btn-xs" data-toggle="collapse" data-target="#func_target_docs_'.$name.'">'.$action.'::'.$name.' docs</button> ' : '')
							.'</div>
						</h1>
					</div>
					<div id="func_self_source_'.$name.'" class="panel-body collapse out"><pre class="prettyprint lang-php"><code>'._prepare_html($self_source['source']).'</code></pre></div> '
					.($target_source['source'] ? '<div id="func_target_source_'.$name.'" class="panel-body collapse out"><pre class="prettyprint lang-php"><code>'.(_prepare_html($target_source['source'])).'</code></pre></div> ' : '')
					.($target_docs ? '<div id="func_target_docs_'.$name.'" class="panel-body collapse out">'._class('html')->well(nl2br($target_docs)).'</div> ' : '')
				.'</div>
				<section class="page-contents" id="func_out_'.$name.'">'.$obj->$name().'</section>
			';
		}
		return implode(PHP_EOL, $items);
	}

	/***/
	public function _subclass($name) {
		return _class('sample_'.$name, YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
	public function assets() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function services() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function form() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function table() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function html() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function main() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function common() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function graphics() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function cache() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function dir() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function utils() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function aliases() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function functions() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function console_tool() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function db() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function db_query_builder() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function db_utils() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function db_migrator() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function model() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function core_api() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function demo() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function misc() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function validate() {
		return $this->_subclass(__FUNCTION__);
	}

	/***/
	public function all() {
		return $this->_subclass(__FUNCTION__);
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
		$names = [];

		$ext = '.class.php';
		$ext_len = strlen($ext);
		$globs = [
			'yf_dev_classes'	=> YF_PATH.'.dev/samples/classes/*'.$ext,
			'yf_dev_form2'		=> YF_PATH.'.dev/samples/form2/*'.$ext,
			'yf_dev_table2'		=> YF_PATH.'.dev/samples/table2/*'.$ext,
#			'app'		=> APP_PATH.'modules/*'.$ext,
#			'project'	=> PROJECT_PATH.'modules/*'.$ext,
		];
		$names = [];
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
		$links = [];
		foreach ($names as $name) {
			if (substr($name, 0, strlen('sample_')) === 'sample_') {
				$name = substr($name, strlen('sample_'));
			}
			$url = '/';
			if (substr($name, 0, strlen('table2_')) === 'table2_') {
				$url = '/@object/table/'. $name;
			} elseif (substr($name, 0, strlen('form2_')) === 'form2_') {
				$url = '/@object/form/'. $name;
			} else {
				$url = '/@object/'. $name;
			}
			$links[url($url)] = t($name). (in_array($name, $this->whats_new) ? ' <sup class="text-success"><small>NEW</small></sup>' : '');
		}
		return html()->navlist($links);
	}

	/***/
	public function _sample_navbar() {
		return implode(PHP_EOL, [
			form_item()->country_box(['selected' => 'US', 'renderer' => 'div_box']),
			form_item()->language_box(['selected' => 'ru', 'renderer' => 'div_box']),
			form_item()->currency_box(['selected' => 'UAH', 'renderer' => 'div_box']),
			form_item()->timezone_box(['selected' => 'UTC', 'renderer' => 'div_box']),
		]);
	}
}
