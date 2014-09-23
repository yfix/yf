<?php
return '
	`id` mediumint(8) NOT NULL auto_increment,
	`user_id` mediumint(8) NOT NULL default \'0\',
	`topic_id` int(10) NOT NULL default \'0\',
	`start_date` int(10) default NULL,
	`last_sent` int(10) NOT NULL default \'0\',
	`topic_track_type` varchar(100) NOT NULL default \'delayed\',
	PRIMARY KEY	(`id`),
	KEY `topic_id` (`topic_id`)
';