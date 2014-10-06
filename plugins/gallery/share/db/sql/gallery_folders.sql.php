<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id2` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `comment` text NOT NULL,
  `allow_comments` tinyint(3) unsigned NOT NULL,
  `privacy` tinyint(3) unsigned NOT NULL,
  `content_level` tinyint(3) unsigned NOT NULL,
  `num_photos` int(10) unsigned NOT NULL,
  `num_comments` int(10) unsigned NOT NULL,
  `num_views` int(10) unsigned NOT NULL,
  `user_nick` varchar(255) NOT NULL,
  `password` varchar(32) NOT NULL,
  `is_default` enum(\'0\',\'1\') NOT NULL,
  `add_date` int(10) unsigned NOT NULL,
  `active` enum(\'1\',\'0\') NOT NULL,
  `allow_tagging` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `id2` (`id2`)
';
