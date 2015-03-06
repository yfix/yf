<?php

/**
* User modules list handler
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_user_modules {

	/** @var array @conf_skip */
	public $_MODULES_TO_SKIP	= array(
		'rewrite',
	);
	/** @var string @conf_skip Pattern for files */
	public $_include_pattern	= array('', '#\.(php|stpl)$#');
	/** @var string @conf_skip Description file pattern */
	public $_desc_file_pattern	= '#[a-z0-9_]\.xml$#i';
	/** @var string @conf_skip Class method pattern */
	public $_method_pattern	= '/function ([a-zA-Z_][a-zA-Z0-9_]+)/is';
	/** @var string @conf_skip Class extends pattern */
	public $_extends_pattern	= '/class (\w+)? extends (\w+)? \{/';
	/** @var bool Parse core 'module' class in get_methods */
	public $PARSE_YF_MODULE	= 0;

	/**
	* Default method
	*/
	function show () {
		$this->refresh_modules_list($silent = true);

		if (main()->is_post()) {
			if (is_array($_POST['name']) && !empty($_POST['name'])) {
				$where = 'name IN("'.implode('","', _es(array_keys($_POST['name']))).'")';
			}
			if ($_POST['activate_selected']) {
				$active = 1;
			} elseif ($_POST['disable_selected']) {
				$active = 0;
			}
			if (isset($active) && $where) {
				db()->update('user_modules', array('active' => $active), $where);
				cache_del(array('user_modules','user_modules_for_select'));
			}
			return js_redirect(url('/@object'));
		}

		if (!isset($this->_yf_plugins)) {
			$this->_yf_plugins = main()->_preload_plugins_list();
			$this->_yf_plugins_classes = main()->_plugins_classes;
		}
		$items = array();
		foreach ((array)db()->get_all('SELECT * FROM '.db('user_modules').' ORDER BY name ASC') as $a) {
			$name = $a['name'];
			$plugin_name = '';
			if (isset($this->_yf_plugins_classes[$name])) {
				$plugin_name = $this->_yf_plugins_classes[$name];
			}
			$locations = array();
			$dir = USER_MODULES_DIR;
			$places = array(
				'framework' => array('dir' => YF_PATH. $dir, 'file' => YF_PREFIX. $name. YF_CLS_EXT),
				'project'	=> array('dir' => ADMIN_SITE_PATH. $dir, 'file' => $name. YF_CLS_EXT),
				'app'		=> array('dir' => APP_PATH. $dir, 'file' => $name. YF_CLS_EXT),
			);
			if ($plugin_name) {
				$places += array(
					'framework_plugin'	=> array('dir' => YF_PATH. 'plugins/'. $plugin_name. '/'. $dir, 'file' => YF_PREFIX. $name. YF_CLS_EXT),
					'project_plugin'	=> array('dir' => PROJECT_PATH. 'plugins/'. $plugin_name. '/'. $dir, 'file' => $name. YF_CLS_EXT),
					'app_plugin'		=> array('dir' => APP_PATH. 'plugins/'. $plugin_name. '/'. $dir, 'file' => $name. YF_CLS_EXT),
				);
			}
			foreach ($places as $pname => $p) {
				if (file_exists($p['dir']. $p['file'])) {
					$locations[$pname] = './?object=file_manager&action=edit_item&f_='.urlencode($p['file']).'&dir_name='.urlencode($p['dir']);
				}
			}
			$items[] = array(
				'name'		=> $a['name'],
				'active'	=> $a['active'],
				'locations'	=> $locations,
			);
		}
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		return table($items, array(
				'condensed' => 1,
				'pager_records_on_page' => 10000,
				'filter' => $_SESSION[$filter_name],
				'filter_params' => array(
					'name' => 'like',
				),
			))
			->form()
			->check_box('name', array('field_desc' => '#', 'width' => '1%'))
			->text('name')
			->func('locations', function($field, $params, $row) {
				foreach ((array)$field as $loc => $link) {
					$out[] = '<a href="'.$link.'" class="btn btn-mini btn-xs">'.$loc.'</a>';
				}
				return implode(PHP_EOL, (array)$out);
			})
			->btn('conf', './?object=conf_editor&action=user_modules&id=%d', array('id' => 'name'))
			->btn_active(array('id' => 'name'))
			->footer_submit(array('value' => 'activate selected'))
			->footer_submit(array('value' => 'disable selected'))
			->footer_link('Refresh list', './?object='.$_GET['object'].'&action=refresh_modules_list', array('icon' => 'icon-refresh fa fa-refresh'))
		;
	}

	/**
	*/
	function active () {
		if (!empty($_GET['id'])) {
			$module_info = db()->query_fetch('SELECT * FROM '.db('user_modules').' WHERE name="'._es($_GET['id']).'" LIMIT 1');
		}
		if (!empty($module_info)) {
			db()->UPDATE('user_modules', array('active' => (int)!$module_info['active']), 'id='.intval($module_info['id']));
		}
		cache_del(array('user_modules','user_modules_for_select'));
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($module_info['active'] ? 0 : 1);
		} else {
			return js_redirect(url('/@object'));
		}
	}

	/**
	* Refresh modules list (try to find modules automatically)
	*/
	function refresh_modules_list ($silent = false) {
		// Cleanup duplicate records
		$q = db()->query('SELECT name, COUNT(*) AS num FROM '.db('user_modules').' GROUP BY name HAVING num > 1');
		while ($a = db()->fetch_assoc($q)) {
			db()->query('DELETE FROM '.db('user_modules').' WHERE name="'._es($a['name']).'" LIMIT '.intval($a['num'] - 1));
		}
		$q = db()->query('SELECT * FROM '.db('user_modules').'');
		while ($a = db()->fetch_assoc($q)) {
			$all_user_modules_array[$a['name']] = $a['name'];
		}

		$refreshed_modules = $this->_get_modules_from_files($include_framework = true, $with_sub_modules = false);

		$insert_data = array();
		foreach ((array)$refreshed_modules as $cur_module_name) {
			if (isset($all_user_modules_array[$cur_module_name])) {
				continue;
			}
			$insert_data[$cur_module_name] = array(
				'name'   => $cur_module_name,
				'active' => 0,
			);
		}
		if ($insert_data) {
			db()->insert('user_modules', db()->es($insert_data));
		}
		// Check for missing modules
		$delete_names = array();
		foreach ((array)$all_user_modules_array as $cur_module_name) {
			if (!isset($refreshed_modules[$cur_module_name])) {
				$delete_names[$cur_module_name] = $cur_module_name;
			}
		}
		if ($delete_names) {
			db()->query('DELETE FROM '.db('user_modules').' WHERE name IN("'.implode('","', _es($delete_names)).'")');
		}
		cache_del(array('user_modules','user_modules_for_select'));
		if (!$silent) {
			return js_redirect(url('/@object'));
		}
	}

	/**
	* Get available user modules
	*/
	function _get_modules ($params = array()) {
		// Need to prevent multiple calls
		if (isset($this->_user_modules_array)) {
			return $this->_user_modules_array;
		}
		$with_all			= isset($params['with_all']) ? $params['with_all'] : 1;
		$with_sub_modules	= isset($params['with_sub_modules']) ? $params['with_sub_modules'] : 0;
		$user_modules_array	= array();
		// Insert value for all modules
		if ($with_all) {
			$user_modules_array[''] = t('-- ALL --');
		}
		$q = db()->query('SELECT * FROM '.db('user_modules').' WHERE active="1"');
		while ($a = db()->fetch_assoc($q)) {
			$user_modules_array[$a['name']] = $a['name'];
		}
		ksort($user_modules_array);
		$this->_user_modules_array = $user_modules_array;
		unset($this->_user_modules_array['']);
		return $user_modules_array;
	}

	/**
	* Get available user modules from the project modules folder
	*/
	function _get_modules_from_files ($include_framework = true, $with_sub_modules = false) {
		$modules = array();

		$yf_prefix_len = strlen(YF_PREFIX);
		$yf_cls_ext_len = strlen(YF_CLS_EXT);
		$site_prefix_len = strlen(YF_SITE_CLS_PREFIX);

		$pattern = USER_MODULES_DIR.'*'.YF_CLS_EXT;
		$places = array();
#$with_sub_modules
/*
			if (!$with_sub_modules) {
				if (false !== strpos(substr($v, strlen($dir_to_scan)), '/')) {
					continue;
				}
			}
*/
		if ($include_framework) {
			$places += array(
				'yf_main'			=> YF_PATH. $pattern,
				'yf_plugins'		=> YF_PATH. 'plugins/*/'. $pattern,
			);
		}
		$places += array(
			'project_main'		=> PROJECT_PATH. $pattern,
			'project_plugins'	=> PROJECT_PATH. 'plugins/*/'. $pattern,
			'app_main'			=> APP_PATH. $pattern,
			'app_plugins'		=> APP_PATH. 'plugins/*/'. $pattern,
		);
		foreach ($places as $place_name => $glob) {
			foreach (glob($glob) as $path) {
				if (substr($path, -$yf_cls_ext_len) !== YF_CLS_EXT) {
					continue;
				}
				$name = substr(basename($path), 0, -$yf_cls_ext_len);
				if (substr($name, 0, $yf_prefix_len) === YF_PREFIX) {
					$name = substr($name, $yf_prefix_len);
				}
				if (substr($name, 0, $site_prefix_len) === YF_SITE_CLS_PREFIX) {
					$module_name = substr($name, $site_prefix_len);
				}
				if (in_array($name, $this->_MODULES_TO_SKIP)) {
					continue;
				}
				if (!strlen($name)) {
					continue;
				}
				$modules[$name] = $name;
			}
		}
		ksort($modules);
		return $modules;
	}

	/**
	* Get available user methods
	*/
	function _get_methods ($params = array()) {
		$ONLY_PRIVATE_METHODS = array();
		if (isset($params['private'])) {
			$ONLY_PRIVATE_METHODS = $params['private'];
		}
		$methods_by_modules = array();
		if (!isset($this->_yf_plugins)) {
			$this->_yf_plugins = main()->_preload_plugins_list();
			$this->_yf_plugins_classes = main()->_plugins_classes;
		}
		if (!isset($this->_user_modules_array)) {
			$this->_get_modules();
		}
		foreach ((array)$this->_user_modules_array as $user_module_name) {
			// Remove site prefix from module name here
			if (substr($user_module_name, 0, strlen(YF_SITE_CLS_PREFIX)) == YF_SITE_CLS_PREFIX) {
				$user_module_name = substr($user_module_name, strlen(YF_SITE_CLS_PREFIX));
			}
			$file_names = array();

			$plugin_name = '';
			if (isset($this->_yf_plugins_classes[$user_module_name])) {
				$plugin_name = $this->_yf_plugins_classes[$user_module_name];
			}

			$tmp = PROJECT_PATH. USER_MODULES_DIR. $user_module_name. YF_CLS_EXT;
			if (file_exists($tmp)) {
				$file_names['project'] = $tmp;
			}
			if ($plugin_name) {
				$tmp = PROJECT_PATH. 'plugins/'. $plugin_name. '/'. USER_MODULES_DIR. $user_module_name. YF_CLS_EXT;
				if (file_exists($tmp)) {
					$file_names['project_plugin'] = $tmp;
				}
			}
			$tmp = YF_PATH. USER_MODULES_DIR. YF_PREFIX. $user_module_name. YF_CLS_EXT;
			if (file_exists($tmp)) {
				$file_names['yf'] = $tmp;
			}
			if ($plugin_name) {
				$tmp = YF_PATH. 'plugins/'. $plugin_name. '/'. USER_MODULES_DIR. YF_PREFIX. $user_module_name. YF_CLS_EXT;
				if (file_exists($tmp)) {
					$file_names['yf_plugin'] = $tmp;
				}
			}
			if (!$file_names) {
				continue;
			}
			foreach ((array)$file_names as $location => $file_name) {
				$file_text = file_get_contents($file_name);
				// Try to get methods from parent classes (if exist one)
				$_methods = $this->_recursive_get_methods_from_extends($file_text, $user_module_name, $ONLY_PRIVATE_METHODS);
				foreach ($_methods as $method_name) {
					$method_name = str_replace(YF_PREFIX, '', $method_name);
					$methods_by_modules[$user_module_name][$method_name] = $method_name;
				}
				// Try to match methods in the current file
				foreach ((array)$this->_get_methods_names_from_text($file_text, $ONLY_PRIVATE_METHODS) as $method_name) {
					$_method_name = '';
					if (substr($method_name, 0, strlen(YF_PREFIX)) == YF_PREFIX) {
						$_method_name = substr($method_name, strlen(YF_PREFIX));
					}
					// Skip constructors in PHP4 style
					if ($_method_name == $user_module_name || $method_name == $user_module_name) {
						continue;
					}
					$methods_by_modules[$user_module_name][$method_name] = $method_name;
				}
			}
		}
		if (is_array($methods_by_modules)) {
			ksort($methods_by_modules);
			foreach ((array)$methods_by_modules as $user_module_name => $methods) {
				if (is_array($methods)) {
					ksort($methods_by_modules[$user_module_name]);
				}
			}
		}
		return $methods_by_modules;
	}

	/**
	* Get methods names from given source text
	*/
	function _recursive_get_methods_from_extends ($file_text = '', $user_module_name = '', $ONLY_PRIVATE_METHODS = false) {
// TODO: need to add 'site__' and 'adm__' functionality
		$extends_file_path = '';
		$methods = array();
		// Check if cur class extends some other class
		if (preg_match($this->_extends_pattern, $file_text, $matches_extends)) {
			$class_name_1 = $matches_extends[1];
			$class_name_2 = $matches_extends[2];
			// Check if we need to extends file from framework
			$_extends_from_fwork = (substr($class_name_2, 0, strlen(YF_PREFIX)) == YF_PREFIX);
			// Check if we parsing current class
			if ($class_name_1 == $user_module_name || str_replace(YF_PREFIX, '', $class_name_1) == $user_module_name) {
				$extends_file_path = YF_PATH. USER_MODULES_DIR. $class_name_2. YF_CLS_EXT;
				// Special processing of the 'yf_module'
				if ($this->PARSE_YF_MODULE && $class_name_2 == YF_PREFIX.'module') {
					$extends_file_path = YF_PATH. 'classes/'.YF_PREFIX.'module'. YF_CLS_EXT;
				}
			}
			if (!empty($extends_file_path) && file_exists($extends_file_path)) {
				$extends_file_text = file_get_contents($extends_file_path);
			} elseif (!empty($extends_file_path2) && file_exists($extends_file_path2)) {
				$extends_file_text = file_get_contents($extends_file_path2);
			}
			// Try to parse extends file for the public methods
			foreach ((array)$this->_get_methods_names_from_text($extends_file_text, $ONLY_PRIVATE_METHODS) as $method_name) {
				// Skip constructors in PHP4 style
				if ($method_name == $user_module_name) {
					continue;
				}
				$methods[$method_name] = $method_name;
			}
			// Try to find extends other module
			if (!empty($extends_file_text)) {
				foreach ((array)$this->_recursive_get_methods_from_extends($extends_file_text, $class_name_2) as $method_name) {
					$methods[$method_name] = $method_name;
				}
			}
			$extends_file_text = '';
		}
		ksort($methods);
		return $methods;
	}

	/**
	* Get methods names from given source text
	*/
	function _get_methods_names_from_text ($text = '', $ONLY_PRIVATE_METHODS = false) {
		$methods = array();
		if (empty($text)) {
			return $methods;
		}
		preg_match_all($this->_method_pattern, $text, $matches);
		foreach ((array)$matches[1] as $method_name) {
			$_is_private_method = ($method_name[0] == '_');
			// Skip non-needed methods
			if ($ONLY_PRIVATE_METHODS && !$_is_private_method) {
				continue;
			}
			if (!$ONLY_PRIVATE_METHODS && $_is_private_method) {
				continue;
			}
			$methods[$method_name] = $method_name;
		}
		ksort($methods);
		return $methods;
	}

	/**
	* Get methods names for usage inside select boxes
	*/
	function _get_methods_for_select ($params = array()) {
		$cache_name = 'user_modules_for_select';
		$data = cache_get($cache_name);
		if (!$data) {
			$data = array('' => '-- All --');
			foreach ((array)$this->_get_methods($params) as $module_name => $module_methods) {
				$data[$module_name] = $module_name.' -> *';
				foreach ((array)$module_methods as $method_name) {
					if ($method_name == $module_name) {
						continue;
					}
					$data[$module_name.'.'.$method_name] = $module_name.' -> '.$method_name;
				}
			}
			cache_set($cache_name, $data);
		}
		return $data;
	}

	/**
	*/
	function filter_save() {
		return _class('admin_methods')->filter_save();
	}

	/**
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], array('show'))) {
			return false;
		}
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$r = array(
			'form_action'	=> url('/@object/filter_save/'.$filter_name),
			'clear_url'		=> url('/@object/filter_save/'.$filter_name.'/clear'),
		);
		$order_fields = array();
		foreach (explode('|', 'name|active') as $f) {
			$order_fields[$f] = $f;
		}
		$locations = array();
		foreach (explode('|', 'framework|framework_p2|framework_plugin|project|project_p2|project_plugin|site') as $f) {
			$locations[$f] = $f;
		}
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
				'class' => 'form-vertical',
			))
			->text('name')
			->select_box('locations', $locations, array('show_text' => 1))
			->active_box()
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'), array('horizontal' => 1, 'translate' => 1))
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__user_modules ($params = array()) {
// TODO
	}

	/**
	*/
	function _hook_settings(&$selected = array()) {
#		return array(
#			array('yes_no_box', 'admin_home__DISPLAY_STATS'),
#		);
	}
}
