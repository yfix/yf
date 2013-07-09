<?php
class yf_manage_shop__create_filter_sql{

	function _create_filter_sql () {
/*
		$SF = &$_SESSION[module("manage_shop")->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
		if ($SF["price_min"]){
			$sql .= " AND price >= ".intval($SF["price_min"])." \r\n";
		}
		if ($SF["price_max"])	{
			$sql .= " AND price <= ".intval($SF["price_max"])." \r\n";
		}
		if ($SF["quantity_min"]){
			$sql .= " AND quantity >= ".intval($SF["quantity_min"])." \r\n";
		}
		if ($SF["quantity_max"])	{
			$sql .= " AND quantity <= ".intval($SF["quantity_max"])." \r\n";
		}
		if (strlen($SF["name"])){
			$sql .= " AND name LIKE '"._es($SF["name"])."%' \r\n";
		}
		 if($SF["status_prod"] == '0'){
			$sql .= " AND active = '".intval($SF["status_prod"])."' \r\n";
		}elseif($SF["status_prod"] == '1'){
			$sql .= " AND active = '".intval($SF["status_prod"])."' \r\n";
		} 
		// Sorting here
		if ($SF["sort_by"])	{
			$sql .= " ORDER BY  " .$SF["sort_by"]." \r\n";
		}
		if ($SF["sort_by"] && strlen($SF["sort_order"])) {
			$sql .= " ".$SF["sort_order"]." \r\n";
		}
		return substr($sql, 0, -3);
*/
	}
	
}