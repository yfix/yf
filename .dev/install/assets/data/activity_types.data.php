<?php
return array (
  2 => 
  array (
    'id' => '2',
    'name' => 'forum_post',
    'desc' => 'For every forum post.',
    'points' => '10',
    'min_value' => '100',
    'min_time' => '120',
    'active' => '1',
    'table_name' => 'forum_posts',
  ),
  3 => 
  array (
    'id' => '3',
    'name' => 'sent_mail',
    'desc' => 'For every email to other site member sent using email form on our site.',
    'points' => '3',
    'min_value' => '100',
    'min_time' => '180',
    'active' => '1',
    'table_name' => 'mailarchive',
  ),
  4 => 
  array (
    'id' => '4',
    'name' => 'rate_user',
    'desc' => 'For every reputation vote.',
    'points' => '3',
    'min_value' => '',
    'min_time' => '120',
    'active' => '1',
    'table_name' => 'reput_user_votes',
  ),
  5 => 
  array (
    'id' => '5',
    'name' => 'blog_post',
    'desc' => '',
    'points' => '10',
    'min_value' => '100',
    'min_time' => '180',
    'active' => '1',
    'table_name' => 'blog_posts',
  ),
  6 => 
  array (
    'id' => '6',
    'name' => 'site_login',
    'desc' => 'For every login to our site (1 per day).',
    'points' => '1',
    'min_value' => '',
    'min_time' => '43200',
    'active' => '1',
    'table_name' => 'log_auth',
  ),
  7 => 
  array (
    'id' => '7',
    'name' => 'blog_comment',
    'desc' => 'For every meaningful comment to someone\'s blog.',
    'points' => '2',
    'min_value' => '100',
    'min_time' => '180',
    'active' => '1',
    'table_name' => 'comments',
  ),
  10 => 
  array (
    'id' => '10',
    'name' => 'bug_report',
    'desc' => 'For reporting site software bugs to us using support ticket system.',
    'points' => '30',
    'min_value' => '100',
    'min_time' => '300',
    'active' => '1',
    'table_name' => 'help_tickets',
  ),
  11 => 
  array (
    'id' => '11',
    'name' => 'article_posted',
    'desc' => 'For every article published on our site.',
    'points' => '300',
    'min_value' => '1000',
    'min_time' => '300',
    'active' => '1',
    'table_name' => 'articles_texts',
  ),
  16 => 
  array (
    'id' => '16',
    'name' => 'article_reposted',
    'desc' => 'For every article published on our site.',
    'points' => '100',
    'min_value' => '1000',
    'min_time' => '300',
    'active' => '1',
    'table_name' => 'articles_texts',
  ),
);