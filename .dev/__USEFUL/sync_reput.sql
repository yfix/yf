CREATE TEMPORARY TABLE `sexy_tmp__5aa5d15f` ( `user_id` int(10) unsigned NOT NULL, `value` int(10) NOT NULL, PRIMARY KEY (`user_id`) );

UPDATE `sexy_reput_user_votes` SET `counted` = `voted` WHERE `counted` = 0 AND `voted` != 0;

UPDATE `sexy_reput_total` SET `points` = 10;

INSERT INTO `sexy_tmp__5aa5d15f` SELECT `user_id`, SUM(`add_points`) FROM `sexy_activity_logs` GROUP BY `user_id`;

UPDATE `sexy_activity_total` AS `t`, `sexy_tmp__5aa5d15f` AS `tmp` SET `t`.`points` = `tmp` .`value` WHERE `t`.`user_id` = `tmp`.`user_id`;

TRUNCATE TABLE `sexy_tmp__5aa5d15f`;

INSERT INTO `sexy_tmp__5aa5d15f` SELECT `target_user_id`, SUM(`counted`) + 10 FROM `sexy_reput_user_votes` GROUP BY `target_user_id`;

UPDATE `sexy_reput_total` AS `t`, `sexy_tmp__5aa5d15f` AS `tmp` SET `t`.`points` = `tmp` .`value` WHERE `t`.`user_id` = `tmp`.`user_id`;

TRUNCATE TABLE `sexy_tmp__5aa5d15f`;

INSERT INTO `sexy_tmp__5aa5d15f` SELECT `user_id`, COUNT(*) FROM `sexy_reput_user_votes` GROUP BY `user_id`;

UPDATE `sexy_reput_total` AS `t`, `sexy_tmp__5aa5d15f` AS `tmp` SET `t`.`num_votes` = `tmp` .`value` WHERE `t`.`user_id` = `tmp`.`user_id`;

TRUNCATE TABLE `sexy_tmp__5aa5d15f`;

INSERT INTO `sexy_tmp__5aa5d15f` SELECT `target_user_id`, COUNT(*) FROM `sexy_reput_user_votes` GROUP BY `target_user_id`;

UPDATE `sexy_reput_total` AS `t`, `sexy_tmp__5aa5d15f` AS `tmp` SET `t`.`num_voted` = `tmp` .`value` WHERE `t`.`user_id` = `tmp`.`user_id`;

TRUNCATE TABLE `sexy_tmp__5aa5d15f`;

UPDATE `sexy_reput_total` AS r, `sexy_activity_total` AS a SET r.alt_power = ( 1 + FLOOR(a.`points` / 150) + FLOOR(r.`points` / 20) ) WHERE r.`user_id` = a.`user_id`;

DROP TEMPORARY TABLE `sexy_tmp__5aa5d15f`;