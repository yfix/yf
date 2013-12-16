<?php

load('oauth_driver1', 'framework', 'classes/oauth/');
class yf_oauth_driver_yahoo extends yf_oauth_driver1 {

	protected $url_request_token = 'https://api.login.yahoo.com/oauth/v2/get_request_token';
	protected $url_access_token = 'https://api.login.yahoo.com/oauth/v2/get_token';
	protected $url_authenticate = 'https://api.login.yahoo.com/oauth/v2/request_auth';
#	protected $url_user = 'http://social.yahooapis.com/v1/user/{guid}/profile?format=json';
	protected $url_user = 'http://social.yahooapis.com/v1/user/{guid}/profile';
	protected $access_token_use_header = false;

// TODO

	/**
	*/
	function get_user_info() {
$debug = $_SESSION['oauth']['yahoo'];
unset($debug['access_token_request']);
unset($debug['request_token_request']);
unset($debug['authorize_request']);
echo '<pre><small>'.print_r($debug, 1).'</small></pre>';

#$this->_storage_clean();
		$access_token = $this->_storage_get('access_token');
		$access_token_secret = $this->_storage_get('access_token_secret');
		if (!$access_token || !$access_token_secret) {
			$access_token = $this->get_access_token();
			$access_token_secret = $this->_storage_get('access_token_secret');
			if (!$access_token || !$access_token_secret) {
#				$this->_storage_clean();
				js_redirect( $this->redirect_uri, $url_rewrite = false );
				return false;
			}
		}
		if (!$this->_storage_get('user')) {
			$guid = 'APF4JWLCUDYDK5IHBK7E5K34ZA';
			$url = str_replace('{guid}', $guid, $this->url_user);
/*
			$url = 'http://query.yahooapis.com/v1/yql?'.http_build_query(array(
				'q' => 'select * from social.profile where guid=me',
				'format' => 'json',
			));
*/
			$this->_storage_set('nonce', md5(microtime().rand(1,10000000)));
			$this->_storage_set('last_time', time());
			$params = array(
				'oauth_version'			=> $this->oauth_version,
				'oauth_consumer_key'	=> $this->client_id,
				'oauth_nonce'			=> $this->_storage_get('nonce'),
				'oauth_timestamp'		=> $this->_storage_get('last_time'),
				'oauth_signature_method'=> 'HMAC-SHA1',
				'oauth_token'			=> $access_token,
			);
			$opts = array(
				'custom_header' => $this->_get_oauth_header($url, $params, 'GET', $access_token_secret),
			);
			$result = common()->get_remote_page($url, $cache = false, $opts, $response);
			$result = $this->_decode_result($result, $response, __FUNCTION__);
			if (isset($result['error']) || substr($response['http_code'], 0, 1) == '4') {
#				$this->_storage_clean();
				js_redirect( $this->redirect_uri, $url_rewrite = false );
				return false;
			} else {
				$this->_storage_set('user_info_request', array('result' => $result, 'response' => $response));
				$this->_storage_set('user', $result);
			}
		}
		return $this->_storage_get('user');
	}

	/**
	*/
/*
	function _get_oauth_header($url, $params, $method = 'POST', $oauth_token_secret = '', $add_to_sign = array()) {
		ksort($params);
		$params['oauth_signature'] = $this->_do_sign_request($url, $params + (array)$add_to_sign, $method, $oauth_token_secret);
		$keyval = array();
		foreach($params as $k => $v) {
			$keyval[$k] = $k.'="'.$v.'"';
		}
		return 'Authorization: OAuth '.implode(', ', $keyval);
	}
*/
}