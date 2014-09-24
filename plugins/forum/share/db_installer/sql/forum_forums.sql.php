<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent` int(10) unsigned NOT NULL DEFAULT \'0\',
  `category` int(10) unsigned NOT NULL DEFAULT \'0\',
  `name` varchar(255) NOT NULL DEFAULT \'\',
  `desc` varchar(255) NOT NULL DEFAULT \'\',
  `created` int(10) unsigned NOT NULL DEFAULT \'0\',
  `status` char(1) NOT NULL DEFAULT \'a\',
  `active` tinyint(1) NOT NULL DEFAULT \'1\',
  `icon` varchar(255) NOT NULL DEFAULT \'\',
  `order` int(10) unsigned NOT NULL DEFAULT \'0\',
  `num_views` int(10) unsigned NOT NULL DEFAULT \'0\',
  `num_topics` int(10) unsigned NOT NULL DEFAULT \'0\',
  `num_posts` int(10) unsigned NOT NULL DEFAULT \'0\',
  `last_post_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `last_post_date` int(10) unsigned NOT NULL DEFAULT \'0\',
  `language` varchar(12) NOT NULL DEFAULT \'0\',
  `options` char(10) NOT NULL,
  `user_groups` varchar(255) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`id`)
';
