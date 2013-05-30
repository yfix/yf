<?php

/**
* 
*/
class profy_analyze_engine_log {

	/** @var int Sow stats interval */
	var $STATS_INTERVAL		= 90; //days
	var $STATS_PERIOD_VIEW = 5184000; // 60 days

	/**
	* Constructor
	*/
	function _init() {
		$this->update_box_val = array(
			"1000"	=> 1,
			"2000"	=> 2,
			"3000"	=> 3,
			"4000"	=> 4,
			"5000"	=> 5,
			"10000"	=> 10,
			"15000"	=> 15,
			"20000"	=> 20,
			"30000"	=> 30,
			"60000"	=> 60,
		);

		$this->_tail_limits = array(
			"3"		=> 3,
			"5"		=> 5,
			"10"	=> 10,
			"20"	=> 20,
			"30"	=> 30,
			"40"	=> 40,
			"50"	=> 50,
			"100"	=> 100,
			"150"	=> 150,
			"200"	=> 200,
		);

		$this->_bots_switch = array(
			"" => "-- ALL --",
			"y" => "Bots Only",
			"n" => "No Bots",
		);

		$Q = db()->query("SELECT * FROM `".db('sites')."` WHERE `active` = '1' AND `country` != '' ORDER BY `name` ASC");
		while($A = db()->fetch_assoc($Q)) {
			$this->sys_sites[$A["id"]] = $A["name"];
			$this->sys_sites_info[$A["id"]] = $A;
		}

		$this->stats_interval = time() - $this->STATS_INTERVAL * 86400;

		$this->_country_names = main()->get_data("countries");
		$this->month_abbr = array(
			"Jan",
			"Feb",
			"Mar",
			"Apr",
			"May",
			"Jun",
			"Jul",
			"Aug",
			"Sep",
			"Oct",
			"Nov",
			"Dec", 
		);
	}

