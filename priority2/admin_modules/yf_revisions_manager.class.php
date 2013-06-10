<?php

/**
* Display online users
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_revisions_manager {

	/** @var bool Filter on/off */
//	public $USE_FILTER		= true;

	/**
	* Default method
	*/
	function show () {
		$sql = "SELECT `id`
					, `object_name`
					, `object_id`
					, COUNT(`object_name`) AS num
					, MAX(`date`) AS last_rev 
				FROM `".db('revisions')."` 
				GROUP BY `object_name`,`object_id` 
				ORDER BY `object_name` ASC, last_rev DESC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$records = db()->query_fetch_all($sql.$add_sql);
		// Process data
		foreach ((array)$records as $A) {
			// Prepare template
			$replace2 = array(
				"object_name"	=> _prepare_html($A["object_name"]),
				"object_id"		=> _prepare_html($A["object_id"]),
				"num_revisions"	=> $A["num"],
				"last_date"		=> _format_date($A["last_rev"], "long"),
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"details_link"	=> "./?object=".$_GET["object"]."&action=view&id=".intval($A["id"]),
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		$replace =array (
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* View item revisions information
	*/
	function view () {
		$sql = "SELECT * FROM `".db('revisions')."` WHERE `object_name`=(
			SELECT `object_name` FROM `".db('revisions')."` WHERE `id`=".intval($_GET["id"])." 
		) AND `object_id`=(
			SELECT `object_id` FROM `".db('revisions')."` WHERE `id`=".intval($_GET["id"])." 
		) ORDER BY `date` DESC";	
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$records = db()->query_fetch_all($sql.$add_sql);
		$rev_quantity = count($records);
		// Process data
		foreach ((array)$records as $A) {
			// Prepare template
			$replace2 = array(
				"rev_number"	=> $rev_quantity--,			
				"rev_date"		=> _format_date($A["date"], "long"),
				"fsize_prev"	=> common()->format_file_size(strlen($A["old_text"])),
				"fsize_curr"	=> common()->format_file_size(strlen($A["new_text"])),
				"comment"		=> _prepare_html($A["comment"]),
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"details_link"	=> "./?object=".$_GET["object"]."&action=preview&id=".intval($A["id"]),
			);
			$items .= tpl()->parse($_GET["object"]."/object_item", $replace2);
		}
		$replace =array (
			"object_name" 	=> _prepare_html($A["object_name"]),
			"object_id"		=> _prepare_html($A["object_id"]),
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
		);
		return tpl()->parse($_GET["object"]."/object_main", $replace);
	}


	/**
	* Preview old and new versions of item as they will be showed to user
	*/
	function preview () {
		$sql = "SELECT * FROM `".db('revisions')."` WHERE `id`=".intval($_GET["id"]);
		$A = db()->query_fetch($sql);
		$replace = array(
			"object_name"	=> _prepare_html($A["object_name"]),
			"object_id"		=> _prepare_html($A["object_id"]),
			"old_text"		=> nl2br(_prepare_html($A["old_text"])),
			"new_text"		=> nl2br(_prepare_html($A["new_text"])),
			"back_url"		=> "javascript:history.back();",
		);
		return tpl()->parse($_GET["object"]."/preview_obj_item", $replace);
	}

	//-----------------------------------------------------------------------------
	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = t("Revisions manger");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
			"view"					=> "",
			"preview"				=> "",
		);			 		
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}

		return array(
			"header"	=> $pheader,
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}
}
