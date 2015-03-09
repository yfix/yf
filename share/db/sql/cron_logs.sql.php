<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cron_id` int(10) unsigned NOT NULL,
  `log` text NOT NULL,
  `time_start` int(10) unsigned NOT NULL,
  `time_end` int(10) unsigned NOT NULL,
  `time_spent` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
