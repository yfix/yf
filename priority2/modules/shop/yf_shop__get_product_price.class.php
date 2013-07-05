<?php
class yf_shop__get_product_price{

	function _get_product_price ($product_info = array()) {
		return $product_info["_group_price"] ? $product_info["_group_price"] : $product_info["price"];
	}
	
}