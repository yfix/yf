<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`methods` text NOT NULL default \'\',
	`query_string` varchar(255) NOT NULL default \'\',
	`language` varchar(12) NOT NULL default \'0\',
	`user_groups` varchar(255) NOT NULL default \'\',
	`site_id` tinyint(3) unsigned NOT NULL default \'0\',
	`server_id` tinyint(3) unsigned NOT NULL default \'0\',
	`tag_id` smallint(5) unsigned NOT NULL default \'0\',
	`tag_replace` text NOT NULL,
	`order` int(10) unsigned NOT NULL default \'0\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	`eval_code` enum(\'1\',\'0\') NOT NULL,
	PRIMARY KEY (`id`)
';