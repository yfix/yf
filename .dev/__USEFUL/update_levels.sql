/* Get stats by user levels */
/* SELECT `level` , COUNT(*) FROM `sexy_user` GROUP BY `level` */

CREATE TEMPORARY TABLE `sexy_tmp__a5629d73` ( `user_id` int(10) unsigned NOT NULL, `user_group` int(1) unsigned NOT NULL, `activity` int(10) unsigned NOT NULL, `reput` int(10) NOT NULL, `num_refs` int(10) unsigned NOT NULL, `level` int(1) unsigned NOT NULL, `act_time` float NOT NULL, PRIMARY KEY (`user_id`) ); 

INSERT IGNORE INTO `sexy_tmp__a5629d73` 
	( `user_id` , `activity` , `act_time` ) 
SELECT `user_id` , `points` , `points` / 164 
FROM `sexy_activity_total`; 

UPDATE `sexy_tmp__a5629d73` AS `tmp` , `sexy_reput_total` AS `t2` 
SET `tmp`.`reput` = `t2`.`points` 
WHERE `tmp`.`user_id` = `t2`.`user_id`; 

UPDATE `sexy_tmp__a5629d73` AS `tmp` , `sexy_user` AS `t2` 
SET `tmp`.`user_group` = `t2`.`group` 
WHERE `tmp`.`user_id` = `t2`.`id`; 

UPDATE `sexy_tmp__a5629d73` 
SET `level` = 1 
WHERE `act_time` >= 3 AND `reput` >= 20; 

CREATE TEMPORARY TABLE `sexy_tmp__b0125579` ( `user_id` int(10) unsigned NOT NULL, `num_refs` int(10) unsigned NOT NULL, PRIMARY KEY (`user_id`) ); 

INSERT IGNORE INTO `sexy_tmp__b0125579` 
	( `user_id` , `num_refs` ) 
SELECT `escort_id` , COUNT(*) 
FROM `sexy_referrals` 
WHERE `user_id` IN(SELECT `user_id` FROM `sexy_tmp__a5629d73` WHERE `level` > 0) 
	AND `active`='1' 
GROUP BY `escort_id`; 

UPDATE `sexy_tmp__a5629d73` AS `tmp` , `sexy_tmp__b0125579` AS `t2` 
SET `tmp`.`num_refs` = `t2`.`num_refs` 
WHERE `tmp`.`user_id` = `t2`.`user_id`; 

DROP TEMPORARY TABLE `sexy_tmp__b0125579`; 

UPDATE `sexy_tmp__a5629d73` 
SET `level` = 2 
WHERE `act_time` >= 5 
	AND `reput` >= 40 
	AND `num_refs` >= 1; 

UPDATE `sexy_user` SET `level`=0; 

UPDATE `sexy_tmp__a5629d73` AS `tmp` , `sexy_user` AS `t2` 
SET `t2`.`level` = `tmp`.`level` 
WHERE `tmp`.`user_id` = `t2`.`id`; 

DROP TEMPORARY TABLE `sexy_tmp__a5629d73`; 