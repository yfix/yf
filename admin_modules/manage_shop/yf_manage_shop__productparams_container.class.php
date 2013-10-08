<?php
class yf_manage_shop__productparams_container {

	function _productparams_container($product_id) {
		$current_params = array();
		$current_param_id = 0;
		
		if (intval($product_id) != 0) {
			$R = db()->query("SELECT * FROM `".db('shop_products_productparams')."` WHERE `product_id`=".$product_id);
			while ($A = db()->fetch_assoc($R)) {
				$current_params_all[$A['productparam_id']][$A['value']] = $A['value'];
			}
		}
		$current_params = array();
		// leave only 1st param
		foreach ((array)$current_params_all as $k=>$v) {
			$current_param_id = $k;
			$current_params[$k] = $v;
			break;
		}
		
		$params = db()->get_all("SELECT `id`,`title` FROM `".db('shop_productparams')."` ORDER BY `title`");
		foreach ($params as $v) {
			$params[$v['id']]['selected'] = $v['id'] == $current_param_id ? 'selected' : "";
		}

		
		foreach ($params as $k=>$v) {
			$options_list = '';
			$arr = db()->get_all("SELECT * FROM `".db('shop_productparams_options')."` WHERE `productparams_id`='{$v['id']}' ORDER BY `title`");
			if (count($arr) < 2) {
				unset($params[$k]);
			} else {
				foreach ($arr as $v1) {
					$options_list .= "<option value='{$v1['id']}' ".(!empty($current_params[$v['id']][$v1['id']]) ? "selected" : "").">{$v1['title']}</option>";
					$options_list .= "<input type='checkbox' name='productparams_options_{$v['id']}[]' value='{$v1['id']}' ".(!empty($current_params[$v['id']][$v1['id']]) ? "checked" : "")."> {$v1['title']}<br />";
					
				}
				$params_options[] = array(
					'productparams_id' => $v['id'],
					'options_list' => $options_list,
				);
			}
		}
		$replace = array(
			"current_param_id" => $current_param_id,
			"params" => $params,
			"params_options" => $params_options,
		);
		return tpl()->parse("manage_shop/productparams_container", $replace);
	}
	
}