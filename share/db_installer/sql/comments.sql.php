<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `object_name` varchar(24) NOT NULL,
  `object_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `parent_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `user_name` varchar(64) NOT NULL DEFAULT \'\',
  `user_email` varchar(64) NOT NULL DEFAULT \'\',
  `add_date` int(10) unsigned NOT NULL DEFAULT \'0\',
  `text` text NOT NULL,
  `ip` varchar(15) NOT NULL DEFAULT \'\',
  `active` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
  `activity` int(10) unsigned NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  KEY `object_name` (`object_name`),
  KEY `object_id` (`object_id`),
  KEY `user_id` (`user_id`)
';
