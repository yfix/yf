<?php

class yf_manage_shop_hook_side_column {

	/***/
	function _hook_side_column () {
		if ($_GET['action'] == 'product_edit') {
			return $this->_product_revisions();
		} elseif ($_GET['action'] == 'product_revisions') {
			return $this->_product_revisions_similar();
		} elseif ($_GET['action'] == 'view_order') {
			return $this->_order_revisions();
		}
		return '';
	}

	/***/
	function _product_revisions () {
		$product_id = intval($_GET['id']);
		$product_info = module('manage_shop')->_product_get_info($product_id);
		if (!$product_info) {
			return false;
		}
		$sql = 'SELECT * FROM '.db('shop_product_revisions').' WHERE item_id='.intval($product_id).' ORDER BY add_date DESC';
		return table($sql, array(
				'caption' => t('Product revisions'),
				'no_records_html' => '',
			))
			->date('add_date', array('format' => 'full', 'nowrap' => 1))
			->admin('user_id', array('desc' => 'admin'))
			->text('action')
			->btn_view('', './?object=manage_shop&action=product_revisions&id=%d')
		;
	}

	/***/
	function _product_revisions_similar () {
		$rev = db()->get('SELECT * FROM '.db('shop_product_revisions').' WHERE id='.intval($_GET['id']));
		$product_id = intval($rev['item_id']);
		$product_info = module('manage_shop')->_product_get_info($product_id);
		if (!$product_info) {
			return false;
		}
		$sql = 'SELECT * FROM '.db('shop_product_revisions').' WHERE item_id='.intval($product_id).' ORDER BY add_date DESC';
		return table($sql, array(
				'caption' => t('Product revisions'),
				'no_records_html' => '',
				'tr' => array(
					$rev['id'] => array('class' => 'success'),	
				),
			))
			->date('add_date', array('format' => 'full', 'nowrap' => 1))
			->admin('user_id', array('desc' => 'admin'))
			->text('action')
			->btn_view('', './?object=manage_shop&action=product_revisions&id=%d')
		;
	}

	/***/
	function _order_revisions () {
// TODO
	}
}