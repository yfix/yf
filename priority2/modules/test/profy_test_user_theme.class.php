<?php

/**
* Test sub-class
*/
class profy_test_user_theme {

	/**
	* Profy module constructor
	*/
	function _init () {
		$this->TEST_OBJ = module("test");
	}

	/**
	* Display sample page with selected user theme name
	*/
	function run_test () {
		$_themes_infos = db()->query_fetch_all("SELECT * FROM `".db('user_themes')."` WHERE `active`='1'");
		foreach ((array)$_themes_infos as $v) {
			$_themes[$v["id"]] = $v["name"];
			$_themes_for_select[$v["id"]] = $v["name"]." (id=".$v["id"].")";
		}
		if (!$_themes) {
			return "Sorry, no user themes availiable";
		}

		if (!empty($_REQUEST["id"])) {
			$force_theme_name = "";
			if (is_numeric($_REQUEST["id"])) {
				if (isset($_themes[$_REQUEST["id"]])) {
					$force_theme_name = $_themes[$_REQUEST["id"]];
				}
			} elseif (in_array($_REQUEST["id"], $_themes)) {
				$force_theme_name = $_REQUEST["id"];
			}
			if (!$force_theme_name) {
				return "Sorry, no such theme found";
			}

			$add_css = "
				#site_header	{background-color: #EAEAEA;} 
				#below_header	{background-color: #ffffff;}
				#center_column	{background-color: #40BDE8;height:600px;} 
				#left_column	{background-color: #C8FC98;} 
				#right_column	{background-color: #FDE95E;} 
				#below_content	{background-color: #000000;} 
			";

			// Show content
			main()->_execute("design_settings", "parse_user_template", array(
				"force_theme_name"	=> $force_theme_name,
				"no_custom_css"		=> 1,
				"no_center_content"	=> 1,
				"center_content"	=> "<script>fill_text(60);</script>",
//				"add_css"			=> $add_css,
			));

			return "";
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"themes_box"	=> common()->select_box("id", $_themes_for_select, "", false),
		);
		return tpl()->parse($_GET["object"]."/".$_GET["action"], $replace);
	}
}
