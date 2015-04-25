<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT \'\',
  `search_url` varchar(255) NOT NULL DEFAULT \'\',
  `q_s_word` varchar(255) NOT NULL DEFAULT \'\',
  `q_s_word2` varchar(255) NOT NULL DEFAULT \'\',
  `q_s_charset` varchar(255) NOT NULL DEFAULT \'\',
  `def_charset` varchar(255) NOT NULL DEFAULT \'\',
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`)
';