	/**
	* Default method
	*/
	function show() {
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* @params $_POST["site_id"], $_POST["last_update"]
	*/
	function realtime() {
		$_cur_date = gmdate("Y-m-d");

		$path = INCLUDE_PATH."logs/log_exec/".$_cur_date.".log";

		$DIR_OBJ = main()->init_class("dir", "classes/");
		// Return AJAX data
		if (!empty($_POST)) {
			main()->NO_GRAPHICS = true;

			$min_time = time() - 86400;

			if (!$_POST["last_update"] || $_POST["last_update"] < $min_time) {
				$_POST["last_update"] = $min_time;
			}

			$data = array(
				"last_update"	=> time(),
				"items"			=> array(),
			);
			$result = array();

			$limit = 30;
			if ($_POST["tail_limit"] && isset($this->_tail_limits[$_POST["tail_limit"]])) {
				$limit = $_POST["tail_limit"];
			}

			foreach ((array)$DIR_OBJ->tail($path, $limit) as $_line) {
				$tmp = explode("#@#", $_line);

				$info = array();
				$info["user_id"]		= (int)$tmp[1];
				$info["user_group"]		= (int)$tmp[2];
				$info["time"]			= (int)$tmp[3];
				$info["ip"]				= $tmp[4];
				$info["ua"]				= $tmp[5];
				$info["referer"]		= $tmp[6];
				$info["query_string"]	= $tmp[7];
				$info["host_and_uri"]	= $tmp[8];
				$info["exec_time"]		= (float)$tmp[9];
				$info["num_db_q"]		= (int)$tmp[10];
				$info["html_size"] 		= (int)$tmp[11];
				$info["site_id"] 		= (int)$tmp[12];
				$info["from_cache"]		= (int)$tmp[13];
				unset($tmp);

				$_date = (int)$info["time"];
				if ($_date < $_POST["last_update"]) {
					continue;
				}
				if ($_POST["site_id"] && $info["site_id"] != $_POST["site_id"]) {
					continue;
				}
				// Bots filtering
				$spider_name = common()->_is_spider($info["ip"], $info["ua"]);
				if ($_POST["bots_switch"]) {
					if ($_POST["bots_switch"] == "y" && !$spider_name) {
						continue;
					}
					if ($_POST["bots_switch"] == "n" && $spider_name) {
						continue;
					}
				}
				$_date = gmdate("H:i:s Y-m-d", $_date);
				if (substr($_date, -10) == $_cur_date) {
					$_date = trim(substr($_date, 0, -10));
				}

				$referer_se = common()->is_search_engine_url($info["referer"]);
				$country_code = common()->_get_country_by_ip($info["ip"]);

				$query = array();
				parse_str($info["query_string"], $query);

				$url = "http://".$info["host_and_uri"];

				$_sys_host = parse_url($url, PHP_URL_HOST);

				$_request_uri = utf8_encode(urldecode(substr($info["host_and_uri"], strlen($_sys_host) + 1)));

				$data["items"][] = array(
					"unique_id"		=> md5($_line),
					"link"			=> $url,
					"date"			=> $_date,
					"request_uri"	=> "/"._truncate($_request_uri, 50, false, "..."),
					"get_params"	=> $query["object"]. ($query["action"] ? "->".$query["action"] : ""). ($query["id"] ? "->".$query["id"] : ""). ($query["page"] ? "->".$query["page"] : ""),
					"get_object"	=> $query["object"],
					"get_action"	=> $query["action"],
					"get_id"		=> $query["id"],
					"get_page"		=> $query["page"],
					"site_id"		=> intval($info["site_id"]),
					"site_name"		=> _prepare_html($this->sys_sites[$info["site_id"]]),
					"ip"			=> _prepare_html($info["ip"]),
					"ip_country"	=> strtolower($country_code),
					"country_name"	=> $this->_country_names[$country_code],
					"spider_name"	=> $spider_name,
					"sys_host"		=> _prepare_html($_sys_host),
					"referer"		=> _prepare_html($info["referer"]),
					"referer_se"	=> _prepare_html($referer_se),
					"user_id"		=> intval($info["user_id"]),
					"exec_time"		=> common()->_format_time_value($info["exec_time"]),
					"num_db_q"		=> $info["num_db_q"],
					"html_size" 	=> $info["html_size"],
					"ua"			=> _prepare_html($info["ua"]),
					"from_cache" 	=> intval((bool)$info["from_cache"]),
				);
			}
			return print common()->json_encode($data);
		}

		$_site_box_data = array(
			""	=> "-- ALL --",
		);
		foreach ((array)$this->sys_sites as $_id => $_name) {
			$_site_box_data[$_id] = $_name;
		}
		$replace = array(
			"get_process_link"	=> process_url("./?object=".$_GET["object"]."&action=".$_GET["action"]),
			"update_box"		=> common()->select_box("update_box", $this->update_box_val, "3000", false, 2, "", false),
			"tail_limit_box"	=> common()->select_box("tail_limit", $this->_tail_limits, "30", false, 2, "", false),
			"site_id_box"		=> common()->select_box("site_id", $_site_box_data, "", false, 2, "", false),
			"bots_switch_box"	=> common()->select_box("bots_switch", $this->_bots_switch, "", false, 2, "", false),
		);
		return tpl()->parse($_GET["object"]."/realtime", $replace);
	}

	/**
	* 
	*/
	function stats() {
		$body .= "\n<br /><a href='./?object=".$_GET["object"]."&action=refresh_stats'>Refresh stats</a><br /><br />\n";
		if(!isset($_GET["id"])){
			if(!isset($_GET["date_from"])){
				$interval["date_from"] = intval(time() - $this->STATS_PERIOD_VIEW);
				$interval["date_to"] = intval(time());
			}else{
				$interval["date_from"] = strtotime($_GET["date_from"]);
				$interval["date_to"] = strtotime($_GET["date_to"]);
			}
			if (!empty($interval["date_from"]) && !empty($interval["date_to"])) {
				$str_date_from = $interval["date_from"] ? gmdate("Y-m-d", $interval["date_from"]) : "";
				$str_date_to = $interval["date_to"] ? gmdate("Y-m-d", $interval["date_to"]) : "";
				$date_interval = $this->_localize_date($str_date_from)." - ".$this->_localize_date($str_date_to);
			}else{$date_interval = "";}

			$body .= "<script type='text/javascript'>var date_from = '".$str_date_from."';var date_to = '".$str_date_to."';var stats_period_view = 60;</script>";
			$body .= "<div align='center'><div style='width:780px;'><h3 style='text-align:right;' id='date_interval_text'>".$date_interval."</h3><div id='date_slider'><span style='clear:both;'></span></div><input id='apply_date_interval' type='button' name='apply_date_interval' value='>' style='width:20px;' /><br style='clear: both;'><b>{t(Show statistics for chosen date interval)}</b></div></div><br style='clear:both;' />";
		}
		$_GET["id"] = preg_replace("#[^0-9\-]#", "", $_GET["id"]);

		if ($_GET["id"]) {
			$body .= "<h3>".$_GET["id"]."</h3>";
		}
		$body .= "%GRAPHS%";
		$body .= "<div align='center'><table>";
		// Day details
		if ($_GET["id"]) {
			$body .= "<thead>";
			$body .= "<th>Site</th>";
			$body .= "<th>Hosts</th>";
			$body .= "<th>Human Hosts</th>";
			$body .= "<th>Spider Hosts</th>";
			$body .= "<th>Member Hosts</th>";
			$body .= "<th>Hits</th>";
			$body .= "<th>Human hits</th>";
			$body .= "<th>Spider hits</th>";
			$body .= "<th>Member hits</th>";
			$body .= "<th>Cache hits</th>";
			$body .= "</thead>";
			// Sys sites
			$Q = db()->query("SELECT * FROM `".db('log_exec_daily')."` WHERE `site_id` != '' AND `day`='"._es($_GET["id"])."' ORDER BY `site_id` ASC");
			while ($info = db()->fetch_assoc($Q)) {
				$site_id = $info["site_id"];
				$body .= "<tr>";
				$body .= "<td>".$this->sys_sites[$site_id]."</td>";
				$body .= "<td>".$info["hosts"]."</td>";
				$body .= "<td>".$info["spider_hosts"]."</td>";
				$body .= "<td>".($info["hosts"] - $info["spider_hosts"])."</td>";
				$body .= "<td>".$info["member_hosts"]."</td>";
				$body .= "<td>".$info["hits"]."</td>";
				$body .= "<td>".($info["hits"] - $info["spider_hits"])."</td>";
				$body .= "<td>".$info["spider_hits"]."</td>";
				$body .= "<td>".$info["member_hits"]."</td>";
				$body .= "<td>".$info["cache_hits"]."</td>";
				$body .= "</tr>";
			}
		} else {
			$body .= "<thead>";
			$body .= "<th>Day</th>";
			$body .= "<th>Hosts</th>";
			$body .= "<th>Human Hosts</th>";
			$body .= "<th>Spider Hosts</th>";
			$body .= "<th>Member Hosts</th>";
			$body .= "<th>Hits</th>";
			$body .= "<th>Human hits</th>";
			$body .= "<th>Spider hits</th>";
			$body .= "<th>Member hits</th>";
			$body .= "<th>Cache hits</th>";
			$body .= "</thead>";
			// Days aggregated
			$Q = db()->query(
				"SELECT `day`, `site_id`, SUM(`hosts`) AS s_hosts, SUM(`spider_hosts`) AS s_spider_hosts, SUM(`member_hosts`) AS s_member_hosts, SUM(`hits`) AS s_hits, SUM(`spider_hits`) AS s_spider_hits, SUM(`cache_hits`) AS s_cache_hits, SUM(`member_hits`) AS s_member_hits
				FROM `".db('log_exec_daily')."` 
				WHERE `site_id` != '' 
				AND `day` >= '"._es($str_date_from)."' 
				AND `day` <= '"._es($str_date_to)."'
				GROUP BY `day`
				ORDER BY `day` DESC"
			);
			while ($info = db()->fetch_assoc($Q)) {
				$day = $info["day"];
				$body .= "<tr>";

				$body .= "<td><a href='./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$day."'>".$day."</a></td>";
				$body .= "<td>".$info["s_hosts"]."</td>";
				$body .= "<td>".($info["s_hosts"] - $info["s_spider_hosts"])."</td>";
				$body .= "<td>".$info["s_spider_hosts"]."</td>";
				$body .= "<td>".$info["s_member_hosts"]."</td>";
				$body .= "<td>".$info["s_hits"]."</td>";
				$body .= "<td>".($info["s_hits"] - $info["s_spider_hits"])."</td>";
				$body .= "<td>".$info["s_spider_hits"]."</td>";
				$body .= "<td>".$info["s_member_hits"]."</td>";
				$body .= "<td>".$info["s_cache_hits"]."</td>";
				$body .= "</tr>";
				$for_graph_hits[$day]	= $info["s_hits"];
				$for_graph_hosts[$day]	= $info["s_hosts"];
			}
			$graph .= "<h3>Hits</h3>".common()->graph($for_graph_hits);
			$graph .= "<h3>Hosts</h3>".common()->graph($for_graph_hosts);
		}
		$body .= "</table></div>";
		$body = str_replace("%GRAPHS%", $graph."<br />\n", $body);
		return $body;
	}

	/**
	* 
	*/
	function refresh_stats() {
		$Q = db()->query("SELECT `day` FROM `".db('log_exec_daily')."` WHERE `site_id` != '' GROUP BY `day`");
		while ($A = db()->fetch_assoc($Q)) {
			$daily_stats[$A["day"]] = true;
		}

		$LOGS_DIR = INCLUDE_PATH."logs/log_exec/";

		set_time_limit(1800);

		$ip_is_bot = array();

		$cur_date = (int)gmdate("Ymd");

		$DIR_OBJ = main()->init_class("dir", "classes/");
		foreach ((array)$DIR_OBJ->scan_dir($LOGS_DIR, 0, "#[0-9]{4}-[0-9]{2}-[0-9]{2}\.log#") as $_file) {
			$day = substr(basename($_file), 0, -4);
			if (isset($daily_stats[$day])) {
				continue;
			}
			// Skip current day and future too
			if (str_replace("-", "", $day) >= $cur_date) {
				continue;
			}
			$path	= $LOGS_DIR. $_file;
			$h = @fopen($path, "r");
			if (!$h) {
				continue;
			}
			$stats = array();
		    while (!feof($h)) {
				$info = array();
				$tmp = explode("#@#", fgets($h, 4096));
				$info["user_id"]	= (int)$tmp[1];
				$info["ip"]			= $tmp[4];
				$info["ua"]			= $tmp[5];
				$info["site_id"] 	= (int)$tmp[12];
				$info["from_cache"]	= (int)$tmp[13];
				unset($tmp);

				$stats["hits"][$info["site_id"]]++;
				$stats["hosts"][$info["site_id"]][$info["ip"]]++;
				if ($info["user_id"]) {
					$stats["member_hits"][$info["site_id"]]++;
					$stats["member_hosts"][$info["site_id"]][$info["ip"]]++;
				}
				// Use cache
				if (!isset($ip_is_bot[$info["ip"]])) {
					$ip_is_bot[$info["ip"]] = common()->_is_spider($info["ip"], $info["ua"]);
				}
				if ($ip_is_bot[$info["ip"]]) {
					$stats["spider_hits"][$info["site_id"]]++;
					$stats["spider_hosts"][$info["site_id"]][$info["ip"]]++;
				}
				if ($info["from_cache"]) {
					$stats["cache_hits"][$info["site_id"]]++;
				}
		    }
		    fclose($h);

			foreach ((array)$stats["hits"] as $site_id => $hits_all) {
				if (!$site_id) {
					continue;
				}
				db()->INSERT("log_exec_daily", array(
					"day"			=> _es($day),
					"site_id"		=> (int)$site_id,
					"hits"			=> (int)$stats["hits"][$site_id],
					"member_hits"	=> (int)$stats["member_hits"][$site_id],
					"spider_hits"	=> (int)$stats["spider_hits"][$site_id],
					"cache_hits"	=> (int)$stats["cache_hits"][$site_id],
					"hosts"			=> is_array($stats["hosts"][$site_id]) ? count($stats["hosts"][$site_id]) : 0,
					"spider_hosts"	=> is_array($stats["spider_hosts"][$site_id]) ? count($stats["spider_hosts"][$site_id]) : 0,
					"member_hosts"	=> is_array($stats["member_hosts"][$site_id]) ? count($stats["member_hosts"][$site_id]) : 0,
				));
			}
		}

		js_redirect("./?object=".$_GET["object"]."&action=stats");
	}
	
	/**
	* Localize date from format "15-05-2009" to format "15 May 2009"
	*/
	function _localize_date ($date) {
		preg_match("/\-([0-9]{2})\-/i", $date, $m);
		$key = intval($m[1]) - 1;
		$localized = str_replace($m[0], " ".$this->month_abbr[$key]." ", $date);
		return  $localized;
	}
	
}
	