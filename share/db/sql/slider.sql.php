<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `descr` varchar(255) NOT NULL,
  `link_title` varchar(255) NOT NULL,
  `link_style` varchar(255) NOT NULL,
  `link_url` varchar(255) NOT NULL,
  `add_date` int(10) unsigned NOT NULL,
  `video` tinyint(1) NOT NULL DEFAULT \'0\',
  `status` tinyint(3) NOT NULL,
  `position` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
