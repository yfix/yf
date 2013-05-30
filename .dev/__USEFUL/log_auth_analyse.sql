/* Group by unique `ip` (list users for this IP) */
SELECT 
	COUNT(DISTINCT(`user_id`)) AS `unique_accounts`
	, `ip`
	, CAST(GROUP_CONCAT(DISTINCT `user_id` ORDER BY `user_id` ASC) AS CHAR) AS `users_list`
FROM `sexy_sys_log_auth` 
WHERE `user_id` IN (SELECT `id` FROM `sexy_user`)
GROUP BY `ip` 
HAVING `unique_accounts` > 1
ORDER BY `unique_accounts` DESC;


/* Group by unique `user_id` (list IPs for this user) */
SELECT 
	COUNT(DISTINCT(`ip`)) AS `multi_ips`
	, `user_id`
	, CAST(GROUP_CONCAT(DISTINCT `ip` ORDER BY `ip` ASC) AS CHAR) AS `ips_list`
FROM `sexy_sys_log_auth` 
WHERE `user_id` IN (SELECT `id` FROM `sexy_user`)
GROUP BY `user_id` 
HAVING `multi_ips` > 1
ORDER BY `multi_ips` DESC;
