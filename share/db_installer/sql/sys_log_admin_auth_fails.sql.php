<?php
$data = '
	`time` decimal(13,3) unsigned NOT NULL DEFAULT \'0.000\',
	`ip` varchar(16) NOT NULL DEFAULT \'\',
	`login` varchar(64) NOT NULL DEFAULT \'\',
	`pswd` varchar(64) NOT NULL DEFAULT \'\',
	`reason` char(1) NOT NULL DEFAULT \'w\',
	`site_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
	`server_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
	PRIMARY KEY (`time`)
';