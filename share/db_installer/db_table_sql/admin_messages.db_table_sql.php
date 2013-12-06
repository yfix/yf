<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`user_id` int(10) unsigned NOT NULL,
	`author_id` int(10) unsigned NOT NULL,
	`title` varchar(255) NOT NULL,
	`text` text NOT NULL,
	`read` enum(\'0\',\'1\') NOT NULL,
	`time` int(10) unsigned NOT NULL,
	PRIMARY KEY  (`id`)
	/** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';