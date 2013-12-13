<?php

load('oauth_driver1', 'framework', 'classes/oauth/');
class yf_oauth_driver_yahoo extends yf_oauth_driver1 {

	protected $url_request_token = 'https://api.login.yahoo.com/oauth/v2/get_request_token';
	protected $url_access_token = 'https://api.login.yahoo.com/oauth/v2/get_token';
	protected $url_authenticate = 'https://api.login.yahoo.com/oauth/v2/request_auth';
	protected $url_user = 'http://query.yahooapis.com/v1/yql';
	protected $access_token_use_header = false;

	/**
	*/
	function get_user_info() {
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
			$user_id = $this->_storage_get('user_id');
			$url = $this->url_user.'?'.http_build_query($this->url_params + (array)$this->url_params_user_info + array(
				'user_id'	=> $user_id,
			));
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
				'custom_header' => $this->_get_oauth_header($this->url_user, $params, 'GET', $access_token_secret, array('user_id' => $user_id)),
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
}