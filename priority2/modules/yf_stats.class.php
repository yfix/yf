<?php

/**
* Site stats display handler
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_stats {

	/** @var int */
	var $ONLINE_PER_LINE	= 4;
	/** @var int */
	var $ONLINE_PER_PAGE	= 20;

	/**
	* Default method
	*/
	function show () {
		// Process template
		$replace = array(
			"online_link"		=> "./?object=".$_GET["object"]."&action=online",
			"most_active_link"	=> "./?object=".$_GET["object"]."&action=most_active",
			"last_updated_link"	=> "./?object=".$_GET["object"]."&action=last_updated",
			"last_24_stats_link"=> "./?object=".$_GET["object"]."&action=last_24_stats",
			"members_link"		=> "./?object=users",
			"quick_users_stats"	=> $this->quick_users_stats(),
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Dislay online users (also guests)
	*/
	function online () {
		// Check CPU Load
		if (conf('HIGH_CPU_LOAD') == 1) {
			return common()->server_is_busy();
		}
		// Convert id into page
		if (isset($_GET["id"])) {
			$_GET["page"] = $_GET["id"];
			unset($_GET["id"]);
		}
		list($total) = db()->query_fetch("SELECT COUNT(*) AS `0` FROM `".db('online')."`");
		// Connect pager
		$sql = "SELECT * FROM `".db('online')."` WHERE `user_id` !=0 AND `type` != 'admin' ORDER BY `time` DESC";
		$url = "./?object=".$_GET["object"]."&action=".$_GET["action"];
		list($add_sql, $pages, $num_members) = common()->divide_pages($sql, $url, null, $this->ONLINE_PER_PAGE);
		// Get number of guests
		$num_guests = $total - $num_members;
		// Get full data
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$online_users[$A["id"]] = $A;
			if (!empty($A["user_id"])) {
				$users_ids[$A["user_id"]] = $A["user_id"];
			}
		}
		// Get users infos
		if (!empty($users_ids)) {
			$users_array = user($users_ids, array("login","nick","photo_verified"), array("WHERE" => array("active" => 1)));
		}
		// Process online users
		foreach ((array)$online_users as $_sess_id => $_info) {
			$cur_user_info = !empty($_info["user_id"]) ? $users_array[$_info["user_id"]] : array();
			// Prepare template
			$replace2 = array(
				"bg_class"			=> !(++$i % 2) ? "bg1" : "bg2",
				"user_id"			=> $cur_user_info["id"],
				"user_name"			=> $_info["user_id"] ? _prepare_html(_display_name($cur_user_info)) : "",
				"user_avatar"		=> $_info["user_id"] ? _show_avatar($cur_user_info["id"], $cur_user_info, 1) : "",
				"user_details_link"	=> $_info["user_id"] ? _profile_link($cur_user_info["id"]) : "",
				"need_div"			=> !(++$c2 % $this->ONLINE_PER_LINE) ? 1 : 0,
			);
			$items .= tpl()->parse($_GET["object"]."/online_item", $replace2);
		}
		// Prepare template
		$replace = array(
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"num_guests"	=> intval($num_guests),
			"num_members"	=> intval($num_members),
		);
		return tpl()->parse($_GET["object"]."/online_main", $replace);
	}

	/**
	* Quick users stats
	*/
	function quick_users_stats () {
		$sql = "(SELECT COUNT(*) AS `total` FROM `".db('user')."` WHERE `group`=2) 
			UNION ALL 
				(SELECT COUNT(*) AS `total` FROM `".db('user')."` WHERE `group`=3)
			UNION ALL 
				(SELECT COUNT(*) AS `total` FROM `".db('user')."` WHERE `group`=4)";
		$data = db()->query_fetch_all($sql);
		$num_hobbyists	= $data[0]["total"];
		$num_escorts	= $data[1]["total"];
		$num_agencies	= $data[2]["total"];
		$total_members = $num_hobbyists + $num_escorts + $num_agencies;
		// Prepare template
		$replace = array(
			"total_members"	=> intval($total_members),
			"num_hobbyists"	=> intval($num_hobbyists),
			"num_escorts"	=> intval($num_escorts),
			"num_agencies"	=> intval($num_agencies),
		);
		return tpl()->parse($_GET["object"]."/quick_users_stats", $replace);
	}

	/**
	* Quick users stats
	*/
	function last_24_stats () {
//		$time_start = time() - 3600 * 24;
		$time_start = time() - 3600 * 24 * 365;
		$time_end	= time();
		// Get hosts
//		list($total_hosts) = db()->query_fetch("SELECT COUNT(`id`) AS `0` FROM `".db('log_exec')."` WHERE `date` >= ".intval($time_start)." AND `date` < ".intval($time_end)." GROUP BY `user_id`");
		list($total_hosts) = db()->query_fetch("SELECT COUNT(*) AS `0` FROM (SELECT COUNT(`id`) AS `0` FROM `".db('log_exec')."` WHERE `date` >= ".intval($time_start)/*." AND `date` < ".intval($time_end)*/." GROUP BY `ip`) AS `tmp`");
		// Get hits
		$sql_1 = "SELECT COUNT(`id`) AS `hits` FROM `".db('log_exec')."` WHERE `date` >= ".intval($time_start)/*." AND `date` < ".intval($time_end).""*/;
		$sql = "(".$sql_1.") 
					UNION ALL 
				(".$sql_1." AND `user_group`=2) 
					UNION ALL 
				(".$sql_1." AND `user_group`=3) 
					UNION ALL 
				(".$sql_1." AND `user_group`=4)";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$data[] = $A["hits"];
		}
		$total_hits		= $data[0];
		$hits_hobbyists	= $data[1];
		$hits_escorts	= $data[2];
		$hits_agencies	= $data[3];
		$hits_guests	= $total_hits - ($hits_hobbyists + $hits_escorts + $hits_agencies);
		// Prepare template
		$replace = array(
			"total_hosts"	=> intval($total_hosts),
			"total_hits"	=> intval($total_hits),
			"hits_guests"	=> intval($hits_guests),
			"hits_hobbyists"=> intval($hits_hobbyists),
			"hits_escorts"	=> intval($hits_escorts),
			"hits_agencies"	=> intval($hits_agencies),
		);
		return tpl()->parse($_GET["object"]."/last_24_stats", $replace);
	}

	/**
	* 
	*/
	function most_active () {
		return __FUNCTION__;
	}

	/**
	* 
	*/
	function last_updated () {
		return __FUNCTION__;
	}
}
