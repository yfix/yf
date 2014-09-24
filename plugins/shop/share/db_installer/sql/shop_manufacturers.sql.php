<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_ids` varchar(255) NOT NULL,
  `name` varchar(64) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT \'\',
  `url` varchar(255) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `desc` varchar(255) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `aliaces` varchar(255) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `meta_keywords` text NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `meta_desc` text NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `image` int(10) unsigned NOT NULL,
  `sort_order` int(3) NOT NULL,
  `active` int(3) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `url` (`url`)
';
