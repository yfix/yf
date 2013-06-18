/*
SET @cur_time:=1187706029
	, @start_project_time:=1170799200
	, @avg_act:=2.786085825
	, @avg_reput:=10.093282206
;
*/

SET @cur_time:=UNIX_TIMESTAMP();
SET @start_project_time:=1170799200;
SET @avg_act:=(
	SELECT AVG(`act`.`points`) 
	FROM `t_activity_total` AS `act`
		, `t_ads` AS `ads` 
	WHERE `act`.`user_id` = `ads`.`user_id` 
/*		AND `ads`.`status` = 'active'*/
);
SET @avg_reput:=(
	SELECT AVG(`r`.`points`) 
	FROM `t_reput_total` AS `r`
		, `t_ads` AS `ads` 
	WHERE `r`.`user_id` = `ads`.`user_id` 
/*		AND `ads`.`status` = 'active'*/
);

SELECT `ads`.`user_id`
	, `ads`.`ad_id` 

	, @daily_coef := ROUND(IFNULL(
		(@cur_time - (
			CASE WHEN `u`.`add_date` < @start_project_time 
			THEN @start_project_time 
			ELSE `u`.`add_date` END
		)) / 86400
 / 30
		, 1), 0
	) AS `daily_coef`

	, @same_country := (
		CASE WHEN `c`.`country_code` != '' 
			AND (`c`.`country_code`=`u`.`country` AND `c`.`name`=`u`.`country`) 
		THEN 1 ELSE 0 END
	) AS `same_country` 

	, @same_location := (
		CASE WHEN `c`.`state_code`=`u`.`state` AND @same_country = 1
		THEN 1 ELSE 0 END
	) AS `same_location`

	, ROUND(IFNULL( 
			(CASE WHEN @same_location THEN 
				(`act`.`points` + `reput`.`points` * @avg_act / @avg_reput)
			ELSE 
				(1 - 1 / (`act`.`points` + `reput`.`points` * @avg_act / @avg_reput))
			END)
			,0) * 1000 / @daily_coef
		,0) AS `cur_pos`

	, `ads`.`subject` 
	, `act`.`points` AS `a_points`
	, `reput`.`points` AS `r_points`
	, FROM_UNIXTIME(`u`.`add_date`, '%Y-%m-%d') AS `user_add_date`

	, ROUND(@avg_act, 2) AS `avg_act`
	, ROUND(@avg_reput, 2) AS `avg_reput`

	, ROUND(`act`.`points` + `reput`.`points` * @avg_act / @avg_reput, 2) AS `case1`
	, ROUND(1 - 1 / (`act`.`points` + `reput`.`points` * @avg_act / @avg_reput), 2) AS `case2`

FROM `t_ads` AS `ads` 
LEFT JOIN `t_user` AS `u` 
	ON `ads`.`user_id` = `u`.`id` 
LEFT JOIN `t_category` AS `c` 
	ON `ads`.`cat_id` = `c`.`id` 
LEFT JOIN `t_activity_total` AS `act` 
	ON `ads`.`user_id` = `act`.`user_id` 
LEFT JOIN `t_reput_total` AS `reput` 
	ON `ads`.`user_id` = `reput`.`user_id`
WHERE 1=1
	AND `ads`.`cat_id` =158
	AND `u`.`sex` = 'male'
/*	AND `ads`.`status` = 'active'*/

ORDER BY `cur_pos` DESC
LIMIT 150
;





SELECT `ad_id` 
	, `cat_id` 
	, `user_id` 
	, `sex` 
	, `subject` 
	, `descript` 
	, `cat_priority` 
	, `same_country` 
	, `rnd` 
FROM `t_ads` 
WHERE 1 =1
AND `cat_id` =158
AND `user_id` 
IN (
	SELECT `id` 
	FROM `t_user` 
	WHERE `id` !=0
	AND `sex` = 'male'
)
ORDER BY `cat_priority` DESC 
	, `same_country` DESC 
	, `rnd` DESC 
LIMIT 0 , 25;




