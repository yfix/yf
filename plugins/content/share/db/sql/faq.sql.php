<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL,
  `author_id` int(10) unsigned NOT NULL,
  `title` text NOT NULL,
  `text` text NOT NULL,
  `active` enum(\'1\',\'0\') NOT NULL,
  `add_date` int(10) unsigned NOT NULL,
  `edit_date` int(10) unsigned NOT NULL,
  `views` int(10) unsigned NOT NULL,
  `locale` char(2) NOT NULL DEFAULT \'ru\',
  `order` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
