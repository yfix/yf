<?php
class yf_manage_shop_statistic{

	function statistic () {
		$total_sum = db()->query_fetch("SELECT SUM(total_sum) FROM ".db('shop_orders')."");
		$total_order = db()->query_fetch("SELECT COUNT(*) FROM ".db('shop_orders')."");
		$total_prod = db()->query_fetch("SELECT COUNT(*) FROM ".db('shop_products')."");
		$total_order_pending = db()->query_fetch("SELECT COUNT(*) FROM ".db('shop_orders')." WHERE status = 'pending'");
		$total_sum_shipped = db()->query_fetch("SELECT SUM(total_sum) FROM ".db('shop_orders')." WHERE status = 'shipped'");
		$replace = array(
			"summ"					=> module("manage_shop")->_format_price($total_sum["SUM(total_sum)"]),
			"total_order"			=> intval($total_order["COUNT(*)"]),
			"total_order_pending"	=> intval($total_order_pending["COUNT(*)"]),
			"total_sum_shipped"		=> module("manage_shop")->_format_price($total_sum_shipped["SUM(total_sum)"]),
			"total_prod"			=> intval($total_prod["COUNT(*)"]),
		);
		return tpl()->parse("manage_shop/stat_main", $replace);
	}
	
}