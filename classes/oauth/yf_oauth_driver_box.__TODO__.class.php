<?php

load('oauth_driver', 'framework', 'classes/oauth/');
class yf_oauth_driver_box extends yf_oauth_driver {

	protected $url_authorize = 'https://www.box.com/api/oauth2/authorize';
	protected $url_access_token = 'https://www.box.com/api/oauth2/token';
#	protected $url_user = '';
#	protected $scope = '';
#	protected $get_access_token_method = 'POST';

// TODO

}