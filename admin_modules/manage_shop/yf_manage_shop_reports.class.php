<?php
class yf_manage_shop_reports{

	function reports() {
		if ($_GET["id"] == "viewed") {
			$items = module('manage_shop')->reports_viewed ();
			$active = "viewed";
		} elseif ($_GET["id"] == "sales") {
			$items = module('manage_shop')->reports_sales ();
			$active = "sales";
		} elseif ($_GET["id"] == "purchased") {
			$items = module('manage_shop')->reports_purchased ();
			$active = "purchased";
		}
		$replace = array(
			"items"			=>	$items,
			"active"		=>	$active,
			"viewed_url"	=> "./?object=manage_shop&action=reports&id=viewed",
			"sales_url"		=> "./?object=manage_shop&action=reports&id=sales",
			"purchased_url"	=> "./?object=manage_shop&action=reports&id=purchased",
		);
		return tpl()->parse("manage_shop/report_main", $replace);
	}

	function reports_viewed() {
/*
		$sql = "SELECT * FROM ".db('shop_products')."";
		$sql .=   " ORDER BY viewed DESC ";
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
		$replace = array(
			"items"		=> $items,
			"pages"		=> $pages,
			"total"		=> intval($total),
			"sort_url"	=> "./?object=manage_shop&action=sort",
		);
		return tpl()->parse("manage_shop/reports_viewed", $replace);
*/
	}
	
}