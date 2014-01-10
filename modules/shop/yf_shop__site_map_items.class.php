<?php
class yf_shop__site_map_items{

	function _site_map_items ($sm_obj) {
		if (!is_object($sm_obj)) {
			return false;
		}
		$shop_cats = _class('cats')->_get_items_array('shop_cats');
		foreach ((array)$shop_cats as $cid => $c) {
			if (!$c['parent_id']) {
				$top_level[$cid] = $cid;
			}
		}
		foreach ((array)$top_level as $cid) {
			$c = &$shop_cats[$cid];
			if (!$c['active']) {
				unset($shop_cats[$cid]);
				continue;
			}
			$sm_obj->_store_item(array(
				'url' => url('/shop/products/'.$cid),
			));
		}
		$q = db()->query('SELECT id FROM '.db('shop_products').' WHERE active="1" AND image="1"');
		while ($a = db()->fetch_assoc($q)) {
			$sm_obj->_store_item(array(
				'url' => url('/shop/product/'.$a['id']),
			));
		}
		return true;
	}
	
}