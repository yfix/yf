<?php
$data = '
	`id` int(10) unsigned NOT NULL,
	`ban_ads` tinyint(1) unsigned NOT NULL default \'0\',
	`ban_reviews` tinyint(1) unsigned NOT NULL default \'0\',
	`ban_email` tinyint(1) unsigned NOT NULL default \'0\',
	`ban_images` tinyint(1) unsigned NOT NULL default \'0\',
	`ban_forum` tinyint(1) unsigned NOT NULL default \'0\',
	`ban_comments` tinyint(1) unsigned NOT NULL default \'0\',
	`ban_blog` tinyint(1) unsigned NOT NULL,
	`ban_bad_contact` enum(\'0\',\'1\') NOT NULL,
	PRIMARY KEY  (`id`)
';