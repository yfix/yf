<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `target_user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `add_date` int(10) unsigned NOT NULL DEFAULT \'0\',
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`target_user_id`)
';
