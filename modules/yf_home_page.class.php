<?php

/**
* Home page handling module
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_home_page extends yf_module {

	/** @var int Number of newest news to show */
	public $NUM_NEWEST_NEWS 			= 4;
	/** @var int Number of newest users to show */
	public $NUM_NEWEST_USERS			= 5;
	/** @var int */
	public $NUM_NEWEST_FORUM_POSTS 	= 4;
	/** @var int */
	public $NEWEST_FORUM_TEXT_LEN 		= 300;
	/** @var int */
	public $NUM_NEWEST_BLOG_POSTS		= 4;
	/** @var int */
	public $NEWEST_BLOG_TEXT_LEN		= 300;
	/** @var int */
	public $NUM_NEWEST_GALLERY_PHOTO	= 5;
	/** @var int */
	public $NUM_NEWEST_ARTICLE_POST	= 4;
	/** @var int */
	public $NUM_NEWEST_COMMENTS		= 4;

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	*/
	function show () {
		$cache_name = 'home_page_default';
		$data = cache_get($cache_name);
		if (!$data) {
// TODO
/*
			$data = array(
				'newest_users'			=> module('users')->_for_home_page($this->NUM_NEWEST_USERS),
				'newest_news'			=> module('news')->_for_home_page(array('num_items' => $this->NUM_NEWEST_NEWS)),
				'newest_forum_posts'	=> module('forum')->_for_home_page($this->NUM_NEWEST_FORUM_POSTS, $this->NEWEST_FORUM_TEXT_LEN),
				'newest_blog_posts'		=> module('blog')->_for_home_page($this->NUM_NEWEST_BLOG_POSTS, $this->NEWEST_BLOG_TEXT_LEN),
				'newest_gallery_photo'	=> module('gallery')->_for_home_page($this->NUM_NEWEST_GALLERY_PHOTO),
				'newest_article_post'	=> module('articles')->_for_home_page($this->NUM_NEWEST_ARTICLE_POST),
				'newest_comments'		=> module('comments')->_for_home_page($this->NUM_NEWEST_COMMENTS),
			);
*/
#			cache_set($cache_name, $data);
		}
		$replace = &$data;
		return tpl()->parse($_GET['object'].'/main', $replace);
	}
}
