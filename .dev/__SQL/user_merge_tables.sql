DROP TABLE IF EXISTS `t_user_data_main`;
CREATE TABLE IF NOT EXISTS `t_user_data_main` (
  `id` int(10) unsigned NOT NULL,
  `group` tinyint(3) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `nick` varchar(64) NOT NULL default '',
  `login` varchar(64) NOT NULL default '',
  `email` varchar(128) NOT NULL,
  `password` varchar(12) NOT NULL default '',
  `phone` varchar(40) NOT NULL default '',
  `fax` varchar(40) NOT NULL default '',
  `address` varchar(255) NOT NULL default '',
  `city` varchar(40) NOT NULL default '',
  `zip_code` varchar(16) NOT NULL default '',
  `state` varchar(20) NOT NULL default '',
  `country` varchar(30) NOT NULL default 'USA',
  `sex` enum('Female','Male','Transsexual') NOT NULL,
  `birth_date` date NOT NULL,
  `profile_url` varchar(64) NOT NULL,
  `status` enum('Independent','Agency') NOT NULL default 'Independent',
  `agency_id` int(10) unsigned NOT NULL default '0',
  `manager_id` int(10) unsigned NOT NULL,
  `is_deleted` enum('0','1') NOT NULL,
  `active` tinyint(1) unsigned NOT NULL default '1',
  `level` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
  , KEY `login` (`login`)
  , KEY `nick` (`nick`)
  , KEY `agency_id` (`agency_id`)
  , KEY `active` (`active`)
  , KEY `group` (`group`)
  , KEY `email` (`email`)
);

DROP TABLE IF EXISTS `t_user_data_info`;
CREATE TABLE IF NOT EXISTS `t_user_data_info` (
  `id` int(10) unsigned NOT NULL,
  `age` smallint(2) unsigned NOT NULL default '0',
  `url` varchar(100) NOT NULL default '',
  `race` varchar(20) NOT NULL default '',
  `measurements` varchar(12) NOT NULL default '',
  `height` tinyint(2) unsigned NOT NULL default '0',
  `weight` tinyint(2) unsigned NOT NULL default '0',
  `hair_color` varchar(15) NOT NULL default '',
  `eye_color` varchar(15) NOT NULL default '',
  `orientation` varchar(15) NOT NULL default '',
  `star_sign` varchar(15) NOT NULL default '',
  `smoking` varchar(15) NOT NULL default '',
  `english` varchar(15) NOT NULL default '',
  `show_mail` enum('0','1') NOT NULL,
  `email_status` tinyint(3) unsigned NOT NULL,
  `phone_status` tinyint(3) unsigned NOT NULL,
  `working_hours` varchar(50) NOT NULL default '',
  `cc_payments` varchar(50) NOT NULL default '',
  `icq` varchar(32) NOT NULL,
  `yahoo` varchar(128) NOT NULL,
  `aim` varchar(128) NOT NULL,
  `msn` varchar(128) NOT NULL,
  `jabber` varchar(128) NOT NULL,
  `skype` varchar(128) NOT NULL,
  `verify_code` varchar(32) NOT NULL default '',
  `admin_comments` text NOT NULL,
  `old_id` int(10) unsigned NOT NULL,
  `avatar` varchar(255) NOT NULL,
  `lon` decimal(8,4) NOT NULL default '0.0000',
  `lat` decimal(8,4) NOT NULL default '0.0000',
  `recip_url` varchar(100) NOT NULL default '',
  `approved_recip` tinyint(2) unsigned default '1',
  `nick_moderated` enum('0','1') NOT NULL default '0',
  `pswd_is_hash` enum('0','1') NOT NULL,
  PRIMARY KEY  (`id`)
  , KEY `race` (`race`)
);

