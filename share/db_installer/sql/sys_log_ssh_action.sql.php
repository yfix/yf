<?php
return '
  `microtime` decimal(13,3) unsigned NOT NULL DEFAULT \'0.000\',
  `server_id` varchar(64) NOT NULL DEFAULT \'\',
  `init_type` enum(\'user\',\'admin\') DEFAULT \'user\',
  `action` varchar(32) NOT NULL DEFAULT \'\',
  `comment` varchar(255) NOT NULL DEFAULT \'\',
  `get_object` varchar(32) NOT NULL DEFAULT \'\',
  `get_action` varchar(32) NOT NULL DEFAULT \'\',
  `user_id` int(11) unsigned NOT NULL DEFAULT \'0\',
  `user_group` tinyint(2) unsigned NOT NULL DEFAULT \'0\',
  `ip` varchar(32) NOT NULL DEFAULT \'\',
  KEY `idx_1` (`microtime`),
  KEY `idx_2` (`server_id`)
';
