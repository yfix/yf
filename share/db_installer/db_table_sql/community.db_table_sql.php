<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`owner_id` int(11) NOT NULL default \'0\',
	`user_id` int(10) unsigned NOT NULL default \'0\',
	`title` text character set utf8 NOT NULL,
	`membership` enum(\'open\',\'moderated\',\'closed\') NOT NULL default \'open\',
	`nonmember_posting` enum(\'0\',\'1\') NOT NULL default \'0\',
	`postlevel` enum(\'members\',\'select\') NOT NULL default \'members\',
	`moderated` enum(\'0\',\'1\') NOT NULL default \'0\',
	`adult` enum(\'none\',\'concepts\',\'explicit\') NOT NULL default \'none\',
	`about` text character set utf8 NOT NULL,
	`active` enum(\'0\',\'1\') NOT NULL default \'0\',
	PRIMARY KEY  (`id`)
';