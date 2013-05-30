DELETE FROM `sexy_sys_online` 
WHERE `user_agent` LIKE '%bot%' 
	OR `user_agent` LIKE '%spider%' 
	OR `user_agent` LIKE '%crawler%'
	OR `user_agent` LIKE '%Yahoo! Slurp%';

SELECT `user_agent` , COUNT( * ) AS `hits` 
FROM `sexy_sys_online` 
GROUP BY `user_agent` 
ORDER BY `hits` DESC;