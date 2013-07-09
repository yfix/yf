<?php
class yf_manage_shop_products_manage{

	function products_manage () {
		if (!empty($_GET["name"])) {
			$_POST["name"] = $_GET["name"];
			module("manage_shop")->clear_filter(1);
			module("manage_shop")->save_filter(1);
		}
		if (!empty($_GET["price_min"])) {
			$_POST["price_min"] = $_GET["price_min"];
			module("manage_shop")->clear_filter(1);
			module("manage_shop")->save_filter(1);
		}
		if (!empty($_GET["price_max"])) {
			$_POST["price_max"] = $_GET["price_max"];
			module("manage_shop")->clear_filter(1);
			module("manage_shop")->save_filter(1);
		}
		if (!empty($_GET["quantity_min"])) {
			$_POST["quantity_min"] = $_GET["quantity_min"];
			module("manage_shop")->clear_filter(1);
			module("manage_shop")->save_filter(1);
		}
		if (!empty($_GET["quantity_max"])) {
			$_POST["quantity_max"] = $_GET["quantity_max"];
			module("manage_shop")->clear_filter(1);
			module("manage_shop")->save_filter(1);
		}
		 if (!empty($_GET["sort_by"])) {
			$_POST["sort_by"] = $_GET["sort_by"];
			module("manage_shop")->clear_filter(1);
			module("manage_shop")->save_filter(1);
		} 
		 if (!empty($_GET["sort_order"])) {
			$_POST["sort_by"] = $_GET["sort_by"];
			module("manage_shop")->clear_filter(1);
			module("manage_shop")->save_filter(1);
		} 
		if (!empty($_GET["status_prod"])) {
			$_POST["status_prod"] = $_GET["status_prod"];
			module("manage_shop")->clear_filter(1);
			module("manage_shop")->save_filter(1);
		}
		$sql = "SELECT * FROM ".db('shop_products')."";
		$filter_sql = module("manage_shop")->USE_FILTER ? module("manage_shop")->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY add_date DESC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql, "", "", 100);
		$products_info = db()->query_fetch_all($sql.$add_sql);
		module("manage_shop")->_total_prod = $total;
		foreach ((array)$products_info as $v){
			$replace2 = array(
				"name"			=> _prepare_html($v["name"]),
				"date"			=> _format_date($v["add_date"], "long"),
				"price"			=> $v["price"],
				"old_price"		=> $v["old_price"],
				"quantity"		=> $v["quantity"],
				"active"		=> $v["active"],
				"edit_url"		=> "./?object=manage_shop&action=product_edit&id=".$v["id"],
				"delete_url"	=> "./?object=manage_shop&action=product_delete&id=".$v["id"],
				"view_url"		=> "./?object=manage_shop&action=product_view&id=".$v["id"],
				"activate_url"	=> "./?object=manage_shop&action=product_activate&id=".$v["id"],
			);
			$items .= tpl()->parse("manage_shop/products_item", $replace2); 
		}
		$replace = array(
			"items"				=> $items,
			"pages"				=> $pages,
			"total"				=> intval($total),
			"filter"			=> module("manage_shop")->USE_FILTER ? module("manage_shop")->_show_filter() : "",
			"add_url"			=> "./?object=manage_shop&action=product_add",
			"categories_url"	=> "./?object=category_editor&action=show_items&id=shop_cats",
			"attributes_url"	=> "./?object=manage_shop&action=attributes_manage",
			"orders_url"		=> "./?object=manage_shop&action=show_orders",
		);
		return tpl()->parse("manage_shop/products_main", $replace);
	}

}