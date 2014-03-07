<?php

/**
*/
class yf_manage_shop_clear_products {

	public $SEARCH_TIP = false;

	function _init () {
		$all_cats = main()->get_data('category_items_all');
		conf('all_cats', $all_cats);
		$this->SEARCH_TIP = t('Case-insensetive search').' ('.t('Example').': "'.t('Search').'" = "'.mb_strtolower(t('Search')).'" = "'.mb_strtoupper(t('Search')).'")';
	}

	/*
	 *
	 */
	function clear_patterns () {
		$html = table('SELECT * FROM '.db('shop_patterns'), array(
			'table_attr'    => 'id="patterns_list"',
			'filter'        => $_SESSION[$_GET['object'].'__patterns'],
			'filter_params' => array(
				'search'  => 'like',
				'repalce' => 'like',
				'cat_id'  => 'in',
			),
		))
		->text('search', array('tip' => $this->SEARCH_TIP))
		->text('replace')
		->text('description')
		->func('cat_id', function($value, $extra, $row_info) {
			$category = conf('all_cats::'.$value); 
			$category = !empty($category) ? $category['name'] : t('In all categories');
			return '<span class="badge badge-warning">'.$category.'</span>';
		}, array('desc' => 'Category'))
		->func('id', function($value, $extra, $row_info) {
			$where = '';	
			if (!empty($row_info['cat_id'])) {
				$cat_ids = _class('cats')->_get_recursive_cat_ids($row_info['cat_id']);
				$where = ' AND (cat_id IN ('.implode(',', $cat_ids).') OR id IN (SELECT product_id FROM '.db('shop_product_to_category').' WHERE category_id IN ('.implode(',', $cat_ids).')))';
			}
			$sql = 'SELECT COUNT(*) AS `0` FROM '.db('shop_products').' WHERE LOWER(name) REGEXP \'[[:<:]]'.mb_strtolower($row_info['search'], 'UTF-8').'[[:>:]]\''.$where;
			list($count) = db()->query_fetch($sql);
			return '<span class="badge badge-info pattern_count">'.$count.'</span>';
		}, array('desc' => 'Products for changing'))
		->btn_func('Run', function($row_info, $params, $instance_params, $_this) {
			if ($row_info['process']) {
				return '<button class="btn btn-mini btn-xs pattern_item btn-warning" data-id="'.$row_info['id'].'"><i class="icon-refresh icon-spin"></i> <span>'.t('Process').'...</span></button>';
			} else {
				return '<button class="btn btn-mini btn-xs btn-info pattern_item" data-id="'.$row_info['id'].'"><i class="icon-play"></i> <span>'.t('Run').'</span></button>';
			}
		})
		->btn('List of changes', './?object=manage_shop&action=clear_pattern_list&id=%d', array('icon' => 'icon-th-list'))
		->btn_edit('', './?object=manage_shop&action=clear_pattern_edit&id=%d',array('no_ajax' => 1))
		->btn_delete('', './?object=manage_shop&action=clear_pattern_delete&id=%d')
		->footer_add('Add pattern', './?object=manage_shop&action=clear_pattern_add',array('no_ajax' => 1));

		$replace = array(
			'pattern_run_url'    => './?object=manage_shop&action=clear_pattern_run',
			'pattern_stop_url'    => './?object=manage_shop&action=clear_pattern_stop',
			'pattern_status_url' => './?object=manage_shop&action=clear_pattern_status',
		);
		$html .= tpl()->parse('manage_shop/product_clear_patterns', $replace);
		return $html;
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
			$cat_ids = _class('cats')->_get_recursive_cat_ids($row_info['cat_id']);
			$where = ' AND (cat_id IN ('.implode(',', $cat_ids).') OR id IN (SELECT product_id FROM '.db('shop_product_to_category').' WHERE category_id IN ('.implode(',', $cat_ids).')))';
		}

		$sql = 'SELECT * FROM '.db('shop_products').' WHERE LOWER(name) REGEXP \'[[:<:]]'.mb_strtolower($pattern_info['search'], 'UTF-8').'[[:>:]]\''.$where;
		$sql = db()->query($sql);
		while($row = db()->fetch_assoc($sql)) {
			$pattern_list[] = array(
				'id'      => $row['id'],
				'now'     => preg_replace('/[<^\w\d]?('.$pattern_info['search'].')[<^\w\d]?/umis', '<b class="text-warning">$1</b>', $row['name']),
				'will_be' => preg_replace('/[<^\w\d]?('.$pattern_info['search'].')[<^\w\d]?/umis', '<b class="text-success">'.$pattern_info['replace'].'</b>', $row['name']),
			);
		}

