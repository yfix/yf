<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `poll_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `date` int(10) unsigned NOT NULL DEFAULT \'0\',
  `value` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `poll_id` (`poll_id`),
  KEY `user_id` (`user_id`)
';
