<?php

class yf_shop_contentm_panel_products {

	/**
	*/
	function products () {
		$sql = 'SELECT * FROM '.db('shop_products');
		$filter_name = $_GET['object'].'__products';
		return table($sql, array(
				'filter' => $_SESSION[$filter_name],
				'filter_params' => array(
					'name'	=> 'like',
					'price' => 'between',
				),
			))
			->image('id', '', array('width' => '50px', 'img_path_callback' => function($_p1, $_p2, $row) {
                $product_id = $row['id'];
				$image = common()->shop_get_images($product_id);
                return $image[0]['thumb'];

            }))
			->text('name')
			->text('cat_id', '', array('data' => _class('cats')->_get_items_names('shop_cats')))
			->text('price')
			->text('quantity')
			->date('add_date')
			->btn_edit('', './?object='.$_GET['object'].'&action=product_edit&id=%d',array('no_ajax' => 1))
			->btn_delete('', './?object='.$_GET['object'].'&action=product_delete&id=%d')
	//		->footer_add('', './?object='.$_GET['object'].'&action=product_add',array('no_ajax' => 1))
		;
	}

	/**
	*/
	function product_edit() {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return 'Empty ID!';
		}
		$sql = 'SELECT * FROM '.db('shop_products').' 
				WHERE id='.intval($_GET['id']);
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
					'manufacturer_id'	=> intval($_POST['manufacturer']),
					'quantity'			=> intval($_POST['quantity']),
				);
				if (!empty($_FILES)) {
					module("shop_supplier_panel")->_product_image_upload($_GET['id']);
					$sql_array['image'] = 1;
					module("manage_shop")->_product_images_add_revision($_GET['id']);
				} 
				db()->UPDATE(db('shop_products'), $sql_array, 'id='.$_GET['id']);
				
				cache_del("_shop_product_params|_get_params_by_product|".$_GET['id']);
				db()->query("DELETE FROM `".db('shop_products_productparams')."` WHERE `product_id`=".$_GET['id']);
				if (count($_POST['productparams']) != 0)
					foreach ($_POST['productparams'] as $param_id)
						if (intval($param_id) != 0) 
							foreach($_POST['productparams_options_' . $param_id] as $v)
								db()->INSERT("shop_products_productparams",array(
									"product_id" => $_GET['id'],
									"productparam_id" => $param_id,
									"value"	=> $v,
								));
						
				common()->admin_wall_add(array('shop product updated: '.$_POST['name'], $_GET['id']));
				module('manage_shop')->_attributes_save($_GET['id']);
			}
			module("manage_shop")->_product_add_revision('edit',$_GET['id']);
			return js_redirect('./?object='.$_GET['object'].'&action=products');
		}

		$images = common()->shop_get_images($product_info["id"]);
		$base_url = WEB_PATH;
		$media_host = ( defined( 'MEDIA_HOST' ) ? MEDIA_HOST : false );
		if( !empty( $media_host ) ) { $base_url = '//' . $media_host . '/'; }		
		foreach((array)$images as $A) {
			$product_image_delete_url ="./?object=manage_shop&action=product_image_delete&id=".$product_info["id"]."&key=".$A['id'];
			$replace2 = array(
				"img_path" 		=> $base_url . $A['big'],
				"thumb_path"	=> $base_url . $A['thumb'],
				"del_url" 		=> $product_image_delete_url,
				"image_key"		=> $A['id'],
			);
			$items .= tpl()->parse("manage_shop/image_items", $replace2);
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
			'quantity'				=> $product_info['quantity'],
			'ext_url'				=> $product_info['external_url'],
			'productparams'			=> module("manage_shop","admin_modules")->_productparams_container($_GET['id']),			
			'manufacturer_box'		=> common()->select_box('manufacturer', module('manage_shop')->_man_for_select, $product_info['manufacturer_id'], false, 2),
			'category_box'			=> common()->multi_select('category', module('manage_shop')->_cats_for_select, $cat_id, false, 2, ' size=5 ', false),
			'cat_id_box'			=> common()->select_box('cat_id', module('manage_shop')->_cats_for_select, $product_info['cat_id'], false, 2),
			'image'					=> $items,
			'set_main_image_url'	=> './?object='.$_GET['object'].'&action=set_main_image&id='.$product_info['id'],
		);
		return tpl()->parse($_GET['object'].'/product_edit', $replace);
	}

	/**
	*/
	function product_delete() {
		$a = db()->get('SELECT * FROM '.db('shop_products').' WHERE id='.(int)$_GET['id']);
		if (!$a) {
			return _e('No such record');
		}
		db()->query('DELETE FROM '.db('shop_product_to_category').' WHERE product_id='.$_GET['id']);		
		db()->query('DELETE FROM '.db('shop_product_to_region').' WHERE product_id='.$_GET['id']);
		db()->query('DELETE FROM '.db('shop_product_productparams').' WHERE product_id='.$_GET['id']);
		db()->query('DELETE FROM '.db('shop_products').' WHERE id='.(int)$_GET['id'].' LIMIT 1');
		module("manage_shop")->_product_add_revision('delete',$_GET['id']);		
		return js_redirect('./?object='.$_GET['object'].'&action=products');
	}
}
