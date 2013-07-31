<?php
class yf_manage_shop_attributes{

	/**
	*/
	function attributes () {
		return common()->table2("SELECT * FROM ".db('shop_product_attributes_info')." ORDER BY `order` ASC")
			->link("category_id", "./?object=category_editor&action=show_items&&id=%d", _class('cats')->_get_items_names("shop_cats"))
			->text("name")
			->text("value_list")
			->text("default_value")
			->btn_edit("", "./?object=manage_shop&action=attribute_edit&id=%d")
			->btn_delete("", "./?object=manage_shop&action=attribute_delete&id=%d")
			->btn_active("", "./?object=manage_shop&action=attribute_activate&id=%d")
			->footer_link("Add","./?object=manage_shop&action=attribute_add")
		;
	}	

	/**
	*/
	function attribute_add () {
		if ($_POST) {
			if (empty($_POST["name"])) {
				_re(t("Name is required"));
			}
			if (!common()->_error_exists()) {
				$value_list = array();
				foreach ((array)explode("\n", $_POST["value_list"]) as $val){
					$value_list[$val] = $val;
				}
				db()->INSERT("shop_product_attributes_info", db()->es(array(
					"name"			=> $_POST["name"],
					"type"			=> $_POST["type"],
					"value_list"	=> $value_list,
					"default_value"	=> $_POST["default_value"],
					"order"			=> $_POST["order"],
					"category_id"	=> $_POST["category_id"],
				)));
				if (main()->USE_SYSTEM_CACHE) {
					cache()->refresh("shop_product_attributes_info");
				}
				return js_redirect("./?object=manage_shop&action=attributes");
			}
		}
		$form_fields = array("name","type","value_list","default_value","order", "comment");
		$replace = array_fill_keys($form_fields, "");
		$replace = my_array_merge($replace, array(
			"form_action"	=> "./?object=manage_shop&action=".$_GET["action"]."&id=".$_GET["id"],
			"error"			=> _e(),
			"back_url"		=> "./?object=manage_shop&action=attributes",
			"active"		=> 1,
		));
		return common()->form2($replace)
			->text("name")
			->textarea("value_list")
			->select_box("category_id", module('manage_shop')->_cats_for_select, array("selected" => $A["category_id"]))
			->save_and_back();
	}

	/**
	*/
	function attribute_edit () {
		if (empty($_GET["id"])) {
			return _e(t("no id"));
		}
		$_GET["id"] = intval($_GET["id"]);
		$A = db()->query_fetch("SELECT * FROM ".db('shop_product_attributes_info')." WHERE id=".$_GET["id"]);
		if ($_POST) {
			if (empty($_POST["name"])) {
				_re(t("Name is required"));
			}
			if (empty($_POST["value_list"])) {
				_re(t("Values list is required"));
			}
			if (!common()->_error_exists()) {
				$value_list = array();
				foreach ((array)explode("\n", $_POST["value_list"]) as $val) {
					$value_list[$val] = $val;
				}
				db()->UPDATE("shop_product_attributes_info", db()->es(array(
					"name"			=> $_POST["name"],
					"type"			=> $_POST["type"],
					"value_list"	=> implode("\n", $value_list),
					"default_value"	=> $_POST["default_value"],
					"order"			=> $_POST["order"],
					"category_id"	=> $_POST["category_id"],
				)), "id=".$_GET["id"]); 
				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("shop_product_attributes_info");
				}
				return js_redirect("./?object=manage_shop&action=attributes");
			}
		}
		$replace = array(
			"form_action"	=> "./?object=manage_shop&action=".$_GET["action"]."&id=".$A["id"],
			"error"			=> _e(),
			"name"			=> $A["name"],
			"value_list"	=> $A["value_list"],
			"default_value"	=> $A["default_value"],
			"order"			=> $A["order"],
			"back_url"		=> "./?object=manage_shop&action=attributes",
			"active"		=> 1,
		);
		return common()->form2($replace)
			->text("name")
			->textarea("value_list")
			->select_box("category_id", module('manage_shop')->_cats_for_select, array("selected" => $A["category_id"]))
			->save_and_back()
			->render();
	}

	/**
	*/
	function attribute_delete () {
		$_GET["id"] = intval($_GET["id"]);
		$field_info = db()->query_fetch("SELECT * FROM ".db('shop_product_attributes_info')." WHERE id = ".intval($_GET["id"]));
		if (empty($field_info)) {
			return _e(t("no field"));
		}
		if ($_GET["id"]) {
			db()->query("DELETE FROM ".db('shop_product_attributes_info')." WHERE id=".$_GET["id"]);
			db()->query("DELETE FROM ".db('shop_product_attributes_values')." WHERE category_id = ".module('manage_shop')->ATTRIBUTES_CAT_ID." AND field_id = ".$_GET["id"]);
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("shop_product_attributes_info");
			}
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	/**
	*/	
	function attribute_activate () {
		if ($_GET["id"]){
			$A = db()->query_fetch("SELECT * FROM ".db('shop_product_attributes_info')." WHERE id=".intval($_GET["id"]));
			if ($A["active"] == 1) {
				$active = 0;
			} elseif ($A["active"] == 0) {
				$active = 1;
			}
			db()->UPDATE(db('shop_product_attributes_info'), array("active" => $active), "id='".intval($_GET["id"])."'");
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($active ? 1 : 0);
		} else {
			return js_redirect("./?object=manage_shop");
		}
	}

}