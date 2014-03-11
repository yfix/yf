<?php
$data = '
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`object_name` varchar(64) NOT NULL,
	`object_id` varchar(64) NOT NULL,
	`old_text` text NOT NULL,
	`new_text` text NOT NULL,
	`date` int(10) unsigned NOT NULL,
	`user_id` int(10) unsigned NOT NULL,
	`site_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
	`server_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
	`ip` char(15) NOT NULL,
	`comment` varchar(255) NOT NULL,
	PRIMARY KEY (`id`)
';