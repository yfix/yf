
/* Get ads with non-existed users */
SELECT * 
FROM `t_ads` AS `t1` 
WHERE NOT EXISTS (
	SELECT `id` 
	FROM `t_user` AS `t2` 
	WHERE `t2`.`id` = `t1`.`user_id` 
);
/**************************************************/

/* Average activity */
SELECT AVG(`act`.`points`) 
FROM `t_activity_total` AS `act`, 
	`t_ads` AS `ads` 
WHERE `act`.`user_id` = `ads`.`user_id` 
	AND `ads`.`status` = 'active';

/* Average reputation */
SELECT AVG(`r`.`points`) 
FROM `t_reput_total` AS `r`, 
	`t_ads` AS `ads` 
WHERE `r`.`user_id` = `ads`.`user_id` 
	AND `ads`.`status` = 'active';


/**************************************************/
SELECT (@avg_act:=(
		SELECT AVG(`act`.`points`) 
		FROM `t_activity_total` AS `act`,
			`t_ads` AS `ads` 
		WHERE `act`.`user_id` = `ads`.`user_id` 
			AND `ads`.`status` = 'active'
	)) AS `avg_act`, 
	(@avg_reput:=(
		SELECT AVG(`r`.`points`) 
		FROM `t_reput_total` AS `r`,
			`t_ads` AS `ads` 
		WHERE `r`.`user_id` = `ads`.`user_id` 
			AND `ads`.`status` = 'active'
	)) AS `avg_reput`



/**************************************************/
SELECT `ads`.`ad_id`, 
	`c`.`name` AS `ad_cat_name`, 
	`c`.`country_code` AS `ad_country_code`, 
	`c`.`state_code` AS `ad_state_code`, 
	`u`.`country` AS `user_country`, 
	`u`.`state` AS `user_state` /*IF()*/ 
FROM `t_ads` AS `ads`, 
	`t_user` AS `u`, 
	`t_category` AS `c` 
WHERE `u`.`id` = `ads`.`user_id`
	AND `ads`.`cat_id` = `c`.`id`
/*	AND `ads`.`status` = 'active'*/;


/**************************************************/
SELECT `ads`.`ad_id`, 
	`act`.`points` AS `user_activity`, 
	`reput`.`points` AS `user_reput`, 
	`c`.`name` AS `ad_cat_name`, 
	`c`.`country_code` AS `ad_country_code`, 
	`u`.`country` AS `user_country`, 
	`c`.`state_code` AS `ad_state_code`, 
	`u`.`state` AS `user_state`, 
	(CASE WHEN `c`.`state_code` != '' AND (`c`.`state_code`=`u`.`state`) THEN 1 ELSE 0 END) AS `s_e`,
	(CASE WHEN `c`.`country_code` != '' AND (`c`.`country_code`=`u`.`country` OR `c`.`name`=`u`.`country`) THEN 1 ELSE 0 END) AS `c_e`
FROM `t_ads` AS `ads`, 
	`t_user` AS `u`, 
	`t_category` AS `c`,
	`t_activity_total` AS `act`,
	`t_reput_total` AS `reput` 
WHERE `u`.`id` = `ads`.`user_id`
	AND `act`.`user_id` = `ads`.`user_id`
	AND `reput`.`user_id` = `ads`.`user_id`
	AND `ads`.`cat_id` = `c`.`id`
	AND `ads`.`status` = 'active';


