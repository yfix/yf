<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT \'0\',
  `provider` varchar(64) NOT NULL DEFAULT \'\',
  `provider_uid` varchar(64) NOT NULL DEFAULT \'\',
  `login` varchar(128) NOT NULL DEFAULT \'\',
  `email` varchar(128) NOT NULL DEFAULT \'\',
  `name` varchar(128) NOT NULL DEFAULT \'\',
  `profile_url` varchar(128) NOT NULL DEFAULT \'\',
  `avatar_url` varchar(128) NOT NULL DEFAULT \'\',
  `json_normalized` text NOT NULL DEFAULT \'\',
  `json_raw` text NOT NULL DEFAULT \'\',
  `add_date` int(11) NOT NULL DEFAULT \'0\',
  `last_date` int(11) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_uid` (`provider`,`provider_uid`)
';
