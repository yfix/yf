<?php
return '
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `desc` varchar(255) NOT NULL,
  `allow_comments` tinyint(3) unsigned NOT NULL,
  `allow_rate` enum(\'1\',\'0\') NOT NULL,
  `allow_tagging` tinyint(3) unsigned NOT NULL,
  `privacy` tinyint(3) unsigned NOT NULL,
  `thumb_type` int(10) unsigned NOT NULL,
  `medium_size` int(10) unsigned NOT NULL,
  `layout_type` int(10) unsigned NOT NULL,
  `thumbs_loc` tinyint(3) unsigned NOT NULL,
  `thumbs_in_row` tinyint(3) unsigned NOT NULL,
  `slideshow_mode` tinyint(3) unsigned NOT NULL,
  `num_photos` int(10) unsigned NOT NULL,
  `num_comments` int(10) unsigned NOT NULL,
  `num_views` int(10) unsigned NOT NULL,
  `user_nick` varchar(255) NOT NULL,
  `geo_cc` char(2) NOT NULL,
  `geo_rc` char(2) NOT NULL,
  `priority` int(11) NOT NULL,
  PRIMARY KEY (`user_id`)
';
