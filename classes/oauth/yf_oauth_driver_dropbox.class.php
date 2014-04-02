<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_dropbox extends yf_oauth_driver2 {

	protected $url_authorize = 'https://www.dropbox.com/1/oauth2/authorize';
	protected $url_access_token = 'https://api.dropbox.com/1/oauth2/token';
	protected $url_user = 'https://api.dropbox.com/1/account/info';
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
			'user_id'		=> $raw['uid'],
			'login'			=> $raw['email'],
			'name'			=> $raw['display_name'],
			'email'			=> $raw['email'],
			'avatar_url'	=> '',
			'profile_url'	=> '',
			'country'		=> $raw['country'],
		);
		return $user_info;
	}
}
