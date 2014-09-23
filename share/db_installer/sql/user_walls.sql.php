<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT \'0\',
  `message` text NOT NULL DEFAULT \'\',
  `object` varchar(255) NOT NULL DEFAULT \'\',
  `action` varchar(255) NOT NULL DEFAULT \'\',
  `object_id` int(11) unsigned NOT NULL DEFAULT \'0\',
  `server_id` int(11) unsigned NOT NULL DEFAULT \'0\',
  `site_id` int(11) unsigned NOT NULL DEFAULT \'0\',
  `important` int(11) unsigned NOT NULL DEFAULT \'0\',
  `old_data` text NOT NULL DEFAULT \'\',
  `new_data` text NOT NULL DEFAULT \'\',
  `add_date` datetime NOT NULL,
  `read` tinyint(1) NOT NULL DEFAULT \'0\',
  `type` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
