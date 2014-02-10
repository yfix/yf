<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`first_name` varchar(64) NOT NULL default \'\',
	`last_name` varchar(64) NOT NULL default \'\',
	`login` varchar(64) NOT NULL default \'\',
	`email` varchar(255) NOT NULL default \'\',
	`password` varchar(64) NOT NULL default \'\',
	`group` int(10) unsigned NOT NULL default \'0\',
	`active` enum(\'0\',\'1\') NOT NULL default \'0\',
	`add_date` int(10) unsigned NOT NULL default \'0\',
	`last_login` int(10) unsigned NOT NULL default \'0\',
	`num_logins` smallint(6) unsigned NOT NULL default \'0\',
	`go_after_login` varchar(255) NOT NULL default \'\',
	PRIMARY KEY  (`id`),
	UNIQUE KEY `login` (`login`)
';