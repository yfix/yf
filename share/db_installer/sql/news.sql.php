<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT \'\',
  `head_text` text NOT NULL,
  `full_text` text NOT NULL,
  `meta_keywords` text NOT NULL,
  `meta_desc` text NOT NULL,
  `add_date` int(10) unsigned NOT NULL DEFAULT \'0\',
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`)
';
