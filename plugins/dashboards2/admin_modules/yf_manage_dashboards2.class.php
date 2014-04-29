<?php

/**
* Dashboards2 management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_dashboards2 {

	/**
	* Bootstrap CSS classes used to create configurable grid
	*/
	private $_col_classes = array(
		1 => 'span12 col-md-12 column',
		2 => 'span6 col-md-6 column',
		3 => 'span4 col-md-4 column',
		4 => 'span3 col-md-3 column',
		6 => 'span2 col-md-2 column',
		12 => 'span1 col-md-1 column',
	);

// TODO: add ability to use user module dashboards2 also

	/**
	*/
	function _init () {

	}

	/**
	*/
	function show() {
		return table('SELECT * FROM '.db('dashboards2'))
			->text('name')
			->text('type')
			->func("lock", function($value, $extra, $row_info){return $value == 1 ? '<span class="label label-success">YES<span>' : '<span class="label label-danger">NO<span>';})
			->btn_view()
			->btn_edit()
			->btn_clone()
			->btn_delete()
			->btn_active()
			->footer_add()
			
		;
	}

	/**
	*/
	function save() {
		main()->NO_GRAPHICS = true;
		print_r(json_encode($_POST['structure']));
		db()->update('dashboards2', db()->es(array(
					'data'	=> json_encode($_POST['structure']),
				)), 'id='.intval($_GET['id']));
		exit;
		
	}

	/**
	*/
	function delete () {
		$id = $_GET['id'];
		$ds_info = db()->get('SELECT * FROM '.db('dashboards2').' WHERE name="'.db()->es($id).'" OR id='.intval($id));
		if (!$ds_info['id']) {
			return _e('No such record');
		}
		$_GET['id'] = $ds_info['id'];
		if (!empty($ds_info['id'])) {
			db()->query('DELETE FROM '.db('dashboards2').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			common()->admin_wall_add(array('dashboard deleted: '.$ds_info['name'].'', $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function clone_item() {
		$id = $_GET['id'];
		$ds_info = db()->get('SELECT * FROM '.db('dashboards2').' WHERE name="'.db()->es($id).'" OR id='.intval($id));
		if (!$ds_info['id']) {
			return _e('No such record');
		}
		$_GET['id'] = $ds_info['id'];
		$sql = $ds_info;
		unset($sql['id']);
		$sql['name'] = $sql['name'].'_clone';
		db()->insert('dashboards2', $sql);
		common()->admin_wall_add( array('dashboard cloned: '.$ds_info['name'], db()->insert_id() ));
		return js_redirect('./?object='.$_GET['object']);
	}

	/**
	*/
	function active () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$ds_info = db()->get('SELECT * FROM '.db('dashboards2').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($ds_info['id'])) {
			db()->update('dashboards2', array('active' => (int)!$ds_info['active']), 'id='.intval($_GET['id']));
			common()->admin_wall_add(array('dashboard '.$ds_info['name'].' '.($ds_info['active'] ? 'inactivated' : 'activated'), $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($ds_info['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}	

	/**
	*/
	function lock () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$ds_info = db()->get('SELECT * FROM '.db('dashboards2').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($ds_info['id'])) {
			db()->update('dashboards2', array('lock' => (int)!$ds_info['lock']), 'id='.intval($_GET['id']));
			common()->admin_wall_add(array('dashboard '.$ds_info['name'].' '.($ds_info['lock'] ? 'unlocked' : 'locked'), $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($ds_info['lock'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function add () {
		if (main()->is_post()) {
			if (!_ee()) {
				db()->insert('dashboards2', db()->es(array(
					'name'		=> $_POST['name'],
					'type'		=> $_POST['type'],
					'active'	=> $_POST['active'],
				)));
				$new_id = db()->insert_id();
				common()->admin_wall_add(array('dashboard added: '.$_POST['name'], $new_id));
				return js_redirect('./?object='.$_GET['object'].'&action=edit&id='.$new_id);
			}
		}
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'],
			'back_link'		=> './?object='.$_GET['object'],
		);
		return form2($replace)
			->text('name')
			->radio_box('type', array('admin' => 'admin', 'user' => 'user'))
			->radio_box('lock', array('1' => 'YES', '0' => 'NO'))
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function edit() {
		$id = intval($_GET['id']);
		if (!$id) {
			return _e('No id');
		}
		$a = db()->query_fetch('SELECT * FROM '.db('dashboards2').' WHERE id='.intval($_GET['id']));
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'name'	=> 'trim|required',
			))
			->db_update_if_ok('dashboards2', array('name','type','active'), 'id='.$id, array('on_after_update' => function() {
				common()->admin_wall_add(array('dashboards2 updated: '.$a['name'], $id));
			}))
			->text('name')
			->radio_box('type', array('admin' => 'admin', 'user' => 'user'))
			->radio_box('lock', array('1' => 'YES', '0' => 'NO'))
			->active_box()
			->save_and_back();
	}


	/**
	*/
	function view () {
		$ds = $this->_get_dashboard_data($_GET['id']);

		if (!$ds['id']) {
			return _e('No such record');
		}
		if (main()->is_post()) {
			if (!_ee()) {
				db()->update('dashboards2', db()->es(array(
					'data'	=> json_encode($_POST['ds_data']),
				)), 'id='.intval($ds['id']));
				common()->admin_wall_add(array('dashboard updated: '.$ds['name'], $_GET['id']));
				return js_redirect('./?object='.$_GET['object'].'&action='.$_GET['object']);
			}
		}

		$rows = "";
		if(isset($ds['data']['rows']) && is_array($ds['data']['rows'] )){
			$rows = $this->_get_grid($ds['data']['rows'], false);
		}
		$replace = array(
			'save_link'	      => './?object='.$_GET['object'].'&action=save&id='.$ds['id'],
			'lock_link'	      => './?object='.$_GET['object'].'&action=lock&id='.$ds['id'],
			'dashboard_name'  =>  $ds['name'],
			'rows'	          => $rows,
			'lock'            => $ds['lock'],
		);
		return tpl()->parse(__CLASS__.'/edit_main', $replace);
	}

	function _get_grid($data = array()) {
		
		$rows = array();
		foreach ((array)$data as $row_id => $row_items) {
			$cols = '';
			$num_cols = '';
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
								$_widgets  = array(
										"type" => $content_items["widget"]["type"], 
										"val" => $content_items["widget"]["val"]
									);
								$content .= tpl()->parse(__CLASS__.'/widgets', $_widgets);
							}
						}
					}
					$cols .= '<div class="col-md-'.$row_class.' span'.$row_class.' column ui-sortable"> '.$content.' </div>';
					$num_cols .= " ". $row_class;
				}
			}
			
			$rows [] = array('cols' => $cols, 'num_cols' => trim($num_cols), );
		}
		$replace = array(
			'rows'	=> $rows,
		);

		return tpl()->parse(__CLASS__.'/grid', $replace);
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
	*/
	function _options_container($info = array(), $saved = array(), $ds = array()) {
		$for_section = $ds['type'] == 'user' ? 'user' : 'admin';

		$a = array();
		if ($info['cloneable']) {
			$a[] = array('text', 'name', array('class' => 'input-medium'));
			$a[] = array('text', 'desc', 'Description', array('class' => 'input-medium'));
			if ($info['auto_type'] == 'php_item') {
				$a[] = array('text', 'method_name', 'Custom class method');
			} elseif ($info['auto_type'] == 'block_item') {
				$a[] = array('select_box', 'block_name', main()->get_data('blocks_names_'.$for_section));
			} elseif ($info['auto_type'] == 'stpl_item') {
				$a[] = array('text', 'stpl_name', 'Custom template');
			}
#			$a[] = array('text', 'html_id', array('class' => 'input-medium'));
#			$a[] = array('textarea', 'code');
		}
		$a[] = array('check_box', 'hide_header', '1', array('no_label' => 1));
		$a[] = array('check_box', 'hide_border', '1', array('no_label' => 1));
		$a[] = array('text', 'grid_class', array('class' => 'input-small'));
		$a[] = array('text', 'offset_class', array('class' => 'input-small'));
		foreach ((array)$info['configurable'] as $k => $v) {
			$a[] = array('select_box', $k, $v);
		}
		return tpl()->parse(__CLASS__.'/ds_options', array(
			'form_items'	=> form($saved, array('class' => 'form-horizontal form-condensed'))->array_to_form($a),
			'color'			=> $saved['color'],
			'item_id'		=> _prepare_html($info['auto_id']),
			'auto_type'		=> $info['cloneable'] ? $info['auto_type'] : '',
		));
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
			$ds['data'] = json_decode($ds['data'], $assoc = true);
		}
		$this->_dashboard_data[$id] = $ds;
		return $ds;
	}

	




}
