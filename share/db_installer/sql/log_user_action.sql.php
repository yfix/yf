<?php
return '
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) unsigned NOT NULL,
  `action_name` varchar(255) NOT NULL,
  `member_id` int(11) unsigned NOT NULL,
  `object_name` varchar(255) NOT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `add_date` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
';
