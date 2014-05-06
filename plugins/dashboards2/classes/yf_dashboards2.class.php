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
		return $this->view($params);
	}

	/**
	* Similar to 'display', but for usage inside this module (action links and more)
	*/
	function view($params = array()) {
		if (!is_array($params)) {
			$params = array();
		}
		$ds_name = isset($params['name']) ? $params['name'] : ($this->_name ? $this->_name : $_GET['id']);
		$ds = $this->_get_dashboard_data($ds_name);
 
		if (!$ds['id']) {
			return _e('No such record');
		}

		$grid = "";
		if(isset($ds['data']['rows']) && is_array($ds['data']['rows'] )){
			$grid = $this->_get_grid($ds['data']['rows']);
		}
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

				$module_obj = module_safe($module_name);
				if (is_object($module_obj) && method_exists($module_obj, $method_name)) {
					$content = $module_obj->$method_name($saved_config);
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

	
}
