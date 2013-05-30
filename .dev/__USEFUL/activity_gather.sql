(SELECT SUM(`activity`) AS `0` FROM `sexy_reviews` WHERE `reviewer_id`=1 AND `status` NOT IN(0,3)) 
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `sexy_forum_posts` WHERE `user_id`=1) 
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `sexy_mailarchive` WHERE `sender`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `sexy_reput_user_votes` WHERE `user_id`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `sexy_blog_posts` WHERE `user_id`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `sexy_sys_log_auth` WHERE `user_id`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `sexy_comments` WHERE `object_name`='blog' AND `user_id`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `sexy_comments` WHERE `object_name`='reviews' AND `user_id`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `sexy_help_tickets` WHERE `user_id`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `sexy_articles_texts` WHERE `user_id`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `sexy_referrals` WHERE `type`='e' AND `user_id`=1);