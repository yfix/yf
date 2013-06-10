<?php

/**
* Shop cart methods
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_shop_cart {

	/**
	* Constructor
	*/
	function _init () {
		// Reference to the parent object
		$this->SHOP_OBJ		= module(SHOP_CLASS_NAME);
	}

	/**
	* Clean cart contents
	*/
	function add_to_cart() {
		$cart = &$_SESSION["SHOP_CART"];

		$A = db()->query_fetch("SELECT `id` FROM `".db('shop_products')."` WHERE `active` = '1' AND ".(is_numeric($_GET["id"]) ? "`id`=".intval($_GET["id"]) : "`url`='"._es($_GET['id'])."'"));

		if (!empty($A)) {
			$_GET['id'] = $A['id'];
		}
		$atts = $this->SHOP_OBJ->_get_products_attributes($A["id"]);
		// Save cart contents
		if ($_GET["id"]) {
			$_GET["id"] = intval($_GET["id"]);
			$_POST["quantity"][$_GET["id"]] = 1;
		}
		// Display 
		if (!empty($atts) && empty($_POST["atts"])) {
			$this->SHOP_OBJ->_CART_PROCESSED = true;

			return js_redirect("./?object=".SHOP_CLASS_NAME."&action=product_details&id=".$_GET["id"]);
		}
		// Do save
		if (!empty($_POST["quantity"]) && !$this->SHOP_OBJ->_CART_PROCESSED) {
			// Save new data into session
			foreach ((array)$_POST["quantity"] as $_product_id => $_quantity) {
				$_product_id	= intval($_product_id);
				$_old_quantity	= isset($cart[$_product_id]) ? $cart[$_product_id]["quantity"] : 0;
				$_quantity		= intval($_quantity) + intval($_old_quantity);
				if ($_product_id && $_quantity) {
					$cart[$_product_id] = array(
						"product_id"=> $_product_id,
						"quantity"	=> $_quantity,
						"atts"			=> $_POST["atts"][$_product_id],
					);
				}
			}
			// Prevent double processing
			$this->SHOP_OBJ->_CART_PROCESSED = true;
		}
		return js_redirect("./?object=shop");
	}

	/**
	* Display cart contents (save changes also here)
	*/
	function cart($params = array()) {
		$STPL_NAME = $params["STPL"] ? $params["STPL"] : SHOP_CLASS_NAME."/cart";
		/*
		$_SESSION["SHOP_CART"][$product_id] = array(
			"product_id"=> 1,
			"quantity"	=> 1,
		);
		*/
		$cart = &$_SESSION["SHOP_CART"];
		// Save cart contents
		if (!empty($_POST["quantity"]) && !$this->SHOP_OBJ->_CART_PROCESSED) {

			$this->SHOP_OBJ->_save_cart_all();

			return js_redirect("./?object=".SHOP_CLASS_NAME."&action=".$_GET["action"]);
		}
		// Get products from db
		$products_ids = array();
		foreach ((array)$cart as $_item_id => $_info) {
			if ($_info["product_id"]) {
				$products_ids[$_info["product_id"]] = $_info["product_id"];
			}
		}
		if (!empty($products_ids)) {
			$products_infos = db()->query_fetch_all("SELECT * FROM `".db('shop_products')."` WHERE `active`='1' AND `id` IN(".implode(",", $products_ids).")");
			$products_atts	= $this->SHOP_OBJ->_get_products_attributes($products_ids);
			$group_prices	= $this->SHOP_OBJ->_get_group_prices($products_ids);
		}
		$total_price = 0;
		foreach ((array)$products_infos as $_info) {
			$_product_id = $_info["id"];
			$_info["_group_price"] = $group_prices[$_product_id][$this->SHOP_OBJ->USER_GROUP];
			$quantity = $cart[$_info["id"]]["quantity"];
			$price = $this->SHOP_OBJ->_get_product_price($_info);

			$dynamic_atts = array();
			foreach ((array)$products_atts[$_product_id] as $_attr_id => $_attr_info) {
				if ($cart[$_product_id]["atts"][$_attr_info["name"]] == $_attr_info["value"]) {
					$dynamic_atts[$_attr_id] = "- ".$_attr_info["name"]." ".$_attr_info["value"];
					$price += $_attr_info["price"];
				}
			}

			$URL_PRODUCT_ID = $this->SHOP_OBJ->_product_id_url($_info);

			$products[$_info["id"]] = array(
				"name"				=> _prepare_html($_info["name"]),
				"price"					=> $this->SHOP_OBJ->_format_price($price),
				"currency"			=> _prepare_html($this->SHOP_OBJ->CURRENCY),
				"quantity"			=> intval($quantity),
				"delete_link"		=> "./?object=".SHOP_CLASS_NAME."&action=clean_cart&id=".$URL_PRODUCT_ID,
				"details_link"		=> process_url("./?object=".$_GET["object"]."&action=product_details&id=".$URL_PRODUCT_ID),
				"dynamic_atts"	=> !empty($dynamic_atts) ? implode("\n<br />", $dynamic_atts) : "",
				"cat_name"			=> _prepare_html($this->SHOP_OBJ->_shop_cats[$_info["cat_id"]]),
				"cat_url"				=> process_url("./?object=".$_GET["object"]."&action=show_products&id=".($this->SHOP_OBJ->_shop_cats_all[$_info["cat_id"]]['url'])),
			);
			$total_price += $price * $quantity;
		}
		$replace = array(
			"form_action"		=> "./?object=".SHOP_CLASS_NAME."&action=".$_GET["action"],
			"products"			=> $products,
			"total_price"		=> $this->SHOP_OBJ->_format_price($total_price),
			"currency"			=> _prepare_html($this->SHOP_OBJ->CURRENCY),
			"clean_all_link"	=> "./?object=".SHOP_CLASS_NAME."&action=clean_cart",
			"order_link"			=> "./?object=".SHOP_CLASS_NAME."&action=order",
			"back_link"			=> js_redirect($_SERVER["HTTP_REFERER"], false),
			"cats_block"		=> $this->SHOP_OBJ->_show_shop_cats(),
		);
		return tpl()->parse($STPL_NAME, $replace);
	}

	/**
	* Display cart contents (usually for side block)
	*/
	function _cart_side() {
		return $this->cart(array("STPL" => SHOP_CLASS_NAME."/cart_side"));
	}

	/**
	* show_cart_main
	*/
	function show_cart_main() {
		$cart = &$_SESSION["SHOP_CART"];
			// Get products from db
		$products_ids = array();
		foreach ((array)$cart as $_item_id => $_info) {
			if ($_info["product_id"]) {
				$products_ids[$_info["product_id"]] = $_info["product_id"];
			}
		}
		if (!empty($products_ids)) {
			$products_infos = db()->query_fetch_all("SELECT * FROM `".db('shop_products')."` WHERE `active`='1' AND `id` IN(".implode(",", $products_ids).")");
			$products_atts	= $this->SHOP_OBJ->_get_products_attributes($products_ids);
			$group_prices	= $this->SHOP_OBJ->_get_group_prices($products_ids);
		}
		$total_price = 0;
		foreach ((array)$products_infos as $_info) {
			$_product_id = $_info["id"];
			$_info["_group_price"] = $group_prices[$_product_id][$this->SHOP_OBJ->USER_GROUP];
			$quantity2 = $cart[$_info["id"]]["quantity"];
			$price = $this->SHOP_OBJ->_get_product_price($_info);
			$dynamic_atts = array();
			foreach ((array)$products_atts[$_product_id] as $_attr_id => $_attr_info) {
				if ($cart[$_product_id]["atts"][$_attr_info["name"]] == $_attr_info["value"]) {
					$dynamic_atts[$_attr_id] = "- ".$_attr_info["name"]." ".$_attr_info["value"];
					$price += $_attr_info["price"];
				}
			}
			$total_price += $price * $quantity2;
			$quantity		+= intval($quantity2);
		}
		$replace = array(
			"total_price"	=> $this->SHOP_OBJ->_format_price($total_price),
			"currency"		=> _prepare_html($this->SHOP_OBJ->CURRENCY),
			"quantity"		=> $quantity,
			"order_link"		=> "./?object=".SHOP_CLASS_NAME."&action=cart",
			"cart_link"		=> "./?object=".SHOP_CLASS_NAME."&action=cart",
		
		);
		return tpl()->parse(SHOP_CLASS_NAME."/show_cart_main", $replace);
	}
	
	/**
	* Save cart
	*/
	function _save_cart_all() {
		$cart = &$_SESSION["SHOP_CART"];
		// Save cart contents
		if (!empty($_POST["quantity"]) && !$this->SHOP_OBJ->_CART_PROCESSED) {
			$cart = array();
			// Save new data into session
			$products_ids = array();
			foreach ((array)$_POST["quantity"] as $_product_id => $_quantity) {
				$_product_id	= intval($_product_id);
				$_quantity		= intval($_quantity);
				if ($_product_id && $_quantity) {
					$cart[$_product_id] = array(
						"product_id"	=> $_product_id,
						"quantity"		=> $_quantity,
						"atts"				=> $_POST["atts"][$_product_id],
					);
				}
			}
			// Prevent double processing
			$this->SHOP_OBJ->_CART_PROCESSED = true;
		}
	}

	/**
	* Clean cart contents
	*/
	function clean_cart() {
		$cart = &$_SESSION["SHOP_CART"];
		
		// $_GET["id"] = intval($_GET["id"]);
		$add_sql = "`url`='"._es($_GET['id']);
		$sql = "SELECT * FROM `".db('shop_products')."` WHERE `active`='1' AND ".$add_sql."'";
		$product_info = db()->query_fetch($sql);
		$_GET["id"] = $product_info["id"];
		// Delete one product from cart
		if ($_GET["id"] && isset($cart[$_GET["id"]])) {
			$cart[$_GET["id"]] = null;
		}
		// Clean all itms from cart
		if (!$_GET["id"] && isset($cart)) {
			$cart = null;
		}
		return js_redirect($_SERVER["HTTP_REFERER"], false); 
	}
}
