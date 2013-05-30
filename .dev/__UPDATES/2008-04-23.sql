CREATE TABLE `test_log_user_action` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
`owner_id` INT UNSIGNED NOT NULL ,
`action_name` VARCHAR( 255 ) NOT NULL ,
`member_id` INT UNSIGNED NOT NULL ,
`object_name` VARCHAR( 255 ) NOT NULL ,
`object_id` INT UNSIGNED NOT NULL ,
`add_date` INT UNSIGNED NOT NULL ,
PRIMARY KEY ( `id` ) 
);