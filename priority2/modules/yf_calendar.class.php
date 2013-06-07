<?php

//-----------------------------------------------------------------------------
// Escorts calendar manager
class yf_calendar {
	/** @var array @conf_skip Calendar date statuses */
	var $_date_statuses = array(
		0	=> "Not defined",
		1	=> "Busy",
		2	=> "Available",
		3	=> "Travel",
	);
	/** @var int Week first day (0 - sunday, 1 - monday, etc) */
// TODO: connect everywhere
	var $_week_first_day	= 0;
	/** @var int Min year */
	var $_min_year		= 2002;
	/** @var int Max year */
	var $_max_year		= 2030;
	/** @var bool If this turned on - then system will hide total ids for user, 
	* and wiil try to use small id numbers dedicated only for this user
	*/
	var $HIDE_TOTAL_ID	= false;
	/** @var int Date output format */
	var $DATE_FORMAT_NUM = 0;
	/** @var array */
	var $_date_formats = array(
		0	=> "Day-Month-Year (US format)",
		1	=> "Month-Day-Year (EU format)",
	);
	/** @var bool Hide empty months */
	var $HIDE_EMPTY_MONTHS = true;

	//-----------------------------------------------------------------------------
	// YF module constructor
	function _init () {
		$this->_max_year = gmdate("Y") + 5;
		// Array of select boxes to process
		$this->_boxes = array(
			"status"		=> 'select_box("status",		$this->_date_statuses,	$selected, false, 2)',
			"date_format"	=> 'radio_box("date_format",	$this->_date_formats,	$selected, 1, 2)',
		);
		$this->_date_statuses[0] = "";
		// Check total id mode
		$this->HIDE_TOTAL_ID = main()->HIDE_TOTAL_ID;
		if ($this->HIDE_TOTAL_ID && (
			MAIN_TYPE_ADMIN || 
			(empty($GLOBALS['HOSTING_ID']) && empty($this->USER_ID))
		)) {
			$this->HIDE_TOTAL_ID = false;
		}
	}

	//-----------------------------------------------------------------------------
	// Default method
	function show () {
		if ($this->USER_ID) {
			return $this->manage();
		} else {
			return $this->view();
		}
	}

