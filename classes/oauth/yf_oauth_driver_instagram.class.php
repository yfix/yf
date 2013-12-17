<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_instagram extends yf_oauth_driver2 {

	protected $url_authorize = 'https://api.instagram.com/oauth/authorize/';
	protected $url_access_token = 'https://api.instagram.com/oauth/access_token';
	protected $url_user = 'https://api.instagram.com/v1/users/self';
	protected $scope = 'basic';
	protected $get_access_token_method = 'POST';
	protected $url_params_access_token = array(
		'grant_type'	=> 'authorization_code',
	);

	/**
	*/
	function _get_user_info_for_auth($raw = array()) {
/*
		$user_info = array(
			'user_id'		=> $raw['id'],
			'login'			=> $raw['login'],
			'name'			=> $raw['id'],
			'email'			=> current($raw['emails']),
			'avatar_url'	=> $raw['avatar_url'],
			'profile_url'	=> $raw['url'],
		);
*/
		return $user_info;
	}
}