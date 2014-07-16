<?php

/**
* dashboards2 for user
*/
class yf_dashboards2 {

	/**
	* Bootstrap CSS classes used to create configurable grid
	*/

// not using now	
	private $_col_classes = array(
		1 => 'span12 col-md-12 column',
		2 => 'span6 col-md-6 column',
		3 => 'span4 col-md-4 column',
		4 => 'span3 col-md-3 column',
		6 => 'span2 col-md-2 column',
		12 => 'span1 col-md-1 column',
	);

	private $_debug_info = array();
	private $_time_start;
// TODO: add options for items:
// min_height=0|(int)
// max_height=0|(int)

# TODO: в дашборде  сдлеать по умолчанию вызов метода show если указан тролько класс  register == register.show

	/**
	*/
	function _init () {
		conf('css_framework', 'bs3');
	}

	/**
	* Designed to be used by other modules to show configured dashboard
	*/
	function display($params = array()) {

		$this->_time_start = microtime(true);
		if (is_string($params)) {
			$name = $params;
		}
		if (!is_array($params)) {
			$params = array();
		}
		if (!$params['name'] && $name) {
			$params['name'] = $name;
		}
		if (!$params['name']) {
			return _e('Empty dashboard name');
		}
		$this->_name = $params['name'];

		$dashboard_data = $this->view($params);

		$this->_debug_info['total_time'] =	round(microtime(true) - $this->_time_start, 5);
		DEBUG_MODE && debug('dashboard', $this->_debug_info);		
		return $dashboard_data;
	}

	/**
	* Similar to 'display', but for usage inside this module (action links and more)
	*/
	function view($params = array()) {
		if (!is_array($params)) {
			$params = array();
		}
		$ds_name = isset($params['name']) ? $params['name'] : ($this->_name ? $this->_name : $_GET['id']);
		$this->_debug_info['name'] = $ds_name;
		$ds = $this->_get_dashboard_data($ds_name);
 
		if (!$ds['id']) {
			return _e('No such record');
		}

		$grid = "";
		if(isset($ds['data']['rows']) && is_array($ds['data']['rows'] )){
			$grid = $this->_get_grid($ds['data']['rows']);
		}
	//	print_r($this->_debug_info);
		return $grid;
	}

	function _get_grid($data = array()) {

		foreach ((array)$data as $row_id => $row_items) {
			$cols = '';
			if(isset($row_items["cols"]) && is_array($row_items["cols"] )){
				foreach ((array)$row_items['cols'] as $col_id => $col_items) {
					$content = "";
					$row_class = $col_items["class"][0];


					if(isset($col_items["content"]) && is_array($col_items["content"] )){
						foreach ((array)$col_items["content"] as $content_id => $content_items) {
							if(isset($content_items["rows"]) && is_array($content_items["rows"] )){
								$content .= $this->_get_grid($content_items["rows"]);
							}
							if(isset($content_items["widget"]) && is_array($content_items["widget"] )){
								$content .= $this->_view_widget_items($content_items["widget"]);
							}
						}
					}

					$cols .= '<div class="col-md-'.$row_class.' span'.$row_class.' column"> '.$content.' </div>';
				}
			}
			$rows [] = array('cols' => $cols , 'css_class' =>$row_items["cols"][$row_id]['css_class'][0]);
		}
		$replace = array(
			'rows'	=> $rows,
		);
		return tpl()->parse(__CLASS__.'/view_main', $replace);
	}


