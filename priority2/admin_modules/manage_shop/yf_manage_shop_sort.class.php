<?php
class yf_manage_shop_sort{

	function sort() {
		main()->NO_GRAPHICS = true;
		list ($name, $sort_by) = split ('[-]', $_POST["id"]);
		if ($name == "percent"){
			$name = "viewed";
		}
		$sql = "SELECT * FROM ".db('shop_products')." ORDER BY ".$name." ".$sort_by." ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql, "", "", 100);
		$products_info = db()->query_fetch_all($sql.$add_sql);
		module('manage_shop')->_total_prod = $total;
		$query = db()->query_fetch("SELECT SUM(viewed) AS total FROM ".db('shop_products')."");
		foreach ((array)$products_info as $v){
			if ($v['viewed'] ) {
				$percent = round(($v['viewed'] / $query["total"]) * 100, 2) . '%';
			} else {
				$percent = '0%';
			}
			$replace2 = array(
				"name"		=> _prepare_html($v["name"]),
				"model"		=> _prepare_html($v["model"]),
				"viewed"	=> _prepare_html($v["viewed"]),
				"percent"	=> $percent,
			);
			$items .= tpl()->parse("manage_shop/item_reports_viewed", $replace2); 
		}
		echo $items;
	}
	
}