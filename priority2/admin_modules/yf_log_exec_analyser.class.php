<?php

/**
* Execution logs analyser
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_log_exec_analyser {

	/** @var string @conf_skip Session array name where filter vars are stored */
	var $_filter_name		= "log_exec_analyser_filter";
	/** @var string @conf_skip */
	var $_DATA_ARRAY_NAME	= "log_exec_data";
	/** @var bool Filter on/off */
	var $USE_FILTER		= true;
	/** @var string Define image type */
	var $_image_type	= "png";
	/** @var int Define image dimensions */
	var $_image_width	= 600;
	/** @var int */
	var $_image_height	= 400;
	/** @var array @conf_skip Define colors (for true color, first byte => alpha channel (0x00...0x7F)) */
	var $_img_colors = array(
		"white"		=> 0x00FFFFFF,
		"black"		=> 0x00000000,
		"grey_1"	=> 0x00CACACA,
		"grey_2"	=> 0x00888888,
		"grey_3"	=> 0x00565656,
		"dark_blue"	=> 0x000080C0,
		"light_blue"=> 0x006CCFFF,
	);
	/** @var array Define font sizes (in pixels) */
	var $_font_widths = array(
		1	=> 5,
		2	=> 6,
	);
	/** @var array */
	var $_font_heights = array(
		1	=> 8,
		2	=> 13,
	);
	/** @var array @conf_skip Statistics date periods */
	var $_stats_periods = array(
		"day"	=> "Daily",
		"month"	=> "Monthly",
		"year"	=> "Yearly",
	);
	/** @var int */
	var $HOURLY_PER_PAGE	= 50;
	/** @var int */
	var $UA_PER_PAGE		= 50;
	/** @var int */
	var $REFERERS_PER_PAGE	= 50;
	/** @var int */
	var $MAX_EXEC_PER_PAGE	= 50;
	/** @var string Folder where archived stats are stored */
	var $STATS_ARCHIVE_DIR = "__logs/exec_stats_archive/";

	/**
	* Constructor (PHP 4.x)
	*/
	function yf_log_exec_analyser () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*/
	function __construct () {
		$this->_analyser_image_path = "./?object=".$_GET["object"]."&action=analyser_image";
		$this->STATS_ARCHIVE_DIR = INCLUDE_PATH. $this->STATS_ARCHIVE_DIR;
	}

	/**
	* Default method
	*/
	function show () {
		$replace = array(
			"image_graphs_link"		=> "./?object=".$_GET["object"]."&action=display_image_graphs",
			"user_agents_link"		=> "./?object=".$_GET["object"]."&action=user_agents_stats",
			"referers_link"			=> "./?object=".$_GET["object"]."&action=referers_stats",
			"max_exec_link"			=> "./?object=".$_GET["object"]."&action=max_exec_stats",
			"exec_log_link"			=> "./?object=db_parser&table=sys_log_exec",
			"hourly_latest_link"	=> "./?object=".$_GET["object"]."&action=hourly_latest_stats",
			"hourly_archive_link"	=> "./?object=".$_GET["object"]."&action=hourly_archive_stats",
			"move_to_archive_action"=> "./?object=".$_GET["object"]."&action=move_to_archive",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Move old records from raw exec log table into hourly archive table
	*/
	function move_to_archive () {
		$PRUNE_DAYS = intval(isset($_POST["days"]) ? $_POST["days"] : 7);
		// Generate main SQL for transfer
		$sql = "REPLACE DELAYED INTO `".db('log_hourly_exec')."` 
					(`start_date`,`avg_exec_time`,`hits`,`hosts`,`traffic`) 
				SELECT 
					UNIX_TIMESTAMP(FROM_UNIXTIME(`date`, '%Y-%m-%d %H')) AS `start_hour`, 
					ROUND(SUM(`exec_time`) / count(`id`) , 3) AS `exec_time` , 
					COUNT(`id`) AS `hits`, 
					COUNT(DISTINCT (`ip`)) AS `hosts`,
					SUM(`page_size`) AS `traffic` 
				FROM `".db('log_exec')."` 
				WHERE `date` <= ".intval(time() - $PRUNE_DAYS * 86400)."
				GROUP BY `start_hour` ";
		db()->query($sql);
		// Count number of created records
		$num_new_records = db()->affected_rows();
		// Save to file old info about user agents, referers, IPs
		if ($num_new_records > 0) {
			// Check if folder exists and create it elsewhere
			if (!file_exists($this->STATS_ARCHIVE_DIR)) {
				$DIR_OBJ = main()->init_class("dir", "classes/");
				$DIR_OBJ->mkdir_m($this->STATS_ARCHIVE_DIR, 0777);
			}
			// User agents
			$sql = "SELECT COUNT( `id` ) AS `hits`, `user_agent` 
					FROM `".db('log_exec')."` 
					WHERE `date` <= ".intval(time() - $PRUNE_DAYS * 86400)."
					GROUP BY `user_agent` 
					ORDER BY `hits` DESC ";
			// Do save CSV log
			$log_name = "ua";
			$file_name = $this->STATS_ARCHIVE_DIR.date("Y_m_d_H_i")."__".$log_name.".csv";
			if ($fp = fopen($file_name, 'w')) {
				$Q = db()->query($sql);
				while ($A = db()->fetch_assoc($Q)) {
					fputcsv($fp, $A);
				}
				@fclose($fp);
			}
			// Referers
			$sql = "SELECT COUNT( `id` ) AS `hits`, `referer` 
					FROM `".db('log_exec')."` 
					WHERE `date` <= ".intval(time() - $PRUNE_DAYS * 86400)."
					GROUP BY `referer` 
					ORDER BY `hits` DESC ";
			// Do save CSV log
			$log_name = "ref";
			$file_name = $this->STATS_ARCHIVE_DIR.date("Y_m_d_H_i")."__".$log_name.".csv";
			if ($fp = fopen($file_name, 'w')) {
				$Q = db()->query($sql);
				while ($A = db()->fetch_assoc($Q)) {
					fputcsv($fp, $A);
				}
				@fclose($fp);
			}
			// Max exec time
			$sql = "SELECT ROUND(AVG(`exec_time`), 3) AS `exec_avg`, 
						ROUND(MAX(`exec_time`), 3) AS `exec_max`, 
						`query_string` 
					FROM `".db('log_exec')."` 
					WHERE `date` <= ".intval(time() - $PRUNE_DAYS * 86400)."
					GROUP BY `query_string` 
					ORDER BY `exec_max` DESC ";
			// Do save CSV log
			$log_name = "max_exec";
			$file_name = $this->STATS_ARCHIVE_DIR.date("Y_m_d_H_i")."__".$log_name.".csv";
			if ($fp = fopen($file_name, 'w')) {
				$Q = db()->query($sql);
				while ($A = db()->fetch_assoc($Q)) {
					fputcsv($fp, $A);
				}
				@fclose($fp);
			}
			// Unique IP addresses
			$sql = "SELECT COUNT(`ip`) AS `hits`, `ip` 
					FROM `".db('log_exec')."` 
					WHERE `date` <= ".intval(time() - $PRUNE_DAYS * 86400)."
					GROUP BY `ip` 
					ORDER BY `hits` DESC ";
			// Do save CSV log
			$log_name = "ip";
			$file_name = $this->STATS_ARCHIVE_DIR.date("Y_m_d_H_i")."__".$log_name.".csv";
			if ($fp = fopen($file_name, 'w')) {
				$Q = db()->query($sql);
				while ($A = db()->fetch_assoc($Q)) {
					fputcsv($fp, $A);
				}
				@fclose($fp);
			}
		}
		// Do delete old records from the raw exec table
		db()->query("DELETE FROM `".db('log_exec')."` WHERE `date` <= ".intval(time() - $PRUNE_DAYS * 86400));
		// Count number of deleted records
		$num_old_records = db()->affected_rows();
		// Process template		
		$replace = array(
			"main_link"			=> "./?object=".$_GET["object"]."&action=show",
			"num_new_records"	=> intval($num_new_records),
			"num_old_records"	=> intval($num_old_records),
			"prune_days"		=> intval($PRUNE_DAYS),
		);
		return tpl()->parse($_GET["object"]."/moved_result", $replace);
	}

	/**
	* Hourly archive stats (from hourly archive table)
	*/
	function hourly_archive_stats () {
		$sql = "SELECT * FROM `".db('log_hourly_exec')."` ";
		$order_by_sql = " ORDER BY `start_date` DESC ";
		$path = "./?object=".$_GET["object"]."&action=".$_GET["action"];
		list($add_sql, $pages, $total) = common()->divide_pages($sql, $path, null, $this->HOURLY_PER_PAGE);
		// Process users
		$Q = db()->query($sql. $order_by_sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$cur_stamp = $A["start_date"];
			$replace2 = array(
				"bg_class"	=> $i++ % 2 ? "bg1" : "bg2",
				"date"		=> date("d/m/Y H", $cur_stamp).":00...".date("H", $cur_stamp + 3600).":00",
				"exec_time"	=> $A["avg_exec_time"],
				"hits"		=> $A["hits"],
				"hosts"		=> $A["hosts"],
				"traffic"	=> $A["traffic"],
			);
			$items .= tpl()->parse($_GET["object"]."/hourly_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"		=> $items,
			"total"		=> $total,
			"pages"		=> $pages,
			"main_link"	=> "./?object=".$_GET["object"]."&action=show",
			"is_archive"=> 1,
		);
		return tpl()->parse($_GET["object"]."/hourly_main", $replace);
	}

	/**
	* Hourly latest stats (from raw log exec table)
	*/
	function hourly_latest_stats () {
		$sql = "SELECT ROUND( SUM( `exec_time` ) / count( `id` ) , 3 ) AS `exec_time` , 
					FROM_UNIXTIME( `date` , '%Y-%m-%d %H' ) AS `hour` , 
					COUNT( `id` ) AS `hits`, 
					COUNT(DISTINCT (`ip`)) AS `hosts`,
					SUM( `page_size` ) AS `traffic` 
				FROM `".db('log_exec')."`
				GROUP BY `hour`";
		$order_by_sql = " ORDER BY `hour` DESC ";
		$path = "./?object=".$_GET["object"]."&action=".$_GET["action"];
		list($add_sql, $pages, $total) = common()->divide_pages($sql, $path, null, $this->HOURLY_PER_PAGE);
		// Process users
		$Q = db()->query($sql. $order_by_sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$cur_stamp = strtotime($A["hour"].":00:00");
			$replace2 = array(
				"bg_class"	=> $i++ % 2 ? "bg1" : "bg2",
				"date"		=> date("d/m/Y H", $cur_stamp).":00...".date("H", $cur_stamp + 3600).":00",
				"exec_time"	=> $A["exec_time"],
				"hits"		=> $A["hits"],
				"hosts"		=> $A["hosts"],
				"traffic"	=> $A["traffic"],
			);
			$items .= tpl()->parse($_GET["object"]."/hourly_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"		=> $items,
			"total"		=> $total,
			"pages"		=> $pages,
			"main_link"	=> "./?object=".$_GET["object"]."&action=show",
			"is_archive"=> 0,
		);
		return tpl()->parse($_GET["object"]."/hourly_main", $replace);
	}

	/**
	* User Agents stats
	*/
	function user_agents_stats () {
		$sql = "SELECT COUNT( `id` ) AS `hits`, `user_agent` 
				FROM `".db('log_exec')."` 
				GROUP BY `user_agent`";
		$order_by_sql = " ORDER BY `hits` DESC ";
		$path = "./?object=".$_GET["object"]."&action=".$_GET["action"];
		list($add_sql, $pages, $total) = common()->divide_pages($sql, $path, null, $this->UA_PER_PAGE);
		// Process users
		$Q = db()->query($sql. $order_by_sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"bg_class"	=> $i++ % 2 ? "bg1" : "bg2",
				"hits"		=> $A["hits"],
				"text"		=> _prepare_html($A["user_agent"]),
			);
			$items .= tpl()->parse($_GET["object"]."/ua_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"		=> $items,
			"total"		=> $total,
			"pages"		=> $pages,
			"main_link"	=> "./?object=".$_GET["object"]."&action=show",
		);
		return tpl()->parse($_GET["object"]."/ua_main", $replace);
	}

	/**
	* Referers stats
	*/
	function referers_stats () {
		$sql = "SELECT COUNT( `id` ) AS `hits`, `referer` 
				FROM `".db('log_exec')."` 
				GROUP BY `referer`";
		$order_by_sql = " ORDER BY `hits` DESC ";
		$path = "./?object=".$_GET["object"]."&action=".$_GET["action"];
		list($add_sql, $pages, $total) = common()->divide_pages($sql, $path, null, $this->REFERERS_PER_PAGE);
		// Process users
		$Q = db()->query($sql. $order_by_sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"bg_class"	=> $i++ % 2 ? "bg1" : "bg2",
				"hits"		=> $A["hits"],
				"text"		=> _prepare_html($A["referer"]),
			);
			$items .= tpl()->parse($_GET["object"]."/referers_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"		=> $items,
			"total"		=> $total,
			"pages"		=> $pages,
			"main_link"	=> "./?object=".$_GET["object"]."&action=show",
		);
		return tpl()->parse($_GET["object"]."/referers_main", $replace);
	}

	/**
	* Max Execution time pages
	*/
	function max_exec_stats () {
		$sql = "SELECT ROUND(AVG(`exec_time`), 3) AS `exec_avg`, 
					ROUND(MAX(`exec_time`), 3) AS `exec_max`, 
					`query_string` 
				FROM `".db('log_exec')."` 
				GROUP BY `query_string`";
		$order_by_sql = " ORDER BY `exec_max` DESC ";
		$path = "./?object=".$_GET["object"]."&action=".$_GET["action"];
		list($add_sql, $pages, $total) = common()->divide_pages($sql, $path, null, $this->MAX_EXEC_PER_PAGE);
		// Process users
		$Q = db()->query($sql. $order_by_sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"bg_class"	=> $i++ % 2 ? "bg1" : "bg2",
				"exec_avg"	=> $A["exec_avg"],
				"exec_max"	=> $A["exec_max"],
				"page_addr"	=> WEB_PATH."?".$A["query_string"],
			);
			$items .= tpl()->parse($_GET["object"]."/max_exec_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"		=> $items,
			"total"		=> $total,
			"pages"		=> $pages,
			"main_link"	=> "./?object=".$_GET["object"]."&action=show",
		);
		return tpl()->parse($_GET["object"]."/max_exec_main", $replace);
	}

	/**
	* Display image-based stats
	*/
	function display_image_graphs () {
		$path = "./?object=".$_GET["object"]."&action=".$_GET["action"];
		// Default date
		if (!$_GET['date']) $_GET['date'] = date("Y-m-d");
		// Default stats type
		if (!$_GET['t']) $_GET['t'] = "visitors";
		// Default period type
		if (!$_GET['type']) $_GET['type'] = "now";
		// Current date
		list($year, $month, $day) = explode("-", $_GET['date']);
		// Allowed years
		if ($year < 1971) $year = 1971;
		if ($year > 2037) $year = 2037;
// TODO: make boxes more flexible and template-based
		for ($i = ($year - 5); $i < ($year + 6); $i++) {
			$years_box .= "<a href=\"".$path."&date=".$i."-".$month."-".$day."&type=year&t=".$_GET['t']."\">".(($year == $i)?"<b>":null).$i.(($year == $i)?"</b>":null)."</a>&nbsp;\r\n";
		}
		for ($i = 1; $i <= 12; $i++) {
			$months_box .= "<a href=\"".$path."&date=".$year."-".$i."-".$day."&type=month&t=".$_GET['t']."\">".((($month == $i)&&($_GET['type']!='year'))?"<b>":null).date("F", strtotime("1990-".$i."-01")).(($month == $i)?"</b>":null)."</a>&nbsp;\r\n";
		}
		$count_days = (mktime(0,0,0,$month+1,1,$year)-mktime(0,0,0,$month,1,$year))/(24*60*60);
		for ($i = 1; $i <= $count_days; $i++) {
			$days_box .= "<a href=\"".$path."&date=".$year."-".$month."-".$i."&type=day&t=".$_GET['t']."\">".((($day == $i)&&(($_GET['type']=='day')||($_GET['type']=='now')))?"<b>":null).$i.(($day == $i)?"</b>":null)."</a>&nbsp;\r\n";
		}
/*
		$body .= "<a href=\"".$path."&date=".$_GET['date']."&type=day&t=visitors\">".(($_GET['t'] == 'visitors')?"<b>".t('visitors')."</b>":t('visitors'))."</a>&nbsp;\r\n";
//		$body .= "<a href=\"".$path."&date=".$_GET['date']."&type=day&t=os\">".(($_GET['t'] == 'os')?"<b>".t('os')."</b>":t('os'))."</a>&nbsp;\r\n";
//		$body .= "<a href=\"".$path."&date=".$_GET['date']."&type=day&t=browser\">".(($_GET['t'] == 'browser')?"<b>".t('browser')."</b>":t('browser'))."</a>&nbsp;\r\n";
*/
// TODO: Connect os and browser stats
		$date_to_show	= $_GET['type'] == "now" || !$_GET['type'] || !$_GET['date'] ? date("Y-m-d") : $_GET["date"];
		$stats_type		= isset($this->_stats_periods[$_GET['type']]) ? $_GET['type'] : "day";
		// Switch between stats grabbers
		if ($_GET['t'] == 'visitors') {
			$this->_get_visitors_stats($date_to_show, $stats_type);
		} elseif ($_GET['t'] == 'os') {
			if (($_GET['type'] == "now") || (!$_GET['type'])) {
				$this->_get_day_os_stat(date("Y-m-d"));
			}
			if ($_GET['type'] == "day") {
				$this->_get_day_os_stat($_GET['date']);
			} elseif ($_GET['type'] == "month"){
				$this->_get_month_os_stat($_GET['date']);
			} elseif ($_GET['type'] == "year") {
				$this->_get_year_os_stat($_GET['date']);
			}
		} elseif ($_GET['t'] == 'browser') {
			if (($_GET['type'] == "now") || (!$_GET['type'])) {
				$this->_get_day_browser_stat(date("Y-m-d"));
			}
			if ($_GET['type'] == "day"){
				$this->_get_day_browser_stat($_GET['date']);
			} elseif ($_GET['type'] == "month") {
				$this->_get_month_browser_stat($_GET['date']);
			} elseif ($_GET['type'] == "year") {
				$this->_get_year_browser_stat($_GET['date']);
			}
		}
		// Prepare template
		$replace = array(
			"years_box"	=> $years_box,
			"months_box"=> $months_box,
			"days_box"	=> $days_box,
			"img_src"	=> $this->_analyser_image_path,
			"img_title"	=> _prepare_html($_SESSION[$this->_DATA_ARRAY_NAME]["title"]),
			"main_link"	=> "./?object=".$_GET["object"]."&action=show",
		);
		return tpl()->parse($_GET["object"]."/image_main", $replace);
	}

	/**
	* Display image graph
	*/
	function analyser_image () {
		main()->NO_GRAPHICS = true;
		// Get input data
		$DATA = &$_SESSION[$this->_DATA_ARRAY_NAME];
		$rows[0]			= $DATA['d0'];	// i
		$rows[1]			= $DATA['d1'];	// h
		$columns			= $DATA['x'];
		$head_blocks_names	= $DATA['head'];
		$head_blocks_values	= $DATA['hsum'];
		// Check input arrays
		if (sizeof($rows[0]) != sizeof($rows[1]) && sizeof($rows[1]) != sizeof($columns)) {
			trigger_error("LOG_ANALYSER: Wrong input data given", E_USER_WARNING);
			return false;
		}
		// Image texts
		$this->_image_header_text	= $DATA['title'];
		$this->_image_footer_text	= " (C) yfix.dev Log Exec Analyser for ".conf('website_name');
		// Create blank image for manipulations
		$img	= imagecreatetruecolor($this->_image_width, $this->_image_height + 5);
		// Set image background
		imagefilledrectangle($img, 0, 0, $this->_image_width, $this->_image_height + 5, $this->_img_colors["white"]);
		// Outer border rectangle
		imagerectangle($img, 0, 0, $this->_image_width - 1, $this->_image_height + 4, $this->_img_colors["grey_1"]);
		// Set header text dimensions
		$x1 = $this->_image_width;
		$y1 = 5;
		// Left header text (number of hosts, etc)
		if ($head_blocks_names['0'] && $head_blocks_values['0']) {
			$xxx	= ($this->_image_width - strlen($this->_image_header_text) * $this->_font_widths[2]) / 2;
			$str1	= " - ".$head_blocks_names['0'];
			$summh	= $this->_font_widths[2] * strlen($head_blocks_values['0']) + 4;

			if ($summh < 20) $summh = 20;

			$xx1 = $xxx - ($xxx/2) - (($this->_font_widths[2] * strlen($str1) + $summh) / 2);

			imagefilledrectangle($img, $xx1, $y1, $xx1 + $summh, $y1 + 10, $this->_img_colors["dark_blue"]);
			imagestring($img, 2, $xx1 + $summh + 5, $y1 - 2, $str1, $this->_img_colors["black"]);
			imagestring($img, 2, $xx1 + ($this->_font_widths[2] * strlen($summh) / 4), $y1 - 2, $head_blocks_values['0'], $this->_img_colors["white"]);
		}
		// Right header text (number of hits, etc)
		if ($head_blocks_names['1'] && $head_blocks_values['1']) {
			$xxx = ($this->_image_width - strlen($this->_image_header_text) * $this->_font_widths[2]) / 2;
			$str1 = " - ".$head_blocks_names['1'];
			$summh = $this->_font_widths[2] * strlen($head_blocks_values['1']) + 4;

			if ($summh < 20) $summh = 20;

			$xx1 = $this->_image_width - ($xxx/2) - (($this->_font_widths[2] * strlen($str1) + $summh) / 2);

			imagefilledrectangle($img, $xx1, $y1, $xx1 + $summh, $y1 + 10, $this->_img_colors["light_blue"]);
			imagestring($img, 2, $xx1 + $summh, $y1 - 2, $str1, $this->_img_colors["black"]);
			imagestring($img, 2, $xx1 + ($this->_font_widths[2] * strlen($summh) / 4), $y1 - 2, $head_blocks_values['1'], $this->_img_colors["black"]);
		}
		// Header title text
		imagestring($img, 2, $x1 / 2 - (strlen($this->_image_header_text) * $this->_font_widths[2] / 2), $y1 / 2, $this->_image_header_text, $this->_img_colors["black"]);
		// Inner border 1 rectangle
		imagerectangle($img, 5, 20, $this->_image_width - 5, $this->_image_height - 5, $this->_img_colors["grey_1"]);
		// Prepare data for display
		$max0 = (int) max($rows[0]);
		$max1 = (int) max($rows[1]);
		$maxy = (int) max($max0, $max1);
		if ($maxy == 0) $maxy = 1;
		$maxx = max($columns);

		$sizex = sizeof($columns);
		if ($sizex == 0) $sizex = 1;

		$sizey = 10;

		$step = $maxy / $sizey;
		$mlenx = 0;

		for ($i = 0; $i <= $sizey; $i++) {
			$yname[$i] = round($step * $i, 1);
			if ($mlenx < strlen($yname[$i])) {
				$mlenx = strlen($yname[$i]);
			}
		}

		$mleny = 0;
		foreach ((array)$columns as $v) {
			if (strlen($v) > $mleny) {
				$mleny = strlen($v);
			}
		}

		$x1 = 10 + $mlenx * $this->_font_widths[2];
		$x2 = $this->_image_width - 10;
		$y1 = 25;
		$y2 = $this->_image_height - 10 - $mleny * $this->_font_widths[2];
		// Body columns
		for ($i = 0; $i < sizeof($columns); $i++) {
//		foreach ((array)$columns as $i => $column_name) {
			$x11 = $x1 + ($x2 - $x1) / $sizex * $i;
			$x21 = $x11 + 7;
			$y11 = ($y2 - $y1) * ($rows[1][$i] * 100 / $maxy) / 100;
			$y21 = $y2;
			imagefilledrectangle($img, $x11, $y2 - $y11, $x21, $y21, $this->_img_colors["dark_blue"]);
			
			$x11 = $x1 + ($x2 - $x1) / $sizex * $i + 8;
			$x21 = $x11 + 7;
			$y11 = ($y2 - $y1) * ($rows[0][$i] * 100 / $maxy) / 100;
			$y21 = $y2;
			imagefilledrectangle($img, $x11, $y2 - $y11, $x21, $y21, $this->_img_colors["light_blue"]);
		};
		// Horizontal grid
		for ($i = 0; $i <= $sizey; $i++) {
			imageline($img, $x1 - ($mlenx * $this->_font_widths[2]), $y2 - ($y2 - $y1) / $sizey * $i, $x2, $y2 - ($y2 - $y1) / $sizey * $i, $this->_img_colors["grey_2"]);
		}
		// Vertical grid
		for ($i = 0; $i <= $sizex; $i++) {
			imageline($img, $x1 + ($x2 - $x1) / $sizex * $i, $y1, $x1 + ($x2 - $x1) / $sizex * $i, $y2 + $mleny * $this->_font_widths[2], $this->_img_colors["grey_2"]);

		}
		// Vertical nunmbers
		for ($i = 0; $i <= $sizey; $i++) {
			imagestring($img, 2, $x1 - (strlen($yname[$i]) * $this->_font_widths[2]), $y2 - ($y2 - $y1) / $sizey * $i, $yname[$i], $this->_img_colors["black"]);
		}
		// Horisontal nunmbers
		for ($i = 0; $i < $sizex; $i++) {
			imagestringup($img, 2, $x1 + ($x2 - $x1) / $sizex * $i, $y2 + (strlen($columns[$i]) * $this->_font_widths[2]), $columns[$i], $this->_img_colors["black"]);
		}
		// Texts under the columns
		for ($i = 0; $i < sizeof($columns); $i++) {
			$x11 = $x1 + ($x2 - $x1) / $sizex * $i;
			$x21 = $x11 + 7;
			$y11 = ($y2 - $y1) * ($rows[1][$i] * 100 / $maxy) / 100;
			$y21 = $y2;
			imagestringup($img, 1, $x11, $y21 - ($y11 / 2) + (strlen($rows[1][$i]) * $this->_font_widths[1] / 2), $rows[1][$i], $this->_img_colors["white"]);
			
			$x11 = $x1 + ($x2 - $x1) / $sizex * $i + 8;
			$x21 = $x11 + 7;
			$y11 = ($y2 - $y1) * ($rows[0][$i] * 100 / $maxy) / 100;
			$y21 = $y2;
			imagestringup($img, 1, $x11, $y21 - ($y11 / 2) + (strlen($rows[0][$i]) * $this->_font_widths[1] / 2),$rows[0][$i], $this->_img_colors["black"]);
		};
		// Bottom text
		imagestring($img, 1, 1, $this->_image_height - 4, $this->_image_footer_text, $this->_img_colors["black"]);
		// Send image contents
		header("Content-type: image/".$this->_image_type);
		$function_name = "image".$this->_image_type;
		$function_name($img);
		// Destroy image
		imagedestroy($img);
	}

	/**
	* Daily/Monthly/Yearly hosts/hits statistics
	*/
	function _get_visitors_stats($cur_date, $type = "day") {
		// Prepare image title
		$title = $this->_stats_periods[$type]. " stats : ".$this->_convert_date($cur_date, $type);
		// Time range to get info
		list($y, $m, )	= explode("-",$cur_date);
		if ($type == "day") {
			$time_start		= strtotime($cur_date);
			$time_end		= $time_start + 86400;
			$int_start		= 0;
			$int_end		= 23;
			$int_format		= "G";
		} elseif ($type == "month") {
			$time_start		= strtotime($y."-".$m."-01");
			$days_in_month	= date("t", $time_start);
			$time_end		= $time_start + 86400 * $days_in_month;
			$int_start		= 1;
			$int_end		= $days_in_month;
			$int_format		= "j";
		} elseif ($type == "year") {
			$time_start		= strtotime($y."-01-01");
			$days_in_year	= 365 + date("L", $time_start);
			$time_end		= $time_start + 86400 * $days_in_year;
			$int_start		= 1;
			$int_end		= 12;
			$int_format		= "n";
		}
		// Prepare arrays
		$unique_hosts		= array();
		for ($i = $int_start; $i <= $int_end; $i++) {
			$columns_titles[$i]		= $type == "year" ? date("F", strtotime($y."-".$i."-01")) : $i;
			$hits_by_interval[$i]	= 0;
			$hosts_by_interval[$i]	= 0;
		}
		// Get data from db
		$Q = db()->query("SELECT `date`, `ip` FROM `".db('log_exec')."` WHERE `date` >= ".intval($time_start)." AND `date` < ".intval($time_end));
		while ($A = db()->fetch_assoc($Q)) {
			$_interval_num = date($int_format, $A['date']);
			$hits_by_interval[$_interval_num]++;
			$unique_hosts[$A['ip']] = 1;
			$tmp_hosts[$_interval_num][$A['ip']] = 1;
		}
// TODO: need to check more carefully how archive stats connect with live ones
/*
		// Get data from archive table
		$Q = db()->query("SELECT `start_date`,`hits`,`hosts` FROM `".db('log_hourly_exec')."` WHERE `start_date` >= ".intval($time_start)." AND `start_date` <= ".intval($time_end));
		while ($A = db()->fetch_assoc($Q)) {
			$_interval_num = date($int_format, $A['date']);
			if (!isset($hits_by_interval[$_interval_num])) {
				$hits_by_interval[$_interval_num]++;
				$unique_hosts[] = 1;
				$tmp_hosts[$_interval_num][] = 1;
			}
		}
*/
		// Group hosts by interval
		foreach ((array)$tmp_hosts as $k => $v) {
			$hosts_by_interval[$k] = array_sum((array)$v);
		}
		// Count total hits
		$total_hits = db()->num_rows($Q);
		// Count total hosts
		$total_hosts = array_sum($unique_hosts);
		// Save data in session for display image method
		$_SESSION[$this->_DATA_ARRAY_NAME] = array(
			"title"	=> $title,
			"d0"	=> $hits_by_interval,
			"d1"	=> $hosts_by_interval,
			"x"		=> $columns_titles,
			"hsum"	=> array($total_hosts, $total_hits),
			"head"	=> array("Hosts", "Hits"),
		);
	}

	/**
	*  Get Day Os Stat
	*/
	function _get_day_os_stat($cur_date) {
// TODO
		$dbResult = db()->query("SELECT `user_agent`,COUNT(`id`) as `count` FROM `".db('counter')."` WHERE `date` = '".$cur_date."' GROUP BY `user_agent` ASC");
//		$dbResult = db()->query("SELECT `user_agent`,COUNT(`id`) as `count` FROM `".db('counter')."` WHERE `date` = '".$cur_date."' GROUP BY `user_agent` ASC");
		while ($dbAssoc = db()->fetch_assoc($dbResult)){
			$arr[] = $dbAssoc;
		}
		$summ = '100%';
		if (is_array($arr)){
			foreach ((array)$arr as $v){
				$this->_detect_browser($v['user_agent']);
				$mass[$this->platform] += $v['count'];
			}
			$allsumm = array_sum($mass);
			foreach ((array)$mass as $k=>$v){
				$name .= $k."|";
				$count .= round($v*100/$allsumm,2)."%|";
			}
		} else {
			$name = "";
			$count = "";
		}
		$title = "Daily stats : ".$this->_convert_date($cur_date);
/*
		$body .= "<img src='".$this->_analyser_image_path
				."&d1=".base64_encode(substr($count,0,-1))."&x=".base64_encode(substr($name,0,-1))."&hsum="
				.base64_encode($summ)."&name="
				.base64_encode(conf('website_name'))."&title=".base64_encode($title)."&head="
				.base64_encode("Operation System")."' alt='".$title."' border=0>";
		return $body;
*/
/*
		// Save data in session for display image method
		$_SESSION[$this->_DATA_ARRAY_NAME] = array(
			"title"	=> $title,
			"d0"	=> $hits_by_interval,
			"d1"	=> $hosts_by_interval,
			"x"		=> $columns_titles,
			"hsum"	=> array($total_hosts, $total_hits),
			"head"	=> array("Hosts", "Hits"),
		);
*/
	}

	/**
	*  Get Month Os Stat
	*/
	function _get_month_os_stat($cur_date) {
// TODO
		list($y,$m,) = explode("-",$cur_date);
		if (strlen($m)<2) $m = "0".$m;
		$dbResult = db()->query("SELECT `user_agent`,COUNT(`id`) as `count` FROM `".db('counter')."` WHERE `date` >= '".$y."-".$m."-01' AND `date`<='".$y."-".$m."-31' GROUP BY `user_agent` ORDER BY `date` ASC");
		while ($dbAssoc = db()->fetch_assoc($dbResult)){
			$arr[] = $dbAssoc;
		}
		$summ = '100%';
		if (is_array($arr)) {
			foreach ((array)$arr as $v) {
				$this->_detect_browser($v['user_agent']);
				$mass[$this->platform] += $v['count'];
			}
			$allsumm = array_sum($mass);
			foreach ((array)$mass as $k=>$v) {
				$name .= $k."|";
				$count .= round($v*100/$allsumm,2)."%|";
			}
		} else {
			$name = "";
			$count = "";
		}
		$title = "Monthly stats : ".substr($this->_convert_date($cur_date),2);
/*
		$body .= "<img src='".$this->_analyser_image_path
				."&d1=".base64_encode(substr($count,0,-1))."&x=".base64_encode(substr($name,0,-1))."&hsum="
				.base64_encode($summ)."&name="
				.base64_encode(conf('website_name'))."&title=".base64_encode($title)."&head="
				.base64_encode("Operation System")."' alt='".$title."' border=0>";
		return $body;
*/
/*
		// Save data in session for display image method
		$_SESSION[$this->_DATA_ARRAY_NAME] = array(
			"title"	=> $title,
			"d0"	=> $hits_by_interval,
			"d1"	=> $hosts_by_interval,
			"x"		=> $columns_titles,
			"hsum"	=> array($total_hosts, $total_hits),
			"head"	=> array("Hosts", "Hits"),
		);
*/
	}

	/**
	*  Get Year Os Stat
	*/
	function _get_year_os_stat($cur_date) {
// TODO
		list($y,,) = explode("-",$cur_date);
		$dbResult = db()->query("SELECT `user_agent`,COUNT(`id`) as `count` FROM `".db('counter')."` WHERE `date` >= '".$y."-01-01' AND `date`<='".$y."-12-31' GROUP BY `user_agent` ASC");
		while ($dbAssoc = db()->fetch_assoc($dbResult)){
			$arr[] = $dbAssoc;
		}
		$summ = "100%";
		if (is_array($arr)){
			foreach ((array)$arr as $v){
				$this->_detect_browser($v['user_agent']);
				$mass[$this->platform] += $v['count'];
			}
			$allsumm = array_sum($mass);
			foreach ((array)$mass as $k=>$v){
				$name .= $k."|";
				$count .= round($v*100/$allsumm,2)."%|";
			}
		} else {
			$name = "";
			$count = "";
		}
		$title = "Years stat : ".$y;
/*
		$body .= "<img src='".$this->_analyser_image_path
				."&d1=".base64_encode(substr($count,0,-1))."&x=".base64_encode(substr($name,0,-1))."&hsum="
				.base64_encode($summ)."&name="
				.base64_encode(conf('website_name'))."&title=".base64_encode($title)."&head="
				.base64_encode("Browsers")."' alt='".$title."' border=0>";
		return $body;
		return $body;
*/
/*
		// Save data in session for display image method
		$_SESSION[$this->_DATA_ARRAY_NAME] = array(
			"title"	=> $title,
			"d0"	=> $hits_by_interval,
			"d1"	=> $hosts_by_interval,
			"x"		=> $columns_titles,
			"hsum"	=> array($total_hosts, $total_hits),
			"head"	=> array("Hosts", "Hits"),
		);
*/
	}

	/**
	*  Get Day Browser Stat
	*/
	function _get_day_browser_stat($cur_date) {
// TODO
		$dbResult = db()->query("SELECT `user_agent`,COUNT(`id`) as `count` FROM `".db('counter')."` WHERE `date` = '".$cur_date."' GROUP BY `user_agent` ASC");
		while ($dbAssoc = db()->fetch_assoc($dbResult)){
			$arr[] = $dbAssoc;
		}
		$summ = "100%";
		if (is_array($arr)){
			foreach ((array)$arr as $v){
				$this->_detect_browser($v['user_agent']);
			$mass[$this->browser." ".$this->version] += $v['count'];
			}
			$allsumm = array_sum($mass);
			foreach ((array)$mass as $k=>$v){
				$name .= $k."|";
				$count .= round($v*100/$allsumm,2)."%|";
			}
		} else {
			$name = "";
			$count = "";
		}
		$title = "Daily stats : ".$this->_convert_date($cur_date);
/*
		$body .= "<img src='".$this->_analyser_image_path
				."&d1=".base64_encode(substr($count,0,-1))."&x=".base64_encode(substr($name,0,-1))."&hsum="
				.base64_encode($summ)."&name="
				.base64_encode(conf('website_name'))."&title=".base64_encode($title)."&head="
				.base64_encode("Operation System")."' alt='".$title."' border=0>";
		return $body;
*/
/*
		// Save data in session for display image method
		$_SESSION[$this->_DATA_ARRAY_NAME] = array(
			"title"	=> $title,
			"d0"	=> $hits_by_interval,
			"d1"	=> $hosts_by_interval,
			"x"		=> $columns_titles,
			"hsum"	=> array($total_hosts, $total_hits),
			"head"	=> array("Hosts", "Hits"),
		);
*/
	}

	/**
	*  Get Month Browser Stat
	*/
	function _get_month_browser_stat($cur_date) {
// TODO
		list($y,$m,) = explode("-",$cur_date);
		if (strlen($m)<2) $m = "0".$m;
		$dbResult = db()->query("SELECT `user_agent`,COUNT(`id`) as `count` FROM `".db('counter')."` WHERE `date` >= '".$y."-".$m."-01' AND `date`<='".$y."-".$m."-31' GROUP BY `user_agent` ASC");
		while ($dbAssoc = db()->fetch_assoc($dbResult)){
			$arr[] = $dbAssoc;
		}
		$summ = "100%";
		if (is_array($arr)){
			foreach ((array)$arr as $v){
				$this->_detect_browser($v['user_agent']);
				$mass[$this->browser." ".$this->version] += $v['count'];
			}
			$allsumm = array_sum($mass);
			foreach ((array)$mass as $k=>$v){
				$name .= $k."|";
				$count .= round($v*100/$allsumm,2)."%|";
			}
		} else {
			$name = "";
			$count = "";
		}
		$title = "Monthly stats : ".substr($this->_convert_date($cur_date),2);
/*
		$body .= "<img src='".$this->_analyser_image_path
				."&d1=".base64_encode(substr($count,0,-1))."&x=".base64_encode(substr($name,0,-1))."&hsum="
				.base64_encode($summ)."&name="
				.base64_encode(conf('website_name'))."&title=".base64_encode($title)."&head="
				.base64_encode("Operation System")."' alt='".$title."' border=0>";
		return $body;
*/
/*
		// Save data in session for display image method
		$_SESSION[$this->_DATA_ARRAY_NAME] = array(
			"title"	=> $title,
			"d0"	=> $hits_by_interval,
			"d1"	=> $hosts_by_interval,
			"x"		=> $columns_titles,
			"hsum"	=> array($total_hosts, $total_hits),
			"head"	=> array("Hosts", "Hits"),
		);
*/
	}

	/**
	*  Get Year Browser Stat
	*/
	function _get_year_browser_stat($cur_date) {
// TODO
		list($y,,) = explode("-",$cur_date);
		$dbResult = db()->query("SELECT `user_agent`,COUNT(`id`) as `count` FROM `".db('counter')."` WHERE `date` >= '".$y."-01-01' AND `date`<='".$y."-12-31' GROUP BY `user_agent` ASC");
		while ($dbAssoc = db()->fetch_assoc($dbResult)){
			$arr[] = $dbAssoc;
		}
		$summ = "100%";
		if (is_array($arr)){
			foreach ((array)$arr as $v){
				$this->_detect_browser($v['user_agent']);
				$mass[$this->browser." ".$this->version] += $v['count'];
			}
			$allsumm = array_sum($mass);
			foreach ((array)$mass as $k=>$v){
				$name .= $k."|";
				$count .= round($v*100/$allsumm,2)."%|";
			}
		} else {
			$name = "";
			$count = "";
		}
		$title = "Years stat : ".$y;
/*
		$body .= "<img src='".$this->_analyser_image_path
				."&d1=".base64_encode(substr($count,0,-1))."&x=".base64_encode(substr($name,0,-1))."&hsum="
				.base64_encode($summ)."&name="
				.base64_encode(conf('website_name'))."&title=".base64_encode($title)."&head="
				.base64_encode("Browsers")."' alt='".$title."' border=0>";
		return $body;
*/
/*
		// Save data in session for display image method
		$_SESSION[$this->_DATA_ARRAY_NAME] = array(
			"title"	=> $title,
			"d0"	=> $hits_by_interval,
			"d1"	=> $hosts_by_interval,
			"x"		=> $columns_titles,
			"hsum"	=> array($total_hosts, $total_hits),
			"head"	=> array("Hosts", "Hits"),
		);
*/
	}

	/**
	*  Detect Browser
	*/
	function _detect_browser($ua) {
// TODO
		$this->useragent = $ua;
		$preparens = "";
		$parens = "";
		$postparens = "";
		$i = strpos($this->useragent,"(");
		if ($i >= 0){
			$preparens = trim(substr($this->useragent,0,$i));
			$parensTMP = substr($this->useragent,$i+1,strlen($this->useragent));
			$j = strpos($parensTMP,")");
			if($j>=0) {
				$parens = substr($parensTMP,0,$j);
				$postparens = trim(substr($parensTMP,$j+1,strlen($parensTMP)));
			};
		} else $preparens = $this->useragent;
		$browVer = $preparens;
		$token = trim(strtok($parens,";"));
		while($token) {
			if($token=="compatible");
			elseif (preg_match("/MSIE/ims",$token))
				$browVer = $token;
			elseif (preg_match("/Opera/ims",$token))
				$browVer = $token;
			elseif (preg_match("/X11/ims",$token) || preg_match("/SunOS/ims",$token) || preg_match("/Linux/ims",$token))
				$this->platform = "Unix";
			elseif(preg_match("/Win/ims",$token))
				$this->platform = $token;
			elseif(preg_match("/Mac/ims",$token) || preg_match("/PPC/ims",$token))
				$this->platform = $token;
			$token = strtok(";");
		}

		$msieIndex = strpos($browVer,"MSIE");
		if($msieIndex >= 0) 
			$browVer = substr($browVer,$msieIndex,strlen($browVer));

		$leftover = "";
		if(substr($browVer,0,strlen("Mozilla")) == "Mozilla") {
			$this->browser = "Netscape";
			$leftover=substr($browVer,strlen("Mozilla")+1,strlen($browVer));
		} elseif(substr($browVer,0,strlen("Lynx")) == "Lynx") { 
			$this->browser = "Lynx";
			$leftover=substr($browVer,strlen("Lynx")+1,strlen($browVer));
		} elseif(substr($browVer,0,strlen("MSIE")) == "MSIE") { 
			$this->browser = "IE";
			$leftover=substr($browVer,strlen("MSIE")+1,strlen($browVer));
		} elseif(substr($browVer,0,strlen("Microsoft Internet Explorer")) == "Microsoft Internet Explorer") { 
			$this->browser = "IE";
			$leftover=substr($browVer,strlen("Microsoft Internet Explorer")+1,strlen($browVer));
		} elseif(substr($browVer,0,strlen("Opera")) == "Opera") { 
			$this->browser = "Opera";
			$leftover=substr($browVer,strlen("Opera")+1,strlen($browVer));
		} elseif(substr($browVer,0,strlen("iCab")) == "iCab") { 
			$this->browser = "iCab";
			$leftover=substr($browVer,strlen("iCab")+1,strlen($browVer));
		}

		$leftover = trim($leftover);
		// CHECK FOR OPERA BROWSERS BELOW VERSION 4.0
		if ($postparens) {
			if(substr($postparens,0,strlen("Opera")) == "Opera") { 
				$this->browser = "Opera";
				$v = trim(substr($postparens,strlen("Opera")+1,strlen($postparens)));
				$v = trim(substr($v,0,strpos($v," ")));
				$leftover = $v;
			}
		}
		// CHECK FOR NETSCAPE 6 PREVIEW RELEASES
		if ($postparens) {
			if (preg_match("/Netscape6/ims",$postparens)) {
				$this->browser = "Netscape";
				$i = strpos($postparens,"Netscape6");
				if ($i >= 0) {
					$v = trim(substr($postparens,$i+strlen("Netscape6")+1,strlen($postparens)));
					$leftover = $v;
				}
			}
		}

		$i = strpos($leftover," ");
		if ($i > 0) {
			$this->version = substr($leftover,0,$i);
		} else { 
			$this->version = $leftover;
		}

		$j = strpos($this->version,".");
		if($j >= 0) { 
			$this->majorVer = substr($this->version,0,$j);
			$this->minorVer = substr($this->version,$j+1,strlen($this->version));
		} else { 
			$this->majorVer = $this->version;
		}
	}

	/**
	*  Convert Date
	*/
	function _convert_date($date = "", $type = "day") {
// TODO: add type processing
		return date("d M Y", strtotime($date));
	}

	/**
	* Generate filter SQL query
	*/
	function _create_filter_sql () {
		$MF = &$_SESSION[$this->_filter_name];
		foreach ((array)$MF as $k => $v) $MF[$k] = trim($v);
/*
		// Generate filter for the common fileds
		if ($MF["id_min"]) 				$sql .= " AND `id` >= ".intval($MF["id_min"])." \r\n";
		if ($MF["id_max"])			 	$sql .= " AND `id` <= ".intval($MF["id_max"])." \r\n";
		if (strlen($MF["nick"])) 		$sql .= " AND `display_name` LIKE '"._es($MF["nick"])."%' \r\n";
		if (strlen($MF["email"])) 		$sql .= " AND `email` LIKE '"._es($MF["email"])."%' \r\n";
		if (strlen($MF["login"])) 		$sql .= " AND `login` LIKE '"._es($MF["login"])."%' \r\n";
		if (strlen($MF["password"])) 	$sql .= " AND `password` LIKE '"._es($MF["password"])."%' \r\n";
		if ($MF["account_type"])		$sql .= " AND `group` = ".intval($MF["account_type"])." \r\n";
		if (strlen($MF["state"]))		$sql .= " AND `state` = '".$MF["state"]."' \r\n";
		if ($MF["country"])	 			$sql .= " AND `country` = '"._es($MF["country"])."' \r\n";
		if (isset($MF["active"]) && $MF["active"] != -1) {
			$sql .= " AND `active` = '".intval((bool) $MF["active"])."' \r\n";
		}
*/
		// Sorting here
		if ($MF["sort_by"])			 	$sql .= " ORDER BY `".$this->_sort_by[$MF["sort_by"]]."` \r\n";
		if ($MF["sort_by"] && strlen($MF["sort_order"])) 	$sql .= " ".$MF["sort_order"]." \r\n";
		return substr($sql, 0, -3);
	}

	/**
	* Session - based members filter form stored in $_SESSION[$this->_filter_name][...]
	*/
	function _show_filter () {
		if (!isset($_SESSION[$this->_filter_name]["active"])) {
			$_SESSION[$this->_filter_name]["active"] = "-1";
		}
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
		return tpl()->parse($_GET["object"]."/filter", $replace);
	}

	/**
	* Filter save method
	*/
	function save_filter ($silent = false) {
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_POST["country"]) && substr($_POST["country"], 0, 2) == "f_") {
			$_POST["country"] = substr($_POST["country"], 2);
		}
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_POST[$name];
		}
		if (!$silent) {
			js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	/**
	* Clear filter
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
	*  Update Counter Days
	*/
/*
	function _update_counter_days () {
// TODO
		$this->imagesPath = WEB_PATH."templates/".conf('theme')."/images/";
		$this->currentDate = date("Y-m-d");
		$this->currentStamp = mktime(0,0,0,date("m"),date("d"),date("Y"));
		$A = db()->fetch_assoc(db()->query("SELECT COUNT(`id`) AS `count` FROM `".db('counter')."` WHERE `date` = NOW()"));
		if ($A['count'] == 0) {
			$cur_date = date("Y-m-d", time() - 60 * 60 * 24);
			list($hits) = db()->fetch_assoc(db()->query("SELECT COUNT(`id`) AS `0` FROM `".db('counter')."` WHERE `date` = '".$cur_date."'"));
			list($hosts) = db()->fetch_assoc(db()->query("SELECT COUNT(DISTINCT(`remote_addr`)) AS `0` FROM `".db('counter')."` WHERE `date` = '".$cur_date."'"));
			$sql = "INSERT INTO `".db('counter_days')."` (
					`hits`, 
					`hosts`, 
					`date` 
				) VALUES (
					'".$hits."', 
					'".$hosts."', 
					'".$cur_date."' 
				)";
//			db()->query($sql);
		}
	}
*/
	/**
	*  Show Counter
	*/
/*
	function _show_counter() {
// TODO
		if (conf('counter_box')) {
			list($hits) = db()->query_fetch("SELECT COUNT(`id`) AS `0` FROM `".db('counter')."` WHERE `date` = NOW()");
			list($hosts) = db()->query_fetch("SELECT COUNT(DISTINCT(`remote_addr`)) AS `0` FROM `".db('counter')."` WHERE `date` = NOW()");
			list($allvisit) = db()->query_fetch("SELECT COUNT(DISTINCT(`remote_addr`)) AS `0` FROM `".db('counter')."` WHERE `date` = NOW()");
			$A = db()->query_fetch("SELECT SUM(`hosts`) AS `count` FROM `".db('counter_days')."`");
			$allvisit += $A['count'];
			return "<img src='".WEB_PATH."counter/index.php?h=".base64_encode($hosts)."&i=".base64_encode($hits)."&a=".base64_encode($allvisit)."&p=".base64_encode($this->fileID)."&f=".base64_encode("templates/".conf('theme')."/images/")."&n=".base64_encode($this->fileNameC)."' width='88' height='31' alt='".ucfirst(t("counter"))."' border=0>";
		}
	}
*/
	/**
	*  Get Location
	*/
/*
	function _get_location($address) {
// TODO
		if (!strcmp($address,"127.0.0.1")) return false;
		if (!is_array($page = @file("http://netgeo.caida.org/perl/netgeo.cgi?method=getRecord&target=".urlencode($address))))
			return false;
		for ($line = 0; $line < count($page); $line++) {
			$data = strtok($page[$line],"<\r\n");
			if (!strcmp(strtok($data,"="),"VERSION")) break;
		}
		if ($line >= count($page)) return false;
		for ($line++; $line < count($page); $line++) {
			$attribute = strtok(strtok($page[$line],"<\r\n"),":");
			if (strcmp($attribute,"")) $location[$attribute] = trim(strtok("<\r\n"));
		}
		if ($location['STATUS'] == "No Match" || ($location['LAT'] == 0 && $location['LONG'] == 0))
			return false;
		return $location['COUNTRY'];
	}
*/
	/**
	* Show Counter Image
	*/
/*
	function show_counter_image () {
// TODO
		$_GET['h'] = base64_decode($_GET['h']);
		$_GET['i'] = base64_decode($_GET['i']);
		$_GET['a'] = base64_decode($_GET['a']);
		$_GET['p'] = base64_decode($_GET['p']);
		$_GET['f'] = base64_decode($_GET['f']);
		$_GET['n'] = base64_decode($_GET['n']);
		if (!$_GET['h']||(preg_match("/[^\d]/",$_GET['h']))||(strlen($_GET['h'])>10)) die();
		if (!$_GET['i']||(preg_match("/[^\d]/",$_GET['i']))||(strlen($_GET['i'])>11)) die();
		if (!$_GET['a']||(preg_match("/[^\d]/",$_GET['a']))||(strlen($_GET['a'])>10)) die();
		if (!$_GET['p']) die();
		if (!$_GET['f']) die();
		if (!$_GET['n']) die();

		$this->_create_counter_image($_GET['i'],$_GET['h'],$_GET['a'],$_GET['p'],$_GET['f'],$_GET['n']);
	}
*/
	/**
	*  Create Counter Image
	*/
/*
	function _create_counter_image($hits, $hosts, $allVisits, $imgPrefix, $imgPath, $imgName) {
// TODO
		$bgImageButton = "../".$imgPath.$imgPrefix.$imgName;

		if (file_exists($bgImageButton)) {
			$editImage = imagecreatefromgif($bgImageButton);
			$writeTextAllVisits = imagecolorallocate($editImage, 255, 255, 0);
			$writeTextHits = imagecolorallocate($editImage, 0, 255, 255);
			$writeTextHosts = imagecolorallocate($editImage, 255, 255, 255);
			imagestring($editImage, 1, 82-(imagefontwidth(1)*strlen($allVisits)), 3, $allVisits, $writeTextAllVisits);
			imagestring($editImage, 1, 82-(imagefontwidth(1)*strlen($hosts)), 11, $hosts, $writeTextHosts);
			imagestring($editImage, 1, 82-(imagefontwidth(1)*strlen($hits)), 19, $hits, $writeTextHits);
			header("Content-type: image/png");
			imagepng($editImage);
			imagedestroy($editImage);
			return true;
		} else {
			return false;
		}
	}
*/

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
				"name"	=> "Latest Hourly Stats",
				"url"	=> "./?object=".$_GET["object"]."&action=hourly_latest_stats",
			),
			array(
				"name"	=> "Archive Hourly Stats",
				"url"	=> "./?object=".$_GET["object"]."&action=hourly_archive_stats",
			),
			array(
				"name"	=> "Raw Exec Log",
				"url"	=> "object=db_parser&table=sys_log_exec",
			),
			array(
				"name"	=> "Max Exec Time Stats",
				"url"	=> "./?object=".$_GET["object"]."&action=max_exec_stats",
			),
			array(
				"name"	=> "Image Graphs",
				"url"	=> "./?object=".$_GET["object"]."&action=display_image_graphs",
			),
			array(
				"name"	=> "User Agents Stats",
				"url"	=> "./?object=".$_GET["object"]."&action=user_agents_stats",
			),
			array(
				"name"	=> "Referers Stats ",
				"url"	=> "./?object=".$_GET["object"]."&action=referers_stats",
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
		$pheader = t("Execution Log Analyser");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
			"hourly_latest_stats"	=> "",
			"hourly_archive_stats" 	=> "",
			"max_exec_stats"		=> "",
			"display_image_graphs"	=> "Image graphs",
			"user_agents_stats"		=> "",
			"referers_stats"		=> "",
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
