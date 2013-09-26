<?php

class yf_manage_shop_filter{

	/**
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], array('products','users'))) {
			return false;
		}
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save',
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&sub=clear',
		);
		if ($_GET['action'] == 'users') {
			$fields = array(
				'id','name','email','phone'
			);
			foreach ((array)$fields as $v) {
				$order_fields[$v] = $v;
			}
			return form2($replace, array(
					'selected' => $_SESSION['manage_users']
				))
				->text('id')
				->text('name')
				->text('email')
				->text('phone')
				->text('address')
				->select_box('order_by', $order_fields)
				->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
				->save_and_clear()
			;			
		} elseif ($_GET['action'] == 'products') {
			$fields = array(
				'name',	'cat_id','price','active','quantity','manufacturer_id','supplier_id','add_date','update_date'
			);
			foreach ((array)$fields as $v) {
				$order_fields[$v] = $v;
			}
			return form2($replace, array(
					'selected' => $_SESSION['manage_shop']
				))
				->text('name')
				->money('price', '', array('class' => 'span1'))
				->money('price__and', '', array('class' => 'span1'))
				->select_box('cat_id', _class('cats')->_get_items_names("shop_cats"), array('desc' => 'Main category'))
				->radio_box('image', array(0 => 'No image', 1 => 'Have image'))
				->select_box('order_by', $order_fields)
				->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
				->save_and_clear()
			;
		}
	}

	/**
	*/
	function filter_save() {
		$arr = parse_url($_SERVER['HTTP_REFERER']);
		parse_str($arr['query'],$query);
		if ($query['action'] == 'users') {
			if ($_GET['sub'] == 'clear') {
				$_SESSION['manage_users'] = array();
			} else {
				$_SESSION['manage_users'] = $_POST;
			}
			return js_redirect('./?object='.$_GET['object'].'&action=users');
		} elseif ($query['action'] == 'products') {
			if ($_GET['sub'] == 'clear') {
				$_SESSION['manage_shop'] = array();
			} else {
				$_SESSION['manage_shop'] = $_POST;
			}
			return js_redirect('./?object='.$_GET['object'].'&action=products');
		}
	}
}