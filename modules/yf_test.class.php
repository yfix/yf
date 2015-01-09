<?php

class yf_test {

	/**
	*/
	function true_for_unittest($out = '') {
		return $out ? (is_array($out) ? implode(',', $out) : $out) : 'true';
	}

	/**
	*/
	function show() {
		if (!DEBUG_MODE) {
			return;
		}
		$methods = array();
		$class_name = get_class($this);
		foreach ((array)get_class_methods($class_name) as $_method_name) {
			if ($_method_name{0} == '_' || $_method_name == $class_name || $_method_name == __FUNCTION__) {
				continue;
			}
			$methods[$_method_name] = './?object='.$_GET['object'].'&action='.$_method_name;
		}
		$body[] = '<ul class="nav nav-list span3">';
		foreach ((array)$methods as $name => $link) {
			$body[] = '<li><a href="'.$link.'"><i class="icon-chevron-right fa fa-chevron-right"></i> '.$name.'</a></li>';
		}
		$body[] = '</ul>';
		return implode(PHP_EOL, $body);
	}

	/**
	*/
	function change_debug() {
		if (!DEBUG_MODE) {
			return;
		}
		if (main()->is_post()) {
			$_SESSION['stpls_inline_edit']		= intval((bool)$_POST['stpl_edit']);
			$_SESSION['locale_vars_edit']		= intval((bool)$_POST['locale_edit']);
			return js_redirect($_SERVER['HTTP_REFERER'], 0);
		}
		$a = $_POST + $_SESSION;
		return form($a)
			->active_box('locale_edit', array('selected' => $_SESSION['locale_vars_edit']))
//			->active_box('stpl_edit', array('selected' => $_SESSION['stpls_inline_edit']))
			->save()
		;
	}

	/**
	*/
	function oauth($params = array()) {
		return module('login_form')->oauth($params);
	}

	/**
	* Css frameworks lister
	*/
	function html5fw($params = array()) {
		main()->no_graphics(true);
		_class('assets')->clean_all();
		if (!$params['use_yf_fixes'] && !$_GET['use_yf_fixes']) {
			_class('assets')->MAIN_TPL_JS = '';
			_class('assets')->MAIN_TPL_CSS = '';
		}
		asset('bootstrap-theme');

		$links = array();
		$prefix = 'html5fw_';
		foreach (get_class_methods($this) as $name) {
			if ($name[0] !== '_' && substr($name, 0, strlen($prefix)) === $prefix) {
				$links[url('/@object/'.$name)] = substr($name, strlen($prefix));
			}
		}
		$body = _class('html')->navlist($links);
		echo '<html><head>'._class('assets')->show_css().'</head><body>'. $body. _class('assets')->show_js(). '</body></html>';
	}

	/**
	*/
	function html5fw_empty($params = array()) {
		return $this->_html5fw_test('empty', function($fw, $params) { }, $params);
	}

	/**
	*/
	function html5fw_bs2($params = array()) {
		return $this->_html5fw_test('bs2', function($fw, $params) {
			asset('bootstrap-theme');
		}, $params);
	}

	/**
	*/
	function html5fw_bs3($params = array()) {
		return $this->_html5fw_test('bs3', function($fw, $params) {
			asset('bootstrap-theme');
		}, $params);
	}

	/**
	*/
	function html5fw_foundation($params = array()) {
		return $this->_html5fw_test('foundation', function($fw, $params) {
			asset('foundation');
		}, $params);
	}

	/**
	*/
	function html5fw_pure($params = array()) {
		return $this->_html5fw_test('pure', function($fw, $params) {
			asset('purecss');
		}, $params);
	}

	/**
	*/
	function html5fw_semantic_ui($params = array()) {
		return $this->_html5fw_test('semantic_ui', function($fw, $params) {
			asset('semantic-ui');
		}, $params);
	}

	/**
	*/
	function html5fw_uikit($params = array()) {
		return $this->_html5fw_test('uikit', function($fw, $params) {
			asset('uikit');
		}, $params);
	}

	/**
	*/
	function html5fw_maxmert($params = array()) {
		return $this->_html5fw_test('maxmert', function($fw, $params) {
			asset('maxmertkit');
		}, $params);
	}

	/**
	* Css frameworks acceptance testing unified method
	*/
	function _html5fw_test($fw = 'bs2', $callback, $params = array()) {
		main()->no_graphics(true);
		_class('assets')->clean_all();
		if (!$params['use_yf_fixes'] && !$_GET['use_yf_fixes']) {
			_class('assets')->MAIN_TPL_JS = '';
			_class('assets')->MAIN_TPL_CSS = '';
		}
		conf('css_framework', $fw);

		$callback($fw, $params);

		$method = $_GET['id'];
		$obj = _class('test_html5fw_'.$fw, 'modules/test/');
		if (!$method) {
			$links = array();
			foreach (get_class_methods($obj) as $name) {
				if ($name[0] !== '_') {
					$links[url('/@object/@action/'.$name)] = $name;
				}
			}
			$body = _class('html')->navlist($links);
		} else {
			$body = $obj->$method($params);
		}
		echo '<html><head>'._class('assets')->show_css().'</head><body>'. $body. _class('assets')->show_js(). '</body></html>';
	}
}