/**************************************************/
/* FULL VERSION (FOR DEBUGGING )*/
SELECT 
	(@avg_act:=(
		SELECT AVG(`act`.`points`) 
		FROM `t_activity_total` AS `act`, `t_ads` AS `ads` 
		WHERE `act`.`user_id` = `ads`.`user_id` AND `ads`.`status` = 'active'
	)) AS `avg_act`
	, (@avg_reput:=(
		SELECT AVG(`r`.`points`) 
		FROM `t_reput_total` AS `r`,	`t_ads` AS `ads` 
		WHERE `r`.`user_id` = `ads`.`user_id` AND `ads`.`status` = 'active'
	)) AS `avg_reput`
	, (@cur_pos:=
		(CASE WHEN 
			(CASE WHEN `c`.`state_code`=`u`.`state` THEN 1 ELSE 0 END) = 0 
			OR (CASE WHEN `c`.`country_code` != '' AND (`c`.`country_code`=`u`.`country` OR `c`.`name`=`u`.`country`) THEN 1 ELSE 0 END) = 0 
		THEN (1 - 1/(IFNULL(`act`.`points`,0) + IFNULL(`reput`.`points`,0) * IFNULL(@avg_act,0) / IFNULL(@avg_reput,0)))
		ELSE (IFNULL(`act`.`points`,0) + IFNULL(`reput`.`points`,0) * IFNULL(@avg_act,0) / IFNULL(@avg_reput,0))
		END)
	) AS `pos1`
	, ROUND(IFNULL(@cur_pos, 1) * 1000, 0) AS `cur_pos`
	, `act`.`points` AS `user_activity`
	, `reput`.`points` AS `user_reput`
	, `ads`.`ad_id`
	, `c`.`name` AS `ad_cat_name`
	, `c`.`country_code` AS `ad_country_code`
	, `u`.`country` AS `user_country`
	, `c`.`state_code` AS `ad_state_code`
	, `u`.`state` AS `user_state`
FROM `t_ads` AS `ads`
LEFT JOIN `t_user` AS `u` ON `ads`.`user_id` = `u`.`id`
LEFT JOIN `t_category` AS `c`  ON `ads`.`cat_id` = `c`.`id`
LEFT JOIN `t_activity_total` AS `act` ON `ads`.`user_id` = `act`.`user_id`
LEFT JOIN `t_reput_total` AS `reput` ON `ads`.`user_id` = `reput`.`user_id`
WHERE `ads`.`status` = 'active'
LIMIT 50;



/**************************************************/
/* COMPACT VERSION (WORK) */
CREATE TEMPORARY TABLE `t_123456` (
	`ad_id`	int(10) unsigned NOT NULL, 
	`rnd`	int(10) NOT NULL, 
	PRIMARY KEY (`ad_id`)
);

SET @avg_act=(
	SELECT AVG(`act`.`points`) 
	FROM `t_activity_total` AS `act`, `t_ads` AS `ads` 
	WHERE `act`.`user_id` = `ads`.`user_id` AND `ads`.`status` = 'active'
);
SET @avg_reput=(
	SELECT AVG(`r`.`points`) 
	FROM `t_reput_total` AS `r`,	`t_ads` AS `ads` 
	WHERE `r`.`user_id` = `ads`.`user_id` AND `ads`.`status` = 'active'
);

INSERT INTO `t_123456` (`ad_id`, `rnd`)
	SELECT 
		`ads`.`ad_id`
		, (@cur_pos:=ROUND(IFNULL(
			(CASE WHEN 
				(CASE WHEN `c`.`state_code`=`u`.`state` THEN 1 ELSE 0 END) = 0 
				OR (CASE WHEN `c`.`country_code` != '' AND (`c`.`country_code`=`u`.`country` OR `c`.`name`=`u`.`country`) THEN 1 ELSE 0 END) = 0 
			THEN (1 - 1/(IFNULL(`act`.`points`,0) + IFNULL(`reput`.`points`,0) * IFNULL(@avg_act,0) / IFNULL(@avg_reput,0)))
			ELSE (IFNULL(`act`.`points`,0) + IFNULL(`reput`.`points`,0) * IFNULL(@avg_act,0) / IFNULL(@avg_reput,0))
			END), 0) * 1000, 0)
		) AS `cur_pos`
	FROM `t_ads` AS `ads`
	LEFT JOIN `t_user` AS `u` ON `ads`.`user_id` = `u`.`id`
	LEFT JOIN `t_category` AS `c`  ON `ads`.`cat_id` = `c`.`id`
	LEFT JOIN `t_activity_total` AS `act` ON `ads`.`user_id` = `act`.`user_id`
	LEFT JOIN `t_reput_total` AS `reput` ON `ads`.`user_id` = `reput`.`user_id`
	WHERE `ads`.`status` = 'active';

UPDATE `t_ads` SET `rnd` = 0;

UPDATE `t_ads` AS `ads`, 
	`t_123456` AS `tmp` 
SET `ads`.`rnd` = `tmp`.`rnd`
WHERE `ads`.`ad_id` = `tmp`.`ad_id`;

DROP TEMPORARY TABLE `t_123456`;
