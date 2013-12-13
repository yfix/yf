<?php

load('oauth_driver', 'framework', 'classes/oauth/');
class yf_oauth_driver_twitter extends yf_oauth_driver {

	protected $url_authorize = 'https://api.twitter.com/oauth/authorize';
	protected $url_access_token = 'https://api.twitter.com/oauth/access_token';
	protected $url_user = 'https://api.twitter.com/1/users/lookup.json';// ?user_id={user_id}';
	protected $provider = 'twitter';
	protected $scope = '';
#	protected $get_access_token_method = 'POST';

// TODO: oauth v1

	/**
	*/
	function get_user_info() {
// TODO
	}

	/**
	*/
	function get_access_token () {
// TODO
	}

	/**
	*/
	function authorize () {
// TODO
	}
}