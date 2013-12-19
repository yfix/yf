<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_amazon extends yf_oauth_driver2 {
/*
	protected $url_authorize = 'https://www.amazon.com/ap/oa';
	protected $url_access_token = 'https://www.amazon.com/ap/oatoken';
	protected $url_user = 'https://api.amazon.com/';
	protected $scope = 'profile';
	protected $get_access_token_method = 'POST';
*/
// TODO

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