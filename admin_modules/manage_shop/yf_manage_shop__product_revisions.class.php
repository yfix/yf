<?php
class yf_manage_shop__product_revisions {

	function _product_add_revision($action, $item_id) {
		$data = array();
		if ($action != 'delete') {
			$data['product'] = db()->get("SELECT * FROM `".db('shop_products')."` WHERE `id`='{$item_id}'");
			$data['params'] =  db()->get_all("SELECT * FROM `".db('shop_products_productparams')."` WHERE `product_id`='{$item_id}'");
			$data['product_to_category'] =  db()->get_all("SELECT * FROM `".db('shop_product_to_category')."` WHERE `product_id`='{$item_id}'");
			$data['product_to_region'] =  db()->get_all("SELECT * FROM `".db('shop_product_to_region')."` WHERE `product_id`='{$item_id}'");
			$data['product_related'] =  db()->get_all("SELECT * FROM `".db('shop_product_related')."` WHERE `product_id`='{$item_id}'");
		}
		db()->INSERT(db('shop_product_revisions'),array(
			'user_id' => intval(main()->ADMIN_ID),
			'add_date' => $_SERVER['REQUEST_TIME'],
			'action' => $action,
			'item_id' => $item_id,
			'data' => json_encode($data),
		));
	}
	
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
		foreach ($images as $v) {
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
}
