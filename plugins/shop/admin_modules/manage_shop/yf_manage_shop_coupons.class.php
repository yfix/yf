<?php
class yf_manage_shop_coupons{

	/**
	*/
	function _init() {
	}

	/**
	*/
	function coupons() {
		return table('SELECT * FROM '.db('shop_coupons'), array(
//				'filter' => $_SESSION[$_GET['object'].'__coupons'],
			))
			->text('code')
            ->user('user_id')
            ->text('total_sum', array('nowrap' => 1))                
            ->date('time_start', array('format' => 'full', 'nowrap' => 1))
            ->date('time_end', array('format' => 'full', 'nowrap' => 1))
            ->link('cat_id', './?object=category_editor&action=edit_item&id=%d', _class('cats')->_get_items_names_cached('shop_cats'))
            ->text('status')
/*			->btn_edit('', './?object='.main()->_get('object').'&action=coupon_edit&id=%d',array('no_ajax' => 1))
			->btn_delete('', './?object='.main()->_get('object').'&action=coupon_delete&id=%d')
			->footer_add('', './?object='.main()->_get('object').'&action=coupon_add') */
		;
	}
}