	/**
	*/
	function _view_widget_items ($widgets = array()) {

		$_orig_object = $_GET['object'];
		$_orig_action = $_GET['action'];
		$is_cloneable_item = true;


		$module_name = '';
		$method_name = '';
		$content = '';
		if ($is_cloneable_item) {
			if ($widgets["type"] == 'php') {
				if (strlen($info['code'])) {
					//						$content = eval('<?'.'php '.$info['code']);
				} elseif ($info['method_name']) {
					//						list($module_name, $method_name) = explode('.', $info['method_name']);
				}
				list($module_name, $method_name) = explode('.', $widgets['val']);
			} elseif ($widgets["type"] == 'block') {
				$content = _class('core_blocks')->show_block(array('block_id' => $info['block_name']));
			} elseif ($widgets["type"] == 'stpl') {
				if (strlen($info['code'])) {
					$content = tpl()->parse_string($info['code']);
				} elseif ($info['stpl_name']) {
					$content = tpl()->parse($info['stpl_name']);
				}
			}
		} else {
			list($module_name, $method_name) = explode('::', $info['full_name']);
		}

		if ($module_name && $method_name) {
			// This is needed to correctly execute widget (maybe not nicest method, I know...)
			//$_GET['object'] = $module_name;
			//$_GET['action'] = $method_name;
			//

			$_time_start = microtime(true);
			$module_obj = module_safe($module_name);
			if (is_object($module_obj) && method_exists($module_obj, $method_name)) {
				$content = $module_obj->$method_name($saved_config);
				$path = $this->get_storadge_and_path($module_name);	
				//	var_dump($bb);				
				$this->_debug_info['widgets'][] = array (
					'object'       => $module_name,
					'action'       => $method_name,
					'storage'      => $path['storage'],
					'loaded_path'  => $path ['loaded_path'],
					'time'         => round(microtime(true) - $_time_start, 5),
				);
			} else {
				trigger_error(__CLASS__.': called module.method from widget not exists: '.$module_name.'.'.$method_name.'', E_USER_WARNING);
			}
			$_GET['object'] = $_orig_object;
			$_GET['action'] = $_orig_action;
		}

		$items[$info['auto_id']] = tpl()->parse(__CLASS__.'/view_item', array(
			//	'id'			=> $info['auto_id'].'_'.$info['auto_id'],
			'name'			=> _prepare_html($widgets["type"]),
			'content'		=> $content,
			'has_config'	=> 0,
			//				'css_class'		=> $saved_config['color'],
			'hide_header'	=> 1,
			'hide_border'	=> 1,
		));



		if (!$items) {
			return '';
		}
		return implode(PHP_EOL, $items);
		
	}

