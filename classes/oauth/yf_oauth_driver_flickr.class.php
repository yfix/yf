<?php

load('oauth_driver1', 'framework', 'classes/oauth/');
class yf_oauth_driver_flickr extends yf_oauth_driver1 {

	protected $url_authenticate = 'http://www.flickr.com/services/oauth/authorize';
	protected $url_request_token = 'http://www.flickr.com/services/oauth/request_token';
	protected $url_access_token = 'http://www.flickr.com/services/oauth/access_token';
	protected $url_user = 'http://api.flickr.com/services/rest'; // ?nojsoncallback=1  &method=flickr.test.login
	protected $get_access_token_method = 'POST';

// TODO
}