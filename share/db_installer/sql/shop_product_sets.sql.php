<?php
$data = '
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(32) NOT NULL DEFAULT \'\',
	`desc` text NOT NULL,
	`products` text NOT NULL,
	`cat_id` int(11) NOT NULL default \'0\',
	`add_date` int(11) NOT NULL DEFAULT \'0\',
	`active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
	PRIMARY KEY (`id`)
';