DROP TABLE IF EXISTS `t_user_data_ban`;
CREATE TABLE IF NOT EXISTS `t_user_data_ban` (
  `id` int(10) unsigned NOT NULL,
  `ban_ads` tinyint(1) unsigned NOT NULL default '0',
  `ban_reviews` tinyint(1) unsigned NOT NULL default '0',
  `ban_email` tinyint(1) unsigned NOT NULL default '0',
  `ban_images` tinyint(1) unsigned NOT NULL default '0',
  `ban_forum` tinyint(1) unsigned NOT NULL default '0',
  `ban_comments` tinyint(1) unsigned NOT NULL default '0',
  `ban_blog` tinyint(1) unsigned NOT NULL,
  `ban_bad_contact` tinyint(1) unsigned NOT NULL,
  `ban_reput` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
);

DROP TABLE IF EXISTS `t_user_data_stats`;
CREATE TABLE IF NOT EXISTS `t_user_data_stats` (
  `id` int(10) unsigned NOT NULL,
  `views` smallint(6) unsigned NOT NULL default '0',
  `visits` smallint(6) unsigned NOT NULL default '0',
  `emails` smallint(6) unsigned NOT NULL default '0',
  `emailssent` smallint(6) unsigned NOT NULL default '0',
  `sitevisits` smallint(6) unsigned NOT NULL default '0',
  `add_date` int(10) unsigned NOT NULL default '0',
  `last_update` int(10) unsigned NOT NULL default '0',
  `last_login` int(10) unsigned NOT NULL default '0',
  `num_logins` smallint(6) unsigned NOT NULL default '1',
  `number_escorts` varchar(50) NOT NULL default '',
  `vote_num` int(15) unsigned NOT NULL default '0',
  `vote_sum` int(15) unsigned NOT NULL default '0',
  `ip` varchar(15) NOT NULL default '',
  PRIMARY KEY  (`id`)
);

/*
CREATE VIEW `t_users` AS 
SELECT * FROM 
	`t_user_data_main`
	, `t_user_data_info`
	, `t_user_data_ban`
	, `t_user_data_stats`;
*/


INSERT IGNORE 
INTO `t_user_data_main`
SELECT 
  `id`,
  `group`,
  `name`,
  `nick`,
  `login`,
  `email`,
  `password`,
  `phone`,
  `fax`,
  `address`,
  `city`,
  `zip_code`,
  `state`,
  `country`,
  `sex`,
  `birth_date`,
  `profile_url`,
  `status`,
  `agency_id`,
  `manager_id`,
  `is_deleted`,
  `active`,
  `level`
FROM `t_user`;


INSERT IGNORE 
INTO `t_user_data_info`
SELECT 
  `id`,
  `age`,
  `url`,
  `race`,
  `measurements`,
  `height`,
  `weight`,
  `hair_color`,
  `eye_color`,
  `orientation`,
  `star_sign`,
  `smoking`,
  `english`,
  `show_mail`,
  `email_status`,
  `phone_status`,
  `working_hours`,
  `cc_payments`,
  `icq`,
  `yahoo`,
  `aim`,
  `msn`,
  `jabber`,
  `skype`,
  `verify_code`,
  `admin_comments`,
  `old_id`,
  `avatar`,
  `lon`,
  `lat`,
  `recip_url`,
  `approved_recip`,
  `nick_moderated`,
  `pswd_is_hash`
FROM `t_user`;


INSERT IGNORE 
INTO `t_user_data_ban`
SELECT 
  `id`,
  `ban_ads`,
  `ban_reviews`,
  `ban_email`,
  `ban_images`,
  `ban_forum`,
  `ban_comments`,
  `ban_blog`,
  `ban_bad_contact`
  `ban_reput`
FROM `t_user`;


INSERT IGNORE 
INTO `t_user_data_stats` 
SELECT 
  `id`,
  `views`,
  `visits`,
  `emails`,
  `emailssent`,
  `sitevisits`,
  `add_date`,
  `last_update`,
  `last_login`,
  `num_logins`,
  `number_escorts`,
  `vote_num`,
  `vote_sum`,
  `ip`
