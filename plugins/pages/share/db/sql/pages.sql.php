<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `locale` char(5) NOT NULL DEFAULT \'\',
  `name` varchar(255) NOT NULL DEFAULT \'\',
  `title` varchar(255) NOT NULL DEFAULT \'\',
  `heading` varchar(255) NOT NULL DEFAULT \'\',
  `text` longtext NOT NULL DEFAULT \'\',
  `meta_keywords` text NOT NULL DEFAULT \'\',
  `meta_desc` text NOT NULL DEFAULT \'\',
  `date_created` datetime,
  `date_modified` datetime,
  `content_type` tinyint(2) unsigned NOT NULL DEFAULT \'1\',
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_2` (`locale`,`name`)
';
