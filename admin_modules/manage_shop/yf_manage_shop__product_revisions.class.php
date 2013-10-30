<?php
class yf_manage_shop__product_revisions {

	function _product_add_revision($action, $item_id) {
		$data = array();
		if ($action != 'delete') {
			$data['product'] = db()->get("SELECT * FROM `".db('shop_products')."` WHERE `id`='{$item_id}'");
			$data['params'] =  db()->get_all("SELECT * FROM `".db('shop_products_productparams')."` WHERE `id`='{$item_id}'");
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
	
}
