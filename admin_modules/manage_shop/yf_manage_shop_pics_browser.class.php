<?php

class yf_manage_shop_pics_browser {

	function pics_browser() {
		if (isset($_GET['active']) && $_GET['active'] == 1) {
			$active = ' AND p.active = \'1\' ';
		} elseif (isset($_GET['active']) && $_GET['active'] == 0) {
			$active = ' AND p.active = \'\' ';
		} else {
			$active = '';
		}

		if (main()->is_post()) {
			foreach($_POST['delete'] as $k=>$v) {
				list ($id,$product_id) = explode("_",$k);
				module('manage_shop')->_product_image_delete($id, $product_id); 
			}
		}
		$cats_list = _class( '_shop_categories', 'modules/shop/' )->recursive_get_child_ids(62521);
		$sql = "SELECT `i`.`product_id`,`i`.`id` FROM `".db('shop_products')."` AS `p`, `".db('shop_product_images')."` AS `i` WHERE `p`.`id`=`i`.`product_id` AND `p`.`cat_id` IN ('".implode("','",$cats_list)."')".$active;
		
		list( $add_sql, $pages, $total_records, $page_current, $pages_total, $pages_limited ) = common()->divide_pages($sql);
		
		$R = db()->query($sql . $add_sql);
		$items = array();
		while ($A = db()->fetch_assoc($R)) {
			$_cls_products = _class( '_shop_products', 'modules/shop/' );		
			$image = $_cls_products->_product_image($A['product_id'],true);
			
			$items[] = array(
				'id' => $A['product_id'],
				'image_id' => $A['id'],
				'image' => $image['big'],
			);
		}
		
		$replace = array(
			'items' => $items,
			'total' => $total_records,
			'pages' => $pages,
		);
		$tpl_name = 'manage_shop/pics_browser';
        return tpl()->parse( $tpl_name, $replace );
	}
}
