<?php

/**
* Display core errors
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_se_keywords_manager {

	/** @var bool Filter on/off */
	public $USE_FILTER		= true;

	/**
	*/
	function __construct () {
		$A = db()->query_fetch_all("SELECT * FROM ".db('search_engines')." ORDER BY id");
		foreach ((array)$A as $V){
			$this->engines[$V["id"]] = _prepare_html($V["name"]); 
			$this->s_engines[$V["id"]] = $V; 
		}
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	/**
	* Default method
	* 
	* @access
	* @param
	* @return
	*/
	function show () {
		$sql = "SELECT id, text, hits, engine FROM ".db('search_keywords')."";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : "";
		$sql .= " ORDER BY hits DESC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$A = db()->query_fetch_all($sql.$add_sql);

		foreach ((array)$A as $V){
			$encoded_text = urlencode($V["text"]);
			$engine_link = "http://".$this->s_engines[$V["engine"]]["search_url"]."?".$this->s_engines[$V["engine"]]["q_s_word"]."=".$encoded_text;
			$replace2 = array(
				"id"			=> intval($V["id"]),
				"text"			=> _prepare_html($V["text"]),
				"hits"			=> intval($V["hits"]),	
				"engine"		=> $this->engines[$V["engine"]],
				"engine_link"	=> $engine_link,
				"delete_link"	=> ".?object=".$_GET["object"]."&action=delete&id=".$V["id"],	

			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		$replace = array(
			"items"					=> $items,
			"pages"					=> $pages,
			"total"					=> $total, 
			"form_action"			=> "./?object=".$_GET["object"]."&action=multi_delete",
			"delete_by_hits_action" => "./?object=".$_GET["object"]."&action=delete_by_hits",
			"filter"				=> $this->USE_FILTER ? $this->_show_filter() : "",
			"delete_by_hits"		=> tpl()->parse($_GET["object"]."/filter2"),
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	*  Multi delete items on a page
	*/
	function multi_delete () {
		$ids_to_delete = array();
		// Prepare ids to delete
		foreach ((array)$_POST["items"] as $_cur_id) {
			if (empty($_cur_id)) {
				continue;
			}
			$ids_to_delete[$_cur_id] = $_cur_id;
		}
		// Do delete ids
		if (!empty($ids_to_delete)) {
			db()->query("DELETE FROM ".db('search_keywords')." WHERE id IN(".implode(",",$ids_to_delete).")");
		}
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Delete single record
	*/
	function delete () {
		$_GET["id"] = intval($_GET["id"]);
		// Do delete record
		db()->query("DELETE FROM ".db('search_keywords')." WHERE id=".intval($_GET["id"]));
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	/**
	* Delete records where hits less than indicates
	*/
	function delete_by_hits () {
		if (intval($_POST["hits"])){
			// Do delete record
			db()->query("DELETE FROM ".db('search_keywords')." WHERE hits<".intval($_POST["hits"]));
		}
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	// Prepare required data for filter
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";
		// Connect common used arrays
		if (file_exists(INCLUDE_PATH."common_code.php")) {
			include (INCLUDE_PATH."common_code.php");
		}
		// Fields in the filter
		$this->_fields_in_filter = array(
			"engine",
			"text",
		);
	}

	// Generate filter SQL query
	function _create_filter_sql () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
		// Generate filter for the common fileds
		if (strlen($SF["text"]))		$sql .= " AND text LIKE '%"._es($SF["text"])."%' \r\n";
		if (strlen($SF["engine"]))		$sql .= " AND engine=".intval($SF["engine"])." \r\n";
		return substr($sql, 0, -3);
	}

	// Session - based filter
	function _show_filter () {
		$replace = array(
			"text"			=> _prepare_html($_SESSION[$this->_filter_name]["text"]),
			"engine_box"	=> common()->select_box("engine", $this->engines, $_SESSION[$this->_filter_name]["engine"], 1, 2, "", false),
			"save_action"	=> "./?object=".$_GET["object"]."&action=save_filter"._add_get(),
			"clear_url"		=> "./?object=".$_GET["object"]."&action=clear_filter"._add_get(),
		);
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $_SESSION[$this->_filter_name][$item_name]);
		}
		return tpl()->parse($_GET["object"]."/filter", $replace);
	}

	// Filter save method
	function save_filter ($silent = false) {
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_REQUEST[$name];
		}
		if (!$silent) {
			if (!empty($_REQUEST["go_home"])) {
				return js_redirect("./?object=".$_GET["object"]);
			}
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	// Clear filter
	function clear_filter ($silent = false) {
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) unset($_SESSION[$this->_filter_name]);
		}
		if (!$silent) {
			if (!empty($_REQUEST["go_home"])) {
				return js_redirect("./?object=".$_GET["object"]);
			}
			return js_redirect("./?object=".$_GET["object"]._add_get());
		}
	}

	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> ucfirst($_GET["object"])." main",
				"url"	=> "./?object=".$_GET["object"],
			),
			array(
				"name"	=> "",
				"url"	=> "./?object=".$_GET["object"],
			),
		);
		return $menu;	
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = t("Search engines keywords manager");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
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
