<?php

load('oauth_driver1', 'framework', 'classes/oauth/');
class yf_oauth_driver_twitter extends yf_oauth_driver1 {

	// Register for API client_id and client_secret here: https://dev.twitter.com/apps

	protected $url_request_token = 'https://api.twitter.com/oauth/request_token';
	protected $url_access_token = 'https://api.twitter.com/oauth/access_token';
	protected $url_authenticate = 'https://api.twitter.com/oauth/authenticate';
	protected $url_user = 'https://api.twitter.com/1.1/users/lookup.json';
	public $header_add_realm = false;

	/**
	*/
	function _get_user_info_for_auth($raw = array()) {
		$user_info = array(
			'user_id'		=> $raw[0]['id'],
			'login'			=> $raw[0]['screen_name'],
			'name'			=> $raw[0]['name'],
#			'email'			=> $raw[0]['email'],
			'avatar_url'	=> $raw[0]['profile_image_url'],
			'profile_url'	=> $raw[0]['url'], // Can be empty
		);
		return $user_info;
	}
}
