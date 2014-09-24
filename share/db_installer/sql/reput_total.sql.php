<?php
return '
  `user_id` int(10) unsigned NOT NULL,
  `points` int(11) NOT NULL,
  `alt_power` int(11) NOT NULL,
  `num_votes` int(10) unsigned NOT NULL,
  `num_voted` int(10) unsigned NOT NULL,
  `reput_change` int(11) NOT NULL,
  PRIMARY KEY (`user_id`)
';
