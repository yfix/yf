<?php

/**
* Board help section
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_forum_help {
	
	/**
	* Show Main
	*/
	function _show_main() {
		if (!module('forum')->SETTINGS["SHOW_HELP"]) {
			return module('forum')->_show_error("Help is disabled");
		}
		$_GET["id"] = intval($_GET["id"]);
		// Show topic item
		if (!empty($_GET["id"])) {
			$help_info = db()->query_fetch("SELECT * FROM ".db('faq')." WHERE id=".intval($_GET["id"]));
			if (empty($help_info["id"])) {
				return module('forum')->_show_error("No such help topic!");
			}
			$replace = array(
				"text"	=> $help_info["text"],
				"title"	=> $help_info["title"],
			);
			$body = tpl()->parse(FORUM_CLASS_NAME."/help_item", $replace);
		// Show items list
		} else {
			$Q = db()->query("SELECT * FROM ".db('faq')." ORDER BY title ASC");
			while ($A = db()->fetch_assoc($Q)) {
				$items[$A["id"]] = array(
					"topic_link"	=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]."&id=".$A["id"],
					"topic_name"	=> $A["title"],
					"desc"			=> $A["description"],
				);
			}
			$replace = array(
				"form_action"	=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]._add_get(array("page")),
				"items"			=> $items,
			);
			$body = tpl()->parse(FORUM_CLASS_NAME."/help_main", $replace);
		}
		return module('forum')->_show_main_tpl($body);
	}
	
	/**
	* Bb Code Help
	*/
	function _bb_code_help() {
		if (!module('forum')->SETTINGS["SHOW_HELP"]) {
			return module('forum')->_show_error("Help is disabled");
		}
		return tpl()->parse(FORUM_CLASS_NAME."/bb_code_help");
	}
	
	/**
	* Contact Admin
	*/
	function _contact_admin() {
// TODO
		return "Contact admin will be here...";
	}
}
