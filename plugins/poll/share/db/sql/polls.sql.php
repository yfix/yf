<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `object_name` varchar(255) NOT NULL,
  `object_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `question` varchar(255) NOT NULL,
  `add_date` int(10) unsigned NOT NULL DEFAULT \'0\',
  `choices` text NOT NULL,
  `votes` smallint(5) unsigned NOT NULL DEFAULT \'0\',
  `multiple` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`),
  KEY `object_name` (`object_name`),
  KEY `object_id` (`object_id`)
';
