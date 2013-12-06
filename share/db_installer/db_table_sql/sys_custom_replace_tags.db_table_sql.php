<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(64) NOT NULL default \'\',
	`desc` varchar(255) NOT NULL default \'\',
	`pattern_find` text NOT NULL,
	`pattern_replace` text NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	PRIMARY KEY (`id`)
';