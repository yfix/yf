<?php
class yf_shop_search{

	/**
	*/
	function search() {
		if ($_GET["id"] == "fast") {
// TODO: fill with real code
		}
		$str_search = 	$_POST ["search"];
		$rezult	=	$this->prepare_str($str_search);
		return $rezult;
	}

	/**
	*/
	function prepare_str($string) {
		$patrn 	= "/([\[\}\]\{\%\"\'\<\>\~\!\@\#\$\%\&\*\(\)\s\-\_\`\:\;\+\=\|\?\№\.\\/\^\,])+/iu";
		$patrn2 	= array("<", ">");
		$string 	= str_replace($patrn2, "", $string);
		$string		= _strtolower(trim($string));
		$string 	= preg_replace($patrn, ",", $string);
		$string  	= trim ($string, ",");
		$str_search = explode(",", $string);
		$rezult	=	$this->search_fast($str_search);
		return $rezult;
	}
		
	/**
	*/
	function search_fast($str_search ) {
		$str_search2 = implode(" ", $str_search);
		$str_search = implode("%", $str_search);
			
		$sql = "SELECT `id` FROM  `".db('shop_products')."`  WHERE  `name` LIKE  ";
		$_sql = "";
		$_sql =  $sql."'%".$str_search."%'";
		$Q = db()->query($_sql);
		$product_ids = "";
		while ($product_id = db()->fetch_assoc($Q)) { 
			$product_ids .= $product_id["id"].",";
		}	
		$product_ids = rtrim($product_ids, ",");
		if ($product_ids ==""){
			$replace = array(
				"str_search"	=>$str_search2,
				"form_action"	=> process_url("./?object=shop&action=search&id=fast"),
			);
			return tpl()->parse("shop/no_search_results", $replace);
		} else {	
			$replace = module('shop')->products_show($product_ids, $str_search2);
		}
		return $replace;	
	}
	
}