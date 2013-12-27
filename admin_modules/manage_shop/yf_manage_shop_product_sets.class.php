<?php
class yf_manage_shop_product_sets{

	/**
	*/
	function _init() {
		// Params for the 'admin_methods'
		$this->_table = array(
			'table'	=> db('shop_product_sets'),
		);
// TODO: check and test everything
	}

	/**
	*/
	function product_sets () {
		return table('SELECT * FROM '.db('shop_product_sets'), array(
				'filter' => $_SESSION[$_GET['object'].'__product_sets']
			))
			->image('id', 'uploads/shop/product_sets/%d.jpg', array('width' => '50px'))
			->text('name')
			->text('description')
			->link('cat_id', './?object=category_editor&action=show_items&&id=%d', _class('cats')->_get_items_names_cached('shop_cats'))
			->text('price')
			->text('old_price')
#			->text('products')
			->btn_edit('','','./?object=manage_shop&action=product_set_edit&id=%d')
			->btn_delete('','','./?object=manage_shop&action=product_set_delete&id=%d')
			->footer_add('','./?object=manage_shop&action=product_set_add')
		;
	}	

	/**
	*/
	function product_set_add () {
//		$replace = _class('admin_methods')->add($this->_table);
#		$replace['form_action'] = './?object=manage_shop&action=product_set_add';
		return form($replace)
			->text('name')
			->textarea('desc')
			->textarea('products')
			->select_box('cat_id', module('manage_shop')->_cats_for_select)
			->save_and_back()
		;
	}	

	/**
	*/
	function product_set_edit () {
//		$replace = _class('admin_methods')->edit($this->_table);
#		$replace['form_action'] = './?object=manage_shop&action=product_set_edit&id='.$_GET['id'];
		return form($replace)
			->text('name')
			->textarea('desc')
			->textarea('products')
			->select_box('cat_id', module('manage_shop')->_cats_for_select)
			->save_and_back()
		;
	}	

	/**
	*/
	function product_set_delete () {
		return _class('admin_methods')->delete($this->_table);
	}	
}