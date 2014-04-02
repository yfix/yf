<?php

load('oauth_driver1', 'framework', 'classes/oauth/');
class yf_oauth_driver_vimeo extends yf_oauth_driver1 {

	protected $url_authenticate = 'https://vimeo.com/oauth/authorize';
	protected $url_request_token = 'https://vimeo.com/oauth/request_token';
	protected $url_access_token = 'https://vimeo.com/oauth/access_token';
	protected $url_user = 'http://vimeo.com/api/rest/v2/?method=vimeo.test.login&format=json';
	public $get_access_token_method = 'POST';

	/**
	*/
	function _get_user_info_for_auth($raw = array()) {
		$user_info = array(
			'user_id'		=> $raw['user']['id'],
#			'login'			=> $raw['login'],
			'name'			=> $raw['user']['username'],
#			'email'			=> $raw['email'],
#			'avatar_url'	=> $raw['avatar_url'],
#			'profile_url'	=> $raw['url'],
		);
		return $user_info;
	}
}
