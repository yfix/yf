<?php
return '
  `id` int() unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int() unsigned NOT NULL,
  `action_name` varchar(255) NOT NULL,
  `member_id` int() unsigned NOT NULL,
  `object_name` varchar(255) NOT NULL,
  `object_id` int() unsigned NOT NULL,
  `add_date` int() unsigned NOT NULL,
  PRIMARY KEY (`id`)
';
