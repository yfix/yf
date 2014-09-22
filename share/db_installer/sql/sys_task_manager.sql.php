<?php
return '
	`id` int(10) NOT NULL auto_increment,
	`title` varchar(255) NOT NULL default \'\',
	`file` varchar(255) NOT NULL default \'\',
	`php_code` TEXT NOT NULL default \'\',
	`next_run` int(10) NOT NULL default \'0\',
	`week_day` tinyint(1) NOT NULL default \'-1\',
	`month_day` smallint(2) NOT NULL default \'-1\',
	`hour` smallint(2) NOT NULL default \'-1\',
	`minute` smallint(2) NOT NULL default \'-1\',
	`cronkey` varchar(32) NOT NULL default \'\',
	`log` tinyint(1) NOT NULL default \'0\',
	`description` text NOT NULL,
	`enabled` tinyint(1) NOT NULL default \'1\',
	`key` varchar(30) NOT NULL default \'\',
	`safemode` tinyint(1) NOT NULL default \'0\',
	PRIMARY KEY (`id`),
	KEY `task_next_run` (`next_run`)
';