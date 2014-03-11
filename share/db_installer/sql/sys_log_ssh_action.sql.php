<?php
$data = '
	`microtime`		decimal(13,3) unsigned NOT NULL default \'0.000\',
	`server_id`		varchar(64) NOT NULL default \'\',
	`init_type`		enum(\'user\',\'admin\') default \'user\',
	`action`		varchar(32) NOT NULL default \'\',
	`comment`		varchar(255) NOT NULL default \'\',
	`get_object`	varchar(32) NOT NULL default \'\',
	`get_action`	varchar(32) NOT NULL default \'\',
	`user_id`		int(11) unsigned NOT NULL default \'0\',
	`user_group`	tinyint(2) unsigned NOT NULL default \'0\',
	`ip`			varchar(32) NOT NULL default \'\',
	KEY  (`microtime`),
	KEY  (`server_id`)
';