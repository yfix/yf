<?php
return '
  `id` mediumint(6) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) NOT NULL DEFAULT \'\',
  `email` varchar(255) NOT NULL DEFAULT \'\',
  `passwd` varchar(255) NOT NULL DEFAULT \'\',
  `text` varchar(255) NOT NULL DEFAULT \'\',
  `tel` varchar(255) NOT NULL DEFAULT \'\',
  `fax` varchar(255) NOT NULL DEFAULT \'\',
  `url` varchar(255) NOT NULL DEFAULT \'\',
  `recip_url` varchar(255) NOT NULL DEFAULT \'\',
  `ban_ads` tinyint(1) NOT NULL DEFAULT \'1\',
  `ban_reviews` tinyint(1) NOT NULL DEFAULT \'1\',
  `ban_images` tinyint(1) NOT NULL DEFAULT \'0\',
  `ban_email` tinyint(1) NOT NULL DEFAULT \'1\',
  `ban_forum` tinyint(1) NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`)
';