	//-----------------------------------------------------------------------------
	// View given month and related info
	function view ($_uid = 0, $params = array()) {
		if ($this->HIDE_TOTAL_ID) {
			$_GET["page"] = $_GET["id"];
			unset($_GET["id"]);
			$USER_ID = $GLOBALS['HOSTING_ID'] ? $GLOBALS['HOSTING_ID'] : $this->USER_ID;
		} else {
			$USER_ID = $_uid ? intval($_uid) : intval($_GET["id"]);
		}
		// Force USER_ID
		if ($_uid) {
			$USER_ID = $_uid;
		}
		// Get user info
		if (!empty($USER_ID)) {
			$user_info = user($USER_ID, "full", array("WHERE" => array("active" => 1)));
		}
		if (empty($user_info)) {
			return _e(t("No such user!"));
		}
		// Prepare month time
		$cur_month_time = time();
		// Check input
		if (!empty($_GET["page"])) {
			list($cur_year, $cur_month) = explode("-", $_GET["page"]);
			$cur_year 	= intval($cur_year);
			$cur_month	= intval($cur_month);
			// Check allowed time
			if ($cur_year >= $this->_min_year && $cur_year <= $this->_max_year && $cur_month >= 1 && $cur_month <= 12) {
				$cur_month_time = strtotime($cur_year."-".$cur_month."-01 GMT");
			}
			unset($_GET["page"]);
		}
		// Prepare numbers
		$cur_month	= gmdate("m", $cur_month_time);
		$cur_year	= gmdate("Y", $cur_month_time);
		// Prepare times
		$prev_month_time	= $this->_get_other_month_time($cur_month_time, -1);
		$next_1_month_time	= $this->_get_other_month_time($cur_month_time, 1);
		$next_2_month_time	= $this->_get_other_month_time($cur_month_time, 2);
		$next_3_month_time	= $this->_get_other_month_time($cur_month_time, 3);
		// Get calendar settings
		$cal_settings = $this->_get_settings($user_info["id"]);
		$this->DATE_FORMAT_NUM = $cal_settings["date_format"];
		// Get marked days from db (where status == "available")
		$Q = db()->query(
			"SELECT `date`,`title`,`status` 
			FROM `".db('calendar_dates')."` 
			WHERE `user_id` = ".intval($user_info["id"])." 
				AND `status` IN(1,2,3)
				AND `date` >= ".intval($prev_month_time)." 
				AND `date` <= ".intval($next_3_month_time)
		);
		while ($A = db()->fetch_assoc($Q)) {
			$this->_marked_days[$A["date"]] = $A;
		}
		// Prepare month params
		$month_params = array(
			"user_id"			=> $user_info["id"],
			"select_cur_day"	=> 1,
		);
		// Get more precise max and min year
		$Q = db()->query(
			"SELECT FROM_UNIXTIME(`date`, '%Y-%m') AS `month_date` 
			FROM `".db('calendar_dates')."` 
			WHERE `user_id` = ".intval($user_info["id"])." 
				AND `status` IN(1,2,3) 
			GROUP BY FROM_UNIXTIME(`date`, '%Y-%m') 
			ORDER BY FROM_UNIXTIME(`date`, '%Y-%m') ASC"
		);
		$_min_year	= 0;
		$_max_year	= 0;
		$months_with_data = array();
		while ($A = db()->fetch_assoc($Q)) {
			list($_y, $_m) = explode("-", $A["month_date"]);
			if (!$_min_year || $_min_year > $_y) {
				$_min_year = $_y;
			}
			if (!$_max_year || $_max_year < $_y) {
				$_max_year = $_y;
			}
			$months_with_data[strtotime($_y."-".$_m."-01 GMT")] = $A["month_date"];
		}
		if ($_min_year) {
			$this->_min_year = $_min_year;
		}
		if ($_max_year) {
			$this->_max_year = $_max_year;
		}
		// Check if we need to display links to the prev/next months
		$need_prev_link = 0;
		$need_next_link = 0;

		$prev_time = 0;
		$next_time = 0;
		if ($cur_year <= $this->_min_year 
			&& !($cur_year == $this->_min_year && $cur_month == 1)
		) {
			if ($this->HIDE_EMPTY_MONTHS) {
				// Try to find prev month to link to
				foreach ((array)$months_with_data as $_time => $_date) {
					if ($_time < $cur_month_time) {
						$need_prev_link = 1;
						$prev_time = $_time;
					}
				}
			} else {
				$need_prev_link = 1;
				$prev_time = $prev_month_time;
			}
		}
		if ($cur_year >= $this->_max_year 
			&& !($cur_year == $this->_max_year && $cur_month == 12)
		) {
			// Try to find next month to link to
			if ($this->HIDE_EMPTY_MONTHS) {
				// Try to find prev month to link to
				foreach ((array)$months_with_data as $_time => $_date) {
					if ($_time <= $cur_month_time) {
						continue;
					}
					$need_next_link = 1;
					$next_time = $_time;
					break;
				}
			} else {
				$need_next_link = 1;
				$next_time = $next_1_month_time;
			}
		}

		$need_month_1 = true;
		$need_month_2 = true;
		if ($this->HIDE_EMPTY_MONTHS) {
			if (!isset($months_with_data[$next_1_month_time])) {
				$need_month_1 = false;
			}
			if (!isset($months_with_data[$next_2_month_time])) {
				$need_month_2 = false;
			}
		}
		// Processing template for widgets
		if ($params["for_widgets"]) {
			// Prepare template
			$month_params = my_array_merge((array)$month_params, (array)$params);
			$replace = array(
				"cur_month"	=> $this->_show_month(gmdate("Y-m-01", $cur_month_time), $month_params),
				"cal_title"	=> _prepare_html($cal_settings["title"]),
			);
			return tpl()->parse("calendar/widget", $replace);
		}
		// Prepare template
		$replace = array(
			"cur_month"			=> $this->_show_month(gmdate("Y-m-01", $cur_month_time), $month_params),
			"next_month_1"		=> $need_month_1 ? $this->_show_month(gmdate("Y-m-01", $next_1_month_time), $month_params) : "",
			"next_month_2"		=> $need_month_2 ? $this->_show_month(gmdate("Y-m-01", $next_2_month_time), $month_params) : "",
			"prev_month_link"	=> $need_prev_link && $prev_time ? "./?object=".$_GET["object"]."&action=".__FUNCTION__.($this->HIDE_TOTAL_ID ? "" : "&id=".intval($user_info["id"]))."&page=".gmdate("Y-m", $prev_time) : "",
			"next_month_link"	=> $need_next_link && $next_time ? "./?object=".$_GET["object"]."&action=".__FUNCTION__.($this->HIDE_TOTAL_ID ? "" : "&id=".intval($user_info["id"]))."&page=".gmdate("Y-m", $next_time) : "",
			"cal_title"			=> _prepare_html($cal_settings["title"]),
			"cal_desc"			=> _prepare_html($cal_settings["desc"]),
			"manage_link"		=> !empty($this->USER_ID) && $this->USER_ID == $user_info["id"] ? "./?object=".$_GET["object"]."&action=manage&id=".gmdate("Y-m", $cur_month_time) : "",
			"date_format_num"	=> intval($this->DATE_FORMAT_NUM),
		);
		return tpl()->parse("calendar/view_main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Display day info
	function day () {
		if ($this->HIDE_TOTAL_ID) {
			$_GET["page"] = $_GET["id"];
			unset($_GET["id"]);
			$USER_ID = $GLOBALS['HOSTING_ID'] ? $GLOBALS['HOSTING_ID'] : $this->USER_ID;
		} else {
			$USER_ID = intval($_GET["id"]);
		}
		// Get user info
		if (!empty($USER_ID)) {
			$user_info = user($USER_ID, "full", array("WHERE" => array("active" => 1)));
		}
		if (empty($user_info)) {
			return _e(t("No such user!"));
		}
		// Prepare date
		$cur_day_time = time();
		// Check input
		if (!empty($_GET["page"])) {
			list($cur_year, $cur_month, $cur_day) = explode("-", $_GET["page"]);
			$cur_year 	= intval($cur_year);
			$cur_month	= intval($cur_month);
			$cur_day	= intval($cur_day);
			// Check allowed time
			if ($cur_year >= $this->_min_year && $cur_year <= $this->_max_year && $cur_month >= 1 && $cur_month <= 12 && $cur_day >= 1 && $cur_day <= 31) {
				// Last check day number
				if ($cur_day <= gmdate("t", strtotime($cur_year."-".$cur_month."-01 GMT"))) {
					$cur_day_time = strtotime($cur_year."-".$cur_month."-".$cur_day." GMT");
				}
			}
			unset($_GET["page"]);
		}
		// Prepare numbers
		$cur_day	= gmdate("d", $cur_day_time);
		$cur_month	= gmdate("m", $cur_day_time);
		$cur_year	= gmdate("Y", $cur_day_time);
		// Prepare times
		$prev_month_time	= $this->_get_other_month_time($cur_day_time, -1);
		$next_1_month_time	= $this->_get_other_month_time($cur_day_time, 1);
		$next_2_month_time	= $this->_get_other_month_time($cur_day_time, 2);
		// Get calendar settings
		$cal_settings = $this->_get_settings($user_info["id"]);
		$this->DATE_FORMAT_NUM = $cal_settings["date_format"];
		// Get marked days from db (where status == "available")
		$Q = db()->query(
			"SELECT `date`,`title`,`status` 
			FROM `".db('calendar_dates')."` 
			WHERE `user_id` = ".intval($user_info["id"])." 
				AND `status` IN(1,2,3) 
				AND `date` >= ".intval($prev_month_time)." 
				AND `date` <= ".intval($next_2_month_time)
		);
		while ($A = db()->fetch_assoc($Q)) {
			$this->_marked_days[$A["date"]] = $A;
		}
		// Get day info
		$day_info = db()->query_fetch("SELECT * FROM `".db('calendar_dates')."` WHERE `user_id`=".intval($user_info["id"])." AND `date`=".intval($cur_day_time), false);
		// Preapre data to display
		$hours_info		= !empty($day_info["hours"]) ? @unserialize($day_info["hours"]) : array();
		@ksort($hours_info);
		$comment_info	= !empty($day_info["desc"]) ? @unserialize($day_info["desc"]) : array();
		@ksort($comment_info);
		$cur_week_day_num = gmdate("w", $cur_day_time);
		// We start with 2006 year
		$base_day_time = strtotime("2006-01-01 00:00:00 GMT");
		// Iterate over selected hours			
		$status_items = array();
		for ($_hour = 0; $_hour <= 23; $_hour++) {
			$_cur_hour_secs		= $_hour * 3600;
			if (empty($hours_info[$_cur_hour_secs])) {
				continue;
			}
			$_start_hour_secs	= $_cur_hour_secs;
			$_end_hour_secs		= $_cur_hour_secs + 59 * 60;
			$_status			= $hours_info[$_cur_hour_secs];
			// Skip repeated values
			$_next_hour_secs = $_cur_hour_secs + 3600;
			if ($hours_info[$_next_hour_secs] == $hours_info[$_cur_hour_secs]) {
				if (!isset($_tmp_hour_secs)) {
					$_tmp_hour_secs = $_cur_hour_secs;
				}
				continue;
			}
			if (isset($_tmp_hour_secs)) {
				$_start_hour_secs = $_tmp_hour_secs;
				unset($_tmp_hour_secs);
			}
			// Prepare current item
			$status_items[$_hour] = array(
				"status_id"		=> $_status,
				"from"			=> gmdate("G:i", $base_day_time + $_start_hour_secs),
				"to"			=> gmdate("G:i", $base_day_time + $_end_hour_secs),
				"comment"		=> $comment_info[$_cur_hour_secs],
				"status"		=> $this->_date_statuses[$_status],
				"status_id"		=> intval($hours_info[$_cur_hour_secs]),
			);
		}
		// Prepare month params
		$month_params = array(
			"user_id"	=> $user_info["id"],
		);
		// Prepare template
		$replace = array(
			"month_name"		=> _prepare_html(gmdate("F", $cur_day_time)),
			"month_num"			=> intval($cur_month),
			"day"				=> intval($cur_day),
			"year"				=> intval($cur_year),
			"cur_month"			=> $this->_show_month(gmdate("Y-m-01", $cur_day_time), $month_params),
			"title"				=> _prepare_html($day_info["title"]),
			"desc"				=> nl2br(_prepare_html($day_info["desc"])),
			"hours"				=> $status_items ? $status_items : "",
			"date_format_num"	=> intval($this->DATE_FORMAT_NUM),
		);
		return tpl()->parse("calendar/day_main", $replace);
	}
	
	//-----------------------------------------------------------------------------
	// Edit calendar contents
	function manage () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		$user_info = $this->_user_info;
		// Prepare month time
		$cur_month_time = time();
		// Check input
		if (!empty($_GET["id"])) {
			list($cur_year, $cur_month) = explode("-", $_GET["id"]);
			$cur_year 	= intval($cur_year);
			$cur_month	= intval($cur_month);
			// Check allowed time
			if ($cur_year >= $this->_min_year && $cur_year <= $this->_max_year && $cur_month >= 1 && $cur_month <= 12) {
				$cur_month_time = strtotime($cur_year."-".$cur_month."-01 GMT");
			}
			unset($_GET["page"]);
		}
		// Prepare numbers
		$cur_month	= gmdate("m", $cur_month_time);
		$cur_year	= gmdate("Y", $cur_month_time);
		// Prepare times
		$prev_month_time	= $this->_get_other_month_time($cur_month_time, -1);
		$next_1_month_time	= $this->_get_other_month_time($cur_month_time, 1);
		$next_2_month_time	= $this->_get_other_month_time($cur_month_time, 2);
		// Get calendar settings
		$cal_settings = $this->_get_settings();
		$this->DATE_FORMAT_NUM = $cal_settings["date_format"];
		// Mass saving
		if (isset($_POST["save2"])) {
			$start_date	= strtotime($_POST["start_year"]."-".$_POST["start_month"]."-".$_POST["start_day"]." ".$_POST["start_time"].":00:00 GMT");
			$end_date	= strtotime($_POST["end_year"]."-".$_POST["end_month"]."-".$_POST["end_day"]." ".$_POST["end_time"].":00:00 GMT");
			
			$start_day	= strtotime($_POST["start_year"]."-".$_POST["start_month"]."-".$_POST["start_day"]." 00:00:00 GMT");
			$end_day	= strtotime($_POST["end_year"]."-".$_POST["end_month"]."-".$_POST["end_day"]." 00:00:00 GMT");
			// Check interval
			if (!($end_date - $start_date)){
				common()->_raise_error(t("Select date and time!"));
			}
			// Do save statuses in selected time period
			if (!common()->_error_exists()) {
				$curent_date = $start_day;
				while ($curent_date <= $end_day) {
					$next_day_hours = array();	
					for ($i = 0; $i < 24; $i++) {
						$_hour_time = $curent_date + $i * 3600;
						if ($_hour_time < $start_date || $_hour_time > $end_date) {
							continue;
						}
						$next_day_hours[$i * 3600]	= $_POST["status"];	
						$desc[$i * 3600]			= $_POST["comments"];
					}
					if (!empty($next_day_hours)) {
						$this->_save_hours_into_db($curent_date, $next_day_hours, $desc);
					}
					$curent_date += 3600 * 24;
				}
				return js_redirect("./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"]);
			}
		}
		$this->_marked_days = array();
		// Get marked days from db (where status == "available")
		$Q = db()->query(
			"SELECT `date`,`title`,`status` 
			FROM `".db('calendar_dates')."` 
			WHERE `user_id` = ".intval($user_info["id"])." 
				AND `status` IN(1,2,3) 
				AND `date` >= ".intval($prev_month_time)." 
				AND `date` <= ".intval($next_2_month_time)
		);
		while ($A = db()->fetch_assoc($Q)) {
			$this->_marked_days[$A["date"]] = $A;
		}
		// Prepare datetime select box
		for ($i = 1; $i <= 31; $i++) {
			$days[$i] = ($i < 10 ? "0" : "").$i;
		}		
		for ($i = 0; $i < 24; $i++) {
			$hours[$i] = ($i < 10 ? "0" : "").$i;
		}
		$current_year = gmdate("Y", time());
		for ($i = $current_year; $i < $current_year + 3; $i++) {
			$years[$i] = $i;
		}
		for ($i = 1; $i < 13; $i++) {
			$a = ($i < 10 ? "0" : "").$i;
			$month[$a] = gmdate("F", strtotime("2008-".$a."-01 GMT")); 
		}		
		// Prepare month params
		$month_params = array(
			"onclick_link"		=> "./?object=".$_GET["object"]."&action=edit_day&id={cur_date}",
			"select_cur_day"	=> 1,
			"show_full_date"	=> 1,
			"user_id"			=> $user_info["id"],
		);
		$need_prev_link = ($cur_year < $this->_min_year || ($cur_year == $this->_min_year && $cur_month == 1)) ? 0 : 1;
		$need_next_link = ($cur_year > $this->_max_year || ($cur_year == $this->_max_year && $cur_month == 12)) ? 0 : 1;
		// Prepare template
		$replace = array(
			"form_action"			=> "./?object=".$_GET["object"]."&action=".__FUNCTION__."&id=".gmdate("Y-m", $cur_month_time),
			"toggle_action"			=> "./?object=".$_GET["object"]."&action=toggle_month&id=".gmdate("Y-m", $cur_month_time),
			"error_message"			=> _e(),
			"prev_month_link"		=> $need_prev_link ? "./?object=".$_GET["object"]."&action=".__FUNCTION__."&id=".gmdate("Y-m", $prev_month_time) : "",
			"next_month_link"		=> $need_next_link ? "./?object=".$_GET["object"]."&action=".__FUNCTION__."&id=".gmdate("Y-m", $next_1_month_time) : "",
			"cur_month"				=> $this->_show_month(gmdate("Y-m-01", $cur_month_time), $month_params),
			"cal_active"			=> intval((bool)$cal_settings["active"]),
			"cur_month_name"		=> _prepare_html(gmdate("F", $cur_month_time)),
			"view_link"				=> "./?object=".$_GET["object"]."&action=view&id=".$user_info["id"]."&page=".gmdate("Y-m", $cur_month_time),
			"start_year_box"		=> common()->select_box("start_year", $years, 0, false, 2, "", false),
			"start_month_box"		=> common()->select_box("start_month", $month, $cur_month, false, 2, "", false),
			"start_day_box"			=> common()->select_box("start_day", $days, gmdate("d", time()), false, 2, "", false),
			"start_time_box"		=> common()->select_box("start_time", $hours, 0, false, 2, "", false),
			"end_year_box"			=> common()->select_box("end_year", $years, 0, false, 2, "", false),
			"end_month_box"			=> common()->select_box("end_month", $month, $cur_month, false, 2, "", false),
			"end_day_box"			=> common()->select_box("end_day", $days, gmdate("d", time()), false, 2, "", false),
			"end_time_box"			=> common()->select_box("end_time", $hours, 0, false, 2, "", false),
			"status_box"			=> common()->select_box("status", $this->_date_statuses, 0, false, 2, "", false),
			"edit_defaults_link"	=> "./?object=".$_GET["object"]."&action=edit_defaults_settings",
			"apply_defaults_link"	=> "./?object=".$_GET["object"]."&action=apply_defaults_settings&id=".$cur_year."-".$cur_month,
			"quick_status_box"		=> $this->_box("status", 1),
			"cur_year_num"			=> $cur_year,
			"cur_month_num"			=> $cur_month,
			"next_day_num"			=> gmdate("d", $next_1_month_time - 3600),
		);
		return tpl()->parse("calendar/manage_main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Edit day contents
	function edit_day () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		$user_info = $this->_user_info;
		// Prepare date
		$cur_day_time = time();
		// Check input
		if (!empty($_GET["id"])) {
			list($cur_year, $cur_month, $cur_day) = explode("-", $_GET["id"]);
			$cur_year 	= intval($cur_year);
			$cur_month	= intval($cur_month);
			$cur_day	= intval($cur_day);
			// Check allowed time
			if ($cur_year >= $this->_min_year && $cur_year <= $this->_max_year && $cur_month >= 1 && $cur_month <= 12 && $cur_day >= 1 && $cur_day <= 31) {
				// Last check day number
				if ($cur_day <= gmdate("t", strtotime($cur_year."-".$cur_month."-01 GMT"))) {
					$cur_day_time = strtotime($cur_year."-".$cur_month."-".$cur_day." GMT");
				}
			}
			unset($_GET["page"]);
		}
		// Prepare numbers
		$cur_day	= gmdate("d", $cur_day_time);
		$cur_month	= gmdate("m", $cur_day_time);
		$cur_year	= gmdate("Y", $cur_day_time);
		// Get calendar settings
		$cal_settings = $this->_get_settings();
		$this->DATE_FORMAT_NUM = $cal_settings["date_format"];
		// Get day info
		$day_info = db()->query_fetch("SELECT * FROM `".db('calendar_dates')."` WHERE `user_id`=".intval($user_info["id"])." AND `date`=".intval($cur_day_time), false);
		// Save settings
		if (isset($_POST["save"])) {
			// Prepare hours
			$_tmp_hours = array();
			foreach ((array)$_POST["hour_status"] as $_hour_time => $_status) {
				$_hour_time = intval($_hour_time);
				$_status	= intval($_status);
				// Skip undefined statuses
				if (empty($_hour_time) || empty($_status) || !array_key_exists($_status, $this->_date_statuses)) {
					continue;
				}
				$_tmp_hours[$_hour_time] = $_status;
			}
			// Check for errors
			if (!common()->_error_exists()) {
				$sql = array(
					"user_id"	=> intval($this->USER_ID),
					"date"		=> intval($cur_day_time),
					"hours"		=> _es(!empty($_tmp_hours) ? serialize($_tmp_hours) : ""),
					"title"		=> _es($_POST["title"]),
					"desc"		=> _es($_POST["desc"]),
					"status"	=> !empty($_tmp_hours) ? (in_array(2, $_tmp_hours) ? 2 : 1) : intval($_POST["status"]),
				);
				if (!empty($day_info)) {
					db()->UPDATE("calendar_dates", $sql, "`id`=".intval($day_info["id"]));
				} else {
					db()->INSERT("calendar_dates", $sql);
				}
				// Return user back to the manage page
				return js_redirect("./?object=".$_GET["object"]."&action=manage&id=".gmdate("Y-m", $cur_day_time));
			}
		}
		// Mass saving
		if (isset($_POST["save2"])) {
			$start_time = intval($_POST["start_time"]);
			$end_time	= intval($_POST["end_time"]);
			if (!($end_time - $start_time)) {
				common()->_raise_error(t("Please select time"));
			}
			if (!common()->_error_exists()) {
				for ($i = $start_time; $i <= $end_time; $i++){
					if ($start_time < 0 || $end_time > 23) {
						continue;
					}
					$next_hours[$i * 3600]	= $_POST["status"];
					$desc[$i * 3600]		= $_POST["comments"];
				}
				$this->_save_hours_into_db($cur_day_time, $next_hours, $desc, $day_info);
				// Return user back
				return js_redirect("./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"]);
			}
		}
		// Extract hours info from db
		$hours_info		= !empty($day_info["hours"]) ? @unserialize($day_info["hours"]) : array();
		$comment_info	= !empty($day_info["desc"]) ? @unserialize($day_info["desc"]) : array();
		@ksort($hours_info);
		@ksort($comment_info);
		$cur_week_day_num = gmdate("w", $cur_day_time);
		// We start with 2006 year
		$base_day_time = strtotime("2006-01-01 00:00:00 GMT");
		// Iterate over selected hours			
		$status_items = array();
		for ($_hour = 0; $_hour <= 23; $_hour++) {
			$_cur_hour_secs		= $_hour * 3600;
			if (empty($hours_info[$_cur_hour_secs])) {
				continue;
			}
			$_start_hour_secs	= $_cur_hour_secs;
			$_end_hour_secs		= $_cur_hour_secs + 59 * 60;
			$_status			= $hours_info[$_cur_hour_secs];
			// Skip repeated values
			$_next_hour_secs = $_cur_hour_secs + 3600;
			if ($hours_info[$_next_hour_secs] == $hours_info[$_cur_hour_secs]) {
				if (!isset($_tmp_hour_secs)) {
					$_tmp_hour_secs = $_cur_hour_secs;
				}
				continue;
			}
			if (isset($_tmp_hour_secs)) {
				$_start_hour_secs = $_tmp_hour_secs;
				unset($_tmp_hour_secs);
			}
			// Prepare current item
			$status_items[$_hour] = array(
				"status_id"		=> $_status,
				"from"			=> gmdate("G:i", $base_day_time + $_start_hour_secs),
				"to"			=> gmdate("G:i", $base_day_time + $_end_hour_secs),
				"comment"		=> $comment_info[$_cur_hour_secs],
				"status"		=> $this->_date_statuses[$_status],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_event&id=".$cur_day_time."-".($_start_hour_secs / 3600)."-".($_cur_hour_secs / 3600),
			);
		}
		for ($i = 0; $i < 24; $i++){
			$i < 10 ? $hours_select["0".$i] = "0".$i : $hours_select[$i] = $i; 
		}
		// Prepare template
		$replace = array(
			"form_action"			=> "./?object=".$_GET["object"]."&action=".__FUNCTION__."&id=".gmdate("Y-m-d", $cur_day_time),
			"error_message"			=> _e(),
			"status_items"			=> $status_items ? $status_items : "",
			"title"					=> _prepare_html($day_info["title"]),
			"desc"					=> _prepare_html($day_info["desc"]),
			"status_box"			=> $this->_box("status", $day_info["status"]),
			"cur_date"				=> $this->_format_date($cur_day_time),
			"view_link"				=> "./?object=".$_GET["object"]."&action=view".($this->HIDE_TOTAL_ID ? "" : "&id=".$user_info["id"])."&page=".gmdate("Y-m", $cur_day_time),
			"back_link"				=> "./?object=".$_GET["object"]."&action=manage&id=".gmdate("Y-m", $cur_day_time),
			"start_time_box"		=> common()->select_box("start_time", $hours_select, 0, false, 2, "", false),
			"end_time_box"			=> common()->select_box("end_time", $hours_select, 23, false, 2, "", false),
			"status_box"			=> common()->select_box("status", $this->_date_statuses, 0, false, 2, "", false),
			"edit_defaults_link"	=> "./?object=".$_GET["object"]."&action=edit_defaults_settings",
			"apply_defaults_link"	=> "./?object=".$_GET["object"]."&action=apply_defaults_settings&id=".$cur_year."-".$cur_month."-".$cur_day,
			"quick_status_box"		=> $this->_box("status", 1),
			"quick_confirm"			=> $hours_info[$cur_week_day_num] ? 1 : 0,
			"date_format_num"		=> intval($this->DATE_FORMAT_NUM),
		);
		return tpl()->parse("calendar/edit_day_main", $replace);
	}

