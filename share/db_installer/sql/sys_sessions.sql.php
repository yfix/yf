<?php
return '
  `id` varchar(32) NOT NULL DEFAULT \'\',
  `user_id` int(10) unsigned NOT NULL,
  `user_group` int(10) unsigned NOT NULL,
  `start_time` int(10) unsigned NOT NULL DEFAULT \'0\',
  `last_time` int(10) unsigned NOT NULL DEFAULT \'0\',
  `type` enum(\'user\',\'admin\') NOT NULL,
  `host_name` varchar(128) NOT NULL,
  `data` longtext,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `start_time` (`start_time`)
';
