<?php

/**
*/
class yf_manage_shop_products{

	var $_filter_params = array(
		'id'              => array('in','p.id'),
		'name'            => array('like','p.name'),
		'price'           => array('between','p.price'),
		'articul'         => array('like','p.articul'),
		//		'price'       => array('eq','p.price'),
		'supplier_id'     => array('eq','p.supplier_id'),
		'manufacturer_id' => array('eq','p.manufacturer_id'),
		'active'          => array('eq','p.active'),
		'status'          => array('eq','p.status'),
		'image'           => array('eq','p.image'),
		#		'cat_id'       => array('field' => 'p.cat_id'),
		'quantity'        => array('field' => 'p.quantity'),
		'add_date'        => array('dt_between', 'p.add_date'),
		'update_date'     => array('field' => 'p.update_date'),
	);

	/**
	*/
	function _init () {
		if (empty($_SESSION['manage_shop__products'])) {
			$_SESSION['manage_shop__products'] = array(
			  'order_by' => 'id',
			  'order_direction' => 'desc',
			);
		}
		// This is needed, as it is currently impossible to set callback function inside class variable
		$this->_filter_params['cat_id'] = function($a) {
			$top_cat_id = (int)$a['value'];
			if ($top_cat_id) {
				$cat_ids = (array) _class('cats')->_recursive_get_children_ids($top_cat_id, 'shop_cats', $sub_children = 1, $as_array = 1);
			}
			$cat_ids[$top_cat_id] = $top_cat_id;
			return $cat_ids ? 'p.cat_id IN('.implode(',', $cat_ids).')' : '';
		};
		$this->_filter_params['region_id'] = function($a, $data) {
			$result = implode( ',', (array)$data[ 'region' ] );
			$result = $result ? 'pr.region_id IN(' . $result .  ')' : '';
			return( $result );
		};
	}

	/**
	*/
	function products () {
		if (module('manage_shop')->SUPPLIER_ID) {
			$sql = 'SELECT p.* FROM '.db('shop_products').' AS p'
					. ' LEFT JOIN ' . db( 'shop_product_to_region' ) . ' AS pr ON pr.product_id = p.id'
					. ' INNER JOIN ' . db( 'shop_admin_to_supplier' ) . ' AS m ON m.supplier_id = p.supplier_id'
					. ' WHERE'
						. ' m.admin_id='.intval(main()->ADMIN_ID).'';
		} else {
			$sql = 'SELECT * FROM '.db('shop_products').' AS p'
					. ' LEFT JOIN ' . db( 'shop_product_to_region' ) . ' AS pr ON pr.product_id = p.id'
					;
		}

		return table($sql, array(
				'filter' => $_SESSION[$_GET['object'].'__products'],
				'filter_params' => $this->_filter_params,
				'hide_empty' => 1,
				'pager_sql_callback' => function($sql) { return preg_replace('/^SELECT.*FROM/ims', 'SELECT COUNT(*) FROM', ltrim($sql)); }
			))
			->image('id', array('width' => '50px', 'img_path_callback' => function($_p1, $_p2, $row) {
				$image = common()->shop_get_images($row['id']);
				return $image[0]['thumb'];
            }))
			->text('name', array('link' => '/shop/product/%d', 'rewrite' => 1, 'data' => '@name', 'link_field_name' => 'id'))
			->link('cat_id', './?object=category_editor&action=edit_item&id=%d', _class('cats')->_get_items_names_cached('shop_cats'))
			->text('price')
			->text('quantity')
			->date('add_date', array('format' => 'full', 'nowrap' => 1))
			->text('articul')
			->btn_edit('', './?object='.main()->_get('object').'&action=product_edit&id=%d', array('no_ajax' => 1, 'btn_no_text' => 1))
			->btn_delete('', './?object='.main()->_get('object').'&action=product_delete&id=%d', array('btn_no_text' => 1))
			->btn_clone('', './?object='.main()->_get('object').'&action=product_clone&id=%d')
			->btn_active('', './?object='.main()->_get('object').'&action=product_activate&id=%d')
			->footer_add('Add product', './?object='.main()->_get('object').'&action=product_add',array('no_ajax' => 1))
			->footer_link('Attributes', './?object='.main()->_get('object').'&action=attributes')
			->footer_link('Categories', './?object=category_editor&action=show_items&id=shop_cats')
			->footer_link('Orders', './?object='.main()->_get('object').'&action=show_orders')
			->footer_link('XLS Export', './?object='.main()->_get('object').'&action=products_xls_export')
			->footer_link('Обновление цен', './?object='.main()->_get('object').'&action=products_price_update&init=1&filter=' . main()->_get( 'action' ))
			->footer_link('Обновление регионов', './?object='.main()->_get('object').'&action=products_region_update&init=1&filter=' . main()->_get( 'action' ))
		;
	}


