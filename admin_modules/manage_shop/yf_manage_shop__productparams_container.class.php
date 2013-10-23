<?php
class yf_manage_shop__productparams_container {

	function _productparams_container($product_id) {
		$current_params = array();
		$current_param_id = 0;
		if (intval($product_id) != 0) {
			$R = db()->query("SELECT * FROM `".db('shop_products_productparams')."` WHERE `product_id`=".$product_id);
			while ($A = db()->fetch_assoc($R)) {
				$current_params[$A['productparam_id']][$A['value']] = $A['value'];
			}
		}
		$params_names = db()->get_2d("SELECT `id`,`title` FROM `".db('shop_productparams')."` ORDER BY `title`");
		
		$params = array();
		$params_selected = array();
		foreach ($current_params as $k=>$v) {
			$params_selected[$k] = $v;
		}
		foreach ($params_names as $k=>$v) {
			$items = db()->get_2d("SELECT `id`,`title` FROM `".db('shop_productparams_options')."` WHERE `productparams_id`={$k} ORDER BY `title`");
			$params[$k]  = array(
				'title' => $v,
				'items' => $items,
			);
		}
		
//		return "<pre>".print_r($params,1).print_r($params_selected,1)."</pre>";
		$replace = array(
			"params" => json_encode($params),
			"params_selected" => json_encode($params_selected),
		);
		return tpl()->parse("manage_shop/productparams_container", $replace);
	}
	
}