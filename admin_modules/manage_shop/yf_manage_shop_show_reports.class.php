<?php
class yf_manage_shop_show_reports{

	function show_reports() {
		if ($_GET["id"] == "viewed") {
			$items = module('manage_shop')->show_reports_viewed ();
			$active = "viewed";
		} elseif ($_GET["id"] == "sales") {
			$items = module('manage_shop')->show_reports_sales ();
			$active = "sales";
		} elseif ($_GET["id"] == "purchased") {
			$items = module('manage_shop')->show_reports_purchased ();
			$active = "purchased";
		}
		$replace = array(
			"items"			=>	$items,
			"active"		=>	$active,
			"viewed_url"	=> "./?object=manage_shop&action=show_reports&id=viewed",
			"sales_url"		=> "./?object=manage_shop&action=show_reports&id=sales",
			"purchased_url"	=> "./?object=manage_shop&action=show_reports&id=purchased",
		);
		return tpl()->parse("manage_shop/report_main", $replace);
	}
	
}