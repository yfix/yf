<?php

/**
* Check if given accounts has "multi_account" intersection
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_multi_accounts {

	/** @var bool Check multi-account cookie match when voting */
	var $CHECK_COOKIE_MATCH		= true;
	/** @var bool Check multi-IPs match when voting */
	var $CHECK_MULTI_IPS		= true;
	/** @var int Multi-IPs match TTL, days (set to 0 to disable) */
	var $MULTI_IP_TTL			= 30;

	/**
	* Multi-accounts checks
	*/
	function _check ($target_user_id = 0, $source_user_id = 0) {
		if (empty($source_user_id) && !empty($this->USER_ID)) {
			$source_user_id = $this->USER_ID;
		}
		if (empty($target_user_id) || empty($source_user_id)) {
			return false;
		}
		// Check if we need to do something here
		if (empty($this->CHECK_COOKIE_MATCH) && empty($this->CHECK_MULTI_IPS)) {
			return false;
		}
		$MULTI_ACCOUNT_FOUND = false;
		$users_ids = array(
			$source_user_id	=> $source_user_id,
			$target_user_id => $target_user_id,
		);
		// Try to get multi-accounts infos
		$Q = db()->query(
			"SELECT `user_id`, `ip_match`, `cookie_match`, `matching_users` 
			FROM `".db('check_multi_accounts')."` 
			WHERE `user_id` IN(".implode(",",$users_ids).")"
			// Here we check fast if more detailed investigation needed
			.(" AND (`ip_match` = '1' OR `cookie_match` = '1') ")
		);
		while ($A = db()->fetch_assoc($Q)) {
			$multi_infos[$A["user_id"]] = $A;
		}
		$voter_info		= $multi_infos[$source_user_id];
		$target_info	= $multi_infos[$target_user_id];
		unset($multi_infos);
		// Try to find cookie match between target and voter
		if ($this->CHECK_COOKIE_MATCH) {
			if (!empty($voter_info) && !empty($voter_info["matching_users"]) 
				&& in_array($target_user_id, explode(",", $voter_info["matching_users"]))
			) {
				$MULTI_ACCOUNT_FOUND = true;
			}
			if (!empty($target_info) && !empty($target_info["matching_users"]) 
				&& in_array($source_user_id, explode(",", $target_info["matching_users"]))
			) {
				$MULTI_ACCOUNT_FOUND = true;
			}
		}
		// Try to find IPs match between target and voter
		if ($this->CHECK_MULTI_IPS && !$MULTI_ACCOUNT_FOUND 
			&& ($voter_info["ip_match"] || $target_info["ip_match"])
		) {
			$Q = db()->query(
				"SELECT 
					COUNT(DISTINCT(`ip`)) AS `multi_ips`
					, `user_id`
					, CAST(GROUP_CONCAT(DISTINCT `ip` ORDER BY `ip` ASC) AS CHAR) AS `ips_list`
				FROM `".db('log_auth')."` 
				WHERE `user_id` IN (".implode(",",$users_ids).") 
					".($this->MULTI_IP_TTL ? " AND `date` >= ".intval(time() - $this->MULTI_IP_TTL * 86400) : "")."
				GROUP BY `user_id` 
				HAVING `multi_ips` > 1 
				ORDER BY `multi_ips` DESC"
			);
			while ($A = db()->fetch_assoc($Q)) {
				$last_multi_ips[$A["user_id"]] = explode(",", $A["ips_list"]);
			}
			// Try to find same IPs
			if (array_intersect(
				(array)$last_multi_ips[$source_user_id], 
				(array)$last_multi_ips[$target_user_id])
			) {
				$MULTI_ACCOUNT_FOUND = true;
			}
		}
/*
		// Raise error message if we found multi-account
		if ($MULTI_ACCOUNT_FOUND) {
			common()->_raise_error(t("Sorry, your vote seems suspicious to our anti-cheat filter and can't be counted!"));
		}
*/
		return $MULTI_ACCOUNT_FOUND;
	}
}
