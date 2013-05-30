<?php

/**
* Realtime errors log analyzer
*/
class profy_analyze_error_log {

	/** @var int Sow stats interval */
	var $STATS_INTERVAL		= 90; //days

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

	
		$this->stats_interval = time() - $this->STATS_INTERVAL * 86400;

		$this->_country_names = main()->get_data("countries");
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

		$path = INCLUDE_PATH."error_logs.log";
		
		$DIR_OBJ = main()->init_class("dir", "classes/");
		$reg_warning = "#WARNING]:\s*(?P<content>.+)#";
		$reg_source = "#SOURCE:\s*(?P<content>.+)#";
		$reg_query_string = "#QUERY_STRING\s*=\s*(?P<content>.+)#";
		
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
				
				preg_match($reg_warning ,$tmp[1],$warning);
				preg_match($reg_source ,$tmp[2],$source);
				preg_match($reg_query_string ,$tmp[3],$query_string);
				$info = array();
				$info["time"]			= $tmp[0];
				$info["content_short"]		= htmlspecialchars(substr($warning["content"], 0, 50));
				$info["content"]		= htmlspecialchars(substr($warning["content"], 0, 200));
				$info["source_short"]			= substr($source["content"], 0, 50);
				$info["source"]			= substr($source["content"], 0, 200);
				$info["query_string_short"]	= substr($query_string["content"], 0, 50);
				$info["query_string"]	= substr($query_string["content"], 0, 200);
				$info["all"]		=  str_replace("#@#", "<br />\n", $_line);	
				
				unset($tmp);
			
				$_date = (int)strtotime($info["time"]);
			
				if ($_date < $_POST["last_update"]) {
					continue;
				} 
				 
				$_date = date("H:i:s | d-m-Y", $_date);
					
		
				$data["items"][] = array(
					"unique_id"		=> md5($_line),
					"date"			=> $_date,
					"content_short"			=>$info["content_short"],
					"source_short"	=> $info["source_short"],
					"query_string_short"	=> $info["query_string_short"],
					"content"			=>wordwrap($info["content"], 60, "<br />\n", 1),
					"source"	=> wordwrap($info["source"], 60, "<br />\n", 1),
					"query_string"	=> wordwrap($info["query_string"], 60, "<br />\n", 1),
					"all"	=> wordwrap($info["all"],160, "<br />\n", 1),
				
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
			"update_box"		=> common()->select_box("update_box", $this->update_box_val, "10000", false, 2, "", false),
			"tail_limit_box"	=> common()->select_box("tail_limit", $this->_tail_limits, "30", false, 2, "", false),
		 
		);
		return tpl()->parse($_GET["object"]."/realtime", $replace);
	}

	/**
	* Update aggregated stats tables
	*/
	function update_stats() {
// TODO
		$LOGS_DIR = INCLUDE_PATH."logs/log_exec/";

		$DIR_OBJ = main()->init_class("dir", "classes/");
		foreach ((array)$DIR_OBJ->scan_dir($LOGS_DIR, 0, "#[0-9]{4}-[0-9]{2}-[0-9]{2}\.log#") as $_file) {
			$path	= $LOGS_DIR. $_file;
//			$items	= count(file($path));
echo $path." ".$items."<br />";
		}
	}

	
	}
	