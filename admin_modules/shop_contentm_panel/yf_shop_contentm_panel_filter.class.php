<?php

class yf_shop_contentm_panel_filter {

	/**
	*/
	function filter_save() {
		$filter_name = $_GET['object'].'__products';
		$_GET['id'] = preg_replace('~[^0-9a-z_]+~ims', '', $_GET['id']);
		if ($_GET['id'] && false !== strpos($_GET['id'], $_GET['object'].'__')) {
			$filter_name = $_GET['id'];
		}
		if ($_GET['sub'] == 'clear') {
			$_SESSION[$filter_name] = array();
		} else {
			$_SESSION[$filter_name] = $_POST;
		}
		$action = 'products';
		if ($filter_name == $_GET['object'].'__products') {
			$action = 'products';
		} elseif ($filter_name == $_GET['object'].'__orders') {
			$action = 'orders';
		}
		return js_redirect('./?object='.$_GET['object'].'&action='.$action);
	}

	/**
	* Hook
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], array('products','orders'))) {
			return false;
		}
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&sub=clear&id='.$filter_name,
		);
		$filters = array(
			'products'	=> function($filter_name, $replace) {
				$fields = array(
					'name','price','active','quantity','manufacturer_id','add_date','update_date'
					// cat_id
				);
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form2($replace, array(
						'selected' => $_SESSION[$filter_name]
					))
					->text('name')
					->money('price', '', array('class' => 'span1'))
					->money('price__and', '', array('class' => 'span1'))
#					->select_box('cat_id', _class('cats')->_get_items_names("shop_cats"), array('desc' => 'Main category'))
					->radio_box('image', array(0 => 'No image', 1 => 'Have image'))
					->select_box('order_by', $order_fields)
					->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
					->save_and_clear();
			},
			'orders' => function($filter_name, $replace) {
				$fields = array(
					'id','add_date'
				);
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				return form2($replace, array(
						'selected' => $_SESSION[$filter_name]
					))
#					->number('id', '', array('class' => 'span1'))
#					->number('id__and', '', array('class' => 'span1'))
					->select_box('order_by', $order_fields)
					->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
					->save_and_clear();
			}
		);
		$action = $_GET['action'];
		if (isset($filters[$action])) {
			return $filters[$action]($filter_name, $replace);
		}
		return false;
	}
}
