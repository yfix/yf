<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `object_id` int(10) unsigned NOT NULL,
  `offer_id` int(10) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `add_time` int(10) unsigned NOT NULL,
  `object` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  `tpl_name` varchar(255) NOT NULL,
  `comment` varchar(255) NOT NULL,
  `recalculation` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  KEY `tpl_name` (`tpl_name`),
  KEY `offer_id` (`offer_id`),
  KEY `object_id` (`object_id`),
  KEY `user_id` (`user_id`)
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
