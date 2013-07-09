<?php

/**
* Test sub-class
*/
class yf_test_color_scheme {

	/**
	* YF module constructor
	*/
	function _init () {
		$this->TEST_OBJ = module("test");
	}

	/**
	* Display sample page with selected user color scheme (apply it to the default theme)
	*/
	function run_test () {
		$DEFAULT_THEME_ID = main()->DEFAULT_THEME_ID;
		$_themes = db()->query_fetch_all("SELECT * FROM ".db('user_themes')." WHERE active='1'");
		$_theme_info = $_themes[$DEFAULT_THEME_ID];

		$DEFAULT_DESIGN_ID = $_theme_info["default_design"];
		$_designs = db()->query_fetch_all("SELECT * FROM ".db('designs')." WHERE active='1'");
		$_design_info = $_designs[$DEFAULT_DESIGN_ID];
		// Check required params
		if (empty($_theme_info) || empty($_design_info)) {
			return "Sorry, wrong default theme or design";
		}
		$color_schemes	= main()->get_data("color_schemes");
		$_schemes_for_select = $color_schemes;
		// Do render custom scheme layout
		if (!empty($_REQUEST["id"])) {
			$force_theme_name	= $_theme_info["name"];
			$force_design_id	= $DEFAULT_DESIGN_ID;
			$force_color_id		= 0;
// TODO: check if current scheme is not suitable to the default theme
// then we will need to select theme from allowed for this scheme list
			$force_color_id = $_REQUEST["id"];
			if (!isset($color_schemes[$_REQUEST["id"]])) {
				return "Sorry, no such scheme found";
			}
			// Show content
			main()->_execute("design_settings", "parse_user_template", array(
				"force_theme_name"	=> $force_theme_name,
				"force_design_id"	=> $force_design_id,
				"force_color_id"	=> $force_color_id,
				"no_custom_css"		=> 1,
				"no_center_content"	=> 1,
				"center_content"	=> "<script>fill_text(60);</script>",
				"add_css"			=> "",
			));
			return "";
		}
		// Show form
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"schemes_box"	=> common()->select_box("id", $_schemes_for_select, "", false),
		);
		return tpl()->parse($_GET["object"]."/".$_GET["action"], $replace);
	}
}
