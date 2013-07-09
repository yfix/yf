<?php

/**
* Test sub-class
*/
class yf_test_user_design {

	/**
	* YF module constructor
	*/
	function _init () {
		$this->TEST_OBJ = module("test");
	}

	/**
	* Display sample page with selected user design id
	*/
	function run_test () {
		$_themes_infos = db()->query_fetch_all("SELECT * FROM ".db('user_themes')." WHERE active='1'");
		foreach ((array)$_themes_infos as $v) {
			$_themes[$v["id"]] = $v["name"];
		}
		if (!$_themes) {
			return "Sorry, no user themes availiable";
		}
		// Get design details (only by ID)
		$design_infos = db()->query_fetch_all("SELECT * FROM ".db('designs')."");
		foreach ((array)$design_infos as $v) {
			if (!isset($_themes[$v["theme_id"]])) {
				continue;
			}
			$_designs[$v["id"]] = $v["name"];
			$_designs_for_select[$v["id"]] = $v["name"]." (id=".$v["id"].", theme_id=".$v["theme_id"]."".($v["owner_id"] ? ", owner_id=".$v["owner_id"] : "").")";
		}

		if (!empty($_REQUEST["id"])) {
			$force_design_id = "";

			$design_info = $design_infos[$_REQUEST["id"]];

			$force_design_id = $design_info["id"];
			if (!$force_design_id) {
				return "Sorry, no such design found";
			}
			$force_theme_name = "";
			if (isset($_themes[$design_info["theme_id"]])) {
				$force_theme_name = $_themes[$design_info["theme_id"]];
			}
			if (!$force_theme_name) {
				return "Sorry, no such theme found";
			}
			$add_css = "";
/*
			if ($design_info["owner_id"]) {
				$_def_design_id = $_themes_infos[$design_info["theme_id"]]["default_design"];
				if (isset($design_infos[$_def_design_id])) {
					$force_design_id = $_def_design_id;
					$add_css = $design_info["css"];
				}
			}
*/
			// Show content
			main()->_execute("design_settings", "parse_user_template", array(
				"force_theme_name"	=> $force_theme_name,
				"force_design_id"	=> $force_design_id,
				"no_custom_css"		=> 1,
				"no_center_content"	=> 1,
				"center_content"	=> "<script>fill_text(60);</script>",
				"add_css"			=> $add_css,
			));
			return "";
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"designs_box"	=> common()->select_box("id", $_designs_for_select, "", false),
		);
		return tpl()->parse($_GET["object"]."/".$_GET["action"], $replace);
	}
}
