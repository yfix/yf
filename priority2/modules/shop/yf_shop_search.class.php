<?php

/**
* Shop search methods
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_shop_search {

	/**
	* Constructor
	*/
	function _init () {
		// Reference to the parent object
	$this->SHOP_OBJ		= module(SHOP_CLASS_NAME);
		
	}
	
	function _short_search_form() {
		$replace = array(
			"search_string"	=>"",
			"form_action"		=> process_url("./?object=".$_GET["object"]."&action=search&id=fast"),
		);
		return tpl()->parse(SHOP_CLASS_NAME."/short_search_form", $replace);
	}
	
	function search() {
		if ($_GET["id"] == "fast") {
			
		}
		$str_search = 	$_POST ["search"];
		$rezult	=	$this->prepare_str($str_search);
		
		return $rezult;
		
	}
	
	function prepare_str($string)
		{
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
		
		function search_fast($str_search )
		{
			// поиск по одному параметру
			$str_search2 = implode(" ", $str_search);
			$str_search = implode("%", $str_search);
			
			$sql = "SELECT `id` FROM  `".db('shop_products')."`  WHERE  `name` LIKE  ";
			$_sql = "";
			$_sql =  $sql."'%".$str_search."%'";
			$Q = db()->query($_sql);
			$product_ids = "";
			while ($product_id = db()->fetch_assoc($Q))
			{ 
				
				$product_ids .= $product_id["id"].",";
			}	
			
			$product_ids = rtrim($product_ids, ",");
			// возврат страницы с пустым результатом	
			if ($product_ids ==""){
				$replace = array(
					"str_search"	=>$str_search2,
					"form_action"	=> process_url("./?object=".$_GET["object"]."&action=search&id=fast"),
				);
			return tpl()->parse(SHOP_CLASS_NAME."/no_search_results", $replace);
				
			// возврат результата поиска
			} else {	
				$replace 	= 	$this->show_products($product_ids, $str_search2);
				//$replace["search_form"] = $this->search_form($str_search, $checked);
				
			}
			
			return $replace;	
			//return tpl()->parse('gallery/search_rezult', $replace);	
		}
		
			function show_products ($product_ids, $str_search2) {
				// вызов модуля показа изображений
				$OBJ = main()->init_class("yf_shop", "modules/shop/");
				return $OBJ->show_products($product_ids, $str_search2);
			
		}
	
	

}
