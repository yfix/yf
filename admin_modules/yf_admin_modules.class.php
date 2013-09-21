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
		if ($_POST) {
/*
			if (!empty($_POST['names'])) {
				$in = '"'.implode('","', _es($_POST['names'])).'"';
				if ($_POST['activate']) {
					db()->UPDATE('admin_modules', array('active' => 1), 'name IN('.$in.')');
				} elseif ($_POST['deactivate']) {
					db()->UPDATE('admin_modules', array('active' => 0), 'name IN('.$in.')');
				}
				cache()->refresh('admin_modules');
			}
*/
			return js_redirect('./?object='.$_GET['object']);
		}

		$items = array();
		foreach ((array)db()->get_all('SELECT * FROM '.db('admin_modules').' ORDER BY name ASC') as $a) {
			$locations = array();
			if (file_exists(ADMIN_SITE_PATH. ADMIN_MODULES_DIR. $a['name']. CLASS_EXT)) {
				$locations['project'] = './?object=file_manager&action=edit_item&f_='.$a['name'].'.class.php'.'&dir_name='.urlencode(INCLUDE_PATH. 'modules');
			}
			if (file_exists(ADMIN_SITE_PATH. 'priority2/'. ADMIN_MODULES_DIR. $a['name']. CLASS_EXT)) {
				$locations['project_p2'] = './?object=file_manager&action=edit_item&f_='.$a['name'].'.class.php'.'&dir_name='.urlencode(INCLUDE_PATH. 'priority2/modules');
			}
			if (file_exists(YF_PATH. ADMIN_MODULES_DIR. YF_PREFIX. $a['name']. CLASS_EXT)) {
				$locations['framework'] = './?object=file_manager&action=edit_item&f_='.'yf_'.$a['name'].'.class.php'.'&dir_name='.urlencode(YF_PATH. 'modules');
			}
			if (file_exists(YF_PATH. 'priority2/'. ADMIN_MODULES_DIR. YF_PREFIX. $a['name']. CLASS_EXT)) {
				$locations['framework_p2'] = './?object=file_manager&action=edit_item&f_='.'yf_'.$a['name'].'.class.php'.'&dir_name='.urlencode(YF_PATH. 'priority2/modules');
			}
			$items[] = array(
				'name'		=> $a['name'],
				'active'	=> $a['active'],
				'locations'	=> $locations,
			);
		}
		return table($items)
			->form()
			->check_box('name', array('field_desc' => '#'))
			->text('name')
			->func('locations', function($field, $params, $row) {
				foreach ((array)$field as $loc => $link) {
					$out[] = '<a href="'.$link.'" class="btn btn-mini">'.$loc.'</a>';
				}
				return implode(PHP_EOL, (array)$out);
			})
			->btn('conf', './?object=conf_editor&action=admin_modules&id=%d', array('id' => 'name'))
			->btn_active()
			->footer_submit(array('value' => 'activate selected'))
			->footer_submit(array('value' => 'disable selected'))
			->footer_link('Refresh list', './?object='.$_GET['object'].'&action=refresh_modules_list')
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
		cache()->refresh('admin_modules');
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
		$Q = db()->query('SELECT name, COUNT(*) AS num FROM '.db('admin_modules').' GROUP BY name HAVING num > 1');
		while ($A = db()->fetch_assoc($Q)) {
			db()->query('DELETE FROM '.db('admin_modules').' WHERE name="'._es($A['name']).'" LIMIT '.intval($A['num'] - 1));
		}
		$Q = db()->query('SELECT * FROM '.db('admin_modules').'');
		while ($A = db()->fetch_assoc($Q)) {
			$all_admin_modules_array[$A['name']] = $A['name'];
		}

		$refreshed_modules = $this->_get_modules_from_files(1);
		foreach ((array)$refreshed_modules as $cur_module_name) {
			if (isset($all_admin_modules_array[$cur_module_name])) {
				continue;
			}
			db()->insert('admin_modules', array(
				'name'		=> _es($cur_module_name),
				'active'	=> 0,
			));
		}
		// Check for missing modules
		foreach ((array)$all_admin_modules_array as $cur_module_name) {
			// Do delete missing modules records
			if (!isset($refreshed_modules[$cur_module_name])) {
				db()->query('DELETE FROM '.db('admin_modules').' WHERE name="'._es($cur_module_name).'"');
			}
		}
		cache()->refresh('admin_modules');
		if (!$silent) {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	* Get available modules
	*/
	function _get_modules ($params = array()) {
		$with_all			= isset($params['with_all']) ? $params['with_all'] : 1;
		$with_sub_modules	= isset($params['with_sub_modules']) ? $params['with_sub_modules'] : 0;
		$admin_modules_array	= array();
		// Insert value for all modules
		if ($with_all) {
			$admin_modules_array[''] = t('-- ALL --');
		}
		// Need to prevent multiple calls
		if (isset($GLOBALS['admin_modules_array'])) {
			return $GLOBALS['admin_modules_array'];
		}

		$Q = db()->query('SELECT * FROM '.db('admin_modules').' WHERE active="1"');
		while ($A = db()->fetch_assoc($Q)) {
			$admin_modules_array[$A['name']] = $A['name'];
		}

		ksort($admin_modules_array);
		$GLOBALS['admin_modules_array'] = $admin_modules_array;
		unset($GLOBALS['admin_modules_array']['']);
		return $admin_modules_array;
	}

	/**
	* Get available modules from files
	*/
	function _get_modules_from_files ($include_framework = true, $with_sub_modules = false) {
		$admin_modules_array = array();
		$dir_to_scan = ADMIN_SITE_PATH. ADMIN_MODULES_DIR;
		foreach ((array)_class('dir')->scan_dir($dir_to_scan) as $k => $v) {
			$v = str_replace('//', '/', $v);
			if (substr($v, -strlen(CLASS_EXT)) != CLASS_EXT) {
				continue;
			}
			if (!$with_sub_modules && false !== strpos(substr($v, strlen($dir_to_scan)), '/')) {
				continue;
			}
			$module_name = substr(basename($v), 0, -strlen(CLASS_EXT));
			$module_name = str_replace(ADMIN_CLASS_PREFIX, '', $module_name);
			if (in_array($module_name, $this->_MODULES_TO_SKIP)) {
				continue;
			}
			$admin_modules_array[$module_name] = $module_name;
		}
		$dir_to_scan = ADMIN_SITE_PATH. 'priority2/'. ADMIN_MODULES_DIR;
		foreach ((array)_class('dir')->scan_dir($dir_to_scan) as $k => $v) {
			$v = str_replace('//', '/', $v);
			if (substr($v, -strlen(CLASS_EXT)) != CLASS_EXT) {
				continue;
			}
			if (!$with_sub_modules && false !== strpos(substr($v, strlen($dir_to_scan)), '/')) {
				continue;
			}
			$module_name = substr(basename($v), 0, -strlen(CLASS_EXT));
			$module_name = str_replace(ADMIN_CLASS_PREFIX, '', $module_name);
			if (in_array($module_name, $this->_MODULES_TO_SKIP)) {
				continue;
			}
			$admin_modules_array[$module_name] = $module_name;
		}
		// Do parse files from the framework
		if ($include_framework) {
			$dir_to_scan = YF_PATH. ADMIN_MODULES_DIR;
			foreach ((array)_class('dir')->scan_dir($dir_to_scan) as $k => $v) {
				$v = str_replace('//', '/', $v);
				if (substr($v, -strlen(CLASS_EXT)) != CLASS_EXT) {
					continue;
				}
				if (!$with_sub_modules && false !== strpos(substr($v, strlen($dir_to_scan)), '/')) {
					continue;
				}
				$module_name = substr(basename($v), 0, -strlen(CLASS_EXT));
				$module_name = str_replace(YF_PREFIX, '', $module_name);
				$module_name = str_replace(ADMIN_CLASS_PREFIX, '', $module_name);
				$module_name = str_replace(SITE_CLASS_PREFIX, '', $module_name);
				if (in_array($module_name, $this->_MODULES_TO_SKIP)) {
					continue;
				}
				$admin_modules_array[$module_name] = $module_name;
			}
			$dir_to_scan = YF_PATH. 'priority2/'. ADMIN_MODULES_DIR;
			foreach ((array)_class('dir')->scan_dir($dir_to_scan) as $k => $v) {
				$v = str_replace('//', '/', $v);
				if (substr($v, -strlen(CLASS_EXT)) != CLASS_EXT) {
					continue;
				}
				if (!$with_sub_modules && false !== strpos(substr($v, strlen($dir_to_scan)), '/')) {
					continue;
				}
				$module_name = substr(basename($v), 0, -strlen(CLASS_EXT));
				$module_name = str_replace(YF_PREFIX, '', $module_name);
				$module_name = str_replace(ADMIN_CLASS_PREFIX, '', $module_name);
				$module_name = str_replace(SITE_CLASS_PREFIX, '', $module_name);
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
		foreach ((array)$GLOBALS['admin_modules_array'] as $user_module_name) {
			if (substr($user_module_name, 0, strlen(ADMIN_CLASS_PREFIX)) == ADMIN_CLASS_PREFIX) {
				$user_module_name = substr($user_module_name, strlen(ADMIN_CLASS_PREFIX));
			}
			$file_text = '';
			$file_names = array();
			$tmp = ADMIN_SITE_PATH. ADMIN_MODULES_DIR. $user_module_name. CLASS_EXT;
			if (file_exists($tmp)) {
				$file_names['admin'] = $tmp;
			}
			$tmp = YF_PATH. ADMIN_MODULES_DIR. YF_PREFIX. $user_module_name. CLASS_EXT;
			if (file_exists($tmp)) {
				$file_names['yf'] = $tmp;
			}
			$tmp = YF_PATH. 'priority2/'. ADMIN_MODULES_DIR. YF_PREFIX. $user_module_name. CLASS_EXT;
			if (file_exists($tmp)) {
				$file_names['yf_p2'] = $tmp;
			}
			$tmp = ADMIN_SITE_PATH. ADMIN_MODULES_DIR. ADMIN_CLASS_PREFIX. $user_module_name. CLASS_EXT;
			if (file_exists($tmp)) {
				$file_names['admin_with_prefix'] = $tmp;
			}
			if (!$file_names) {
				continue;
			}
			foreach ((array)$file_names as $location => $file_name) {
				$file_text = file_get_contents($file_name);
				// Try to get methods from parent classes (if exist one)
				$_methods = $this->_recursive_get_methods_from_extends($file_text, ($location == 'admin_with_prefix' ? ADMIN_CLASS_PREFIX : ''). $user_module_name, $ONLY_PRIVATE_METHODS);
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
		// Check if cur class extends some other class
		if (preg_match($this->_extends_pattern, $file_text, $matches_extends)) {
			$class_name_1 = $matches_extends[1];
			$class_name_2 = $matches_extends[2];

			$_extends_from_fwork = (substr($class_name_2, 0, strlen(YF_PREFIX)) == YF_PREFIX);
			if ($_type == 'admin') {
				if (substr($class_name_1, 0, strlen(ADMIN_CLASS_PREFIX)) == ADMIN_CLASS_PREFIX) {
					if ($_extends_from_fwork) {
						$extends_file_path = YF_PATH. ADMIN_MODULES_DIR. $class_name_2. CLASS_EXT;
						$extends_file_path2 = YF_PATH. 'priority2/'. ADMIN_MODULES_DIR. $class_name_2. CLASS_EXT;
					} else {
						$extends_file_path = INCLUDE_PATH. USER_MODULES_DIR. $class_name_2. CLASS_EXT;
						$extends_file_path2 = INCLUDE_PATH. 'priority2/'. USER_MODULES_DIR. $class_name_2. CLASS_EXT;
						$_type = 'user';
					}
					$user_module_name = substr($user_module_name, strlen(ADMIN_CLASS_PREFIX));
				} elseif ($class_name_1 == $user_module_name || str_replace(YF_PREFIX, '', $class_name_1) == $user_module_name) {
					$extends_file_path = YF_PATH. ADMIN_MODULES_DIR. $class_name_2. CLASS_EXT;
					$extends_file_path2 = YF_PATH. 'priority2/'. ADMIN_MODULES_DIR. $class_name_2. CLASS_EXT;
				}
			} elseif ($_type == 'user') {
				if ($class_name_1 == $user_module_name || str_replace(YF_PREFIX, '', $class_name_1) == $user_module_name) {
					$extends_file_path = YF_PATH. USER_MODULES_DIR. $class_name_2. CLASS_EXT;
					$extends_file_path2 = YF_PATH. 'priority2/'. USER_MODULES_DIR. $class_name_2. CLASS_EXT;
				}
			}
			// Special processing of the 'yf_module'
			if ($this->PARSE_YF_MODULE && $class_name_2 == YF_PREFIX. 'module') {
				$extends_file_path = YF_PATH. 'classes/'. YF_PREFIX. 'module'. CLASS_EXT;
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
	*/
	function _hook_widget__admin_modules ($params = array()) {
// TODO
	}
}
