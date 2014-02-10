<?php
$data = '
	`id` tinyint(3) unsigned NOT NULL auto_increment,
	`name` varchar(255) NOT NULL default \'\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	`go_after_login` varchar(255) NOT NULL default \'\',
	PRIMARY KEY  (`id`)
';