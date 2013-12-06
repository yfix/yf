<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(64) NOT NULL default \'\',
	`desc` varchar(255) NOT NULL default \'\',
	`for_admin` enum(\'0\',\'1\') NOT NULL default \'0\',
	`for_user` enum(\'1\',\'0\') NOT NULL default \'1\',
	`active` enum(\'0\',\'1\') NOT NULL default \'0\',
	PRIMARY KEY (`id`)
';