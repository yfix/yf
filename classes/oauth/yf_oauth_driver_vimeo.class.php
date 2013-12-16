<?php

load('oauth_driver1', 'framework', 'classes/oauth/');
class yf_oauth_driver_vimeo extends yf_oauth_driver1 {

	protected $url_authenticate = 'https://vimeo.com/oauth/authorize';
	protected $url_access_token = 'https://vimeo.com/oauth/access_token';
#	protected $url_user = '';
	protected $scope = '';
	protected $get_access_token_method = 'POST';

// TODO
}