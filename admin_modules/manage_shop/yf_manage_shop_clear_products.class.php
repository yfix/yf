<?php

/**
*/
class yf_manage_shop_clear_products {

	/**
	 */
	function clear_patterns () {
		return table('SELECT * FROM '.db('shop_patterns'), array(
			'filter' => $_SESSION[$_GET['object'].'__patterns'],
			'filter_params' => array(
				'search'  => 'like',
				'repalce' => 'like',
			),
		))
		->text('search')
		->text('replace')
		->func('id', function($value, $extra, $row_info){
			$sql = 'SELECT COUNT(*) AS `0` FROM '.db('shop_products').' WHERE LOWER(name) REGEXP \'[[:<:]]'.strtolower($row_info['search']).'[[:>:]]\'';
			list($count) = db()->query_fetch($sql);
			return '<span class="badge badge-info">'.$count.'</span>';
		}, array('desc' => 'Products'))
			->btn('Run', './?object=manage_shop&action=clear_pattern_edit&id=%d', array('icon' => 'icon-play', 'class' => 'btn-info'))
			->btn_edit('', './?object=manage_shop&action=clear_pattern_edit&id=%d',array('no_ajax' => 1))
			->btn_delete('', './?object=manage_shop&action=clear_pattern_delete&id=%d')
			->btn_active('', './?object=manage_shop&action=clear_pattern_activate&id=%d')
			->footer_add('Add product', './?object=manage_shop&action=clear_pattern_add',array('no_ajax' => 1))
		;
	}

	function clear_pattern_add () {
		$validate_rules = array(
			'search' => array('trim|required|xss_clean'),
			'replace'	 => array('trim|required|xss_clean'),
		);

		$a = $_POST;
		$a['redirect_link'] = './?object=manage_shop&action=clear_patterns';

		return form($a, array('legend' => t('Add pattern')))
			->validate($validate_rules)
			->db_insert_if_ok('shop_patterns', array('search','replace'))
			->text('search')
			->text('replace')
			->select_box('cat_id', module('manage_shop')->_cats_for_select, array('desc' => 'Category', 'show_text' => 1))
			->save();
	}

	function clear_pattern_edit () {
		if (!isset($_GET['id']) && intval($_GET['id'])) {
			return t('Empty clear pattern ID');
		}

		$_GET['id'] = intval($_GET['id']);

		$pattern_info = db()->query_fetch('SELECT * FROM '.db('shop_patterns').' WHERE id = '.$_GET['id']);
		if (empty($pattern_info)) {
			return t('Wrong clean pattern');
		}

		$validate_rules = array(
			'search' => array('trim|required|xss_clean'),
			'replace'	 => array('trim|required|xss_clean'),
		);

		$a = $pattern_info;
		$a['redirect_link'] = './?object=manage_shop&action=clear_patterns';

		return form($a, array('legend' => t('Edit pattern')))
			->validate($validate_rules)
			->db_update_if_ok('shop_patterns', array('search','replace'), 'id = '.$_GET['id'])
			->text('search')
			->text('replace')
			->select_box('cat_id', module('manage_shop')->_cats_for_select, array('desc' => 'Category', 'show_text' => 1))
			->save();
	}

	/*
	 * 
	 */
	function clear_pattern_activate () {
		if ($_GET['id']){
			$pattern_info = db()->query_fetch('SELECT * FROM '.db('shop_patterns').' WHERE id='.intval($_GET['id']));
			if ($pattern_info['active'] == 1) {
				$active = 0;
			} elseif ($pattern_info['active'] == 0) {
				$active = 1;
			}
			db()->UPDATE(db('shop_patterns'), array('active' => $active), 'id='.intval($_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($active ? 1 : 0);
		} else {
			return js_redirect('./?object=manage_shop&action=');
		}
	}

	/**
	 */
	function clear_pattern_delete () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return 'Empty ID!';
		}
		db()->query('DELETE FROM '.db('shop_patterns').' WHERE id='.$_GET['id']);
		return js_redirect('./?object=manage_shop&action=show_clear_patterns');
	}

	/**
	 */
	function clear_patterns_by_category ($cat = '') {
		main()->NO_GRAPHICS = true;
		$cat_id =  $_GET['cat_id'];
		$sql1 = 'SELECT product_id FROM '.db('shop_product_to_category').' WHERE category_id ='. $cat_id ;
		$products = db()->query($sql1);
		while ($pattern_info = db()->fetch_assoc($products)) {
			$product_info .= $pattern_info['product_id'].',';
		}	
		$product_info = rtrim($product_info, ',');

		$sql = 'SELECT * FROM '.db('shop_patterns').' WHERE active="1" AND id IN ('.$product_info .')  ORDER BY name';
		$product = db()->query_fetch_all($sql);
		$products = array();
		foreach ((array)$product as $v) {
			$products []  = array (
				'product_id' => $v['id'],
				'name'       => $v['name'],
			);
		}
		echo json_encode($products);
	}	
}