	/**
	*/
	function products_xls_export () {
		$old_supplier_id = '';
		ini_set("memory_limit","1024M");
		if (module('manage_shop')->SUPPLIER_ID) {
			$sql = 'SELECT `p`.`id`,`p`.`articul`,`p`.`name`,`p`.`price` FROM '.db('shop_products').' AS p
					INNER JOIN '.db('shop_admin_to_supplier').' AS m ON m.supplier_id = p.supplier_id
					WHERE
						m.admin_id='.intval(main()->ADMIN_ID).'';
		} else {
			$sql = 'SELECT `p`.`id`,`p`.`articul`,`p`.`name`,`p`.`price` FROM '.db('shop_products').' AS p';
		}
		$filter_arr = main()->is_post() ? array('supplier_id' => intval($_POST['supplier_id'])) : $_SESSION[$_GET['object'].'__products'];
		list($filter_sql,$order_sql) = _class('table2_filter', 'classes/table2/')->_filter_sql_prepare($filter_arr, $this->_filter_params, $sql);
		if ($filter_sql || $order_sql) {
			$sql .= ' WHERE 1 '.$filter_sql;
			if ($order_sql) {
				$sql .= ' '.$order_sql;
			}
		}

		if (file_exists(YF_PATH."libs/phpexcel/PHPExcel.php")) {
			require_once(YF_PATH."libs/phpexcel/PHPExcel.php");
		} else {
			require_once(INCLUDE_PATH."libs/phpexcel/PHPExcel.php");
		}
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->SetCellValue('A1', t('id'));
		$objPHPExcel->getActiveSheet()->SetCellValue('B1', t('articul'));
		$objPHPExcel->getActiveSheet()->SetCellValue('C1', t('name'));
		$objPHPExcel->getActiveSheet()->SetCellValue('D1', t('price'));

		foreach(range('A','D') as $columnID) {
			$objPHPExcel->getActiveSheet()->getColumnDimension($columnID)
				->setAutoSize(true);
		}

		$R = db()->query($sql);
		$i = 2;
		while ($A = db()->fetch_assoc($R)) {
			$objPHPExcel->getActiveSheet()->SetCellValue('A'.$i, $A['id']);
			$objPHPExcel->getActiveSheet()->SetCellValue('B'.$i, $A['articul']);
			$objPHPExcel->getActiveSheet()->SetCellValue('C'.$i, $A['name']);
			$objPHPExcel->getActiveSheet()->SetCellValue('D'.$i, $A['price']);
			$i++;
		}

		$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);

