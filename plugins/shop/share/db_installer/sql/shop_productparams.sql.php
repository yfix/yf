<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT \'\',
  `type` tinyint(4) NOT NULL DEFAULT \'0\',
  `sort` varchar(10) NOT NULL DEFAULT \'\',
  `description_short` text NOT NULL,
  `size_table_url` varchar(100) NOT NULL,
  `size_table_title` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
';
