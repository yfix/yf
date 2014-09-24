<?php
return '
  `id` int(10) unsigned NOT NULL,
  `sender_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `receiver_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `s_section` enum(\'sent\',\'trash\') NOT NULL DEFAULT \'sent\',
  `r_section` enum(\'inbox\',\'trash\') NOT NULL DEFAULT \'inbox\',
  `s_status` enum(\'read\',\'unread\',\'replied\',\'sent\',\'approved\',\'disapproved\',\'deleted\') NOT NULL DEFAULT \'unread\',
  `r_status` enum(\'read\',\'unread\',\'replied\',\'approved\',\'disapproved\',\'deleted\') NOT NULL DEFAULT \'unread\',
  `add_date` int(10) unsigned NOT NULL DEFAULT \'0\',
  `subject` varchar(255) NOT NULL DEFAULT \'\',
  `message` text NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT \'standard\',
  `special_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `sender_id` (`sender_id`)
';