/*
SELECT `u`.`id` , 
	@date_coef := IFNULL(
		(1188304195 - (CASE WHEN `u`.`add_date` < 1171490400 THEN 1171490400 ELSE `u`.`add_date` END)) 
		* 1
		/ 86400
	, 1) AS `date_coef`
	, ROUND( IFNULL((
		`act`.`points` 
			* 1
		+ (`reput`.`points` - 10) 
			* 10 
		* 13.728571428 / 10.619117647
			), 0) 
		* 100 
		/ @date_coef
		 , 0 ) AS `cur_pos` 

	, `ads`.`ad_id` 
	, `ads`.`sex` 
	, `ads`.`subject` 
	, `ads`.`descript` 
	, `ads`.`cat_priority` 
	, `ads`.`same_country` 
	
	, `reput`.`points` AS `reput`
	, `act`.`points` AS `act`

FROM `t_user` AS `u` 
LEFT JOIN `t_activity_total` AS `act` ON `u`.`id` = `act`.`user_id` 
LEFT JOIN `t_reput_total` AS `reput` ON `u`.`id` = `reput`.`user_id`
LEFT JOIN `t_ads` AS `ads` ON `u`.`id` = `ads`.`user_id`
WHERE 
	`u`.`sex` = 'female'
	AND `ads`.`cat_id` = 98
ORDER BY 
	`ads`.`cat_priority` DESC
	,`ads`.`same_country` DESC
	,`cur_pos` DESC
;
*/




SELECT `u`.`id` , 
	@date_coef := IFNULL(
		(1188304195 - (CASE WHEN `u`.`add_date` < 1171490400 THEN 1171490400 ELSE `u`.`add_date` END)) 
		* 1
		/ 86400
	, 1) AS `date_coef`
	, ROUND( IFNULL((
		`act`.`points` 
		* 1

		+ 

		((`reput`.`points` - 10) 
		* 1 
		* 13.728571428 / (10.619117647 - 10)
		)
			), 0) 
		/ (1 + 30 / @date_coef)
		 , 0 ) AS `cur_pos` 

	, `ads`.`ad_id` 
	, `ads`.`sex` 
	, `ads`.`subject` 
	, `ads`.`descript` 
	, `ads`.`cat_priority` 
	, `ads`.`same_country` 
	
	, `reput`.`points` AS `reput`
	, `act`.`points` AS `act`

FROM `t_user` AS `u` 
LEFT JOIN `t_activity_total` AS `act` ON `u`.`id` = `act`.`user_id` 
LEFT JOIN `t_reput_total` AS `reput` ON `u`.`id` = `reput`.`user_id`
LEFT JOIN `t_ads` AS `ads` ON `u`.`id` = `ads`.`user_id`
WHERE 
	`u`.`sex` = 'female'
	AND `ads`.`cat_id` = 98
ORDER BY 
	`ads`.`cat_priority` DESC
	,`ads`.`same_country` DESC
	,`cur_pos` DESC
;


/* Test */
SELECT `u`.`id` 
	, @date_coef := IFNULL( 
			(1193658833 - (CASE WHEN `u`.`add_date` < 1171490400 THEN 1171490400 ELSE `u`.`add_date` END ) ) 
			/ 86400
		, 1 )
	, ROUND( 
		IFNULL( (
			`act`.`points` 
			* 1 

			+ 

			( `reput`.`points` -10 ) 
			* 1 
			* 51.763033175 
			/ ( 10.886956521 -10 ) 
		) , 0 ) 
		*1 
		/ ( 1 + 30 / @date_coef )
	 , 0) AS `cur_pos` 

	, `ads`.`ad_id` 
	, `ads`.`sex` 
	, `ads`.`subject` 
	, `ads`.`descript` 
	, `ads`.`cat_priority` 
	, `ads`.`same_country` 
	
	, `reput`.`points` AS `reput`
	, `act`.`points` AS `act`

FROM `t_user` AS `u` 
LEFT JOIN `t_activity_total` AS `act` ON `u`.`id` = `act`.`user_id` 
LEFT JOIN `t_reput_total` AS `reput` ON `u`.`id` = `reput`.`user_id` 
LEFT JOIN `t_ads` AS `ads` ON `u`.`id` = `ads`.`user_id`
WHERE 
	`u`.`sex` = 'female'
	AND `ads`.`cat_id` = 98
ORDER BY 
	`ads`.`cat_priority` DESC
	,`ads`.`same_country` DESC
	,`cur_pos` DESC
;