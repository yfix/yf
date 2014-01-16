<?php

class yf_manage_shop_filter{

	public $_avail_filters = array('products','users','orders','suppliers','manufacturers','product_sets','attributes');

	/**
	*/
	function filter_save() {
		$_GET['id'] = preg_replace('~[^0-9a-z_]+~ims', '', $_GET['id']);
		if ($_GET['id'] && false !== strpos($_GET['id'], $_GET['object'].'__')) {
			$filter_name = $_GET['id'];
			list(,$action) = explode('__', $filter_name);
		}
		if (!in_array($action, $this->_avail_filters)) {
			return js_redirect('./?object='.$_GET['object']);
		}
		if ($_GET['page'] == 'clear') {
			$_SESSION[$filter_name] = array();
		} else {
			$_SESSION[$filter_name] = $_POST;
			foreach (explode('|', 'clear_url|form_id|submit') as $f) {
				if (isset($_SESSION[$filter_name][$f])) {
					unset($_SESSION[$filter_name][$f]);
				}
			}
		}
		return js_redirect('./?object='.$_GET['object'].'&action='.$action);
	}

	/**
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], $this->_avail_filters)) {
			return false;
		}
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		);
		$filters = array(
			'products'	=> function($filter_name, $replace) {

				$fields = array('id','name','cat_id','price','active','quantity','manufacturer_id','supplier_id','add_date','update_date');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form($replace, array('selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'))
					->number('id')
					->text('name')
					->text('articul')
					->row_start(array('desc' => 'price'))
						->number('price')
						->number('price__and')
					->row_end()
					->select_box('cat_id', module('manage_shop')->_cats_for_select, array('desc' => 'Main category', 'show_text' => 1, 'no_translate' => 1))
					->select_box('supplier_id', _class('manage_shop')->_suppliers_for_select, array('desc' => 'Supplier', 'no_translate' => 1, 'hide_empty' => 1))
					->select_box('manufacturer_id', _class('manage_shop')->_man_for_select, array('desc' => 'Manufacturer', 'no_translate' => 1))
					->active_box('active', array('horizontal' => 1))
					->yes_no_box('image', array('horizontal' => 1))
					->select_box('order_by', $order_fields, array('show_text' => 1));

			},
			'users' => function($filter_name, $replace) {

				$fields = array('add_date','id','name','email','phone');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form($replace, array('selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'))
					->number('id', array('class' => 'span1'))
					->text('name')
					->text('email')
					->text('phone')
					->text('address')
					->select_box('order_by', $order_fields, array('show_text' => 1));

			},
			'orders' => function($filter_name, $replace) {

				$fields = array('id','date','name','phone','email','total_sum','user_id','status');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form($replace, array('selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'))
					->row_start(array('desc' => 'id'))
						->number('id', array('class' => 'span1'))
						->number('id__and', array('class' => 'span1'))
					->row_end()
					->text('name')
					->text('phone')
					->text('email')
					->number('user_id')
					->select_box('status', common()->get_static_conf('order_status'), array('show_text' => 1))
					->select_box('order_by', $order_fields, array('show_text' => 1));

			},
			'manufacturers' => function($filter_name, $replace) {

				$fields = array('id','name','add_date');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form($replace, array('selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'))
					->text('name')
					->select_box('order_by', $order_fields, array('show_text' => 1));

			},
			'suppliers' => function($filter_name, $replace) {

				$fields = array('id','name','add_date');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form($replace, array('selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'))
					->text('name')
					->select_box('order_by', $order_fields, array('show_text' => 1));

			},
			'product_sets' => function($filter_name, $replace) {

				$fields = array('id','name','cat_id','add_date');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form($replace, array('selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'))
					->text('name')
					->select_box('cat_id', _class('cats')->_get_items_names_cached('shop_cats'), array('desc' => 'Main category'))
					->select_box('order_by', $order_fields, array('show_text' => 1));

			},
			'attributes' => function($filter_name, $replace) {

				$fields = array('id','title','add_date');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form($replace, array('selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'))
					->text('title')
					->select_box('order_by', $order_fields, array('show_text' => 1));

			},
		);
		$action = $_GET['action'];
		if (isset($filters[$action])) {
			return $filters[$action]($filter_name, $replace)
				->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'), array('horizontal' => 1))
				->save_and_clear();
		}
		return false;
	}
}