<?php

/**
* Planner for regular tasks (backend)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_tasks_planner {

	/** @var string Path to the CURL library on the server (for call from CRON) */
	public $CURL_PATH			= "curl -s -o /dev/null ";
	/** @var string Path to the tasks PHP files */
	public $TASKS_FILES_PATH	= "./classes/tasks/";
	/** @var string Session array name where filter vars are stored */
	public $_filter_name		= "tasks_filter";
	/** @var bool Filter on/off */
	public $USE_FILTER			= true;

	/**
	* Constructor
	*/
	function _init() {
		$this->_std_trigger = array(
			"1" => "<span class='positive'>YES</span>",
			"0" => "<span class='negative'>NO</span>",
		);
		// Array of select boxes to process
		$this->_boxes = array(
			"task_enabled"	=> 'radio_box("task_enabled",	$this->_std_trigger,	$selected, false, 2, "", false)',
			"log_enabled"	=> 'radio_box("log_enabled",	$this->_std_trigger,	$selected, false, 2, "", false)',
			"minutes"		=> 'select_box("minute",		$this->_minutes,		$selected, false, 2, "", false)',
			"hours"			=> 'select_box("hour",			$this->_hours,			$selected, false, 2, "", false)',
			"week_days"		=> 'select_box("week_day",		$this->_week_days,		$selected, false, 2, "", false)',
			"month_days"	=> 'select_box("month_day",		$this->_month_days,		$selected, false, 2, "", false)',
		);
		// Prepare arrays
		$this->_minutes[-1] = "Every Minute";
		for ($i = 0; $i <= 59; $i++) $this->_minutes[$i] = $i;

		$this->_hours[-1] = "Every Hour";
		for ($i = 0; $i <= 23; $i++) $this->_hours[$i] = $i;

		$this->_week_days[-1] = "Every Week Day";
		for ($i = 1; $i <= 7; $i++) $this->_week_days[$i] = gmstrftime("%A", strtotime("2007-01-".$i));

		$this->_month_days[-1] = "Every Day of the Month";
		for ($i = 1; $i <= 31; $i++) $this->_month_days[$i] = $i;
	}

	/**
	* Default method (display all avaliable tasks)
	*/
	function show() {
		// Connect pager
		$sql = "SELECT * FROM `".db('task_manager')."` ORDER BY `next_run` ASC";
		list($limit_sql, $pages, $total) = common()->divide_pages($sql);
		// Process records
		$Q = db()->query($sql. $limit_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"id"			=> $A["id"],
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"name"			=> _prepare_html($A["title"]),
				"desc"			=> _prepare_html($A["description"]),
				"file_name"		=> _prepare_html($A["file"]),
				"next_run"		=> _format_date($A["next_run"], "long"),
				"week_day"		=> $A["week_day"],
				"month_day"		=> $A["month_day"],
				"hour"			=> $A["hour"],
				"minute"		=> $A["minute"],
				"cronkey"		=> _prepare_html($A["cronkey"]),
				"log_enabled"	=> intval((bool)$A["log"]),
				"active"		=> intval((bool)$A["enabled"]),
				"fast_key"		=> _prepare_html($A["key"]),
				"run_expired"	=> intval($A["next_run"] < time()),
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit_task&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_task&id=".$A["id"],
				"run_link"		=> "./?object=".$_GET["object"]."&action=run_task&id=".$A["id"],
				"cron_run_link"	=> $this->CURL_PATH. WEB_PATH."?object=task_loader&ck=".$A["cronkey"],
			);
			$items .= tpl()->parse($_GET["object"]."/task_item", $replace2);
		}
		$replace = array(
			"add_link"		=> "./?object=".$_GET["object"]."&action=add_task",
			"view_logs_link"=> "./?object=".$_GET["object"]."&action=view_logs",
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Add new task
	*/
	function add_task() {
		// Do save
		if (isset($_POST["go"])) {
			// Check for errors
			if (!common()->_error_exists()) {
				db()->INSERT("task_manager", array(
					"title"			=> _es($_POST["task_name"]),
					"description"	=> _es($_POST["task_desc"]),
					"file"			=> _es($_POST["task_file"]),
					"php_code"		=> _es($_POST["php_code"]),
					"minute"		=> intval($_POST["minute"]),
					"hour"			=> intval($_POST["hour"]),
					"week_day"		=> intval($_POST["week_day"]),
					"month_day"		=> intval($_POST["month_day"]),
					"cronkey"		=> md5(microtime(true).$_POST["task_title"]),
					"enabled"		=> intval((bool)$_POST["task_enabled"]),
					"log"			=> intval((bool)$_POST["log_enabled"]),
				));
				js_redirect("./?object=".$_GET["object"]);
			}
		}
		// Display form
		if (!isset($_POST["go"]) || common()->_error_exists()) {
			$replace = array(
				"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
				"for_edit"			=> 0,
				"task_name"			=> _prepare_html($task_info["title"]),
				"task_desc"			=> _prepare_html($task_info["description"]),
				"tasks_files_path"	=> _prepare_html($this->TASKS_FILES_PATH),
				"task_file_name"	=> _prepare_html($task_info["file"]),
				"task_key"			=> _prepare_html($task_info["key"]),
				"php_code"			=> _prepare_html($task_info["php_code"]),
				"minutes_box"		=> $this->_box("minutes",		-1),
				"hours_box"			=> $this->_box("hours",			-1),
				"week_days_box"		=> $this->_box("week_days",		-1),
				"month_days_box"	=> $this->_box("month_days",	-1),
				"log_enabled_box"	=> $this->_box("log_enabled",	1),
				"task_enabled_box"	=> $this->_box("task_enabled",	1),
				"back_link"			=> "./?object=".$_GET["object"],
				"error_message"		=> _e(),
			);
			return tpl()->parse($_GET["object"]."/edit_task", $replace);
		}
	}

	/**
	* Edit selected task
	*/
	function edit_task() {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			common()->_raise_error(t("No ID!"));
			return _e();
		}
		// Try to get current task info
		$task_info = db()->query_fetch("SELECT * FROM `".db('task_manager')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($task_info["id"])) {
			common()->_raise_error(t("No such task!"));
			return _e();
		}
		// Do save
		if (isset($_POST["go"])) {
			// Check for errors
			if (!common()->_error_exists()) {
				db()->UPDATE("task_manager", array(
					"title"			=> _es($_POST["task_name"]),
					"description"	=> _es($_POST["task_desc"]),
					"file"			=> _es($_POST["task_file"]),
					"php_code"		=> _es($_POST["php_code"]),
					"minute"		=> intval($_POST["minute"]),
					"hour"			=> intval($_POST["hour"]),
					"week_day"		=> intval($_POST["week_day"]),
					"month_day"		=> intval($_POST["month_day"]),
					"enabled"		=> intval((bool)$_POST["task_enabled"]),
					"log"			=> intval((bool)$_POST["log_enabled"]),
				), "`id`=".intval($_GET["id"]));
				js_redirect("./?object=".$_GET["object"]);
			}
		}
		// Display form
		if (!isset($_POST["go"]) || common()->_error_exists()) {
			$replace = array(
				"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
				"for_edit"			=> 1,
				"task_name"			=> _prepare_html($task_info["title"]),
				"task_desc"			=> _prepare_html($task_info["description"]),
				"tasks_files_path"	=> _prepare_html($this->TASKS_FILES_PATH),
				"task_file_name"	=> _prepare_html($task_info["file"]),
				"task_key"			=> _prepare_html($task_info["key"]),
				"php_code"			=> _prepare_html($task_info["php_code"]),
				"minutes_box"		=> $this->_box("minutes",		$task_info["minute"]),
				"hours_box"			=> $this->_box("hours",			$task_info["hour"]),
				"week_days_box"		=> $this->_box("week_days",		$task_info["week_day"]),
				"month_days_box"	=> $this->_box("month_days",	$task_info["month_day"]),
				"log_enabled_box"	=> $this->_box("log_enabled",	$task_info["log"]),
				"task_enabled_box"	=> $this->_box("task_enabled",	$task_info["enabled"]),
				"back_link"			=> "./?object=".$_GET["object"],
				"error_message"		=> _e(),
			);
			return tpl()->parse($_GET["object"]."/edit_task", $replace);
		}
	}

	/**
	* Delete task
	*/
	function delete_task() {
		$_GET["id"] = intval($_GET["id"]);
		// Do delete
		if (!empty($_GET["id"])) {
			db()->query("DELETE FROM `".db('task_manager')."` WHERE `id`=".intval($_GET["id"])." LIMIT 1");
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	* Run selected task
	*/
	function run_task() {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			common()->_raise_error(t("No ID!"));
			return _e();
		}
		// Try to get current task info
		$task_info = db()->query_fetch("SELECT * FROM `".db('task_manager')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($task_info["id"])) {
			common()->_raise_error(t("No such task!"));
			return _e();
		}
		$_GET["ck"] = $task_info["cronkey"];
		// Set long time limit
		@set_time_limit(1200);
		// Init Task manager object
		$TASK_MGR_OBJ = &main()->init_class("task_manager", "classes/");
		// Do run
		$TASK_MGR_OBJ->run_task();
		// Return user back
		return js_redirect("./?object=".$_GET["object"]);
	}

	/**
	* Display logs method
	*/
	function view_logs() {
		// Connect pager
		$sql = "SELECT * FROM `".db('task_logs')."` ORDER BY `log_date` DESC";
		list($limit_sql, $pages, $total) = common()->divide_pages($sql);
		// Process records
		$Q = db()->query($sql. $limit_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"id"			=> $A["log_id"],
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"log_name"		=> _prepare_html($A["log_title"]),
				"log_desc"		=> _prepare_html($A["log_desc"]),
				"log_ip"		=> _prepare_html($A["log_ip"]),
				"log_date"		=> _format_date($A["log_date"], "long"),
				"log_time"		=> common()->_format_time_value($A["log_time"]),
			);
			$items .= tpl()->parse($_GET["object"]."/logs_item", $replace2);
		}
		$replace = array(
			"tasks_link"	=> "./?object=".$_GET["object"],
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
		);
		return tpl()->parse($_GET["object"]."/logs_main", $replace);
	}

	/**
	* Delete selected logs records
	*/
	function delete_logs() {
// TODO
//		$_GET["prune_days"] = 
		// Do delete
//		db()->query("DELETE FROM `".db('task_manager')."` WHERE `log_date` <= ".intval(time() - $_GET["prune_days"] * 86400));
		// Return user back
		js_redirect("./?object=".$_GET["object"]."&action=view_logs");
	}

	/**
	* Generate filter SQL query
	*/
	function _create_filter_sql () {
		$MF = &$_SESSION[$this->_filter_name];
		foreach ((array)$MF as $k => $v) $MF[$k] = trim($v);
// TODO
/*
		// Generate filter for the common fileds
		if ($MF["id_min"]) 				$sql .= " AND `id` >= ".intval($MF["id_min"])." \r\n";
		if ($MF["id_max"])			 	$sql .= " AND `id` <= ".intval($MF["id_max"])." \r\n";
*/
		// Sorting here
		if ($MF["sort_by"])			 	$sql .= " ORDER BY `".$this->_sort_by[$MF["sort_by"]]."` \r\n";
		if ($MF["sort_by"] && strlen($MF["sort_order"])) 	$sql .= " ".$MF["sort_order"]." \r\n";
		return substr($sql, 0, -3);
	}

	/**
	* Session - based logs filter form stored in $_SESSION[$this->_filter_name][...]
	*/
	function _show_filter () {
		$replace = array(
			"save_action"	=> "./?object=".$_GET["object"]."&action=save_filter"._add_get(),
			"clear_url"		=> "./?object=".$_GET["object"]."&action=clear_filter"._add_get(),
		);
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = $_SESSION[$this->_filter_name][$name];
		}
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $_SESSION[$this->_filter_name][$item_name]);
		}
		return tpl()->parse($_GET["object"]."/logs_filter", $replace);
	}

	/**
	* Save logs filter
	*/
	function save_filter ($silent = false) {
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_POST[$name];
		}
		if (!$silent) {
			js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	/**
	* Clear logs filter
	*/
	function clear_filter ($silent = false) {
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) unset($_SESSION[$this->_filter_name]);
		}
		if (!$silent) {
			js_redirect("./?object=".$_GET["object"]._add_get());
		}
	}

	/**
	* Process custom box
	*/
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
				"name"	=> "Add task",
				"url"	=> "./?object=".$_GET["object"]."&action=add_task",
			),
			array(
				"name"	=> "View tasks logs",
				"url"	=> "./?object=".$_GET["object"]."&action=view_logs",
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
		$pheader = t("Task manager");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"			=> "",
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
