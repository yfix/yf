<?php
class yf_manage_shop_show_orders{

	function show_orders() {
		$sql = "SELECT * FROM ".db('shop_orders')."";
		$filter_sql = module('manage_shop')->USE_FILTER ? module('manage_shop')->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY date DESC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$orders_info = db()->query_fetch_all($sql.$add_sql);
		if (!empty($orders_info)) {
			foreach ((array)$orders_info as $v){
				$summ = $summ + $v["total_sum"];
				$user_ids[] = $v["user_id"];
			}
			$user_infos = user($user_ids);
		}
		foreach ((array)$orders_info as $v){
			$items[] = array(
				"order_id"	=> $v["id"],
				"date"		=> _format_date($v["date"], "long"),
				"sum"		=> module('manage_shop')->_format_price($v["total_sum"]),
				"user_link"	=> _profile_link($v["user_id"]),
				"user_name"	=> _display_name($user_infos[$v["user_id"]]),
				"status"	=> $v["status"],
				"delete_url"=> "./?object=manage_shop&action=delete_order&id=".$v["id"],
				"view_url"	=> "./?object=manage_shop&action=view_order&id=".$v["id"],
			);
		}
		$replace = array(
			"items"	=> (array)$items,
			"pages"	=> $pages,
			"summ"	=> module('manage_shop')->_format_price($summ),
			"total"	=> intval($total),
			"filter"=> module('manage_shop')->USE_FILTER ? module('manage_shop')->_show_filter() : "",
		);
		return tpl()->parse("manage_shop/order_main", $replace);
	}
	
}