		header('Content-type: application/vnd.ms-excel');
		header('Content-Disposition: attachment; filename="products_'.date('Y_m_d_H_i_s').'.xlsx"');
		$objWriter->save('php://output');
		exit;
	}

	/**
	*/
	function _get_product($pid) {
		if (module('manage_shop')->SUPPLIER_ID) {
			$sql = 'SELECT p.* FROM '.db('shop_products').' AS p
					INNER JOIN '.db('shop_admin_to_supplier').' AS m ON m.supplier_id = p.supplier_id
					WHERE
						p.id='.intval($pid).'
						AND m.admin_id='.intval(main()->ADMIN_ID).'';
		} else {
			$sql = 'SELECT * FROM '.db('shop_products').' WHERE id='.intval($pid);
		}
		return db()->get($sql);
	}

	/**
	*/
	function product_activate () {
		if ($_GET['id']) {
			$a = $this->_get_product($_GET['id']);
		}
		if ($a['id']) {
			if ($a['active'] == 1) {
				$active = 0;
			} elseif ($a['active'] == 0) {
				$active = 1;
			}
			db()->update_safe(db('shop_products'), array('active' => $active), 'id='.intval($_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($active ? 1 : 0);
		} else {
			return js_redirect('./?object='.main()->_get('object').'');
		}
	}

	/**
	*/
	function product_delete () {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$a = $this->_get_product($_GET['id']);
		}
		if ($a['id']) {
			module('manage_shop')->_product_check_first_revision('product', $_GET['id']);
			module('manage_shop')->_product_image_delete($_GET['id']);
			db()->query('DELETE FROM '.db('shop_product_to_category').' WHERE product_id='.$_GET['id']);
			db()->query('DELETE FROM '.db('shop_product_to_region').' WHERE product_id='.$_GET['id']);
			db()->query('DELETE FROM '.db('shop_product_productparams').' WHERE product_id='.$_GET['id']);
			db()->query('DELETE FROM '.db('shop_products').' WHERE id='.$_GET['id']);
			module("manage_shop")->_product_add_revision('delete',$_GET['id']);
			common()->admin_wall_add(array('shop product deleted: '.$_GET['id'], $_GET['id']));
		}
		return js_redirect('./?object='.main()->_get('object').'action=products');
	}

	/**
	*/
	function product_clone () {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$a = $this->_get_product($_GET['id']);
		}
		if (!$a['id']) {
			return _e('No such product!');
		}
		$sql = $a;
		$old_product_id = $sql['id'];
		unset($sql['id']);
		$sql['name'] = 'Clone '.$sql['name'];
		$sql['active'] = 0;

		db()->insert('shop_products', $sql);
		$new_product_id = db()->insert_id();
		common()->admin_wall_add(array('shop product cloned: '.$a['name'], $new_product_id));

		$arr =  db()->get_all("SELECT * FROM `".db('shop_products_productparams')."` WHERE `product_id`='{$old_product_id}'");
		db()->query('DELETE FROM '.db('shop_products_productparams').' WHERE product_id='.$new_product_id);
		foreach ((array)$arr as $v) {
			db()->INSERT('shop_products_productparams', array(
				'product_id' => $new_product_id,
				'productparam_id' => $v['productparam_id'],
				'value' => $v['value'],
			));
		}
		$arr =  db()->get_all("SELECT * FROM `".db('shop_product_to_category')."` WHERE `product_id`='{$old_product_id}'");
		db()->query('DELETE FROM '.db('shop_product_to_category').' WHERE product_id='.$new_product_id);
		foreach ((array)$arr as $v) {
			db()->INSERT('shop_product_to_category', array(
				'product_id' => $new_product_id,
				'category_id' => $v['category_id'],
			));
		}
		$arr =  db()->get_all("SELECT * FROM `".db('shop_product_to_region')."` WHERE `product_id`='{$old_product_id}'");
		db()->query('DELETE FROM '.db('shop_product_to_region').' WHERE product_id='.$new_product_id);
		foreach ((array)$arr as $v) {
			db()->INSERT('shop_product_to_region', array(
				'product_id' => $new_product_id,
				'region_id' => $v['region_id'],
			));
		}
		$arr =  db()->get_all("SELECT * FROM `".db('shop_product_related')."` WHERE `product_id`='{$old_product_id}'");
		db()->query('DELETE FROM '.db('shop_product_related').' WHERE product_id='.$new_product_id);
		foreach ((array)$arr as $v) {
			db()->INSERT('shop_product_related', array(
				'product_id' => $new_product_id,
				'related_id' => $v['related_id'],
			));
		}
		$arr =  db()->get_all("SELECT * FROM `".db('shop_product_to_unit')."` WHERE `product_id`='{$old_product_id}'");
		db()->query('DELETE FROM '.db('shop_product_to_unit').' WHERE product_id='.$new_product_id);
		foreach ((array)$arr as $v) {
			db()->INSERT('shop_product_to_unit', array(
				'product_id' => $new_product_id,
				'unit_id' => $v['unit_id'],
			));
		}
		$arr =  db()->get_all("SELECT * FROM `".db('shop_product_images')."` WHERE `product_id`='{$old_product_id}' AND `active`=1");
		db()->query('DELETE FROM '.db('shop_product_images').' WHERE product_id='.$new_product_id);
		foreach ((array)$arr as $v) {
			db()->INSERT('shop_product_images', array(
				'product_id' 	=> $new_product_id,
				'is_default' 	=> $v['is_default'],
				'md5'			=> $v['md5'],
				'date_uploaded'	=> time(),
				'active'		=> $v['active'],
			));
			$old_img_names[] = '/product_'.$old_product_id.'_'.$v['id'];
			$new_img_names[] = '/product_'.$new_product_id.'_'.db()->insert_id();
		}

		if ($sql['image'] && $new_product_id) {
			$dirs = sprintf('%06s', $old_product_id);
			$dir2 = substr($dirs, -3, 3);
			$dir1 = substr($dirs, -6, 3);
			$m_path = $dir1.'/'.$dir2.'/';
			$old_images = _class('dir')->scan_dir(
				module('manage_shop')->products_img_dir. $m_path,
				true,
				'/product_'.$old_product_id.'_.+?\.jpg/'
			);
			foreach((array)$old_images as $old_image_path) {
				$nd = sprintf('%06s', $new_product_id);
				$nd2 = substr($nd, -3, 3);
				$nd1 = substr($nd, -6, 3);
				$n_path = $nd1.'/'.$nd2.'/';
				$new_image_path = str_replace($old_img_names, $new_img_names, str_replace($m_path, $n_path, $old_image_path));
				$new_dir = dirname($new_image_path);
				if (!file_exists($new_dir)) {
					mkdir($new_dir, 0777, true);
				}
				copy($old_image_path, $new_image_path);
			}
		}
		return js_redirect('./?object='.main()->_get('object').'&action=products');
	}

	/**
	*/
	function products_show_by_category ($cat = '') {
		main()->NO_GRAPHICS = true;
		$cat_id =  $_GET['cat_id'];
		$sql1 = 'SELECT product_id FROM '.db('shop_product_to_category').' WHERE category_id ='. $cat_id ;
			$products = db()->query($sql1);
			while ($A = db()->fetch_assoc($products)) {
				$product_info .= $A['product_id'].',';
			}
			$product_info = rtrim($product_info, ',');

		$sql = 'SELECT * FROM '.db('shop_products').' WHERE active="1" AND id IN ('.$product_info .')  ORDER BY name';
		$product = db()->query_fetch_all($sql);
		$products = array();
		foreach ((array)$product as $v) {
			$products []  = array (
				'product_id'	=> $v['id'],
				'name'			=> $v['name'],
			);
		}
		print json_encode($products);
		exit(); // To prevent printing additional debug info later and break JS
	}

	function _search_autocomplete( $options = array() ) {
		main()->NO_GRAPHICS = true;
		// prepare options
		$_ = &$options;
		$table = $_[ 'table' ];
		$where = $_[ 'where' ];
		if( empty( $table ) ) { exit(); }
		// prepare search words
		if( empty( $_GET[ 'search_word' ] ) ) { exit(); }
		$words = mb_split( '\s', mb_strtolower( _es( $_GET[ 'search_word' ] ) ) );
		$sql_words = str_replace( array( '%', '_', '*', '?' ), array( '\%', '\_', '%', '_' ), $words );
		$sql_words  = '%' . implode( '%', $sql_words ) . '%';
		// prepare search ids
		$ids = array();
		foreach( $words as $i => $w ) {
			$id = (int)$w;
			if( $id < 1 ) { continue; }
			$ids[ $id ] = $id;
		}
		$sql_ids = '';
		if( !empty( $ids ) ) {
			$sql_ids = 'OR id IN(' . implode( ',', $ids ) . ')';
		}
		// collect sql where
		$sql_where = array();
		// prepare exclude ids
		if( !empty( $_GET[ 'exclude' ] ) ) {
			$exclude = $_GET[ 'exclude' ];
			$ids = array();
			foreach( $exclude as $id ) {
				$id = (int)$id;
				if( $id < 1 ) { continue; }
				$ids[ $id ] = $id;
			}
			if( !empty( $ids ) ) {
				$sql_where[] = 'id NOT IN(' . implode( ',', $ids ) . ')';
			}
		}
		// prepare where
		if( !empty( $where ) ) { $sql_where[] = $where; }
		if( !empty( $sql_where ) ) { $sql_where = implode( ' AND ', $sql_where ) . ' AND'; }
		else { $sql_where = ''; }
		// prepare sql
		$sql_table = db( $table );
		$sql = sprintf('
			SELECT id, name FROM %s
			WHERE %s (
				LOWER( name ) LIKE "%s"
				%s
			) LIMIT 20
			'
			, $sql_table
			, $sql_where
			, $sql_words
			, $sql_ids
		);
		$result = db()->get_all( $sql );
		if( empty( $result ) ) { exit(); }
		$json = array();
		foreach( $result as $i ){
			$id = (int)$i[ 'id' ];
			$text = "[$id] $i[name]";
			$json[] = array(
				'id'   => $id,
				'text' => $text,
			);
		}
		echo( json_encode( $json ) );
		exit();
	}

	function category_search_autocomplete () {
		$options = array(
			'table' => 'sys_category_items',
			'where' => 'cat_id = ' . (int)_class( 'cats' )->_get_cat_id_by_name( 'shop_cats' ),
		);
		$this->_search_autocomplete( $options );
	}

	function product_search_autocomplete () {
		$options = array(
			'table' => 'shop_products',
		);
		$this->_search_autocomplete( $options );
	}

}
