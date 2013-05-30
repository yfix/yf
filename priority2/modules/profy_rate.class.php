<?php

/**
* Ratings handler
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_rate {

	/** @var int Number of stars to use */
	var $NUM_STARS		= 5;
	/** @var bool Global switch on/off */
	var $ENABLE_RATINGS	= 1;
	/** @var int Vote repeat TTL (duration), in seconds */
//	var $VOTE_DURATION	= 3600;
	var $VOTE_DURATION	= 10;

	/**
	* Framework constructor
	*/
	function _init () {
		define("RATE_CLASS_NAME", "rate");
	}

	/**
	* Default method
	*/
	function show () {
		return "rate stats wiil be here";
// TODO
	}

	/**
	* Show stats item
	*/
	function _show_stats_item ($info_array = array()) {
// TODO
	}

	/**
	* Traditional vote method
	*/
	function vote () {
// TODO
	}

	/**
	* Get rates infos array for given object_id's
	*/
	function _get_rates_info ($object_name = "", $object_ids = array()) {
		if (isset($objects_ids[""])) {
			unset($objects_ids[""]);
		}
		if (empty($object_name) || !is_array($objects_ids) || empty($objects_ids)) {
			return false;
		}
		// Get info from db
		$Q = db()->query("SELECT * FROM `".db('rates')."` WHERE `object_name`='"._es($object_name)."' AND `object_id` IN(".implode(",", $objects_ids).")");
		while ($A = db()->fetch_assoc($Q)) $rate_infos[$A["object_id"]] = $A;
		// Return result data
		return $rate_infos;
	}

	/**
	* Get denied to vote rates for the given user id
	*/
	function _get_user_denied_rate_ids ($user_id = "", $rates_ids = array()) {
		$denied_rate_ids = array();
		// Get info from db
		$Q = db()->query("SELECT `rate_id` FROM `".db('rate_votes')."` WHERE `user_id`=".intval($user_id)." AND `add_date`>".intval(time() - $this->VOTE_DURATION)." ".(!empty($rates_ids) ? " AND `rate_id` IN(".implode(",", $rates_ids).")" : ""));
		while ($A = db()->fetch_assoc($Q)) $denied_rate_ids[$A["rate_id"]] = $A["rate_id"];
		// Return result data
		return $denied_rate_ids;
	}

	/**
	* Special for the AJAX method (do not call directly!)
	*/
	function do_vote () {
		main()->NO_GRAPHICS = true;
		if (!$this->ENABLE_RATINGS) {
			return false;
		}
		// Check input ID
		$RATE_ID	= intval($_REQUEST["post_id"]);
		$VOTE_VALUE	= intval($_REQUEST["rate_value"]);
		// Try to get rate info (it need to be created before to prevent flooding)
		if (!empty($RATE_ID)) {
			$RATE_INFO = db()->query_fetch("SELECT * FROM `".db('rates')."` WHERE `id`=".intval($RATE_ID)." AND `active`='1'");
		}
		// Last check for the vote record
		if (empty($RATE_INFO["id"])) {
			return false;
		}
		// Check if vote allowed
		$ALLOW_VOTE = false;
		// Check if user is allowed to make vote here and now
		if ($this->USER_ID) {
			$num_latest_votes = db()->query_num_rows("SELECT `id` FROM `".db('rate_votes')."` WHERE `rate_id`=".intval($RATE_INFO["id"])." AND `user_id`=".intval($this->USER_ID)." AND `add_date`>".intval(time() - $this->VOTE_DURATION));
			if (empty($num_latest_votes)) {
				$ALLOW_VOTE = true;
			}
		}
		// DO check and save vote if params are ok
		if ($ALLOW_VOTE) {
			// Save vote
			db()->INSERT("rate_votes", array(
				"user_id"	=> intval($this->USER_ID),
				"rate_id"	=> intval($RATE_ID),
				"value"		=> intval($VOTE_VALUE),
				"add_date"	=> time(),
				"ip"		=> _es(common()->get_ip()),
			));
			// Update main rates table
			db()->query(
				"UPDATE `".db('rates')."` SET 
					`num_votes`		= `num_votes` + 1, 
					`votes_sum`		= `votes_sum` + ".intval($VOTE_VALUE).", 
					`last_vote_date`= ".time()."
				WHERE `id`=".intval($RATE_ID)
			);
			// Update array info
			$RATE_INFO["num_votes"] += 1;
			$RATE_INFO["votes_sum"] += intval($VOTE_VALUE);
		}
		// To prevent returning js again
		$this->_num_calls++;
		// Display images again
		$params = array(
			"object_name"	=> $RATE_INFO["object_name"],
			"object_id"		=> $RATE_INFO["object_id"],
			"rate_info"		=> $RATE_INFO,
			"force_no_vote"	=> 1,
		);
		echo $this->_show_for_object($params);
	}

	/**
	* Display box for given object
	*/
	function _show_for_object ($params = array()) {
		if (!$this->ENABLE_RATINGS) {
			return false;
		}
		// Prepare input params
		$OBJECT_NAME		= !empty($params["object_name"]) ? $params["object_name"] : $_GET["object"];
		$OBJECT_ID			= intval(!empty($params["object_id"]) ? $params["object_id"] : $_GET["id"]);
		$OWNER_USER_ID		= intval(!empty($params["rate_info"]) ? $params["rate_info"]["owner_id"] : $params["owner_id"]);
		$FORCE_DENY_VOTING	= $params['force_no_vote'];
		// Get cur rate info
		if (!empty($params["rate_info"])) {
			$RATE_INFO	= $params["rate_info"];
		} elseif (isset($GLOBALS['_RATE_INFOS_CACHE'][$OBJECT_NAME][$OBJECT_ID])) {
			$RATE_INFO	= $GLOBALS['_RATE_INFOS_CACHE'][$OBJECT_NAME][$OBJECT_ID];
		} elseif (!empty($OBJECT_ID) && !empty($OBJECT_NAME)) {
			// Try to get rate info
			$RATE_INFO = db()->query_fetch("SELECT * FROM `".db('rates')."` WHERE `object_name`='"._es($OBJECT_NAME)."' AND `object_id`=".intval($OBJECT_ID));
			// Do create rate info record
			if (empty($RATE_INFO)) {
				$RATE_INFO = array(
					"object_id"		=> intval($OBJECT_ID),
					"object_name"	=> _es($OBJECT_NAME),
					"owner_id"		=> intval($OWNER_USER_ID),
					"add_date"		=> time(),
					"last_vote_date"=> 0,
					"num_votes"		=> 0,
					"votes_sum"		=> 0,
					"active"		=> 1,
				);
				db()->INSERT("rates", $RATE_INFO);
				// Get new record id
				$RATE_INFO['id'] = db()->INSERT_ID();
			}
		}
		// Last check for the vote record
		if (empty($RATE_INFO['id'])) {
			return false;
		}
		$ALLOW_VOTE = false;
		// Check if user is allowed to make vote here and now
		if ($this->USER_ID) {
			// Try ot get from cache first
			if (isset($GLOBALS['_RATE_LATEST_VOTES'][$OBJECT_NAME][$RATE_INFO["id"]])) {
				$num_latest_votes = $GLOBALS['_RATE_LATEST_VOTES'][$OBJECT_NAME][$RATE_INFO["id"]];
			} else {
				$num_latest_votes = db()->query_num_rows("SELECT `id` FROM `".db('rate_votes')."` WHERE `rate_id`=".intval($RATE_INFO["id"])." AND `user_id`=".intval($this->USER_ID)." AND `add_date`>".intval(time() - $this->VOTE_DURATION));
			}
			if (empty($num_latest_votes)) {
				$ALLOW_VOTE = true;
			}
		}
		// Force deny voting here (e.g. immediatelly after saving vote)
		if (!empty($FORCE_DENY_VOTING)) {
			$ALLOW_VOTE = false;
		}
		// No votes done
		$VOTES_SUM		= intval($RATE_INFO["votes_sum"]);
		$NUM_VOTES		= intval($RATE_INFO["num_votes"]);
		$CUR_RATE_VALUE	= $NUM_VOTES > 0 ? round($VOTES_SUM / $NUM_VOTES, 1) : 0;
		// To prevent loading rating js code twice
		$this->_num_calls++;
		// Process template
		$replace = array(
			"object_id"			=> intval($OBJECT_ID),
			"object_name"		=> _prepare_html($OBJECT_NAME),
			"rate_id"			=> intval($RATE_INFO["id"]),
			"total_votes"		=> intval($NUM_VOTES),
			"cur_rate"			=> round($CUR_RATE_VALUE, 1),
			"max_rate"			=> intval($this->NUM_STARS),
			"stars"				=> $this->_show_rate_stars($CUR_RATE_VALUE),
			"total_stars"		=> intval($this->NUM_STARS),
			"do_vote_url"		=> process_url("./?object=".RATE_CLASS_NAME."&action=do_vote"),
			"allow_vote"		=> intval((bool)$ALLOW_VOTE),
			"display_js"		=> intval($this->_num_calls == 1),
			"rate_images_url"	=> WEB_PATH."js/profy_rate/images/",
		);
		return tpl()->parse(RATE_CLASS_NAME."/vote_box", $replace);
	}

	/**
	* Show stars for the current rate
	*/
	function _show_rate_stars ($CUR_RATE = 0) {
		// Prepare stars
		for ($i = 0; $i < $this->NUM_STARS; $i++) {
			$cur_img_name = "off";
			if (($i + 1) <= $CUR_RATE) {
				$cur_img_name = "on";
			} elseif ($CUR_RATE >= ($i + 0.5)) {
				$cur_img_name = "half";
			}
			$STARS_ARRAY[] = array(
				"number"		=> $i + 1,
				"cur_image"		=> "rating_".$cur_img_name,
				"insert_half"	=> $cur_img_name == "half" ? ($i + 1) : 0,
			);
		}
		return $STARS_ARRAY;
	}

	/**
	* Prefetch rate infos (out them into cahe to avoid many queries)
	*/
	function _prefetch_rate_infos ($params = array()) {
		if (!$this->ENABLE_RATINGS) {
			return false;
		}
		// Prepare input params
		$OBJECT_NAME	= !empty($params["object_name"])	? $params["object_name"] : $_GET["object"];
		$OBJECTS_IDS	= !empty($params["objects_ids"])	? $params["objects_ids"] : array();
		$OWNERS_IDS		= !empty($params["owners_ids"])		? $params["owners_ids"] : array();
		// Objects ids and their owners is required
		if (empty($OBJECT_NAME) || empty($OBJECTS_IDS) || empty($OWNERS_IDS)) {
			return false;
		}
		// Get rates infos
		$Q = db()->query(
			"SELECT * 
			FROM `".db('rates')."` 
			WHERE `object_name`='"._es($OBJECT_NAME)."' 
				AND `object_id` IN(".implode(",", $OBJECTS_IDS).")"
		);
		while ($A = db()->fetch_assoc($Q)) {
			$infos[$A["object_id"]] = $A;
		}
		// Create missing infos
		foreach ((array)$OBJECTS_IDS as $_id) {
			if (!empty($infos[$_id])) {
				continue;
			}
			// Do create rate info record
			$RATE_INFO = array(
				"object_id"		=> intval($_id),
				"object_name"	=> _es($OBJECT_NAME),
				"owner_id"		=> intval($OWNERS_IDS[$_id]),
				"add_date"		=> time(),
				"last_vote_date"=> 0,
				"num_votes"		=> 0,
				"votes_sum"		=> 0,
				"active"		=> 1,
			);
			db()->INSERT("rates", $RATE_INFO);
			// Get new record id
			$RATE_INFO['id'] = db()->INSERT_ID();

			$infos[$_id] = $RATE_INFO;
		}
		// Store in global cache
		$GLOBALS['_RATE_INFOS_CACHE'][$OBJECT_NAME] = $infos;
		// Check if user is allowed to make vote here and now
		if ($this->USER_ID) {
			// Collect rates ids
			foreach ((array)$infos as $_info) {
				$rates_ids[$_info["id"]] = $_info["id"];
				$num_latest_votes[$_info["id"]] = 0;
			}
			if (empty($rates_ids)) {
				return false;
			}
			// Try to override num latest votes
			$Q = db()->query(
				"SELECT `rate_id`, COUNT(*) AS `num`
				FROM `".db('rate_votes')."` 
				WHERE `user_id`=".intval($this->USER_ID)." 
					AND `rate_id` IN(".implode(",", $rates_ids).")
					AND `add_date` > ".intval(time() - $this->VOTE_DURATION)."
				GROUP BY `rate_id`"
			);
			while ($A = db()->fetch_assoc($Q)) {
				$num_latest_votes[$A["rate_id"]] = $A["num"];
			}
			// Store in global cache
			$GLOBALS['_RATE_LATEST_VOTES'][$OBJECT_NAME] = $num_latest_votes;
		}
	}
}
