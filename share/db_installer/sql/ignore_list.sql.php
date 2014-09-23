<?php
return '
  `user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `target_user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `add_date` int(10) unsigned NOT NULL DEFAULT \'0\',
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`user_id`)
';
