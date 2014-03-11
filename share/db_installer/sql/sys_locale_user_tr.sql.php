<?php
$data = '
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`site_id` smallint(4) unsigned NOT NULL DEFAULT \'0\',
	`user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
	`var` varchar(255) NOT NULL DEFAULT \'\',
	`translation` text NOT NULL,
	`locale` varchar(12) NOT NULL DEFAULT \'\',
	`last_update` int(10) unsigned NOT NULL DEFAULT \'0\',
	PRIMARY KEY (`id`),
	UNIQUE KEY `locale_var_user_id` (`user_id`,`var`,`locale`)
';