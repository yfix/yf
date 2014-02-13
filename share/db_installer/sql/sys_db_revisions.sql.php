<?php
$data = '
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`query_method` varchar(128) NOT NULL DEFAULT \'\',
	`query_table` varchar(128) NOT NULL DEFAULT \'\',
	`date` datetime NOT NULL,
	`data_old` longtext NOT NULL,
	`data_new` longtext NOT NULL,
	`data_diff` longtext NOT NULL,
	`user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
	`user_group` int(10) unsigned NOT NULL DEFAULT \'0\',
	`site_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
	`server_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
	`ip` char(15) NOT NULL,
	`url` text NOT NULL,
	`extra` longtext NOT NULL,
	PRIMARY KEY (`id`)
';