	//-----------------------------------------------------------------------------
	// General saving method
	function _save_hours_into_db($date, $hours, $desc, $old_day_info = array()) {
		if (empty($date)) {
			return false;
		}
		// Get info if not done yet
		if (empty($old_day_info)) {
			$old_day_info = db()->query_fetch("SELECT * FROM `".db('calendar_dates')."` WHERE `user_id`=".intval($this->USER_ID)." AND `date`=".intval($date));
		}
		// Merge hours and descriptions
		if (!empty($old_day_info["hours"])) {
			$old_hours = @unserialize($old_day_info["hours"]);
			$hours = my_array_merge((array)$old_hours, (array)$hours);
		}
		if (!empty($old_day_info["desc"])) {
			$old_desc = @unserialize($old_day_info["desc"]);
			$desc = my_array_merge((array)$old_desc, (array)$desc);
		}
		// Check availiability and fix hours
		foreach ((array)$hours as $a => $b) {
			// Fix long hours numbers
			if ($a > 86400) {
				unset($hours[$a]);
				unset($desc[$a]);
			}
			$b == 2 ? $available++	: "";			
			$b == 3 ? $travel++		: "";			
			$b == 1 ? $busy++		: "";
		}
		$busy		? $status = 1 : "";
		$travel		? $status = 3 : "";
		$available	? $status = 2 : "";
		// Prepare SQL
		$sql = array(
			"user_id"	=> intval($this->USER_ID),
			"date"		=> intval($date),
			"hours"		=> _es(!empty($hours) ? serialize($hours) : ""),
			"desc"		=> _es(!empty($desc) ? serialize($desc) : ""),
			"status" 	=> intval($status),
		);
		if (!empty($old_day_info)) {
			db()->UPDATE("calendar_dates", $sql, "`id`=".intval($old_day_info["id"]));
		} else {
			db()->INSERT("calendar_dates", $sql);
		}
	}
	
