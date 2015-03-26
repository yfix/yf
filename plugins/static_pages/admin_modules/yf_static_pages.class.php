<?php

/**
* Static/HTML pages content editor
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_static_pages {

	const table = 'static_pages';

	/**
	*/
	function _get_info($id = null) {
		$id = isset($id) ? $id : $_GET['id'];
		return db()->from(self::table)
			->where('name', _strtolower(urldecode($id)) )
			->or_where('id', (int)$id)->get();
	}

	/**
	*/
	function _fix_page_name($in = null) {
		if (!strlen($in)) {
			return '';
		}
		// Detect non-latin characters
		if (strlen($in) !== _strlen($in)) {
			$in = common()->make_translit(_strtolower($in));
		}
		$in = preg_replace('/[^a-z0-9\_\-]/i', '_', strtolower($in));
		$in = trim(str_replace(array('__', '___'), '_', $in), '_');
		return $in;
	}

	/**
	*/
	function show() {
		return table(db()->from(self::table), array(
				'filter' => true,
				'filter_params' => array(
					'name'	=> 'like',
				),
			))
			->text('name')
			->btn_edit(array('no_ajax' => 1))
			->btn_delete()
			->btn('View', url('/@object/view/%d'))
			->btn_active()
			->footer_link('Add', url('/@object/add'));
	}

	/**
	*/
	function add() {
		$a = (array)$_POST + (array)$a;
		$a['back_link'] = url('/@object');
		$_this = $this;
		return form($a)
			->validate(array(
				'__before__'=> 'trim',
				'name' => array('required', function(&$in) use ($_this) {
					$in = $_this->_fix_page_name($in);
					return (bool)strlen($in);
				}),
			))
			->db_insert_if_ok(self::table, array('name'))
			->on_after_update(function() {
				$id = db()->insert_id();
				common()->admin_wall_add(array('static page added: '.$name, $id));
				cache_del('static_pages_names');
				js_redirect(url('/@object/edit/'.$id));
			})
			->text('name')
			->save_and_back()
		;
	}

	/**
	*/
	function edit() {
		$a = $this->_get_info();
		if (!$a) {
			return _404();
		}
		$a = (array)$_POST + (array)$a;
		$a['back_link'] = url('/@object');
		$_this = $this;
		return form($a)
			->validate(array(
				'__before__'=> 'trim',
				'name' => array('required', function(&$in) use ($_this) {
					$in = $_this->_fix_page_name($in);
					return (bool)strlen($in);
				}),
				'text' => 'required',
			))
			->db_update_if_ok(self::table, array('name','text','page_title','page_heading','meta_keywords','meta_desc','active'), 'id='.$a['id'])
			->on_after_update(function() {
				common()->admin_wall_add(array('static page updated: '.$a['name'], $a['id']));
				cache_del('static_pages_names');
			})
			->text('name')
			->textarea('text', array('id' => 'text', 'cols' => 200, 'rows' => 10, 'ckeditor' => array('config' => _class('admin_methods')->_get_cke_config())))
			->text('page_title')
			->text('page_heading')
			->text('meta_keywords')
			->text('meta_desc')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		$a = $this->_get_info();
		if ($a) {
			db()->from(self::table)->whereid($a['id'])->delete();
			common()->admin_wall_add(array('static page deleted: '.$a['id'], $a['id']));
			cache_del('static_pages_names');
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $page_name;
		} else {
			return js_redirect(url('/@object'));
		}
	}

	/**
	*/
	function active () {
		$a = $this->_get_info();
		if (!empty($a['id'])) {
			db()->update(self::table, array('active' => (int)!$a['active']), (int)$a['id']);
			common()->admin_wall_add(array('static page: '.$a['name'].' '.($a['active'] ? 'inactivated' : 'activated'), $a['id']));
			cache_del('static_pages_names');
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo intval( ! $a['active']);
		} else {
			return js_redirect(url('/@object'));
		}
	}

	/**
	*/
	function view() {
		$a = $this->_get_info();
		if (empty($a)) {
			return _404();
		}
		$body = stripslashes($a['text']);
		$replace = array(
			'form_action'	=> url('/@object/edit/'.$a['id']),
			'back_link'		=> url('/@object'),
			'body'			=> $body,
		);
		return form($replace)
			->container($body, '', array(
				'id'	=> 'content_editable',
				'wide'	=> 1,
				'ckeditor' => array(
					'hidden_id'	=> 'text',
				),
			))
			->hidden('text')
			->save_and_back();
	}

	/**
	*/
	function _show_header() {
		$pheader = t('Static pages');
		$subheader = _ucwords(str_replace('_', ' ', $_GET['action']));
		$cases = array (
			//$_GET['action'] => {string to replace}
			'show'	=> '',
			'edit'	=> '',
		);
		if (isset($cases[$_GET['action']])) {
			$subheader = $cases[$_GET['action']];
		}
		return array(
			'header'	=> $pheader,
			'subheader'	=> $subheader ? _prepare_html($subheader) : '',
		);
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
		$order_fields = array(
			'name'		=> 'name',
			'active'	=> 'active',
		);
		return form($r, array(
				'class' => 'form-vertical',
				'filter' => true,
			))
			->text('name')
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'), array('horizontal' => true))
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__static_pages_list ($params = array()) {
		$meta = array(
			'name' => 'Static pages quick access',
			'desc' => 'List of static pages with quick links to edit/preview',
			'configurable' => array(
				'order_by'	=> array('id','name','active'),
			),
		);
		if ($params['describe_self']) {
			return $meta;
		}
		$sql = db()->from(self::table);

		$config = $params;
		$avail_orders = $meta['configurable']['order_by'];
		if (isset($avail_orders[$config['order_by']])) {
			$sql->order_by($avail_orders[$config['order_by']]);
		}
		$avail_limits = $meta['configurable']['limit'];
		if (isset($avail_limits[$config['limit']])) {
			$sql->limit($avail_limits[$config['limit']]);
		}
		return table($sql, array('no_header' => 1, 'btn_no_text' => 1))
			->link('name', url('/@object/view/%d'), '', array('width' => '100%'))
			->btn_edit()
		;
	}
}
