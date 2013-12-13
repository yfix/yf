<?php

load('oauth_driver', 'framework', 'classes/oauth/');
class yf_oauth_driver_odnoklassniki extends yf_oauth_driver {

	protected $url_authorize = 'http://www.odnoklassniki.ru/oauth/authorize';
	protected $url_access_token = 'http://api.odnoklassniki.ru/oauth/token.do';
	protected $url_user = 'http://api.odnoklassniki.ru/fb.do';
	protected $provider = 'odnoklassniki';
	protected $scope = 'SET_STATUS;VALUABLE_ACCESS';
	protected $get_access_token_method = 'POST';
	protected $url_params_access_token = array(
		'grant_type'	=> 'authorization_code',
	);

	/**
	*/
	function get_user_info() {
		$access_token = $this->_storage_get('access_token');
		if (!$access_token) {
			$access_token = $this->get_access_token();
			if (!$access_token) {
				return false;
			}
		}
		if (!$this->_storage_get('user')) {
			$method = 'users.getCurrentUser';
			$sign = md5('application_key='.$this->client_public. 'method='. $method. md5($access_token. $this->client_secret));
			$url = $this->url_user.'?'.http_build_query(array(
				'access_token'		=> $access_token,
				'application_key'	=> $this->client_public,
				'method'			=> $method,
				'sig'				=> $sign,
			));
			$result = common()->get_remote_page($url, $cache = false, $opts, $response);
			$result = $this->_decode_result($result, $response);
			$this->_storage_set('user_info_request', array('result' => $result, 'response' => $response));
			$this->_storage_set('user', $result);
		}
		return $this->_storage_get('user');
	}
}