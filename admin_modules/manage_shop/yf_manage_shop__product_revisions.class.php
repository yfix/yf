<?php
class yf_manage_shop__product_revisions {


	function _product_add_revision($action = false, $ids = false) {
		$this->_add_revision('product', $action, $ids);
	}
	
	function _order_add_revision($action = false, $ids = false) {
		$this->_add_revision('order', $action, $ids);
	}

	/*
	 * $ids can be single item id or array of items' ids
	 * when action equal 'delete' the data will be empty
	 */
	function _add_revision($type, $action, $ids = false) {
		if (empty($ids) || empty($action) || empty($type)) {
			return false;
		} elseif (!is_array($ids) && intval($ids)) {
			$ids = array(intval($ids));
		}

		$ids_with_comma = implode(',', $ids);

		/* sql   - string query
		 * filed - general field for all tables
		 * multi - count flag for set array[] or array[][]
		 */
		$all_queries = array(
			'product' => array(
				'product'             => array('sql' => 'SELECT * FROM '.db('shop_products').' WHERE id IN ('.$ids_with_comma.');', 'field' => 'id', 'multi' => false, ),
				'params'              => array('sql' => 'SELECT * FROM '.db('shop_products_productparams').' WHERE product_id IN ('.$ids_with_comma.');', 'field' => 'product_id', 'multi' => true),
				'product_to_category' => array('sql' => 'SELECT * FROM '.db('shop_product_to_category').' WHERE product_id IN ('.$ids_with_comma.');', 'field' => 'product_id', 'multi' => true),
				'product_to_region'   => array('sql' => 'SELECT * FROM '.db('shop_product_to_region').' WHERE product_id IN ('.$ids_with_comma.');', 'field' => 'product_id', 'multi' => true),
				'product_related'     => array('sql' => 'SELECT * FROM '.db('shop_product_related').' WHERE product_id IN ('.$ids_with_comma.');', 'field' => 'product_id', 'multi' => true),
			),
			'order' => array(
				'orders'      => array('sql' => 'SELECT * FROM '.db('orders').' WHERE id IN ('.$ids_with_comma.');', 'field' => 'id', 'multi' => false),
				'order_items' => array('sql' => 'SELECT * FROM '.db('order_items').' WHERE product_id IN ('.$ids_with_comma.');', 'field' => 'id', 'multi' => true),
			),
		);

		//check SQL confs for getting data
		if (!isset($all_queries[$type])) {
			return false;
		}
		
		//help to clear from temp values for diff before and after conditions
		$temp_indexes['product']['add_date'] = 0;
		$temp_indexes['product']['update_date'] = 0;
		$temp_indexes['order']['date'] = 0;
		//-------------------------------------------------------------------

		$revision_db = db('shop_'.$type.'_revisions');
		$all_data = array();

		if ($action != 'delete') {
			$revision_sql = 'SELECT item_id, data FROM (SELECT item_id, data FROM '.$revision_db.' WHERE item_id IN ('.$ids_with_comma.') ORDER BY id DESC) as r GROUP BY item_id';
			$all_last_revision = db()->get_2d($revision_sql);

			foreach ($all_queries[$type] as $key => $info) {
				$sql_res = db()->query($info['sql']);
				while ($row = db()->fetch_assoc($sql_res)) {
					$complex_key = $row[$info['field']];
					if ($info['multi']) {
						$all_data[$complex_key][$key][] = $row;
					} else {
						$all_data[$complex_key][$key] = $row;
					}
				}
			}
		}

		foreach ($ids as $id) {
			if (isset($all_last_revision[$id]) && isset($all_data[$id])) {
				$cur_revision = json_decode($all_last_revision[$id], true);
				$cur_revision = array_replace_recursive($cur_revision, $temp_indexes);
				$cur_revision = json_encode($cur_revision);

				$new_revision = $all_data[$id];
				$new_revision = array_replace_recursive($new_revision , $temp_indexes);
				$new_revision = json_encode($new_revision);

				if ($cur_revision == $new_revision) {
					continue;
				}
			}

			$insert_array[] = array(
				'user_id'  => intval(main()->ADMIN_ID),
				'add_date' => time(),
				'action'   => $action,
				'item_id'  => $id,
				'data'     => isset($all_data[$id]) ? json_encode($all_data[$id]) : '',
			);
		}

		if (!empty($insert_array)) {
			$insert_array = array_chunk($insert_array, 100);
			foreach ($insert_array as $insert_item) {
				db()->insert_safe($revision_db, $insert_item);
			}
		}
	}

	/**
	 */
	function _product_images_add_revision($item_id) {
		$dirs = sprintf('%06s', $item_id);
		$dir2 = substr($dirs, -3, 3);
		$dir1 = substr($dirs, -6, 3);
		$m_path = $dir1.'/'.$dir2.'/';		
		$images = _class('dir')->scan_dir(
			module('manage_shop')->products_img_dir. $m_path,
			true,
			'/product_'.$item_id.'_.+?\.jpg/'
		);
		$data = array();
		foreach ((array)$images as $v) {
			if (!$v) {
				continue;
			}
			$k = str_replace(module('manage_shop')->products_img_dir, "", $v);
			$data[$k] = base64_encode(file_get_contents($v));
		}
		db()->INSERT(db('shop_product_images_revisions'),array(
			'user_id' => intval(main()->ADMIN_ID),
			'add_date' => $_SERVER['REQUEST_TIME'],
			'item_id' => $item_id,
			'data' => json_encode($data),
		));		
	}	

	/**
	 */
	function product_revisions() {
		$rev_id = intval($_GET['id']);
		if ($rev_id) {
			return $this->product_revisions_view();
		}
		return table('SELECT * FROM '.db('shop_product_revisions'))
			->date('add_date', array('format' => 'full', 'nowrap' => 1))
			->link('item_id', './?object='.$_GET['object'].'&action=product_edit&id=%d')
			->admin('user_id', array('desc' => 'admin'))
			->text('action')
			->btn_view('', './?object=manage_shop&action=product_revisions&id=%d')
			;
	}

	/**
	 */
	function product_revisions_view() {
		$sql = 'SELECT * FROM '.db('shop_product_revisions').' WHERE id='.intval($_GET['id']);
		$a = db()->get($sql);
		$product_info = module('manage_shop')->_product_get_info($a['item_id']);
		if (empty($product_info['id'])) {
			return _e('Product not found');
		}
		return form($a, array(
			'dd_mode' => 1,
		))
		->link('item_id', './?object='.$_GET['object'].'&action=product_edit&id='.$product_info['id'], array(
			'desc' => 'Product',
			'data' => array($a['item_id'] => $product_info['name']. ' [id='. $a['item_id'].']'),
		))
		->admin_info('user_id')
		->info_date('add_date', array('format' => 'full'))
		->info('action')
		->func('data', function($extra, $r, $_this){ return '<pre>'.var_export(_class('utils')->object_to_array(json_decode($r[$extra['name']])), 1).'</pre>'; })
		;
	}
}
