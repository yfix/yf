<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_bitly extends yf_oauth_driver2 {

	protected $url_authorize = 'https://bitly.com/oauth/authorize';
	protected $url_access_token = 'https://api-ssl.bitly.com/oauth/access_token';
	protected $url_user = 'https://api-ssl.bitly.com/v3/user/info';
	public $scope = '';
	public $get_access_token_method = 'POST';
	public $url_params_access_token = [
		'grant_type'	=> 'authorization_code',
	];

	/**
	*/
	function _get_user_info_for_auth($raw = []) {
		$user_info = [
			'user_id'		=> $raw['data']['login'],
			'login'			=> $raw['data']['login'],
			'name'			=> $raw['data']['display_name'] ?: $raw['data']['login'],
			'email'			=> '',
			'avatar_url'	=> $raw['data']['profile_image'],
			'profile_url'	=> $raw['data']['profile_url'],
		];
		return $user_info;
	}
}
