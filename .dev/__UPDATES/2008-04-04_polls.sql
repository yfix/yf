CREATE TABLE IF NOT EXISTS `test_polls` (
`id` int( 10 ) unsigned NOT NULL AUTO_INCREMENT ,
`object_name` varchar( 255 ) NOT NULL ,
`object_id` int( 10 ) unsigned NOT NULL default '0',
`user_id` int( 10 ) unsigned NOT NULL default '0',
`question` varchar( 255 ) NOT NULL ,
`add_date` int( 10 ) unsigned NOT NULL default '0',
`choices` text NOT NULL ,
`votes` smallint( 5 ) unsigned NOT NULL default '0',
`active` enum( '1', '0' ) NOT NULL default '1',
PRIMARY KEY ( `id` ) ,
KEY `object_name` ( `object_name` ) ,
KEY `object_id` ( `object_id` ) 
)

CREATE TABLE IF NOT EXISTS `test_poll_votes` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `poll_id` int(10) unsigned NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL default '0',
  `date` int(10) unsigned NOT NULL default '0',
  `value` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `poll_id` (`poll_id`),
  KEY `user_id` (`user_id`)
)