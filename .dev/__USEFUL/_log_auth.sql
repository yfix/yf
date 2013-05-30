/*
SELECT `1`.`ip`,`1`.`user_id`, COUNT(`1`.`user_id`)
FROM `sexy_sys_log_auth` AS `1`, 
	`sexy_sys_log_auth` AS `2` 
WHERE `1`.`user_id` != `2`.`user_id` 
	AND `1`.`ip` = `2`.`ip` 
GROUP BY `1`.`ip` 
ORDER BY `1`.`ip`;
*/
/*
SELECT `1`.`ip`,`1`.`user_id`, COUNT(`1`.`user_id`)
FROM `sexy_sys_log_auth` AS `1`, 
	`sexy_sys_log_auth` AS `2` 
WHERE `1`.`user_id` != `2`.`user_id` 
	AND `1`.`ip` = `2`.`ip` 
GROUP BY `1`.`ip`, `1`.`user_id`
ORDER BY `1`.`ip`;
*/
/*
SELECT COUNT( `ip` ) , `user_id` 
FROM `sexy_sys_log_auth` 
WHERE 1 =1
GROUP BY `ip` 
HAVING COUNT( `ip` ) >1
ORDER BY COUNT( `ip` ) DESC 
LIMIT 0 , 30
*/
SELECT COUNT(DISTINCT(`user_id`)) AS `unique_accounts`, 
		COUNT(*) AS `num_logins_from_this_ip`, 
		`ip` 
FROM `sexy_sys_log_auth` 
WHERE 1 =1
GROUP BY `ip` 
HAVING `unique_accounts` > 1
ORDER BY `unique_accounts` DESC;