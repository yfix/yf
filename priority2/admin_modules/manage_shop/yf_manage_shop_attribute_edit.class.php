<?php
class yf_manage_shop_attribute_edit{

	function attribute_edit () {
		if (empty($_GET["id"])) {
			return _e(t("no id"));
		}
		$_GET["id"] = intval($_GET["id"]);
		
		$A = db()->query_fetch("SELECT * FROM ".db('shop_product_attributes_info')." WHERE id=".$_GET["id"]);
		if (isset($_POST["go"])) {
			if (empty($_POST["name"])) {
				_re(t("Name is required"));
			}
			if (!common()->_error_exists()) {
				$value_list	= explode("\n", $_POST["value_list"]);
				$i=0;
				foreach ((array)$value_list as $val){
					$i++;
					$value_list_temp[$i] = $val;
				}
				$value_list = serialize($value_list_temp);
				// Save data
				$sql_array = array(
					"name"			=> _es($_POST["name"]),
					"type"			=> $_POST["type"],
					"value_list"	=> $value_list,
					"default_value"	=> _es($_POST["default_value"]),
					"order"			=> $_POST["order"],
				);
				db()->UPDATE("shop_product_attributes_info", $sql_array, "id=".$_GET["id"]); 
				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("shop_product_attributes_info");
				}
				return js_redirect("./?object=manage_shop&action=attributes");
			}
		}
		$replace = array(
			"form_action"	=> "./?object=manage_shop&action=".$_GET["action"]."&id=".$A["id"],
			"error"			=> _e(),
			"name"			=> _prepare_html($A["name"]),
			"value_list"	=> _prepare_html(implode("\n", (array)unserialize($A["value_list"]))),
			"default_value"	=> _prepare_html($A["default_value"]),
			"order"			=> $A["order"],
			"back_url"		=> "./?object=manage_shop&action=attributes",
			"active"		=> 1,
		);
		return common()->form2($replace)
			->text("name")
			->textarea("value_list")
			->save_and_back()
			->render();
	}
	
}