	/**
	*/
	function _get_dashboard_data ($id = '') {
		if (!$id) {
			$id = isset($params['name']) ? $params['name'] : ($this->_name ? $this->_name : $_GET['id']);
		}
		if (!$id) {
			return false;
		}
		if (isset($this->_dashboard_data[$id])) {
			return $this->_dashboard_data[$id];
		}
		$ds = db()->get('SELECT * FROM '.db('dashboards2').' WHERE name="'.db()->es($id).'" OR id='.intval($id));
		if ($ds) {
			$ds['data'] = object_to_array(json_decode($ds['data']));
		}
		$this->_dashboard_data[$id] = $ds;
		return $ds;
	}
/**
	 */
	function get_storadge_and_path ($class_name = '', $custom_path=''){
	
		if (empty($class_name) || $class_name == 'main') {
			return false;
		}
		$cur_hook_prefix = MAIN_TYPE_ADMIN ? YF_ADMIN_CLS_PREFIX : YF_SITE_CLS_PREFIX;
		$loaded_class_name	= false;
		// Site loaded class have top priority
		$site_class_name = $cur_hook_prefix. $class_name;
		$class_file = $class_name. YF_CLS_EXT;
		// Developer part of path is related to hostname to be able to make different code overrides for each
		$dev_path = '.dev/'. main()->HOSTNAME.'/';
		// additional path variables
		$SITE_PATH = MAIN_TYPE_USER ? SITE_PATH : ADMIN_SITE_PATH;
		if (MAIN_TYPE_USER) {
			if (empty($custom_path)) {
				$site_path			= USER_MODULES_DIR;
				$site_path_dev		= $dev_path. USER_MODULES_DIR;
				$project_path		= USER_MODULES_DIR;
				$project_path_dev	= $dev_path. USER_MODULES_DIR;
				$fwork_path			= USER_MODULES_DIR;
				$fwork_path2		= 'priority2/'. USER_MODULES_DIR;
			} elseif (false === strpos($custom_path, SITE_PATH) && false === strpos($custom_path, PROJECT_PATH)) {
				$site_path			= $custom_path;
				$site_path_dev		= $dev_path. $custom_path;
				$project_path		= $custom_path;
				$project_path_dev	= $dev_path. $custom_path;
				$fwork_path			= $custom_path;
				$fwork_path2		= 'priority2/'. $custom_path;
			} else {
				$site_path			= $custom_path;
			}
		} elseif (MAIN_TYPE_ADMIN) {
			if (empty($custom_path)) {
				$site_path			= ADMIN_MODULES_DIR;
				$site_path_dev		= $dev_path. ADMIN_MODULES_DIR;
				$project_path		= ADMIN_MODULES_DIR;
				$project_path_dev	= $dev_path. ADMIN_MODULES_DIR;
				$fwork_path			= ADMIN_MODULES_DIR;
				$fwork_path2		= 'priority2/'. ADMIN_MODULES_DIR;
				$project_path2		= USER_MODULES_DIR;
			} elseif (false === strpos($custom_path, SITE_PATH) && false === strpos($custom_path, PROJECT_PATH) && false === strpos($custom_path, ADMIN_SITE_PATH)) {
				$site_path			= $custom_path;
				$site_path_dev		= $dev_path. $custom_path;
				$project_path		= $custom_path;
				$project_path_dev	= $dev_path. $custom_path;
				$fwork_path			= $custom_path;
				$fwork_path2		= 'priority2/'. $custom_path;
			} else {
				$site_path			= $custom_path;
			}
		}
		if (!isset(main()->_plugins)) {
			main()->_preload_plugins_list();
		}
		$yf_plugins = main()->_plugins;
		$yf_plugins_classes = main()->_plugins_classes;

		// Order of storages matters a lot!
		$storages = array();
		if (conf('DEV_MODE')) {
			if ($site_path_dev && $site_path_dev != $project_path_dev) {
				$storages['dev_site']	= array($SITE_PATH. $site_path_dev);
			}
			$storages['dev_project'] = array(PROJECT_PATH. $project_path_dev);
		}
		if (strlen($SITE_PATH. $site_path) && ($SITE_PATH. $site_path) != (PROJECT_PATH. $project_path)) {
			$storages['site'] 		= array($SITE_PATH. $site_path);
		}
		$storages['site_hook']		= array($SITE_PATH. $site_path, $cur_hook_prefix);
		$storages['project']		= array(PROJECT_PATH. $project_path);
		$storages['framework']		= array(YF_PATH. $fwork_path, YF_PREFIX);
		$storages['framework_p2']	= array(YF_PATH. $fwork_path2, YF_PREFIX);
		if (MAIN_TYPE_ADMIN) {
			$storages['admin_user_project']		= array(PROJECT_PATH. $project_path2);
			$storages['admin_user_framework']	= array(YF_PATH. USER_MODULES_DIR, YF_PREFIX);
		}
		if (isset($yf_plugins[$class_name]) || isset($yf_plugins_classes[$class_name])) {
			if (isset($yf_plugins[$class_name])) {
				$plugin_name = $class_name;
			} else {
				$plugin_name = $yf_plugins_classes[$class_name];
			}
			$plugin_info = $yf_plugins[$plugin_name];
			$plugin_subdir = 'plugins/'.$plugin_name.'/';

			if ($site_path && $site_path != $project_path) {
				$storages['plugins_site']	= array($SITE_PATH. $plugin_subdir. $site_path);
			}
			if (isset($plugin_info['project'])) {
				$storages['plugins_project']	= array(PROJECT_PATH. $plugin_subdir. $project_path);
				if (MAIN_TYPE_ADMIN) {
					$storages['plugins_admin_user_project']	= array(PROJECT_PATH. $plugin_subdir. $project_path2);
				}
			} elseif (isset($plugin_info['framework'])) {
				$storages['plugins_framework']	= array(YF_PATH. $plugin_subdir. $fwork_path, YF_PREFIX);
				if (MAIN_TYPE_ADMIN) {
					$storages['plugins_admin_user_framework'] = array(YF_PATH. $plugin_subdir. USER_MODULES_DIR, YF_PREFIX);
				}
			}
		}
		$storage = '';
		$loaded_path = '';
		foreach ((array)$storages as $_storage => $v) {
			$_path		= strval($v[0]);
			$_prefix	= strval($v[1]);
			if (empty($_path)) {
				continue;
			}

		/*	if(file_exists(	$_path. $_prefix. $class_file)){
				$data = array (
					'loaded_class_name'	=> $_prefix. $class_name,
					'storage' => $_storage,
					'loaded_path' => $_path. $_prefix. $class_file,
				);
			//	break;
		}*/
			if (class_exists($_prefix. $class_name)) {
				$data = array (
					'loaded_class_name'	=> $_prefix. $class_name,
					'storage' => $_storage,
					'loaded_path' => $_path. $_prefix. $class_file,
				);
				break;

			}

		}
		// Try to load classes from db
	/*	if (empty($loaded_class_name) && $this->ALLOW_SOURCE_FROM_DB && is_object($this->db)) {
			$result_from_db = $this->db->query_fetch('SELECT * FROM '.db('code_source').' WHERE keyword="'._es($class_name).'"');
			if (!empty($result_from_db)) {
				eval($result_from_db['source']);
			}
			if (class_exists($class_name)) {
				$loaded_class_name	= $class_name;
				$storage = 'db';
			}
		
	}*/
		return $data;
	}
	
	
}
