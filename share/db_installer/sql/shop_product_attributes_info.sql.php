<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`category_id` int(11) NOT NULL default \'0\',
	`name` varchar(64) NOT NULL default \'\',
	`type` varchar(64) NOT NULL default \'\',
	`value_list` text NOT NULL,
	`default_value` text NOT NULL,
	`order` int(10) unsigned NOT NULL default \'0\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	PRIMARY KEY	(`id`)
';