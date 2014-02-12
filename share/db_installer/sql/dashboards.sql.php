<?php
$data = '
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(50) NOT NULL default \'\',
	`data` text NOT NULL default \'\',
	`type` enum(\'user\',\'admin\') NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	PRIMARY KEY  (`id`),
	UNIQUE KEY  (`id`)
';