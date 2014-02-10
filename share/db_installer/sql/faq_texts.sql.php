<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`cat_id` int(10) unsigned NOT NULL,
	`author_id` int(10) unsigned NOT NULL,
	`question_text` text NOT NULL,
	`answer_text` text NOT NULL,
	`status` enum(\'suspended\',\'active\') NOT NULL default \'active\',
	`priority` tinyint(3) unsigned NOT NULL,
	`add_date` int(10) unsigned NOT NULL,
	`edit_date` int(10) unsigned NOT NULL,
	`views` int(10) unsigned NOT NULL,
	PRIMARY KEY	(`id`)
';