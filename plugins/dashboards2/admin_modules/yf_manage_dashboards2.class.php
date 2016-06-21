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
	private $_col_classes = [
		1 => 'span12 col-md-12 column',
		2 => 'span6 col-md-6 column',
		3 => 'span4 col-md-4 column',
		4 => 'span3 col-md-3 column',
		6 => 'span2 col-md-2 column',
		12 => 'span1 col-md-1 column',
	];

// TODO: add ability to use user module dashboards2 also

	/**
	*/
	function _init () {
		conf('css_framework', 'bs3');
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
		db()->update('dashboards2', db()->es([
					'data'	=> json_encode($_POST['structure']),
				]), 'id='.intval($_GET['id']));
		exit;
		
	}

		/**
	*/
	function parse_structure() {
		main()->NO_GRAPHICS = true;
		$data = $_POST['structure'];
		foreach ($data['cols']  as $key => $_cols){
			$cols [] = [
				'class' => str_replace ( ' column ui-sortable', '', $_cols['class'] ), 
				'id'    => $_cols['id'],
				'key'   => $key,
			];
		}
		$replace = [
			'row_class' => $data['class'],
			'row_id'	=> $data['id'],
			'cols'      => $cols,
		];
		$form =   tpl()->parse(__CLASS__.'/form_class', $replace);
echo json_encode(['data'=>$form]);
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
			common()->admin_wall_add(['dashboard deleted: '.$ds_info['name'].'', $_GET['id']]);
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
		common()->admin_wall_add( ['dashboard cloned: '.$ds_info['name'], db()->insert_id() ]);
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
			db()->update('dashboards2', ['active' => (int)!$ds_info['active']], 'id='.intval($_GET['id']));
			common()->admin_wall_add(['dashboard '.$ds_info['name'].' '.($ds_info['active'] ? 'inactivated' : 'activated'), $_GET['id']]);
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
			db()->update('dashboards2', ['lock' => (int)!$ds_info['lock']], 'id='.intval($_GET['id']));
			common()->admin_wall_add(['dashboard '.$ds_info['name'].' '.($ds_info['lock'] ? 'unlocked' : 'locked'), $_GET['id']]);
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
				db()->insert('dashboards2', db()->es([
					'name'		=> $_POST['name'],
					'type'		=> $_POST['type'],
					'active'	=> $_POST['active'],
				]));
				$new_id = db()->insert_id();
				common()->admin_wall_add(['dashboard added: '.$_POST['name'], $new_id]);
				return js_redirect('./?object='.$_GET['object'].'&action=edit&id='.$new_id);
			}
		}
		$replace = [
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'],
			'back_link'		=> './?object='.$_GET['object'],
		];
		return form2($replace)
			->text('name')
			->radio_box('type', ['admin' => 'admin', 'user' => 'user'])
			->radio_box('lock', ['1' => 'YES', '0' => 'NO'])
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
		return form($a, ['autocomplete' => 'off'])
			->validate([
				'name'	=> 'trim|required',
			])
			->db_update_if_ok('dashboards2', ['name','type','active'], 'id='.$id, ['on_after_update' => function() {
				common()->admin_wall_add(['dashboards2 updated: '.$a['name'], $id]);
			}])
			->text('name')
			->radio_box('type', ['admin' => 'admin', 'user' => 'user'])
			->radio_box('lock', ['1' => 'YES', '0' => 'NO'])
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
				db()->update('dashboards2', db()->es([
					'data'	=> json_encode($_POST['ds_data']),
				]), 'id='.intval($ds['id']));
				common()->admin_wall_add(['dashboard updated: '.$ds['name'], $_GET['id']]);
				return js_redirect('./?object='.$_GET['object'].'&action='.$_GET['object']);
			}
		}

		$rows = "";
		if(isset($ds['data']['rows']) && is_array($ds['data']['rows'] )){
			$rows = $this->_get_grid($ds['data']['rows'], false);
		}
		$replace = [
			'save_link'	      => './?object='.$_GET['object'].'&action=save&id='.$ds['id'],
			'parse_structure' => './?object='.$_GET['object'].'&action=parse_structure&id='.$ds['id'],
			'lock_link'	      => './?object='.$_GET['object'].'&action=lock&id='.$ds['id'],
			'dashboard_name'  =>  $ds['name'],
			'rows'	          => $rows,
			'lock'            => $ds['lock'],
		];
		return tpl()->parse(__CLASS__.'/edit_main', $replace);
	}

	function _get_grid($data = []) {
		
		$rows = [];
		foreach ((array)$data as $row_id => $row_items) {
			$cols = '';
			$num_cols = '';
			if(isset($row_items["cols"]) && is_array($row_items["cols"] )){
				foreach ((array)$row_items['cols'] as $col_id => $col_items) {
					$content = "";
					if(is_array($col_items['class'])){
						$col_class = $col_items["class"][0];
					}else {
						$col_class = $col_items['class'];
					}
					if(isset($col_items["content"]) && is_array($col_items["content"] )){
						foreach ((array)$col_items["content"] as $content_id => $content_items) {
							if(isset($content_items["rows"]) && is_array($content_items["rows"] )){
								$content .= $this->_get_grid($content_items["rows"]);
							}
							if(isset($content_items["widget"]) && is_array($content_items["widget"] )){
								$_widgets  = [
										"type" => $content_items["widget"]["type"], 
										"val" => $content_items["widget"]["val"]
									];
								$content .= tpl()->parse(__CLASS__.'/widgets', $_widgets);
							}
						}
					}
					$id = '';
					if(!empty($col_items["id"])){
						$id = ' id="'.$col_items["id"]. '" ';
					}
					if(is_array($col_items['class'])){
						$cols .= '<div class="col-md-'.$col_class.' column ui-sortable"'.$id.' > '.$content.' </div>';
						$num_cols .= " ". $col_class;
					}else {
						preg_match( '/[\d]+/', $col_class, $matches); 
						$cols .= '<div class="'.$col_class.' column ui-sortable"' .$id.'> '.$content.' </div>';
						$num_cols .= " ". $matches[0];
					}
				}
			}
			
			$rows [] = [
				'cols'      => $cols,
				'num_cols'  => trim($num_cols),
				'id'        => $row_items['id'],
				'class'     => trim($row_items['class'])
			];
		}
		$replace = [
			'rows'	=> $rows,
		];

		return tpl()->parse(__CLASS__.'/grid', $replace);
	}

	/**
	* Designed to be used by other modules to show configured dashboard
	*/
	function display($params = []) {
		if (is_string($params)) {
			$name = $params;
		}
		if (!is_array($params)) {
			$params = [];
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
	function _options_container($info = [], $saved = [], $ds = []) {
		$for_section = $ds['type'] == 'user' ? 'user' : 'admin';

		$a = [];
		if ($info['cloneable']) {
			$a[] = ['text', 'name', ['class' => 'input-medium']];
			$a[] = ['text', 'desc', 'Description', ['class' => 'input-medium']];
			if ($info['auto_type'] == 'php_item') {
				$a[] = ['text', 'method_name', 'Custom class method'];
			} elseif ($info['auto_type'] == 'block_item') {
				$a[] = ['select_box', 'block_name', main()->get_data('blocks_names_'.$for_section)];
			} elseif ($info['auto_type'] == 'stpl_item') {
				$a[] = ['text', 'stpl_name', 'Custom template'];
			}
#			$a[] = array('text', 'html_id', array('class' => 'input-medium'));
#			$a[] = array('textarea', 'code');
		}
		$a[] = ['check_box', 'hide_header', '1', ['no_label' => 1]];
		$a[] = ['check_box', 'hide_border', '1', ['no_label' => 1]];
		$a[] = ['text', 'grid_class', ['class' => 'input-small']];
		$a[] = ['text', 'offset_class', ['class' => 'input-small']];
		foreach ((array)$info['configurable'] as $k => $v) {
			$a[] = ['select_box', $k, $v];
		}
		return tpl()->parse(__CLASS__.'/ds_options', [
			'form_items'	=> form($saved, ['class' => 'form-horizontal form-condensed'])->array_to_form($a),
			'color'			=> $saved['color'],
			'item_id'		=> _prepare_html($info['auto_id']),
			'auto_type'		=> $info['cloneable'] ? $info['auto_type'] : '',
		]);
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
