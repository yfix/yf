INSERT IGNORE INTO `sexy_check_multi_accounts` ( `user_id` , `num_m_ips` , `matching_ips` );


SELECT `user_id`
	, COUNT(DISTINCT(`ip`)) AS `multi_ips`
	, CAST(GROUP_CONCAT(DISTINCT `ip` ORDER BY `ip` ASC) AS CHAR) AS `ips_list` 
FROM `sexy_sys_log_auth` 
WHERE `user_id` IN (
	SELECT `id` 
	FROM `sexy_user` 
)
GROUP BY `user_id` 
HAVING `multi_ips` > 1
ORDER BY `multi_ips` DESC ;


INSERT IGNORE INTO `sexy_check_multi_ips` ( `ip` , `num_m_users` , `matching_users` );

SELECT `ip` , COUNT( DISTINCT (
`user_id` 
) ) AS `unique_accounts` , CAST( GROUP_CONCAT( DISTINCT `user_id` 
ORDER BY `user_id` ASC ) AS CHAR ) AS `users_list` 
FROM `sexy_sys_log_auth` 
WHERE `user_id` 
IN (

SELECT `id` 
FROM `sexy_user` 
)
GROUP BY `ip` 
HAVING `unique_accounts` >1
ORDER BY `unique_accounts` DESC ;