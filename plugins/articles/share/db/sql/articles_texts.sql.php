<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `is_own_article` enum(\'1\',\'0\') NOT NULL,
  `author_name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text NOT NULL,
  `full_text` longtext NOT NULL,
  `status` enum(\'new\',\'edited\',\'suspended\',\'active\') NOT NULL,
  `credentials` text NOT NULL,
  `add_date` int(10) unsigned NOT NULL,
  `edit_date` int(10) unsigned NOT NULL,
  `views` int(10) unsigned NOT NULL,
  `short_url` varchar(255) NOT NULL,
  `activity` int(10) unsigned NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
';
