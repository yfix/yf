<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  `order` int(10) unsigned NOT NULL,
  `active` enum(\'1\',\'0\') NOT NULL,
  `user_groups` varchar(255) NOT NULL,
  `site_ids` varchar(255) NOT NULL,
  `server_ids` varchar(255) NOT NULL,
  `cond_code` varchar(255) NOT NULL DEFAULT \'\',
  `icon` varchar(255) NOT NULL,
  `other_info` text NOT NULL,
  PRIMARY KEY (`id`)
';
