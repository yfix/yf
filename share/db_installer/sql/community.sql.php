<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL DEFAULT \'0\',
  `user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `title` varchar(255) NOT NULL,
  `membership` enum(\'open\',\'moderated\',\'closed\') NOT NULL DEFAULT \'open\',
  `nonmember_posting` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  `postlevel` enum(\'members\',\'select\') NOT NULL DEFAULT \'members\',
  `moderated` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  `adult` enum(\'none\',\'concepts\',\'explicit\') NOT NULL DEFAULT \'none\',
  `about` varchar(255) NOT NULL,
  `active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
';
