<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`start_date` int(10) unsigned NOT NULL default \'0\',
	`avg_exec_time` float NOT NULL default \'0\',
	`hits` int(10) unsigned NOT NULL default \'0\',
	`hosts` int(10) unsigned NOT NULL default \'0\',
	`traffic` int(10) unsigned NOT NULL default \'0\',
	PRIMARY KEY	(`id`),
	UNIQUE KEY `start_date` (`start_date`)
';