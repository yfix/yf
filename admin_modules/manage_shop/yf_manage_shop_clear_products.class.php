<?php

/**
*/
class yf_manage_shop_clear_products {

	public $CATEGORY_SQL = false;

	function _init () {
		$this->CATEGORY_SQL = 'SELECT * FROM '.db('category_items').' WHERE cat_id = 1;';
	}

	/**
	 */
	function clear_patterns () {
		return table('SELECT * FROM '.db('shop_patterns'), array(
			'filter' => $_SESSION[$_GET['object'].'__patterns'],
			'filter_params' => array(
				'search'  => 'like',
				'repalce' => 'like',
				'cat_id'  => 'in',
			),
		))
		->text('search')
		->text('replace')
		->func('id', function($value, $extra, $row_info){
			$where = '';	
			if (!empty($pattern_info['cat_id'])) {
				$cat_ids = $this->get_recursive_cat_ids($pattern_info['cat_id']);
				$where = ' AND (cat_id IN ('.implode(',', $cat_ids).') OR id IN (SELECT product_id FROM '.db('shop_product_to_category').' WHERE category_id IN ('.implode(',', $cat_ids).')))';
			}
			$sql = 'SELECT COUNT(*) AS `0` FROM '.db('shop_products').' WHERE LOWER(name) REGEXP \'[[:<:]]'.mb_strtolower($row_info['search'], 'UTF-8').'[[:>:]]\''.$where;
			list($count) = db()->query_fetch($sql);
			return '<span class="badge badge-info">'.$count.'</span>';
		}, array('desc' => 'Products'))
			->btn('Run', './?object=manage_shop&action=clear_pattern_run&id=%d', array('icon' => 'icon-play', 'class' => 'btn-info'))
			->btn('View list of changes', './?object=manage_shop&action=clear_pattern_list&id=%d', array('icon' => 'icon-th-list'))
			->btn_edit('', './?object=manage_shop&action=clear_pattern_edit&id=%d',array('no_ajax' => 1))
			->btn_delete('', './?object=manage_shop&action=clear_pattern_delete&id=%d')
			->btn_active('', './?object=manage_shop&action=clear_pattern_activate&id=%d')
			->footer_add('Add pattern', './?object=manage_shop&action=clear_pattern_add',array('no_ajax' => 1))
			;
	}

	function clear_pattern_list () {
		if (!isset($_GET['id']) && intval($_GET['id'])) {
			return t('Empty clear pattern ID');
		}

		$_GET['id'] = intval($_GET['id']);

		$pattern_info = db()->query_fetch('SELECT * FROM '.db('shop_patterns').' WHERE id = '.$_GET['id']);
		if (empty($pattern_info)) {
			return t('Wrong clean pattern');
		}
		
		$where = '';	
		if (!empty($pattern_info['cat_id'])) {
			$cat_ids = $this->get_recursive_cat_ids($pattern_info['cat_id']);
			$where = ' AND (cat_id IN ('.implode(',', $cat_ids).') OR id IN (SELECT product_id FROM '.db('shop_product_to_category').' WHERE category_id IN ('.implode(',', $cat_ids).')))';
		}

		$sql = 'SELECT * FROM '.db('shop_products').' WHERE LOWER(name) REGEXP \'[[:<:]]'.mb_strtolower($pattern_info['search'], 'UTF-8').'[[:>:]]\''.$where;
		$sql = db()->query($sql);
		while($row = db()->fetch_assoc($sql)) {
			$pattern_list[] = array(
				'now'     => preg_replace('/[<^\w\d]?('.$pattern_info['search'].')[<^\w\d]?/umis', '<b>$1</b>', $row['name']),
				'will_be' => preg_replace('/[<^\w\d]?('.$pattern_info['search'].')[<^\w\d]?/umis', '<b>'.$pattern_info['replace'].'</b>', $row['name']),
			);
		}

		return table($pattern_list)
			->text('now')
			->text('will_be')
			->footer_link('Back', './?object=manage_shop&action=clear_patterns', array('icon' => 'icon-reply'))
			;
	}

	/*
	 *
	 */
	function clear_pattern_add () {
		$validate_rules = array(
			'search' => array('trim|required|xss_clean'),
			'replace'	 => array('trim|required|xss_clean'),
		);

		$a = $_POST;
		$a['redirect_link'] = './?object=manage_shop&action=clear_patterns';

		return form($a, array('legend' => t('Add pattern')))
			->validate($validate_rules)
			->db_insert_if_ok('shop_patterns', array('search','replace', 'cat_id'))
			->text('search')
			->text('replace')
			->select_box('cat_id', module('manage_shop')->_cats_for_select, array('desc' => 'Category', 'show_text' => 1))
			->save();
	}

	/*
	 *
	 */
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
			->db_update_if_ok('shop_patterns', array('search','replace', 'cat_id'), 'id = '.$_GET['id'])
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

	/*
	 * 
	 */
	function clear_pattern_delete () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return 'Empty ID!';
		}
		db()->query('DELETE FROM '.db('shop_patterns').' WHERE id='.$_GET['id']);
		return js_redirect('./?object=manage_shop&action=show_clear_patterns');
	}

	/*
	 *
	 */
	function clear_pattern_run () {
		if (!isset($_GET['id']) && intval($_GET['id'])) {
			return t('Empty clear pattern ID');
		}

		$_GET['id'] = intval($_GET['id']);

		$pattern_info = db()->query_fetch('SELECT * FROM '.db('shop_patterns').' WHERE id = '.$_GET['id']);
		if (empty($pattern_info)) {
			return t('Wrong clean pattern');
		}

		$where = '';	
		if (!empty($pattern_info['cat_id'])) {
			$cat_ids = $this->get_recursive_cat_ids($pattern_info['cat_id']);
			$where = ' AND (cat_id IN ('.implode(',', $cat_ids).') OR id IN (SELECT product_id FROM '.db('shop_product_to_category').' WHERE category_id IN ('.implode(',', $cat_ids).')))';
		}

		$sql = 'SELECT * FROM '.db('shop_products').' WHERE LOWER(name) REGEXP \'[[:<:]]'.mb_strtolower($pattern_info['search'], 'UTF-8').'[[:>:]]\''.$where;
		$sql = db()->query($sql);
		while($row = db()->fetch_assoc($sql)) {
			$pattern_list[] = array(
				'now'     => preg_replace('/[<^\w\d]?('.$pattern_info['search'].')[<^\w\d]?/umis', '<b>$1</b>', $row['name']),
				'will_be' => preg_replace('/[<^\w\d]?('.$pattern_info['search'].')[<^\w\d]?/umis', '<b>'.$pattern_info['replace'].'</b>', $row['name']),
			);
		}

	}

	function get_recursive_cat_ids ($cat_id = 0, $all_cats = false) {
		$cat_id = intval($cat_id);
		if (empty($all_cats)) {
			$all_cats = db()->get_all($this->CATEGORY_SQL);
			if (empty($all_cats)) {
				return false;
			}
		}
		
		$current_func = __FUNCTION__;
		$ids[$cat_id] = $cat_id;
		foreach ($all_cats as $key => $item) {
			if ($item['parent_id'] == $cat_id) {
				$ids += $this->$current_func($item['id'], $all_cats);
			}
		}

		return $ids;
	}
}
