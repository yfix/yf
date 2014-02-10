<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`site_id` smallint(4) unsigned NOT NULL default \'0\',
	`user_id` int(10) unsigned NOT NULL default \'0\',
	`var` varchar(255) NOT NULL default \'\',
	`translation` text NOT NULL default \'\',
	`locale` varchar(12) NOT NULL default \'\',
	`last_update` int(10) unsigned NOT NULL default \'0\',
	PRIMARY KEY (`id`),
	UNIQUE KEY `locale_var_user_id` (`user_id`,`var`,`locale`)
	/** DEFAULT CHARSET=UTF8 **/ 
';