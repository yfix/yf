<?php
$data = '
	`id` int(11) NOT NULL auto_increment,
	`user_id` int(11) NOT NULL default \'\',
	`provider` varchar(64) NOT NULL default \'\',
	`login` varchar(128) NOT NULL default \'\',
	`email` varchar(128) NOT NULL default \'\',
	`add_date` int(11) NOT NULL default \'\',
	`last_date` int(11) NOT NULL default \'\',
	PRIMARY KEY	(`id`),
	UNIQUE KEY `user_id_provider` (`user_id`,`provider`)
';