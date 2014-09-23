<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `community_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `member` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  `post` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  `unmoderated` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  `moderator` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  `maintainer` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
';
