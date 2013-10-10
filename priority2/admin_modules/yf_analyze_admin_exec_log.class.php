<?php

/**
* Realtime admin exec log analyzer
*/
class yf_analyze_admin_exec_log{

	/** @var int Sow stats interval */
	public $STATS_INTERVAL		= 90; //days

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

// check this
		$Q = db()->query("SELECT * FROM ".db('sites')." WHERE active = '1' AND vertical = '' ORDER BY name ASC");
		while($A = db()->fetch_assoc($Q)) {
			$this->sys_sites[$A["id"]] = $A["name"];
			$this->sys_sites_info[$A["id"]] = $A;
		}

		$Q1 = db()->query("SELECT * FROM ".db('servers')." WHERE active = '1' ORDER BY name ASC");
		while($A1 = db()->fetch_assoc($Q1)) {
			$this->sys_servers[$A1["id"]] = $A1["name"];
			$this->sys_servers_info[$A1["id"]] = $A1;
		}

		$this->stats_interval = time() - $this->STATS_INTERVAL * 86400;

//		$this->_country_names = main()->get_data("countries");
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

		$path = INCLUDE_PATH."logs/log_admin_exec/".$_cur_date.".log";

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
				$info["admin_id"]		= (int)$tmp[1];
				$info["admin_group"]	= (int)$tmp[2];
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
				$info["server_id"]		= (int)$tmp[13];
				$info["from_cache"]		= (int)$tmp[14];
				unset($tmp);
				$_date = (int)$info["time"];
				if ($_date < $_POST["last_update"]) {
					continue;
				}
				if ($_POST["site_id"] && $info["site_id"] != $_POST["site_id"]) {
					continue;
				}
				$_date = gmdate("H:i:s Y-m-d", $_date);
				if (substr($_date, -10) == $_cur_date) {
					$_date = trim(substr($_date, 0, -10));
				}

				$referer_se = common()->is_search_engine_url($info["referer"]);
//				$country_code = common()->_get_country_by_ip($info["ip"]);

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
					"server_id"		=> intval($info["server_id"]),
					"server_name"	=> _prepare_html($this->sys_servers[$info["server_id"]]),
					"ip"			=> _prepare_html($info["ip"]),
//					"ip_country"	=> strtolower($country_code),
//					"country_name"	=> $this->_country_names[$country_code],
//					"spider_name"	=> $spider_name,
					"sys_host"		=> _prepare_html($_sys_host),
					"referer"		=> _prepare_html($info["referer"]),
					"referer_se"	=> _prepare_html($referer_se),
					"admin_id"		=> intval($info["admin_id"]),
					"exec_time"		=> common()->_format_time_value($info["exec_time"]),
					"num_db_q"		=> $info["num_db_q"],
					"html_size" 	=> $info["html_size"],
					"ua"			=> _prepare_html($info["ua"]),
					"from_cache" 	=> intval((bool)$info["from_cache"]),
				);
			}
			return print json_encode($data);
		}

		$_site_box_data = array(
			""	=> "-- ALL --",
		);
		foreach ((array)$this->sys_sites as $_id => $_name) {
			$_site_box_data[$_id] = $_name;
		}
		$replace = array(
			"get_process_link"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"update_box"		=> common()->select_box("update_box", $this->update_box_val, "3000", false, 2, "", false),
			"tail_limit_box"	=> common()->select_box("tail_limit", $this->_tail_limits, "30", false, 2, "", false),
			"site_id_box"		=> common()->select_box("site_id", $_site_box_data, "", false, 2, "", false),
		);
		return tpl()->parse($_GET["object"]."/realtime", $replace);
	}

	function _hook_widget__latest_admin_log ($params = array()) {
// TODO
	}
}