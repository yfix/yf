<?php

/**
* Manage common polls created by admin
*/
class yf_polls_manager {

	/** @var bool Filter on/off */
//	var $USE_FILTER		= true;

	/**
	* Constructor (PHP 4.x)
	*/
	function yf_polls_manager () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*/
	function __construct () {
		$this->POLL_OBJ = main()->init_class("poll", "modules/");
		$this->_polls_info = db()->query_fetch_all(
			"SELECT * FROM `".db('polls')."` WHERE `user_id`=0 ORDER BY `add_date`,`votes` DESC"
		);
	}

	/**
	* Default method
	*/
	function show () {
		foreach ((array)$this->_polls_info as $A) {
			$choices_array = explode("\n", $A["choices"]);
			array_walk($choices_array, '_prepare_html');
			$A["choices"] = implode("<br />",$choices_array);
			$replace2 = array(
				"question"		=> _prepare_html($A["question"]),
				"choices"		=> $A["choices"],
				"add_date"		=> _format_date($A["add_date"], "long"),
				"votes"			=> $A["votes"],
				"delete_url"	=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
				"activate_url"  => "./?object=".$_GET["object"]."&action=activate&id=".$A["id"],
				"active"		=> $A["active"],
			);
			$items .= tpl()->parse($_GET["object"]."/manage_item", $replace2);
		}
		$replace = array(
			"items"			=> $items,						
			"create_url"	=> "./?object=".$_GET["object"]."&action=create_poll",
		);
		return tpl()->parse($_GET["object"]."/manage_main", $replace);
	}

	/**
	* Show create form and store poll data to db
	*/
	function create_poll () {
		return is_object($this->POLL_OBJ) ? $this->POLL_OBJ->_create(array("common" => 1)) : "";
	}

	/**
	* Delete poll
	*/
	function delete () {

		if (db()->query("DELETE FROM `".db('polls')."` WHERE `id` = ".intval($_GET["id"])." AND `user_id`=0")) {
			db()->query("DELETE FROM `".db('poll_votes')."` WHERE `poll_id` = ".intval($_GET["id"]));			
		}
		return js_redirect("./?object=".$_GET["object"]);
	}

	/**
	* Activate\deactivate polls
	*/
	function activate () {
		$_GET["id"] = intval($_GET["id"]);
		if ($_GET["id"]){
			//
			foreach ((array)$this->_polls_info as $A) {
				if ($A["id"] == $_GET["id"] && $A["active"]) {
					$_active = 0;
				} elseif ($A["id"] == $_GET["id"] && !$A["active"]) {
					$_active = 1;
				}				
			}
			$sql_array = array(
				"active"	=> $_active,
			);
			db()->UPDATE("polls", $sql_array, "`id`=".$_GET["id"]);
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($A["active"] ? 1 : 0);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
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
				"name"	=> "Create common poll",
				"url"	=> "./?object=".$_GET["object"]."&action=create_poll",
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
		$pheader = t("Polls manger");
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
