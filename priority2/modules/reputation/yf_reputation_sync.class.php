<?php

/**
* Reputation syncronization
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_reputation_sync {

	/**
	* Constructor
	*/
	function yf_reputation_sync () {
		// Reference to the parent object
		$this->REPUT_OBJ	= module(REPUT_CLASS_NAME);
	}
	
	/**
	* Do cleanup
	*/
	function _do_cron_job() {
		// First fix "counted" field
		db()->query("UPDATE `".db('reput_user_votes')."` SET `counted` = `voted` WHERE `counted` = 0 AND `voted` != 0");
		// fix negative alt power
		db()->query("UPDATE `".db('reput_total')."` SET `alt_power` = 0 WHERE `alt_power` < 0");
		// Set initial value for reputation
		db()->query("UPDATE `".db('reput_total')."` SET `points` = ".intval($this->REPUT_OBJ->START_REPUT));
		// Set initial value for activity
		db()->query("UPDATE `".db('activity_total')."` SET `points` = 0");

		// create activity sum SQL
		$ACTIVITY_OBJ = main()->init_class("activity");
		$sql_array = $ACTIVITY_OBJ->_sql_arrays_for_update(true);

		// Prepare temporary table
		$tmp_table_name = db()->_get_unique_tmp_table_name();
		db()->query(
			"CREATE TEMPORARY TABLE `".$tmp_table_name."` ( 
				`user_id`	int(10) unsigned NOT NULL, 
				`asum`		int(10) NOT NULL, 
				PRIMARY KEY (`user_id`)
			)"
		);

		// Do count activity
		foreach ((array)$sql_array as $_name => $_sql_select) {
			db()->query(
				"INSERT INTO `".$tmp_table_name."` ".$_sql_select
			);
			db()->query(
				"UPDATE `".db('activity_total')."` AS t1, 
						`".$tmp_table_name."` AS t2 
				SET t1.`points` = t1.`points` + t2.`asum`
				WHERE t1.`user_id` = t2.`user_id`"
			);
			db()->query("TRUNCATE TABLE `".$tmp_table_name."`");
		}
		// Drop temp table
		db()->query("DROP TEMPORARY TABLE `".$tmp_table_name."`");
		// Prepare temporary table
		$tmp_table_name = db()->_get_unique_tmp_table_name();
		db()->query(
			"CREATE TEMPORARY TABLE `".$tmp_table_name."` ( 
				`user_id`	int(10) unsigned NOT NULL, 
				`value`		int(10) NOT NULL, 
				PRIMARY KEY (`user_id`)
			)"
		);
		// Count reputation points
		db()->query(
			"INSERT INTO `".$tmp_table_name."`  
				SELECT `target_user_id`, SUM(`counted`) + ".intval($this->REPUT_OBJ->START_REPUT)." 
				FROM `".db('reput_user_votes')."` 
				GROUP BY `target_user_id`"
		);
		db()->query(
			"UPDATE `".db('reput_total')."` AS `t`, 
					`".$tmp_table_name."` AS `tmp` 
				SET `t`.`points` = `tmp` .`value` 
			WHERE `t`.`user_id` = `tmp`.`user_id`"
		);
		db()->query("TRUNCATE TABLE `".$tmp_table_name."`");
		// Count num_votes
		db()->query(
			"INSERT INTO `".$tmp_table_name."`  
				SELECT `user_id`, COUNT(*) 
				FROM `".db('reput_user_votes')."` 
				GROUP BY `user_id`"
		);
		db()->query(
			"UPDATE `".db('reput_total')."` AS `t`, 
					`".$tmp_table_name."` AS `tmp` 
				SET `t`.`num_votes` = `tmp` .`value` 
			WHERE `t`.`user_id` = `tmp`.`user_id`"
		);
		db()->query("TRUNCATE TABLE `".$tmp_table_name."`");
		// Count num_voted
		db()->query(
			"INSERT INTO `".$tmp_table_name."`  
				SELECT `target_user_id`, COUNT(*) 
				FROM `".db('reput_user_votes')."` 
				GROUP BY `target_user_id`"
		);
		db()->query(
			"UPDATE `".db('reput_total')."` AS `t`, 
					`".$tmp_table_name."` AS `tmp` 
				SET `t`.`num_voted` = `tmp` .`value` 
			WHERE `t`.`user_id` = `tmp`.`user_id`"
		);
		db()->query("TRUNCATE TABLE `".$tmp_table_name."`");
		// Update reputation altering power
		db()->query(
			"UPDATE `".db('reput_total')."` AS r, 
					`".db('activity_total')."` AS a 
				SET r.alt_power = ( 
					".intval($this->REPUT_OBJ->START_REPUT_ALT)." 
					+ (CASE WHEN 
							FLOOR(LOG(a.`points` / ".intval($this->REPUT_OBJ->ACT_REPUT_VALUE).")) > 0 
						THEN FLOOR(LOG(a.`points` / ".intval($this->REPUT_OBJ->ACT_REPUT_VALUE).")) 
						ELSE 0 
					END) 
					+ (CASE 
						WHEN 
							FLOOR(r.`points` / ".intval($this->REPUT_OBJ->REPUT_ADD_ALT_POINTS).") > 0 
						THEN FLOOR(r.`points` / ".intval($this->REPUT_OBJ->REPUT_ADD_ALT_POINTS).")
						ELSE 0 
					END) 
				) 
				WHERE r.`user_id` = a.`user_id`"
		);
		// Drop temp table
		db()->query("DROP TEMPORARY TABLE `".$tmp_table_name."`");
	}
}
