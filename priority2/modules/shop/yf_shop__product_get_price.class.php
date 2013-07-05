<?php
class yf_shop__product_get_price{

	function _product_get_price ($product_info = array()) {
		return $product_info["_group_price"] ? $product_info["_group_price"] : $product_info["price"];
	}
	
}