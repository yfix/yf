<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT \'\',
  `url` varchar(255) NOT NULL,
  `desc` varchar(255) NOT NULL,
  `meta_keywords` text NOT NULL,
  `meta_desc` text NOT NULL,
  `image` int(10) unsigned NOT NULL,
  `sort_order` int(3) NOT NULL,
  `admin_id` int(3) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `url` (`url`)
';
