<?php
return '
	`id` int(11) NOT NULL auto_increment,
	`user_id` int(11) NOT NULL default \'0\',
	`provider` varchar(64) NOT NULL default \'\',
	`provider_uid` varchar(64) NOT NULL default \'\',
	`login` varchar(128) NOT NULL default \'\',
	`email` varchar(128) NOT NULL default \'\',
	`name` varchar(128) NOT NULL default \'\',
	`profile_url` varchar(128) NOT NULL default \'\',
	`avatar_url` varchar(128) NOT NULL default \'\',
	`json_normalized` text NOT NULL default \'\',
	`json_raw` text NOT NULL default \'\',
	`add_date` int(11) NOT NULL default \'0\',
	`last_date` int(11) NOT NULL default \'0\',
	PRIMARY KEY	(`id`),
	UNIQUE KEY `provider_uid` (`provider`,`provider_uid`)
';