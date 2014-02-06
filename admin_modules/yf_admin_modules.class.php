<?php

/**
* Admin modules list handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_admin_modules {

	/** @var array */
	public $_MODULES_TO_SKIP	= array();
	/** @var string @conf_skip Pattern for files */
	public $_include_pattern	= array('', '#\.(php|stpl)$#');
	/** @var string @conf_skip Description file pattern */
	public $_desc_file_pattern	= '#[a-z0-9_]\.xml$#i';
	/** @var string @conf_skip Class method pattern */
	public $_method_pattern	= '/function ([a-zA-Z_][a-zA-Z0-9_]+)/is';
	/** @var string @conf_skip Class extends pattern */
	public $_extends_pattern	= '/class (\w+)? extends (\w+)? \{/';
	/** @var bool Parse core 'module' class in get_methods */
	public $PARSE_YF_MODULE	= false;

	/**
	* Framework constructor
	*/
	function _init () {
		$this->_modules = $this->_get_modules();
		unset($this->_modules['']);
	}

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
				db()->update('admin_modules', array('active' => $active), $where);
				cache_del('admin_modules');
			}
			return js_redirect('./?object='.$_GET['object']);
		}

		if (!isset($this->_yf_plugins)) {
			$this->_yf_plugins = main()->_preload_plugins_list();
			$this->_yf_plugins_classes = main()->_plugins_classes;
		}
		$items = array();
		foreach ((array)db()->get_all('SELECT * FROM '.db('admin_modules').' ORDER BY name ASC') as $a) {
			$name = $a['name'];
			$plugin_name = '';
			if (isset($this->_yf_plugins_classes[$name])) {
				$plugin_name = $this->_yf_plugins_classes[$name];
			}
			$locations = array();
			$d = ADMIN_SITE_PATH. ADMIN_MODULES_DIR;
			$f = $name. YF_CLS_EXT;
			if (file_exists($d. $f)) {
				$locations['project'] = './?object=file_manager&action=edit_item&f_='.$f.'&dir_name='.urlencode($d);
			}
			$d = ADMIN_SITE_PATH. 'priority2/'. ADMIN_MODULES_DIR;
			$f = $name. YF_CLS_EXT;
			if (file_exists($d. $f)) {
				$locations['project_p2'] = './?object=file_manager&action=edit_item&f_='.$f.'&dir_name='.urlencode($d);
			}
			$d = YF_PATH. ADMIN_MODULES_DIR;
			$f = YF_PREFIX. $name. YF_CLS_EXT;
			if (file_exists($d. $f)) {
				$locations['framework'] = './?object=file_manager&action=edit_item&f_='.$f.'&dir_name='.urlencode($d);
			}
			$d = YF_PATH. 'priority2/'. ADMIN_MODULES_DIR;
			$f = YF_PREFIX. $name. YF_CLS_EXT;
			if (file_exists($d. $f)) {
				$locations['framework_p2'] = './?object=file_manager&action=edit_item&f_='.$f.'&dir_name='.urlencode($d);
			}
			$d = YF_PATH. 'plugins/'. $plugin_name. '/'. ADMIN_MODULES_DIR;
			$f = YF_PREFIX. $name. YF_CLS_EXT;
			if ($plugin_name && file_exists($d. $f)) {
				$locations['framework_plugin'] = './?object=file_manager&action=edit_item&f_='.$f.'&dir_name='.urlencode($d);
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
			->btn('conf', './?object=conf_editor&action=admin_modules&id=%d', array('id' => 'name'))
			->btn_active(array('id' => 'name'))
			->footer_submit(array('value' => 'activate selected'))
			->footer_submit(array('value' => 'disable selected'))
			->footer_link('Refresh list', './?object='.$_GET['object'].'&action=refresh_modules_list', array('icon' => 'icon-refresh'))
		;
	}

	/**
	*/
	function active () {
		if (!empty($_GET['id'])) {
			$module_info = db()->query_fetch('SELECT * FROM '.db('admin_modules').' WHERE name="'._es($_GET['id']).'" LIMIT 1');
		}
		if (!empty($module_info)) {
			db()->UPDATE('admin_modules', array('active' => (int)!$module_info['active']), 'id='.intval($module_info['id']));
		}
		cache_del('admin_modules');
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($module_info['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function refresh_modules_list ($silent = false) {
		// Cleanup duplicate records
		$q = db()->query('SELECT name, COUNT(*) AS num FROM '.db('admin_modules').' GROUP BY name HAVING num > 1');
		while ($a = db()->fetch_assoc($q)) {
			db()->query('DELETE FROM '.db('admin_modules').' WHERE name="'._es($a['name']).'" LIMIT '.intval($a['num'] - 1));
		}
		$q = db()->query('SELECT * FROM '.db('admin_modules').'');
		while ($a = db()->fetch_assoc($q)) {
			$all_admin_modules_array[$a['name']] = $a['name'];
		}

		$refreshed_modules = $this->_get_modules_from_files($include_framework = true, $with_sub_modules = false);

		$insert_data = array();
		foreach ((array)$refreshed_modules as $cur_module_name) {
			if (isset($all_admin_modules_array[$cur_module_name])) {
				continue;
			}
			$insert_data[$cur_module_name] = array(
				'name'   => $cur_module_name,
				'active' => 0,
			);
		}
		if ($insert_data) {
			db()->insert('admin_modules', db()->es($insert_data));
		}
		// Check for missing modules
		$delete_names = array();
		foreach ((array)$all_admin_modules_array as $cur_module_name) {
			if (!isset($refreshed_modules[$cur_module_name])) {
				$delete_names[$cur_module_name] = $cur_module_name;
			}
		}
		if ($delete_names) {
			db()->query('DELETE FROM '.db('admin_modules').' WHERE name IN("'.implode('","', _es($delete_names)).'")');
		}
		cache_del(array('admin_modules','admin_modules_for_select'));
		if (!$silent) {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	* Get available modules
	*/
	function _get_modules ($params = array()) {
		// Need to prevent multiple calls
		if (isset($this->_admin_modules_array)) {
			return $this->_admin_modules_array;
		}
		$with_all			= isset($params['with_all']) ? $params['with_all'] : 1;
		$with_sub_modules	= isset($params['with_sub_modules']) ? $params['with_sub_modules'] : 0;
		$admin_modules_array	= array();
		// Insert value for all modules
		if ($with_all) {
			$admin_modules_array[''] = t('-- ALL --');
		}
		$Q = db()->query('SELECT * FROM '.db('admin_modules').' WHERE active="1"');
		while ($A = db()->fetch_assoc($Q)) {
			$admin_modules_array[$A['name']] = $A['name'];
		}
		ksort($admin_modules_array);
		$this->_admin_modules_array = $admin_modules_array;
		unset($this->_admin_modules_array['']);
		return $admin_modules_array;
	}

	/**
	* Get available modules from files
	*/
	function _get_modules_from_files ($include_framework = true, $with_sub_modules = false) {
		$admin_modules_array = array();

		$pattern_include = '-f ~/'.preg_quote(ADMIN_MODULES_DIR,'~').'.*'.preg_quote(YF_CLS_EXT,'~').'$~';
		$pattern_no_submodules = '~/'.preg_quote(ADMIN_MODULES_DIR,'~').'[^/]+'.preg_quote(YF_CLS_EXT,'~').'$~ims';

		$yf_prefix_len = strlen(YF_PREFIX);
		$yf_cls_ext_len = strlen(YF_CLS_EXT);
		$admin_prefix_len = strlen(YF_ADMIN_CLS_PREFIX);
		$site_prefix_len = strlen(YF_SITE_CLS_PREFIX);

		$dir_to_scan = ADMIN_SITE_PATH. ADMIN_MODULES_DIR;
		foreach ((array)_class('dir')->scan($dir_to_scan, true, $pattern_include) as $k => $v) {
			$v = str_replace('//', '/', $v);
			if (substr($v, -$yf_cls_ext_len) != YF_CLS_EXT) {
				continue;
			}
			if (!$with_sub_modules) {
				if (false !== strpos(substr($v, strlen($dir_to_scan)), '/')) {
					continue;
				}
			}
			$module_name = substr(basename($v), 0, -$yf_cls_ext_len);
			if (substr($module_name, 0, $admin_prefix_len) == YF_ADMIN_CLS_PREFIX) {
				$module_name = substr($module_name, $admin_prefix_len);
			}
			if (in_array($module_name, $this->_MODULES_TO_SKIP)) {
				continue;
			}
			$admin_modules_array[$module_name] = $module_name;
		}

		$dir_to_scan = PROJECT_PATH. 'priority2/'. ADMIN_MODULES_DIR;
		foreach ((array)_class('dir')->scan($dir_to_scan, true, $pattern_include) as $k => $v) {
			$v = str_replace('//', '/', $v);
			if (substr($v, -$yf_cls_ext_len) != YF_CLS_EXT) {
				continue;
			}
			if (!$with_sub_modules) {
				if (false !== strpos(substr($v, strlen($dir_to_scan)), '/')) {
					continue;
				}
			}
			$module_name = substr(basename($v), 0, -$yf_cls_ext_len);
			if (substr($module_name, 0, $admin_prefix_len) == YF_ADMIN_CLS_PREFIX) {
				$module_name = substr($module_name, $admin_prefix_len);
			}
			if (in_array($module_name, $this->_MODULES_TO_SKIP)) {
				continue;
			}
			$admin_modules_array[$module_name] = $module_name;
		}

		// Plugins parsed differently
		foreach ((array)_class('dir')->scan(PROJECT_PATH. 'plugins/', true, $pattern_include) as $k => $v) {
			$v = str_replace('//', '/', $v);
			if (substr($v, -$yf_cls_ext_len) != YF_CLS_EXT) {
				continue;
			}
			if (!$with_sub_modules) {
				if (!preg_match($pattern_no_submodules, $v)) {
					continue;
				}
			}
			$module_name = substr(basename($v), 0, -$yf_cls_ext_len);
			if (substr($module_name, 0, $admin_prefix_len) == YF_ADMIN_CLS_PREFIX) {
				$module_name = substr($module_name, $admin_prefix_len);
			}
			if (in_array($module_name, $this->_MODULES_TO_SKIP)) {
				continue;
			}
			$admin_modules_array[$module_name] = $module_name;
		}

		// Do parse files from the framework
		if ($include_framework) {
			$dir_to_scan = YF_PATH. ADMIN_MODULES_DIR;
			foreach ((array)_class('dir')->scan($dir_to_scan) as $k => $v) {
				$v = str_replace('//', '/', $v);
				if (substr($v, -$yf_cls_ext_len) != YF_CLS_EXT) {
					continue;
				}
				if (!$with_sub_modules) {
					if (false !== strpos(substr($v, strlen($dir_to_scan)), '/')) {
						continue;
					}
				}
				$module_name = substr(basename($v), 0, -$yf_cls_ext_len);
				if (substr($module_name, 0, $yf_prefix_len) == YF_PREFIX) {
					$module_name = substr($module_name, $yf_prefix_len);
				}
				if (substr($module_name, 0, $admin_prefix_len) == YF_ADMIN_CLS_PREFIX) {
					$module_name = substr($module_name, $admin_prefix_len);
				}
				if (substr($module_name, 0, $site_prefix_len) == YF_SITE_CLS_PREFIX) {
					$module_name = substr($module_name, $site_prefix_len);
				}
				if (in_array($module_name, $this->_MODULES_TO_SKIP)) {
					continue;
				}
				$admin_modules_array[$module_name] = $module_name;
			}
			$dir_to_scan = YF_PATH. 'priority2/'. ADMIN_MODULES_DIR;
			foreach ((array)_class('dir')->scan($dir_to_scan) as $k => $v) {
				$v = str_replace('//', '/', $v);
				if (substr($v, -$yf_cls_ext_len) != YF_CLS_EXT) {
					continue;
				}
				if (!$with_sub_modules) {
					if (false !== strpos(substr($v, strlen($dir_to_scan)), '/')) {
						continue;
					}
				}
				$module_name = substr(basename($v), 0, -$yf_cls_ext_len);
				if (substr($module_name, 0, $yf_prefix_len) == YF_PREFIX) {
					$module_name = substr($module_name, $yf_prefix_len);
				}
				if (substr($module_name, 0, $admin_prefix_len) == YF_ADMIN_CLS_PREFIX) {
					$module_name = substr($module_name, $admin_prefix_len);
				}
				if (substr($module_name, 0, $site_prefix_len) == YF_SITE_CLS_PREFIX) {
					$module_name = substr($module_name, $site_prefix_len);
				}
				if (in_array($module_name, $this->_MODULES_TO_SKIP)) {
					continue;
				}
				$admin_modules_array[$module_name] = $module_name;
			}

			// Plugins parsed differently
			foreach ((array)_class('dir')->scan(YF_PATH. 'plugins/', true, $pattern_include) as $k => $v) {
				$v = str_replace('//', '/', $v);
				if (substr($v, -$yf_cls_ext_len) != YF_CLS_EXT) {
					continue;
				}
				if (!$with_sub_modules) {
					if (!preg_match($pattern_no_submodules, $v)) {
						continue;
					}
				}
				$module_name = substr(basename($v), 0, -$yf_cls_ext_len);
				if (substr($module_name, 0, $yf_prefix_len) == YF_PREFIX) {
					$module_name = substr($module_name, $yf_prefix_len);
				}
				if (substr($module_name, 0, $admin_prefix_len) == YF_ADMIN_CLS_PREFIX) {
					$module_name = substr($module_name, $admin_prefix_len);
				}
				if (substr($module_name, 0, $site_prefix_len) == YF_SITE_CLS_PREFIX) {
					$module_name = substr($module_name, $site_prefix_len);
				}
				if (in_array($module_name, $this->_MODULES_TO_SKIP)) {
					continue;
				}
				$admin_modules_array[$module_name] = $module_name;
			}
		}
		ksort($admin_modules_array);
		return $admin_modules_array;
	}

	/**
	* Get available methods
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
		if (!isset($this->_admin_modules_array)) {
			$this->_get_modules();
		}
		foreach ((array)$this->_admin_modules_array as $user_module_name) {
			if (substr($user_module_name, 0, strlen(YF_ADMIN_CLS_PREFIX)) == YF_ADMIN_CLS_PREFIX) {
				$user_module_name = substr($user_module_name, strlen(YF_ADMIN_CLS_PREFIX));
			}
			$plugin_name = '';
			if (isset($this->_yf_plugins_classes[$user_module_name])) {
				$plugin_name = $this->_yf_plugins_classes[$user_module_name];
			}
			$file_names = array();
			$tmp = ADMIN_SITE_PATH. ADMIN_MODULES_DIR. $user_module_name. YF_CLS_EXT;
			if (file_exists($tmp)) {
				$file_names['admin'] = $tmp;
			}
			$tmp = ADMIN_SITE_PATH. ADMIN_MODULES_DIR. YF_ADMIN_CLS_PREFIX. $user_module_name. YF_CLS_EXT;
			if (file_exists($tmp)) {
				$file_names['admin_with_prefix'] = $tmp;
			}
			if ($plugin_name) {
				$tmp = PROJECT_PATH. 'plugins/'. $plugin_name. '/'. ADMIN_MODULES_DIR. $user_module_name. YF_CLS_EXT;
				if (file_exists($tmp)) {
					$file_names['admin_plugin'] = $tmp;
				}
			}
			$tmp = YF_PATH. ADMIN_MODULES_DIR. YF_PREFIX. $user_module_name. YF_CLS_EXT;
			if (file_exists($tmp)) {
				$file_names['yf'] = $tmp;
			}
			$tmp = YF_PATH. 'priority2/'. ADMIN_MODULES_DIR. YF_PREFIX. $user_module_name. YF_CLS_EXT;
			if (file_exists($tmp)) {
				$file_names['yf_p2'] = $tmp;
			}
			if ($plugin_name) {
				$tmp = YF_PATH. 'plugins/'. $plugin_name. '/'. ADMIN_MODULES_DIR. YF_PREFIX. $user_module_name. YF_CLS_EXT;
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
				$_methods = $this->_recursive_get_methods_from_extends($file_text, ($location == 'admin_with_prefix' ? YF_ADMIN_CLS_PREFIX : ''). $user_module_name, $ONLY_PRIVATE_METHODS);
				foreach ($_methods as $method_name) {
					$method_name = str_replace(YF_PREFIX, '', $method_name);
					$methods_by_modules[$user_module_name][$method_name] = $method_name;
				}
				// Try to match methods in the current file
				foreach ((array)$this->_get_methods_names_from_text($file_text, $ONLY_PRIVATE_METHODS) as $method_name) {
					$method_name = str_replace(YF_PREFIX, '', $method_name);
					// Skip constructors in PHP4 style
					if ($method_name == $user_module_name) {
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
	function _recursive_get_methods_from_extends ($file_text = '', $user_module_name = '', $_type = 'admin', $ONLY_PRIVATE_METHODS = false) {
		$extends_file_path = '';
		$methods = array();
// TODO: connect plugins
		// Check if cur class extends some other class
		if (preg_match($this->_extends_pattern, $file_text, $matches_extends)) {
			$class_name_1 = $matches_extends[1];
			$class_name_2 = $matches_extends[2];

			$_extends_from_fwork = (substr($class_name_2, 0, strlen(YF_PREFIX)) == YF_PREFIX);
			if ($_type == 'admin') {
				if (substr($class_name_1, 0, strlen(YF_ADMIN_CLS_PREFIX)) == YF_ADMIN_CLS_PREFIX) {
					if ($_extends_from_fwork) {
						$extends_file_path = YF_PATH. ADMIN_MODULES_DIR. $class_name_2. YF_CLS_EXT;
						$extends_file_path2 = YF_PATH. 'priority2/'. ADMIN_MODULES_DIR. $class_name_2. YF_CLS_EXT;
					} else {
						$extends_file_path = INCLUDE_PATH. USER_MODULES_DIR. $class_name_2. YF_CLS_EXT;
						$extends_file_path2 = INCLUDE_PATH. 'priority2/'. USER_MODULES_DIR. $class_name_2. YF_CLS_EXT;
						$_type = 'user';
					}
					$user_module_name = substr($user_module_name, strlen(YF_ADMIN_CLS_PREFIX));
				} elseif ($class_name_1 == $user_module_name || str_replace(YF_PREFIX, '', $class_name_1) == $user_module_name) {
					$extends_file_path = YF_PATH. ADMIN_MODULES_DIR. $class_name_2. YF_CLS_EXT;
					$extends_file_path2 = YF_PATH. 'priority2/'. ADMIN_MODULES_DIR. $class_name_2. YF_CLS_EXT;
				}
			} elseif ($_type == 'user') {
				if ($class_name_1 == $user_module_name || str_replace(YF_PREFIX, '', $class_name_1) == $user_module_name) {
					$extends_file_path = YF_PATH. USER_MODULES_DIR. $class_name_2. YF_CLS_EXT;
					$extends_file_path2 = YF_PATH. 'priority2/'. USER_MODULES_DIR. $class_name_2. YF_CLS_EXT;
				}
			}
			// Special processing of the 'yf_module'
			if ($this->PARSE_YF_MODULE && $class_name_2 == YF_PREFIX. 'module') {
				$extends_file_path = YF_PATH. 'classes/'. YF_PREFIX. 'module'. YF_CLS_EXT;
			}
			if (!empty($extends_file_path) && file_exists($extends_file_path)) {
				$extends_file_text = file_get_contents($extends_file_path);
			} elseif (!empty($extends_file_path2) && file_exists($extends_file_path2)) {
				$extends_file_text = file_get_contents($extends_file_path2);
			}
			foreach ((array)$this->_get_methods_names_from_text($extends_file_text, $ONLY_PRIVATE_METHODS) as $method_name) {
				if ($method_name == $user_module_name) {
					continue;
				}
				$methods[$method_name] = $method_name;
			}
			if (!empty($extends_file_text)) {
				foreach ((array)$this->_recursive_get_methods_from_extends($extends_file_text, $class_name_2, $_type) as $method_name) {
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
		$cache_name = 'admin_modules_for_select';
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
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		);
		$order_fields = array();
		foreach (explode('|', 'name|active') as $f) {
			$order_fields[$f] = $f;
		}
		$locations = array();
		foreach (explode('|', 'framework|framework_p2|framework_plugin|project|project_p2|site') as $f) {
			$locations[$f] = $f;
		}
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
			))
			->text('name')
			->select_box('locations', $locations, array('show_text' => 1))
			->radio_box('active', main()->get_data('pair_active'))
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__admin_modules ($params = array()) {
// TODO
	}
}
