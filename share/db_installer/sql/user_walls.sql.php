<?php
return '
	`id` int(10) unsigned NOT NULL auto_increment,
	`user_id` int(11) unsigned NOT NULL default \'0\',
	`message` text NOT NULL default \'\',
	`object` varchar(255) NOT NULL default \'\',
	`action` varchar(255) NOT NULL default \'\',
	`object_id` int(11) unsigned NOT NULL default \'0\',
	`server_id` int(11) unsigned NOT NULL default \'0\',
	`site_id` int(11) unsigned NOT NULL default \'0\',
	`important` int(11) unsigned NOT NULL default \'0\',
	`old_data` text NOT NULL default \'\',
	`new_data` text NOT NULL default \'\',
	`add_date` datetime NOT NULL,
    `read` tinyint(1) NOT NULL DEFAULT \'0\',
	`type` varchar(100) NOT NULL,
	PRIMARY KEY  (`id`)
	/** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';