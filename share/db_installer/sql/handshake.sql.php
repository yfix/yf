<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`sender` int(11) NOT NULL default \'0\',
	`receiver` int(11) NOT NULL default \'0\',
	`text` text character set utf8 NOT NULL,
	`add_date` text NOT NULL,
	`action_date` text NOT NULL,
	`status` text NOT NULL,
	PRIMARY KEY  (`id`)
';