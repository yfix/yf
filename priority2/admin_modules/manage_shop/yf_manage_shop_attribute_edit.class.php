<?php
class yf_manage_shop_attribute_edit{

	function attribute_edit () {
		if (empty($_GET["id"])) {
			return _e(t("no id"));
		}
		$_GET["id"] = intval($_GET["id"]);
		
		$A = db()->query_fetch("SELECT * FROM ".db('dynamic_fields_info')." WHERE id=".$_GET["id"]);
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
				db()->UPDATE("dynamic_fields_info", $sql_array, "id=".$_GET["id"]); 
				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("dynamic_fields_info");
				}
				return js_redirect("./?object=manage_shop&action=attributes_manage");
			}
		}
		$replace = array(
			"name"			=> _prepare_html($A["name"]),
			"value_list"	=> _prepare_html(implode("\n", (array)unserialize($A["value_list"]))),
			"default_value"	=> _prepare_html($A["default_value"]),
			"order"			=> $A["order"],
			"back_url"		=> "./?object=manage_shop&action=attributes_manage",
			"active"		=> 1,
			"form_action"	=> "./?object=manage_shop&action=".$_GET["action"]."&id=".$A["id"],
			"error"			=> _e(),
		);
		return tpl()->parse("manage_shop/attributes_edit", $replace);
	}
	
}