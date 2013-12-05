<?php
$data = '
	`id` int(10) unsigned NOT NULL,
	`views` smallint(6) unsigned NOT NULL default \'0\',
	`visits` smallint(6) unsigned NOT NULL default \'0\',
	`emails` smallint(6) unsigned NOT NULL default \'0\',
	`emailssent` smallint(6) unsigned NOT NULL default \'0\',
	`sitevisits` smallint(6) unsigned NOT NULL default \'0\',
	`add_date` int(10) unsigned NOT NULL default \'0\',
	`last_view` int(10) unsigned NOT NULL default \'0\',
	`last_login` int(10) unsigned NOT NULL default \'0\',
	`last_update` int(10) unsigned NOT NULL default \'0\',
	`num_logins` smallint(6) unsigned NOT NULL default \'0\',
	`num_views` int(10) unsigned NOT NULL default \'0\',
	PRIMARY KEY  (`id`)
';