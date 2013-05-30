SELECT `u`.`country`, 
	`a`.`cat_id`,
	`c`.`name`,
	`c`.`country_code`, 
	`a`.`same_country` 
FROM `sexy_ads` AS `a`, 
	`sexy_user` AS `u`, 
	`sexy_category` AS `c` 
WHERE `u`.`id` = `a`.`user_id` 
	AND `c`.`id` = `a`.`cat_id`