<?php
$data = '
	`var_id` int(10) unsigned NOT NULL DEFAULT \'0\',
	`value` text NOT NULL,
	`locale` varchar(12) NOT NULL DEFAULT \'\',
	PRIMARY KEY `var_id_locale` (`var_id`,`locale`)
';
/*
	,FOREIGN KEY (`var_id`) REFERENCES `{db_prefix}sys_locale_vars` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
	FOREIGN KEY (`locale`) REFERENCES `{db_prefix}sys_locale_langs` (`locale`) ON DELETE CASCADE ON UPDATE NO ACTION
*/