<?php

return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url_from` varchar(1024) NOT NULL,
  `url_to` varchar(1024) NOT NULL,
  `date` datetime NOT NULL,
  `ip` varchar(16) NOT NULL DEFAULT \'\',
  `query_string` varchar(1024) NOT NULL DEFAULT \'\',
  `user_agent` varchar(255) NOT NULL DEFAULT \'\',
  `referer` varchar(1024) NOT NULL DEFAULT \'\',
  `object` varchar(255) NOT NULL DEFAULT \'\',
  `action` varchar(255) NOT NULL DEFAULT \'\',
  `user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `user_group` tinyint(3) unsigned NOT NULL,
  `site_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
  `server_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
  `locale` varchar(16) NOT NULL DEFAULT \'\',
  `is_admin` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  `rewrite_mode` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  `use_rewrite` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  `debug_mode` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  `exec_time` float NOT NULL DEFAULT \'0\',
  `redirect_type` varchar(16) NOT NULL DEFAULT \'\',
  `reason` varchar(1024) NOT NULL DEFAULT \'\',
  `trace` varchar(1024) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`id`)
';
