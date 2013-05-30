SET @_test_user_id:=426391;

SET @_start_reput:=10;
SET @_start_reput_alt:=1;
SET @_act_reput_value:=150;
SET @_reput_add_alt_points:=20;

SELECT ( 
	@_start_reput_alt 
	+ (CASE WHEN 
			FLOOR(LOG(a.`points` / @_act_reput_value)) > 0 
		THEN FLOOR(LOG(a.`points` / @_act_reput_value)) 
		ELSE 0 
	END) 
	+ (CASE 
		WHEN 
			FLOOR(r.`points` / @_reput_add_alt_points) > 0 
		THEN FLOOR(r.`points` / @_reput_add_alt_points)
		ELSE 0 
	END)
) AS `alt_power`
FROM `sexy_reput_total` AS r, 
	`sexy_activity_total` AS a 
WHERE r.`user_id` = a.`user_id`
	AND `r`.`user_id` = @_test_user_id;

/*
UPDATE `sexy_reput_total` AS r, 
		`sexy_activity_total` AS a 
SET r.alt_power = ( 
	@_start_reput_alt 
	+ (CASE WHEN 
			FLOOR(LOG(a.`points` / @_act_reput_value)) > 0 
		THEN FLOOR(LOG(a.`points` / @_act_reput_value)) 
		ELSE 0 
	END) 
	+ (CASE 
		WHEN 
			FLOOR(r.`points` / @_reput_add_alt_points) > 0 
		THEN FLOOR(r.`points` / @_reput_add_alt_points)
		ELSE 0 
	END) 
) 
WHERE r.`user_id` = a.`user_id`
*/