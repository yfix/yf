<?php
$data = '
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`ad` varchar(64) NOT NULL,
	`langs` varchar(255) NOT NULL DEFAULT \'\',
	`cat_ids` text NOT NULL,
	`program_ids` text NOT NULL,
	`customer` text NOT NULL,
	`module_names` varchar(255) NOT NULL DEFAULT \'\',
	`user_countries` text NOT NULL,
	`is_logged_in` int(11) NOT NULL DEFAULT \'0\',
	`date_start` int(11) NOT NULL DEFAULT \'0\',
	`date_end` int(11) NOT NULL DEFAULT \'0\',
	`html` text NOT NULL,
	`user_id` int(11) NOT NULL DEFAULT \'0\',
	`edit_user_id` int(11) NOT NULL DEFAULT \'0\',
	`add_date` int(11) NOT NULL DEFAULT \'0\',
	`edit_date` int(11) NOT NULL DEFAULT \'0\',
	`active` tinyint(4) NOT NULL DEFAULT \'0\',
	PRIMARY KEY (`id`)
	/** ENGINE=MyISAM DEFAULT CHARSET=utf8 **/
';