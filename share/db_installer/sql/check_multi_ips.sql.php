<?php
return '
	`ip` char(15) NOT NULL,
	`matching_users` longtext NOT NULL,
	`matching_ips` longtext NOT NULL,
	`num_m_users` int(10) unsigned NOT NULL default \'0\',
	`num_m_ips` int(10) unsigned NOT NULL default \'0\',
	`last_update` int(10) unsigned NOT NULL default \'0\',
	PRIMARY KEY	(`ip`)
';