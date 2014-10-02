<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` int(10) unsigned NOT NULL,
  `object_id` int(10) unsigned NOT NULL,
  `object_name` varchar(64) NOT NULL DEFAULT \'\',
  `owner_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `date` int(10) unsigned NOT NULL,
  `text` varchar(128) NOT NULL DEFAULT \'\',
  `site_id` int(10) unsigned NOT NULL,
  `ip` varchar(16) NOT NULL DEFAULT \'\',
  `user_agent` varchar(255) NOT NULL DEFAULT \'\',
  `referer` varchar(255) NOT NULL DEFAULT \'\',
  `request_uri` varchar(255) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`id`)
';
