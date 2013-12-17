<?php

load('oauth_driver1', 'framework', 'classes/oauth/');
class yf_oauth_driver_bitbucket extends yf_oauth_driver1 {

	protected $url_request_token = 'https://bitbucket.org/api/1.0/oauth/request_token';
	protected $url_authenticate = 'https://bitbucket.org/api/1.0/oauth/authenticate';
	protected $url_access_token = 'https://bitbucket.org/api/1.0/oauth/access_token';
	protected $url_user = 'https://bitbucket.org/api/1.0/user';
	protected $get_access_token_method = 'POST';

	/**
	*/
	function _get_user_info_for_auth($raw = array()) {
		$user_info = array(
			'user_id'		=> $raw['user']['username'],
			'login'			=> $raw['user']['username'],
			'name'			=> $raw['user']['display_name'],
			'email'			=> '',
			'avatar_url'	=> $raw['user']['avatar'],
			'profile_url'	=> 'https://bitbucket.org/'.$raw['user']['username'],
		);
		return $user_info;
	}
}