<?php
class yf_shop_show_orders{

	function show_orders() {
		if (!main()->USER_ID) {
			if (!empty($_POST)) {
				module('shop')->validate_order_data();
				// Display next form if we have no errors
				if (!common()->_error_exists()) {
					return module('shop')->view_order(true);
				}
			}
			$items[] = array(
				"order_id"		=> $_POST["order_id"],
				"email"			=> $_POST["email"],
				"form_action"	=> "./?object=shop&action=show_orders",
				"back_link"		=> "./?object=shop",
			);
		} else {
			$sql = "SELECT * FROM ".db('shop_orders')." WHERE user_id=".intval(main()->USER_ID);
			//$filter_sql = $this->PARENT_OBJ->USE_FILTER ? $this->PARENT_OBJ->_create_filter_sql() : "";
			$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY date DESC ";
			list($add_sql, $pages, $total) = common()->divide_pages($sql);
			$orders_info = db()->query_fetch_all($sql.$add_sql);
			if (!empty($orders_info)) {
				foreach ((array)$orders_info as $v){
					$user_ids[] = $v["user_id"];
				}
				$user_infos = user($user_ids);
			}
			foreach ((array)$orders_info as $v){
				if ($v["status"] == "pending" or $v["status"] == "pending payment" ){
					$del = "./?object=shop&action=delete_order&id=".$v["id"];
				} else {
					$del = "";
				}
				$items[] = array(
					"order_id"	=> $v["id"],
					"date"		=> _format_date($v["date"], "long"),
					"sum"		=> module('shop')->_format_price($v["total_sum"]),
					"user_link"	=> _profile_link($v["user_id"]),
					"user_name"	=> _display_name($user_infos[$v["user_id"]]),
					"status"	=> $v["status"],
					"delete_url"=> $del,
					"view_url"	=> "./?object=shop&action=view_order&id=".$v["id"],
				);
			}
		}
		$replace = array(
			"error_message"	=> _e(),
			"items"			=> (array)$items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"filter"		=> module('shop')->USE_FILTER ? module('shop')->_show_filter() : "",
		);
		return tpl()->parse("shop/order_show", $replace);
	}
	
}