<?php
return '
	`id` int(11) NOT NULL auto_increment,
	`user_id` int(10) unsigned NOT NULL default \'0\',
	`community_id` int(10) unsigned NOT NULL default \'0\',
	`member` enum(\'0\',\'1\') NOT NULL default \'0\',
	`post` enum(\'0\',\'1\') NOT NULL default \'0\',
	`unmoderated` enum(\'0\',\'1\') NOT NULL default \'0\',
	`moderator` enum(\'0\',\'1\') NOT NULL default \'0\',
	`maintainer` enum(\'0\',\'1\') NOT NULL default \'0\',
	PRIMARY KEY  (`id`)		
';