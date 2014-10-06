<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `name` varchar(255) NOT NULL DEFAULT \'\',
  `text` text NOT NULL DEFAULT \'\',
  `page_title` varchar(255) NOT NULL DEFAULT \'\',
  `page_heading` varchar(255) NOT NULL DEFAULT \'\',
  `meta_keywords` text NOT NULL DEFAULT \'\',
  `meta_desc` text NOT NULL DEFAULT \'\',
  `locale` char(6) NOT NULL DEFAULT \'\',
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`)
';
