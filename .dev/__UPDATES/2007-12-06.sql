ALTER TABLE `sexy_tips` ADD `locale` CHAR( 7 ) DEFAULT 'en' NOT NULL ;
ALTER TABLE `sexy_tips` CHANGE `text` `text` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `sexy_moods` ADD `locale` CHAR( 7 ) DEFAULT 'en' NOT NULL ;
ALTER TABLE `sexy_moods` CHANGE `name` `name` varchar(255) CHARACTER SET utf8 NOT NULL default '';

ALTER TABLE `sexy_prof_interests` ADD `locale` CHAR( 7 ) DEFAULT 'en' NOT NULL ;
ALTER TABLE `sexy_prof_interests` 
	CHANGE `name` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
