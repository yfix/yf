<?php
return '
	`id` int(10) NOT NULL auto_increment,
	`title` text NOT NULL,
	`desc` text NOT NULL,
	`group_id` smallint(3) NOT NULL default \'0\',
	`group_name` varchar(255) NOT NULL default \'\',
	`type` varchar(255) NOT NULL default \'\',
	`keyword` text NOT NULL,
	`value` text NOT NULL,
	`default` text NOT NULL,
	`extra` text NOT NULL,
	`eval_php` text NOT NULL,
	`position` smallint(3) NOT NULL default \'0\',
	`display` tinyint(1) NOT NULL default \'0\',
	PRIMARY KEY  (`id`)
';