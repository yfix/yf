<?php
$data = '
	`var_id` int(10) unsigned NOT NULL default \'0\',
	`value` text NOT NULL,
	`locale` varchar(12) NOT NULL default \'\',
	KEY `lang` (`locale`),
	KEY `var_id` (`var_id`)
	/** DEFAULT CHARSET=UTF8 **/ 
';