<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_box extends yf_oauth_driver2 {

	protected $url_authorize = 'https://www.box.com/api/oauth2/authorize';
	protected $url_access_token = 'https://www.box.com/api/oauth2/token';
	protected $url_user = 'https://api.box.com/2.0/users/me';
	public $scope = '';
	public $url_params_access_token = array(
		'grant_type'	=> 'authorization_code',
	);
	public $get_access_token_method = 'POST';
	public $get_user_info_user_bearer = true;
	public $redirect_uri_force_https = true;

	/**
	*/
	function _get_user_info_for_auth($raw = array()) {
		$user_info = array(
			'user_id'		=> $raw['id'],
			'login'			=> $raw['login'],
			'name'			=> $raw['name'],
			'email'			=> $raw['login'],
			'avatar_url'	=> $raw['avatar_url'],
			'profile_url'	=> '',
			'phone'			=> $raw['phone'],
			'lang'			=> $raw['language'],
		);
		return $user_info;
	}
}