	//-----------------------------------------------------------------------------
	// Do delete selected event
	function delete_event() {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		list($day_time, $hour_start, $hour_end) = explode("-", $_GET["id"]);
		$day_time	= intval($day_time);
		$hour_start = intval($hour_start);
		$hour_end	= intval($hour_end);
		// Get calendar info for selected day
		$day_info = db()->query_fetch("SELECT * FROM `".db('calendar_dates')."` WHERE `user_id`=".intval($this->USER_ID)." AND `date`=".intval($day_time));
		if (!empty($day_info)) {
			// Clean selected interval
			for ($i = $hour_start; $i <= $hour_end; $i++) {
				if ($hour_start < 0 || $hour_end > 23) {
					continue;
				}
				$hour[$i * 3600] = 0;
				$desc[$i * 3600] = "";
			}
			if (!empty($day_info["hours"])) {
				$old_hours = @unserialize($day_info["hours"]);	
				$hours = my_array_merge((array)$old_hours, (array)$hour);
			}
			if (!empty($day_info["desc"])) {
				$old_desc = @unserialize($day_info["desc"]);
				$desc = my_array_merge((array)$old_desc, (array)$desc);
			}
			foreach ((array)$hours as $a => $b) {
				// Fix long hours numbers
				if ($a > 86400) {
					unset($hours[$a]);
					unset($desc[$a]);
				}
				$b == 2 ? $available++	: "";
				$b == 3 ? $travel++		: "";
				$b == 1 ? $busy++		: "";
			}
			$busy		? $status = 1	: "";
			$travel		? $status = 3	: "";
			$available	? $status = 2	: "";
			// Prepare SQL
			$sql = array(
				"status"	=> $status,
				"hours"		=> _es(!empty($hours) ? serialize($hours) : ""),	
				"desc"		=> _es(!empty($desc) ? serialize($desc) : ""),
			);
			db()->UPDATE("calendar_dates", $sql, "`id`=".intval($day_info["id"]));
		}
		return js_redirect("./?object=".$_GET["object"]."&action=edit_day&id=".gmdate("Y-m-d", $day_time));
	}
	
