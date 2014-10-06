<?php
return '
  `id` varchar(32) NOT NULL DEFAULT \'0\',
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT \'0\',
  `user_name` varchar(64) DEFAULT NULL,
  `user_group` smallint(3) unsigned DEFAULT NULL,
  `ip_address` varchar(16) DEFAULT NULL,
  `user_agent` varchar(64) DEFAULT NULL,
  `login_date` int(10) unsigned NOT NULL DEFAULT \'0\',
  `last_update` int(10) unsigned DEFAULT NULL,
  `login_type` tinyint(1) unsigned DEFAULT NULL,
  `location` varchar(40) DEFAULT NULL,
  `in_forum` smallint(5) unsigned NOT NULL DEFAULT \'0\',
  `in_topic` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `in_topic` (`in_topic`),
  KEY `in_forum` (`in_forum`)
';
