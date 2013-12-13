<?php

load('oauth_driver', 'framework', 'classes/oauth/');
class yf_oauth_driver_twitter extends yf_oauth_driver {

	protected $url_authorize = 'https://api.twitter.com/oauth/authorize';
	protected $url_request_token = 'https://api.twitter.com/oauth/request_token';
	protected $url_access_token = 'https://api.twitter.com/oauth/access_token';
	protected $url_user = 'https://api.twitter.com/1/users/lookup.json';// ?user_id={user_id}';
	protected $scope = '';
#	protected $get_access_token_method = 'POST';

// TODO: oauth v1

	/**
	*/
	function get_user_info() {
		return $this->authorize();
// TODO
/*
		$access_token = $this->_storage_get('access_token');
		if (!$access_token) {
			$access_token = $this->get_access_token();
			if (!$access_token) {
				return false;
			}
		}
		if (!$this->_storage_get('user')) {
			$url = $this->url_user.'?'.http_build_query($this->url_params + $this->url_params_user_info + array(
				'access_token'	=> $access_token,
			));
			$result = common()->get_remote_page($url, $cache = false, $opts, $response);
			$result = $this->_decode_result($result, $response, __FUNCTION__);
			if (isset($result['error']) || substr($response['http_code'], 0, 1) == '4') {
				return false;
			} else {
				$this->_storage_set('user_info_request', array('result' => $result, 'response' => $response));
				$this->_storage_set('user', $result);
			}
		}
		return $this->_storage_get('user');
*/
	}

	/**
	*/
	function get_access_token() {
// TODO
/*
		$access_token = $this->_storage_get('access_token');
		if ($access_token) {
			return $access_token;
		}
		$code = $_GET['code'];
		if (!$code) {
			return $this->authorize();
		}
		$url_params = $this->url_params + $this->url_params_access_token + array(
			'client_id'		=> $this->client_id,
			'client_secret' => $this->client_secret,
			'redirect_uri' 	=> $this->redirect_uri,
			'code'			=> $code,
		);
		if ($this->get_access_token_method == 'POST') {
			$url = $this->url_access_token;
			$opts = array(
				'post'	=> $url_params,
			);
		} else {
			$url = $this->url_access_token.'?'.http_build_query($url_params);
		}
		$result = common()->get_remote_page($url, $cache = false, $opts, $response);
		$result = $this->_decode_result($result, $response, __FUNCTION__);
		if (isset($result['error']) || substr($response['http_code'], 0, 1) == '4') {
			js_redirect( $this->redirect_uri, $url_rewrite = false );
			return false;
		} else {
			$this->_storage_set('access_token_request', array('result' => $result, 'response' => $response));
			$this->_storage_set('access_token', $result['access_token']);
		}
		return $this->_storage_get('access_token');
*/
	}

	/**
	*/
	function authorize() {
		$url = $this->url_request_token;

		$this->_storage_set('nonce', md5(microtime().rand(1,10000000)));
		$this->_storage_set('last_time', time());

		$params = array(
			'oauth_version'			=> '1.0',
#			'oauth_callback'		=> $this->encode($this->redirect_uri),
			'oauth_consumer_key'	=> $this->client_id,
			'oauth_nonce'			=> $this->_storage_get('nonce'), // 'kYjzVBB8Y0ZFabxSWbWovY3uYSQ2pTgmZeNu2VS4cg',
			'oauth_timestamp'		=> $this->_storage_get('last_time'),
			'oauth_signature_method'=> 'HMAC-SHA1',
#			'oauth_signature'		=> '', // 'tnnArxj06cWHq44gCs1OSKk%2FjLY%3D',
#			'oauth_token' => '', // '370773112-GmHxMAgYyLbNEtIKZeRNFsMKPR9EyMZeS9weJAEb',
		);
		ksort($params);

		$str = array();
		foreach($params as $k => $v) {
			$str[$k] = $k.'="'.$this->encode($v).'"';
		}
		$str = 'POST'. '&'. $this->encode($url). '&'. $this->encode(http_build_query($params));
#echo $str.'<br>'.PHP_EOL;

		$sign = $this->hmac($str, $this->client_secret.'&'.$oauth_token_secret); // $oauth_token_secret here is empty, it is ok    http://habrahabr.ru/post/145988/
#		$sign = $this->hmac_sha1($str, $this->client_secret.'&'.$oauth_token_secret); // $oauth_token_secret here is empty, it is ok    http://habrahabr.ru/post/145988/
#echo $sign.'<br>'.PHP_EOL;
echo		$sign = $this->encode(base64_encode($sign));
#echo		$sign = $this->encode($sign);
#echo		$sign = base64_encode($sign);
#echo $sign.'<br>'.PHP_EOL;

		$params['oauth_signature'] = $sign;

		$keyval = array();
		foreach($params as $k => $v) {
			$keyval[$k] = $k.'="'.$v.'"';
		}
		$opts = array(
			'post'	=> array(),
			'custom_header' => 'Authorization: OAuth '.implode(', ', $keyval),
		);
		$result = common()->get_remote_page($url, $cache = false, $opts, $response);
		return false;
	}

	function encode($value) {
		return(is_array($value) ? $this->encode_array($value) : str_replace('%7E', '~', str_replace('+',' ', rawurlencode($value))));
	}
	function encode_array($array) {
		foreach($array as $key => $value) {
			$array[$key] = $this->Encode($value);
		}
		return $array;
	}
	function hmac($data, $key, $function = 'sha1') {
		if (!$function) {
			$function = 'sha1';
		}
		switch($function) {
			case 'sha1':
				$pack = 'H40';
				break;
			default:
				return '';
		}
		if (strlen($key) > 64) {
			$key = pack($pack, $function($key));
		}
		if( strlen($key) < 64) {
			$key = str_pad($key, 64, "\0");
		}
		return pack($pack, $function(
			(str_repeat("\x5c", 64) ^ $key)
			.pack($pack, $function(
				(str_repeat("\x36", 64) ^ $key)
				.$data
			))
		));
	}

	/**
	*/
	function hmac_sha1($key, $data) {
		// Adjust key to exactly 64 bytes
		if (strlen($key) > 64) {
			$key = str_pad(sha1($key, true), 64, chr(0));
		}
		if (strlen($key) < 64) {
			$key = str_pad($key, 64, chr(0));
		}
		// Outter and Inner pad
		$opad = str_repeat(chr(0x5C), 64);
		$ipad = str_repeat(chr(0x36), 64);
		// Xor key with opad & ipad
		for ($i = 0; $i < strlen($key); $i++) {
			$opad[$i] = $opad[$i] ^ $key[$i];
			$ipad[$i] = $ipad[$i] ^ $key[$i];
		}
		return sha1($opad.sha1($ipad.$data, true));
	}
}