FROM `t_user`;


/*
SELECT * 
FROM 
	`t_user_data_main`   AS um
	,`t_user_data_info`  AS ui
	,`t_user_data_ban`   AS ub 
	,`t_user_data_stats` AS us
WHERE ui.id = um.id
	AND ub.id = um.id
	AND us.id = um.id
	AND um.id = 1;
*/

/*
UPDATE 
	`t_user_data_main`   AS um
	,`t_user_data_info`  AS ui
	,`t_user_data_ban`   AS ub 
	,`t_user_data_stats` AS us
SET um.`group` = 3
	, um.`name` = ''
	, um.`nick` = 'Ivan Lobanov2'
	, um.`login` = 'iva@apptown.ru'
	, um.`email` = 'iva@apptown.ru'
	, um.`password` = 'dfdf'
	, um.`phone` = '0951234567'
	, um.`fax` = '2222'
	, um.`address` = ''
	, um.`city` = 'Moscow'
	, um.`zip_code` = '12345'
	, um.`state` = ''
	, um.`country` = 'Russia'
	, um.`sex` = 'Female'
	, um.`birth_date` = '1981-07-24'
	, um.`profile_url` = 'ivan'
	, um.`status` = 'Independent'
	, um.`agency_id` = 1226
	, um.`manager_id` = 0
	, um.`is_deleted` = '0'
	, um.`active` = 1
	, um.`level` = 0

	, ui.`age` = 25
	, ui.`url` = ''
	, ui.`recip_url` = 'http://sex.com'
	, ui.`approved_recip` = 1
	, ui.`race` = ''
	, ui.`measurements` = ''
	, ui.`height` = 23
	, ui.`weight` = 24
	, ui.`hair_color` = 'Silver'
	, ui.`eye_color` = 'Black'
	, ui.`orientation` = 'Bisexual'
	, ui.`star_sign` = 'Aries'
	, ui.`smoking` = 'No'
	, ui.`english` = 'Native'
	, ui.`show_mail` = '0'
	, ui.`email_status` = 0
	, ui.`phone_status` = 0
	, ui.`working_hours` = ''
	, ui.`cc_payments` = 'Yes'
	, ui.`icq` = '132465798'
	, ui.`yahoo` = 'iva@apptown.ru'
	, ui.`aim` = 'iva@apptown.ru'
	, ui.`msn` = 'iva@apptown.ru'
	, ui.`jabber` = 'iva@apptown.ru'
	, ui.`skype` = 'iva@apptown.ru'
	, ui.`verify_code` = 'MXd2Y24xMTcyODM1MDEx'
	, ui.`admin_comments` = 'Some my cool text\\\\\\\\'
	, ui.`old_id` = 0
	, ui.`avatar` = ''
	, ui.`lon` = '42.8141'
	, ui.`lat` = '-73.9400'
	, ui.`nick_moderated` = '0'
	, ui.`pswd_is_hash` = '0'

	, ub.`ban_ads` = 0
	, ub.`ban_reviews` = 0
	, ub.`ban_email` = 0
	, ub.`ban_images` = 0
	, ub.`ban_forum` = 0
	, ub.`ban_comments` = 0
	, ub.`ban_blog` = 0
	, ub.`ban_bad_contact` = '0'

	, us.`views` = 763
	, us.`visits` = 9
	, us.`emails` = 23
	, us.`emailssent` = 53
	, us.`sitevisits` = 28
	, us.`add_date` = 1024130223
	, us.`last_update` = 1182340781
	, us.`last_login` = 1183717812
	, us.`num_logins` = 336
	, us.`number_escorts` = ''
	, us.`vote_num` = 0
	, us.`vote_sum` = 0
	, us.`ip` = '195.98.165.94'

WHERE ui.id = um.id
	AND ub.id = um.id
	AND us.id = um.id
	AND um.id = 1;
*/