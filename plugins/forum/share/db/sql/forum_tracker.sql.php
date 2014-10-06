<?php
return '
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) NOT NULL DEFAULT \'0\',
  `topic_id` int(10) NOT NULL DEFAULT \'0\',
  `start_date` int(10) DEFAULT NULL,
  `last_sent` int(10) NOT NULL DEFAULT \'0\',
  `topic_track_type` varchar(100) NOT NULL DEFAULT \'delayed\',
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`)
';