		return table($pattern_list)
			->header_link('Back', './?object=manage_shop&action=clear_patterns', array('icon' => 'icon-reply', 'class' => 'btn-warning'))
			->text('now')
			->text('will_be')
			->btn_edit('', './?object=manage_shop&action=product_edit&id=%d', array('no_ajax' => 1))
			->footer_link('Back', './?object=manage_shop&action=clear_patterns', array('icon' => 'icon-reply', 'class' => 'btn-warning'))
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
			->text('search', array('tip' => $this->SEARCH_TIP))
			->text('replace')
			->select_box('cat_id', module('manage_shop')->_cats_for_select, array('desc' => 'Category', 'show_text' => 1))
			->textarea('description')
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
			'search'  => array('trim|required|xss_clean'),
			'replace' => array('trim|required|xss_clean'),
		);

		$a = $pattern_info;
		$a['redirect_link'] = './?object=manage_shop&action=clear_patterns';

		return form($a, array('legend' => t('Edit pattern')))
			->validate($validate_rules)
			->db_update_if_ok('shop_patterns', array('search','replace', 'cat_id'), 'id = '.$_GET['id'])
			->text('search', array('tip' => $this->SEARCH_TIP))
			->text('replace')
			->select_box('cat_id', module('manage_shop')->_cats_for_select, array('desc' => 'Category', 'show_text' => 1))
			->textarea('description')
			->save();
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
			exit;
		}

		$_GET['id'] = intval($_GET['id']);

		$pattern_info = db()->query_fetch('SELECT * FROM '.db('shop_patterns').' WHERE id = '.$_GET['id'].' AND process = 0');
		if (empty($pattern_info)) {
			echo json_encode(array('status' => 'already'));
			exit;
		}

		$admin_fs_path = ADMIN_SITE_PATH; // INCLUDE_PATH.'admin/
		shell_exec('cd '.$admin_fs_path.' && php index.php --object=manage_shop --action=clear_pattern_child_process --id='.$_GET['id'].' > /dev/null &');

		echo json_encode(array('status' => 'done'));
		exit;
	}
	
	/*
	 *
	 */
	function clear_pattern_stop () {
		if (!isset($_GET['id']) && intval($_GET['id'])) {
			exit;
		}

		$_GET['id'] = intval($_GET['id']);

		$pattern_info = db()->query_fetch('SELECT * FROM '.db('shop_patterns').' WHERE id = '.$_GET['id'].' AND process != 0');
		if (empty($pattern_info)) {
			echo json_encode(array('status' => 'non'));
			exit;
		}

		shell_exec('kill '.$pattern_info['process'].' > /dev/null &');
		db()->query('UPDATE '.db('shop_patterns').' SET process = 0 WHERE id = '.$_GET['id'].';');

		echo json_encode(array('status' => 'done'));
		exit;
	}

	/*
	 *
	 */
	function clear_pattern_status () {
		if (empty($_POST['ids'])) {
			exit;
		}
		$sql = 'SELECT * FROM '.db('shop_patterns').' WHERE id IN ('.implode(',', $_POST['ids']).')';
		$patterns = db()->get_all($sql);
		foreach ($patterns as $key => $item) {
			$where = '';	
			if (!empty($item['cat_id'])) {
				$cat_ids = _class('cats')->_get_recursive_cat_ids($row_info['cat_id']);
				$where = ' AND (cat_id IN ('.implode(',', $cat_ids).') OR id IN (SELECT product_id FROM '.db('shop_product_to_category').' WHERE category_id IN ('.implode(',', $cat_ids).')))';
			}
			$sql = 'SELECT COUNT(*) AS `0` FROM '.db('shop_products').' WHERE LOWER(name) REGEXP \'[[:<:]]'.mb_strtolower($item['search'], 'UTF-8').'[[:>:]]\''.$where;
			list($count) = db()->query_fetch($sql);
			$patterns[$key]['count'] = $count;
		}
		echo json_encode($patterns);
		exit;
	}

	/*
	 *
	 */
	function clear_pattern_child_process () {
		if (!isset($_GET['id']) && intval($_GET['id'])) {
			return t('Empty clear pattern ID');
		}

		$_GET['id'] = intval($_GET['id']);
		$process_id = getmypid();

		db()->query('UPDATE '.db('shop_patterns').' SET process = '.$process_id.' WHERE process = 0 AND id = '.$_GET['id'].';');

		$pattern_info = db()->query_fetch('SELECT * FROM '.db('shop_patterns').' WHERE id = '.$_GET['id'].' AND process = '.$process_id);
		if (empty($pattern_info)) {
			return t('Wrong clean pattern');
		}

		//give a chance to stop processing if it needs;
		sleep(5);
		db()->begin();

		$where = '';	
		if (!empty($pattern_info['cat_id'])) {
			$cat_ids = _class('cats')->_get_recursive_cat_ids($row_info['cat_id']);
			$where = ' AND (cat_id IN ('.implode(',', $cat_ids).') OR id IN (SELECT product_id FROM '.db('shop_product_to_category').' WHERE category_id IN ('.implode(',', $cat_ids).')))';
		}
		$sql = 'SELECT * FROM '.db('shop_products').' WHERE LOWER(name) REGEXP \'[[:<:]]'.mb_strtolower($pattern_info['search'], 'UTF-8').'[[:>:]]\''.$where;
		$sql = db()->query($sql);
		while($row = db()->fetch_assoc($sql)) {
			$update_array[] = array(
				'id'   => $row['id'],
				'name' => preg_replace('/[<^\w\d]?('.$pattern_info['search'].')[<^\w\d]?/umis', $pattern_info['replace'], $row['name']),
			);

			$update_ids[] = $row['id'];
		}
		module('manage_shop')->_product_check_first_revision('product', $update_ids);
		if (!empty($update_array)) {
			$update_array = array_chunk($update_array, 300);
			foreach ($update_array as $update_items) {
				db()->update_batch_safe('shop_products', $update_items, 'id');
			}
			$revision_ids = module('manage_shop')->_product_add_revision('correct_name', $update_ids);
			module('manage_shop')->_add_group_revision('product', $revision_ids, $_GET['id']);
		}

		db()->commit();
		
		db()->query('UPDATE '.db('shop_patterns').' SET process = 0 WHERE process = '.$process_id.' AND id = '.$_GET['id'].';');
	}
}
