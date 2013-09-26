<?php

class yf_shop_supplier_panel_products {

	/**
	*/
	function products () {
		$SUPPLIER_ID = module('shop_supplier_panel')->SUPPLIER_ID;

		$sql = 'SELECT p.* FROM '.db('shop_products').' AS p
				INNER JOIN '.db('shop_admin_to_supplier').' AS m ON m.supplier_id = p.supplier_id 
				WHERE 
					m.admin_id='.intval(main()->ADMIN_ID).'';
		$filter_name = $_GET['object'].'__products';
		return table($sql, array(
				'filter' => $_SESSION[$filter_name],
				'filter_params' => array(
					'name'	=> 'like',
					'price' => 'between',
				),
			))
			->image('id', 'uploads/shop/products/{subdir2}/{subdir3}/product_%d_1_full.jpg')
			->text('name')
			->text('cat_id', '', array('data' => _class('cats')->_get_items_names('shop_cats')))
			->text('price')
			->text('quantity')
			->date('add_date')
			->btn_edit('', './?object='.$_GET['object'].'&action=product_edit&id=%d')
			->btn_delete('', './?object='.$_GET['object'].'&action=product_delete&id=%d')
			->footer_add('', './?object='.$_GET['object'].'&action=product_add')
		;
	}

	/**
	*/
	function product_edit() {
		$SUPPLIER_ID = module('shop_supplier_panel')->SUPPLIER_ID;

		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return 'Empty ID!';
		}
		$sql = 'SELECT p.* FROM '.db('shop_products').' AS p
				INNER JOIN '.db('shop_admin_to_supplier').' AS m ON m.supplier_id = p.supplier_id 
				WHERE 
					p.id='.intval($_GET['id']).'
					AND m.admin_id='.intval(main()->ADMIN_ID).'';
		$product_info = db()->query_fetch($sql);
		if (!empty($_POST)) {
			if (!$_POST['name']) {
				_re('Product name must be filled');
			}
			if ($_POST['ext_url']) {
				if (substr($_POST['ext_url'], 0, 7) !== 'http://') {
					$_POST['ext_url'] = 'http://'.$_POST['ext_url'];
				}
			}
			if (!_ee()) {
				$sql_array = array(
					'name'				=> _es($_POST['name']),
					'cat_id'			=> _es($_POST['cat_id']),
					'url'				=> _es(common()->_propose_url_from_name($_POST['name'])),
					'description'		=> _es($_POST['desc']),
					'external_url'		=> _es($_POST['ext_url']),
					'price'				=> floatval(str_replace(',', '.', $_POST['price'])),
					'price_promo'		=> floatval(str_replace(',', '.', $_POST['price_promo'])),
					'price_partner'		=> floatval(str_replace(',', '.', $_POST['price_partner'])),
					'price_raw'			=> floatval(str_replace(',', '.', $_POST['price_raw'])),
					'manufacturer_id'	=> intval($_POST['manufacturer']),
					'quantity'			=> intval($_POST['quantity']),
				);
				if (!empty($_FILES)) {
					$product_id = $_GET['id'];
					$product_name = _es(common()->_propose_url_from_name($_POST['name']));
					$rez_upload = module('manage_shop')->_product_image_upload($product_id, $product_name);
					$sql_array['image'] = 1;
				} 
				db()->UPDATE(db('shop_products'), $sql_array, 'id='.$_GET['id']);
				common()->admin_wall_add(array('shop product updated: '.$_POST['name'], $_GET['id']));
/*
				db()->query('DELETE FROM  '.db('shop_product_to_category').' WHERE product_id = '.$_GET['id']);
				foreach ((array)$_POST['category'] as $k => $v){
					$cat_id['product_id'] = $_GET['id'];
					$cat_id['category_id'] = $v;
					db()->INSERT(db('shop_product_to_category'), $cat_id);
				}
*/
				module('manage_shop')->_attributes_save($_GET['id']);
			}
			return js_redirect('./?object='.$_GET['object'].'&action=products');
		}
		if ($product_info['image'] == 0) {
			$thumb_path = '';
		} else {
			$dirs = sprintf('%06s',$product_info['id']);
			$dir2 = substr($dirs,-3,3);
			$dir1 = substr($dirs,-6,3);
			$mpath = $dir1.'/'.$dir2.'/';
			$image_files = _class('dir')->scan_dir(
				module('manage_shop')->products_img_dir. $mpath, 
				true, 
				'/product_'.$product_info['id'].'.+?_(small)\.jpg'.'/'
			);
			$reg = '/product_'.$product_info['id'].'_(?P<content>[\d]+)_(small)\.jpg/';
			foreach((array)$image_files as $filepath) {
				preg_match($reg, $filepath, $rezult);
				$i =  $rezult['content'];

				$product_image_delete_url ='./?object='.$_GET['object'].'&action=product_image_delete&id='.$product_info['id'].'&name='.$product_info['url'].'&key='.$i;

				$thumb_path_temp = module('manage_shop')->products_img_webdir. $mpath. 'product_'.$product_info['id'].'_'.$i. module('manage_shop')->THUMB_SUFFIX.'.jpg';
				$img_path = module('manage_shop')->products_img_webdir. $mpath. 'product_'.$product_info['id'].'_'.$i. module('manage_shop')->FULL_IMG_SUFFIX.'.jpg';

				$replace2 = array(
					'img_path' 		=> $img_path,
					'thumb_path'	=> $thumb_path_temp,
					'del_url' 		=> $product_image_delete_url,
					'name'			=> $product_info['url'],
				);
				$items .= tpl()->parse('manage_shop/image_items', $replace2);
			}
		}	
		$sql1 = 'SELECT category_id FROM '.db('shop_product_to_category').' WHERE product_id = '. $_GET['id'];
		$products = db()->query($sql1);
		while ($A = db()->fetch_assoc($products)) {
			$cat_id[$A['category_id']] .= $A['category_id'];
		}	
		$replace = array(
			'form_action'			=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$product_info['id'],
			'back_url'				=> './?object='.$_GET['object'].'&action=products',
			'categories_url'		=> './?object='.$_GET['object'].'&action=category_mapping',
			'name'					=> $product_info['name'],
			'desc'					=> $product_info['description'],
			'meta_keywords'			=> $product_info['meta_keywords'],
			'meta_desc'				=> $product_info['meta_desc'],
			'price'					=> $product_info['price'],
			'price_promo'			=> $product_info['price_promo'],
			'price_partner'			=> $product_info['price_partner'],
			'price_raw'				=> $product_info['price_raw'],
			'quantity'				=> $product_info['quantity'],
			'ext_url'				=> $product_info['external_url'],
			'manufacturer_box'		=> common()->select_box('manufacturer', module('manage_shop')->_man_for_select, $product_info['manufacturer_id'], false, 2),
			'category_box'			=> common()->multi_select('category', module('manage_shop')->_cats_for_select, $cat_id, false, 2, ' size=5 ', false),
			'cat_id_box'			=> common()->select_box('cat_id', module('manage_shop')->_cats_for_select, $product_info['cat_id'], false, 2),
			'image'					=> $items,
		);
		return tpl()->parse($_GET['object'].'/product_edit', $replace);
	}

	/**
	*/
	function product_add() {
		$SUPPLIER_ID = module('shop_supplier_panel')->SUPPLIER_ID;

		if (!empty($_POST)) {
			if (!$_POST['name']) {
				_re('Product name must be filled');
			}
			if ($_POST['ext_url']) {
				if (substr($_POST['ext_url'], 0, 7) !== 'http://') {
					$_POST['ext_url'] = 'http://'.$_POST['ext_url'];
				}
			}
			if (!_ee()) {
				$sql_array = array(
					'supplier_id'		=> intval($SUPPLIER_ID),
					'name'				=> _es($_POST['name']),
					'cat_id'			=> _es($_POST['cat_id']),
					'url'				=> _es(common()->_propose_url_from_name($_POST['name'])),
					'description'		=> _es($_POST['desc']),
					'external_url'		=> _es($_POST['ext_url']),
					'price'				=> floatval(str_replace(',', '.', $_POST['price'])),
					'price_promo'		=> floatval(str_replace(',', '.', $_POST['price_promo'])),
					'price_partner'		=> floatval(str_replace(',', '.', $_POST['price_partner'])),
					'price_raw'			=> floatval(str_replace(',', '.', $_POST['price_raw'])),
					'manufacturer_id'	=> intval($_POST['manufacturer']),
					'quantity'			=> intval($_POST['quantity']),
				);
				db()->INSERT(db('shop_products'), $sql_array);
				foreach ((array)$_POST['category'] as $k => $v){
					$cat_id['product_id'] = $_GET['id'];
					$cat_id['category_id'] = $v;
					db()->INSERT(db('shop_product_to_category'), $cat_id);
				}
				$product_id = db()->INSERT_ID();
				// Image upload
				if (!empty($_FILES)) {
					$product_id = $_GET['id'];
					$product_name = _es(common()->_propose_url_from_name($_POST['name']));
					$rez_upload = module('manage_shop')->_product_image_upload ($product_id, $product_name);
					$sql_array['image'] = 1;
				} 
				common()->admin_wall_add(array('shop product added: '.$_POST['name'], $product_id));
/*
				db()->query('DELETE FROM  '.db('shop_product_to_category').' WHERE product_id = '.$_GET['id']);
				foreach ((array)$_POST['category'] as $k => $v){
					$cat_id['product_id'] = $_GET['id'];
					$cat_id['category_id'] = $v;
					db()->INSERT(db('shop_product_to_category'), $cat_id);
				}
*/
				module('manage_shop')->_attributes_save($_GET['id']);
			}
			return js_redirect('./?object='.$_GET['object'].'&action=products');
		}
		$replace = array(
			'form_action'			=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$product_info['id'],
			'back_url'				=> './?object='.$_GET['object'].'&action=products',
			'categories_url'		=> './?object='.$_GET['object'].'&action=category_mapping',
			'name'					=> '',
			'desc'					=> '',
			'meta_keywords'			=> '',
			'meta_desc'				=> '',
			'price'					=> '',
			'price_promo'			=> '',
			'price_partner'			=> '',
			'price_raw'				=> '',
			'quantity'				=> '',
			'ext_url'				=> '',
			'manufacturer_box'		=> common()->select_box('manufacturer', module('manage_shop')->_man_for_select, $product_info['manufacturer_id'], false, 2),
			'category_box'			=> common()->multi_select('category', module('manage_shop')->_cats_for_select, $cat_id, false, 2, ' size=5 ', false),
			'cat_id_box'			=> common()->select_box('cat_id', module('manage_shop')->_cats_for_select, $product_info['cat_id'], false, 2),
			'image'					=> '',
		);
		return tpl()->parse($_GET['object'].'/product_edit', $replace);
	}

	/**
	*/
	function product_delete() {
		$SUPPLIER_ID = module('shop_supplier_panel')->SUPPLIER_ID;
		$a = db()->get('SELECT * FROM '.db('shop_products').' WHERE supplier_id='.(int)$SUPPLIER_ID.' AND id='.(int)$_GET['id']);
		if (!$a) {
			return _e('No such record');
		}
/*
		module('manage_shop')->_product_image_delete($_GET['id']);
		db()->query('DELETE FROM '.db('shop_product_attributes_values').' WHERE object_id='.$_GET['id']);
		db()->query('DELETE FROM '.db('shop_group_options').' WHERE product_id='.$_GET['id']);		
		common()->admin_wall_add(array('shop product deleted: '.$_GET['id'], $_GET['id']));
*/
		db()->query('DELETE FROM '.db('shop_products').' WHERE supplier_id='.(int)$SUPPLIER_ID.' AND id='.(int)$_GET['id'].' LIMIT 1');
		return js_redirect('./?object='.$_GET['object'].'&action=products');
	}
}
