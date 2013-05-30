CREATE TABLE `test_user_data_info_fields` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
);
        

CREATE TABLE `test_user_data_info_values` (
  `user_id` int(10) unsigned NOT NULL,
  `field_id` int(10) unsigned NOT NULL,
  `value` text NOT NULL,
  KEY `user_id` (`user_id`)
);
        