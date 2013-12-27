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

				$fields = array('name','cat_id','price','active','quantity','manufacturer_id','supplier_id','add_date','update_date');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form($replace, array('selected' => $_SESSION[$filter_name]))
					->text('name')
					->text('id')
					->text('articul')
					->money('price', array('class' => 'span1'))
					->money('price__and', array('class' => 'span1'))
					->select_box('cat_id', _class('cats')->_get_items_names('shop_cats'), array('desc' => 'Main category', 'show_text' => 1))
					->radio_box('image', array(0 => 'No image', 1 => 'Have image'))
					->select_box('order_by', $order_fields, array('show_text' => 1))
					->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
					->save_and_clear();

			},
			'users' => function($filter_name, $replace) {

				$fields = array('add_date','id','name','email','phone');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form($replace, array('selected' => $_SESSION[$filter_name]))
					->number('id', array('class' => 'span1'))
					->text('name')
					->text('email')
					->text('phone')
					->text('address')
					->select_box('order_by', $order_fields)
					->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
					->save_and_clear();

			},
			'orders' => function($filter_name, $replace) {

				$fields = array('id','add_date');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form($replace, array('selected' => $_SESSION[$filter_name]))
					->number('id', array('class' => 'span1'))
					->number('id__and', array('class' => 'span1'))
					->select_box('order_by', $order_fields)
					->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
					->save_and_clear();

			},
			'manufacturers' => function($filter_name, $replace) {

				$fields = array('id','name','add_date');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form($replace, array('selected' => $_SESSION[$filter_name]))
					->text('name')
					->select_box('order_by', $order_fields)
					->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
					->save_and_clear();

			},
			'suppliers' => function($filter_name, $replace) {

				$fields = array('id','name','add_date');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form($replace, array('selected' => $_SESSION[$filter_name]))
					->text('name')
					->select_box('order_by', $order_fields)
					->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
					->save_and_clear();

			},
			'product_sets' => function($filter_name, $replace) {

				$fields = array('id','name','cat_id','add_date');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form($replace, array('selected' => $_SESSION[$filter_name]))
					->text('name')
					->select_box('cat_id', _class('cats')->_get_items_names('shop_cats'), array('desc' => 'Main category'))
					->select_box('order_by', $order_fields)
					->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
					->save_and_clear();

			},
			'attributes' => function($filter_name, $replace) {

				$fields = array('id','title','add_date');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form($replace, array('selected' => $_SESSION[$filter_name]))
					->text('title')
					->select_box('order_by', $order_fields)
					->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
					->save_and_clear();

			},
		);
		$action = $_GET['action'];
		if (isset($filters[$action])) {
			return $filters[$action]($filter_name, $replace);
		}
		return false;
	}
}