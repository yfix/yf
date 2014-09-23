<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_id` int(11) NOT NULL,
  `search` varchar(255) NOT NULL,
  `replace` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `process` int(11) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
';
