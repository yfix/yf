<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ads_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `author_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `action` text NOT NULL,
  `date` int(10) unsigned NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
