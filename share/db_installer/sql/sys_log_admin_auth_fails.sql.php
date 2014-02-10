<?php
$data = '
	`time` decimal(13,3) unsigned NOT NULL default \'0.000\',
	`ip` varchar(16) NOT NULL default \'\',
	`login` varchar(64) NOT NULL default \'\',
	`pswd` varchar(64) NOT NULL default \'\',
	`reason` char(1) NOT NULL default \'w\',
	`site_id` tinyint(3) unsigned NOT NULL default \'0\',
	`server_id` tinyint(3) unsigned NOT NULL default \'0\',
	PRIMARY KEY	(`time`)
';