	//-----------------------------------------------------------------------------
	// Edit calendar default settings
	function edit_defaults_settings () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		// Get calendar settings
		$cal_settings = $this->_get_settings($this->_user_info["id"]);
		$this->DATE_FORMAT_NUM = $cal_settings["date_format"];
		// Save settings
		if (isset($_POST["save2"])) {
			if (empty($_POST["status"])) {
				common()->_raise_error(t("Status is required!"));
			}
			$start_time	= intval($_POST["start_time"]);
			$end_time	= intval($_POST["end_time"]);

			if (($end_time - $start_time) <= 0) {
				common()->_raise_error(t("Please select time!"));
			}
			// Prepare new week days settings
			$new_settings = array();
			for ($_week_day = 0; $_week_day <= 6; $_week_day++) {
				if (!isset($_POST["week_day_".$_week_day])) {
					continue;
				}
				for ($i = $start_time; $i < $end_time; $i++) {
					$next_hours[$i * 3600]	= $_POST["status"];
					$desc[$i * 3600]		= $_POST["comments"];
				}	
				$new_settings[$_week_day] = array(
					"hours"	=> $next_hours,
					"desc"	=> $desc
				);
			}
			if (empty($new_settings)) {
				common()->_raise_error(t("Please select at least one week day!"));
			}
			if (!common()->_error_exists()) {
				// Merge with old ones
				if (!empty($cal_settings["default"])) {
					$new_settings = my_array_merge((array)@unserialize($cal_settings["default"]), (array)$new_settings);
				}
				@ksort($new_settings);

				db()->UPDATE("calendar_settings", array(
					"default"	=> _es(serialize($new_settings)),
				), "`user_id`=".$this->USER_ID);

				return js_redirect("./?object=".$_GET["object"]."&action=".__FUNCTION__);
			}
		}
		// We start with 2006 year
		$base_day_time = strtotime("2006-01-01 00:00:00 GMT");
		// Prepare selected data for display
		$week_days = "";
		// Iterate over week days seleted default settings
		foreach ((array)@unserialize($cal_settings["default"]) as $_day_num => $value) {
			if (empty($value)) {
				continue;
			}
			$hours_info		= $value["hours"];
			@ksort($hours_info);
			$comment_info	= $value["desc"];
			@ksort($comment_info);
			// Iterate over selected hours			
			$status_items = array();
			for ($_hour = 0; $_hour <= 23; $_hour++) {
				$_cur_hour_secs		= $_hour * 3600;
				if (empty($hours_info[$_cur_hour_secs])) {
					continue;
				}
				$_start_hour_secs	= $_cur_hour_secs;
				$_end_hour_secs		= $_cur_hour_secs + 59 * 60;
				$_status			= $hours_info[$_cur_hour_secs];
				// Skip repeated values
				$_next_hour_secs = $_cur_hour_secs + 3600;
				if ($hours_info[$_next_hour_secs] == $hours_info[$_cur_hour_secs]) {
					if (!isset($_tmp_hour_secs)) {
						$_tmp_hour_secs = $_cur_hour_secs;
					}
					continue;
				}
				if (isset($_tmp_hour_secs)) {
					$_start_hour_secs = $_tmp_hour_secs;
					unset($_tmp_hour_secs);
				}
				// Prepare current item
				$status_items[$_hour] = array(
					"status_id"		=> $_status,
					"from"			=> gmdate("G:i", $base_day_time + $_start_hour_secs),
					"to"			=> gmdate("G:i", $base_day_time + $_end_hour_secs),
					"comment"		=> $comment_info[$_cur_hour_secs],
					"status"		=> $this->_date_statuses[$_status],
					"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_default&id=".$_day_num."-".$_start_hour_secs."-".$_cur_hour_secs,
				);
			}
			if (empty($status_items)) {
				continue;
			}
			// Prepare template
			$replace_week_day = array(			
				"status_items"	=> $status_items,
				"week_day_name"	=> $this->_get_week_day_name($_day_num),
			);
			$week_days .= tpl()->parse("calendar/defaults_week_day", $replace_week_day);
		}
		// Prepare hours for select
		for ($i = 0; $i <= 24; $i++) {
			$hours_select[$i] = ($i < 10 ? "0" : "").$i;
		}
		// DO NOT REMOVE! Needed for preparing $this->_week_day_names
		$this->_get_week_day_name();
		// Prepare template
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".__FUNCTION__,
			"error_message"		=> _e(),
			"selected_week_days"=> $week_days,
			"title"				=> _prepare_html($day_info["title"]),
			"desc"				=> _prepare_html($day_info["desc"]),
			"status_box"		=> $this->_box("status", $day_info["status"]),
			"hours"				=> $hours,
			"cur_date"			=> $this->_format_date($cur_day_time),
			"view_link"			=> "./?object=".$_GET["object"]."&action=view".($this->HIDE_TOTAL_ID ? "" : "&id=".$this->_user_info["id"])."&page=".gmdate("Y-m", $cur_day_time),
			"back_link"			=> "./?object=".$_GET["object"]."&action=manage&id=".gmdate("Y-m", $cur_day_time),
			"start_time_box"	=> common()->select_box("start_time", $hours_select, 0, false, 2, "", false),
			"end_time_box"		=> common()->select_box("end_time",	$hours_select, 24, false, 2, "", false),
			"status_box"		=> common()->select_box("status",		$this->_date_statuses, 0, false, 2, "", false),
			"week_day_box"		=> common()->multi_check_box("week_day",	$this->_week_day_names, "", 1),
			"clean_link"		=> "./?object=".$_GET["object"]."&action=clean_default_settings",
		);
		return tpl()->parse("calendar/defaults_settings", $replace);
	}
	
	//-----------------------------------------------------------------------------
	// Delete default item
	function delete_default () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		// Get calendar settings
		$cal_settings = $this->_get_settings($this->_user_info["id"]);
		$this->DATE_FORMAT_NUM = $cal_settings["date_format"];
		// Get params to delete
		list($week_day, $start_hour_secs, $end_hour_secs) = explode("-", $_GET["id"]);
		$week_day = intval($week_day);
		$start_hour_secs = intval($start_hour_secs);
		$end_hour_secs = intval($end_hour_secs);
		// Check consistency
		if (is_numeric($week_day) && is_numeric($end_hour_secs) && is_numeric($end_hour_secs) && !empty($cal_settings["default"])) {
			$new_settings = @unserialize($cal_settings["default"]);
			for ($_hour_secs = $start_hour_secs; $_hour_secs <= $end_hour_secs; $_hour_secs += 3600) {
				unset($new_settings[$week_day]["hours"][$_hour_secs]);
				unset($new_settings[$week_day]["desc"][$_hour_secs]);
			}
			@ksort($new_settings);
		}
		// Do delete
		if (!empty($new_settings)) {
			db()->UPDATE("calendar_settings", array(
				"default"	=> _es(@serialize($new_settings)),
			), "`user_id`=".$this->USER_ID);
		}
		return js_redirect("./?object=".$_GET["object"]."&action=edit_defaults_settings");
	}
	
	//-----------------------------------------------------------------------------
	// Clean default week days settings
	function clean_default_settings () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		db()->UPDATE("calendar_settings", array("default"	=> ""), "`user_id`=".$this->USER_ID);
		return js_redirect("./?object=".$_GET["object"]."&action=edit_defaults_settings");
	}
	
	//-----------------------------------------------------------------------------
	// Apply default settings to the selected month
	function apply_defaults_settings () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		// Get calendar settings
		$cal_settings = $this->_get_settings($this->_user_info["id"]);
		$this->DATE_FORMAT_NUM = $cal_settings["date_format"];
		// Prepare params
		list($year, $month, $day) = explode("-", $_GET["id"]);
		$year	= intval($year);
		$month	= intval($month);
		$day	= intval($day);
		$IS_SINGLE_DAY = !empty($day) ? 1 : 0;
		if (!empty($year) && !empty($month)) {
			$cur_time = strtotime($year."-".$month."-".($IS_SINGLE_DAY ? $day : "01")." GMT");
		}
		// Prepare default data
		$defaults = @unserialize($cal_settings["default"]);
		// Go!
		if ($cur_time > 0 && !empty($defaults) && !empty($cur_time)) {
			// Prepare days to process
			$days_to_process = array();
			if ($IS_SINGLE_DAY) {
				$days_to_process[$cur_time] = $cur_time;
			} else {
				$_days_in_month = gmdate("t", $cur_time);
				for ($i = 1; $i <= $_days_in_month; $i++) {
					$_time = $cur_time + ($i - 1) * 86400;
					$days_to_process[$_time] = $_time;
				}
			}
			// Process selected days
			foreach ((array)$days_to_process as $_time) {
				$_week_day = gmdate("w", $_time);
				$def_hours		= $defaults[$_week_day]["hours"];
				$def_comments	= $defaults[$_week_day]["desc"];
				$this->_save_hours_into_db($_time + $_hour_start, $def_hours, $def_comments);
			}
		}
		if ($IS_SINGLE_DAY) {
			return js_redirect("./?object=".$_GET["object"]."&action=edit_day&id=".$_GET["id"]);
		} else {
			return js_redirect("./?object=".$_GET["object"]."&action=manage&id=".$_GET["id"]);
		}
	}

	//-----------------------------------------------------------------------------
	// Display one month contents
	function _show_month ($start_date = 0, $params = array()) {

		$_tpl_prefix = ""; 
		if ($params["for_widgets"]) {
			$_tpl_prefix = "widget_"; 				
		}

		$start_date		= !empty($start_date) ? $start_date : gmdate("Y-m-01");
		$start_time		= strtotime($start_date." GMT");
		$start_week		= gmdate("W", $start_time);
		$start_month	= gmdate("m", $start_time);
		$start_year		= gmdate("Y", $start_time);
		$month_first_week_day = gmdate("w", $start_time);
		// Prepare weeks
		for ($_week = 0; $_week <= 5; $_week++) {
			$_cur_week_time = $start_time + $_week * 7 * 86400;
			$start_week_day = gmdate("w", $_cur_week_time);
			// Display days from prev month (left pad the week)
			if ($_week == 0 && $start_week_day > 0) {
				$_cur_week_time -= $start_week_day * 86400;
			}
			// Check if we do not need to display this week (no days from given month)
			if ($_week == 5 && gmdate("m", $_cur_week_time - $month_first_week_day * 86400) != $start_month) {
				break;
			}
			$week_days = "";
			for ($_day = 0; $_day <= 6; $_day++) {
				$day_time = $_cur_week_time + $_day * 86400 - ($_week ? $month_first_week_day * 86400 : 0);
				// Prepare links
				$day_link		= "./?object=".$_GET["object"]."&action=day".($this->HIDE_TOTAL_ID ? "" : "&id=".$params["user_id"])."&page=".gmdate("Y-m-d", $day_time);
				$onclick_link	= $params["onclick_link"];
				if ($this->_marked_days[$day_time] && !$onclick_link) {
					$onclick_link = $day_link;
				}
				// Prepare template
				$replace2 = array(
					"from_cur_month"	=> intval(gmdate("m", $day_time) == $start_month),
					"day_num"			=> intval(gmdate("d", $day_time)),
					"month_name"		=> _prepare_html(gmdate("M", $day_time)),
					"month_num"			=> intval(gmdate("m", $day_time)),
					"text"				=> _prepare_html($this->_marked_days[$day_time]["title"]),
					"day_selected"		=> isset($this->_marked_days[$day_time]) ? 1 : 0,
					"day_link"			=> $day_link,
					"onclick_link"		=> !empty($onclick_link) ? str_replace("{cur_date}", gmdate("Y-m-d", $day_time), $onclick_link) : "",
					"is_cur_day"		=> !empty($params["select_cur_day"]) && gmdate("Y-m-d", $day_time) == gmdate("Y-m-d") ? 1 : 0,
					"full_date"			=> !empty($params["show_full_date"]) ? $this->_format_date($day_time) : "",
					"status"			=> $this->_date_statuses[$this->_marked_days[$day_time]["status"]],
					"status_id"			=> $this->_marked_days[$day_time]["status"],
					"date_format_num"	=> intval($this->DATE_FORMAT_NUM),
				);
				$week_days .= tpl()->parse("calendar/".$_tpl_prefix."month_day_item", $replace2);
			}
			$weeks[$_week] = array(
				"days"	=> $week_days,
			);
		}
		// Prepare template
		$replace = array(
			"weeks"				=> $weeks,
			"month_name"		=> _prepare_html(gmdate("F", $start_time)),
			"month_num"			=> intval($start_month),
			"year"				=> intval(gmdate("Y", $start_time)),
			"date_format_num"	=> intval($this->DATE_FORMAT_NUM),
		);
		return tpl()->parse("calendar/".$_tpl_prefix."month_main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Get calendar settings
	function _get_settings ($user_id = 0) {
		if (empty($user_id) && !empty($this->USER_ID)) {
			$user_id = $this->USER_ID;
		}
		if (empty($user_id)) {
			return false;
		}
		// Get data from db
		$cal_settings = db()->query_fetch(
			"SELECT * FROM `".db('calendar_settings')."` WHERE `user_id`=".intval($user_id)
		);
		// Create default calendar settings if not done yet
		if (empty($cal_settings)) {
			$cal_settings = array(
				"user_id"		=> $user_id,
				"title"			=> "",
				"desc"			=> "",
				"default"		=> "",
				"active"		=> 1,
				"date_format"	=> 0,
			);
			db()->INSERT("calendar_settings", $cal_settings);
		}
		return $cal_settings;
	}

	//-----------------------------------------------------------------------------
	// Get other month time
	function _get_other_month_time ($cur_month_time = 0, $diff = 0) {
		if (empty($cur_month_time) || empty($diff)) {
			return $cur_month_time;
		}
		// Prepare numbers
		$cur_month	= gmdate("m", $cur_month_time);
		$cur_year	= gmdate("Y", $cur_month_time);

		$new_year_num	= $cur_year;
		$new_month_num	= $cur_month + $diff;

		if ($diff < 0) {
			if ($new_month_num < 1) {
				$new_year_num--;
				$new_month_num	= $new_month_num + 12;
			}
		} elseif ($diff > 0) {
			if ($new_month_num > 12) {
				$new_year_num++;
				$new_month_num	= $new_month_num - 12;
			}
		}
		return strtotime($new_year_num."-".$new_month_num."-01 GMT");
	}

	//-----------------------------------------------------------------------------
	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	//-----------------------------------------------------------------------------
	// 
	function _get_week_day_name($num = 0) {
		// Cache it
		if (!isset($this->_week_day_names)) {
			for ($i = 0; $i <= 6; $i++) {
				// Do not touch! 
				// (In 2007 year first month starts from monday) 
				// (in 2006 from sunday)
				$this->_week_day_names[$i] = gmdate("l", strtotime("2006-01-0".($i + 1))." GMT");
			}
		}
		return $this->_week_day_names[$num];
	}

	/**
	* Prepare date for output
	*/
	function _format_date ($date = 0, $type = "") {
		// Euro statndard
		if ($this->DATE_FORMAT_NUM == 1) {
			$format = "M d, Y";
		// US statndard
		} else {
			$format = "d M, Y";
		}
		return gmdate($format, $date);
	}

	/**
	* This method called on settings update to synchronize with site modules
	*/
	function _callback_on_update ($data = array()) {
		if (empty($data)) {
			return false;
		}
		if (!is_object($MODULES_OBJ)) {
			$MODULES_OBJ = main()->init_class("site_modules", "modules/");
		}
		$MODULES_OBJ->_modules_record_exists = true;
		foreach ((array)$MODULES_OBJ->_modules_array as $k => $mod_settings){
			if ($mod_settings["name"] == "calendar") {
				$MODULES_OBJ->_modules_array[$k]["page_header"] = $data["page_header"];
			}
		}
		$MODULES_OBJ->_site_modules_to_db($MODULES_OBJ->_modules_array); 
		return true;
	}

	/**
	* For widget
	*/
	function _widget_dates ($params = array()) {
		if ($params["describe"]) {
			return array("allow_cache" => 0);
		}
		if (!$this->USER_ID) {
			return "";
		}
		return $this->view($this->USER_ID, array("for_widgets" => 1));
	}

	/**
	* Calendar settings
	*/
	function settings () {

		// Save settings
		if (isset($_POST["save"])) {	
			// Check for errors
			if (!common()->_error_exists()) {
				$sql = array(
					"title"			=> _es($_POST["title"]),
					"desc"			=> _es($_POST["desc"]),
					"active"		=> isset($_POST["disable"]) ? 0 : 1,
					"date_format"	=> intval(isset($this->_date_formats[$_POST["date_format"]]) ? $_POST["date_format"] : $this->DATE_FORMAT_NUM),
				);
				db()->UPDATE("calendar_settings", $sql, "`user_id`=".intval($this->USER_ID));

				// Synchronize blog title with site menu
				$this->_callback_on_update(array("page_header" => $_POST["title"]));
			}
			return js_redirect("./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"]);
		}

		$cal_settings = $this->_get_settings();
		$this->DATE_FORMAT_NUM = $cal_settings["date_format"];

		// Prepare template
		$replace = array(
			"cal_title"				=> _prepare_html($cal_settings["title"]),
			"cal_desc"				=> _prepare_html($cal_settings["desc"]),
			"date_format_box"		=> $this->_box("date_format", $this->DATE_FORMAT_NUM),
			"date_format_num"		=> intval($this->DATE_FORMAT_NUM),
			"back_url"				=> "./?object=calendar&action=manage",
			"form_action"			=> "./?object=calendar&action=settings",
		);
		return tpl()->parse("calendar/settings", $replace);
	}	
}
