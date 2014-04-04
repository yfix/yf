CREATE TEMPORARY TABLE `t_tmp__5aa5d15f` ( `user_id` int(10) unsigned NOT NULL, `value` int(10) NOT NULL, PRIMARY KEY (`user_id`) );

UPDATE `t_reput_user_votes` SET `counted` = `voted` WHERE `counted` = 0 AND `voted` != 0;

UPDATE `t_reput_total` SET `points` = 10;

INSERT INTO `t_tmp__5aa5d15f` SELECT `user_id`, SUM(`add_points`) FROM `t_activity_logs` GROUP BY `user_id`;

UPDATE `t_activity_total` AS `t`, `t_tmp__5aa5d15f` AS `tmp` SET `t`.`points` = `tmp` .`value` WHERE `t`.`user_id` = `tmp`.`user_id`;

TRUNCATE TABLE `t_tmp__5aa5d15f`;

INSERT INTO `t_tmp__5aa5d15f` SELECT `target_user_id`, SUM(`counted`) + 10 FROM `t_reput_user_votes` GROUP BY `target_user_id`;

UPDATE `t_reput_total` AS `t`, `t_tmp__5aa5d15f` AS `tmp` SET `t`.`points` = `tmp` .`value` WHERE `t`.`user_id` = `tmp`.`user_id`;

TRUNCATE TABLE `t_tmp__5aa5d15f`;

INSERT INTO `t_tmp__5aa5d15f` SELECT `user_id`, COUNT(*) FROM `t_reput_user_votes` GROUP BY `user_id`;

UPDATE `t_reput_total` AS `t`, `t_tmp__5aa5d15f` AS `tmp` SET `t`.`num_votes` = `tmp` .`value` WHERE `t`.`user_id` = `tmp`.`user_id`;

TRUNCATE TABLE `t_tmp__5aa5d15f`;

INSERT INTO `t_tmp__5aa5d15f` SELECT `target_user_id`, COUNT(*) FROM `t_reput_user_votes` GROUP BY `target_user_id`;

UPDATE `t_reput_total` AS `t`, `t_tmp__5aa5d15f` AS `tmp` SET `t`.`num_voted` = `tmp` .`value` WHERE `t`.`user_id` = `tmp`.`user_id`;

TRUNCATE TABLE `t_tmp__5aa5d15f`;

UPDATE `t_reput_total` AS r, `t_activity_total` AS a SET r.alt_power = ( 1 + FLOOR(a.`points` / 150) + FLOOR(r.`points` / 20) ) WHERE r.`user_id` = a.`user_id`;

DROP TEMPORARY TABLE `t_tmp__5aa5d15f`;