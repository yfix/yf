<?php
$data = '
	`log_id` int(10) NOT NULL auto_increment,
	`log_title` varchar(255) NOT NULL default \'\',
	`log_date` int(10) NOT NULL default \'0\',
	`log_ip` varchar(16) NOT NULL default \'0\',
	`log_desc` text NOT NULL,
	`log_time` float NOT NULL default \'0\',
	PRIMARY KEY	(`log_id`)
';