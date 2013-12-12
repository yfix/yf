<?php

class yf_oauth {

	private $_providers = array();

	/**
	*/
	function _init() {
// TODO: create special section in debug panel with curl requests inside
		conf('USE_CURL_DEBUG', true);
	}

	/**
	*/
	function login($provider) {
		$providers = $this->_load_oauth_providers();
		$config = $this->_load_oauth_config();
		if (!$config[$provider] || !$config[$provider]['client_id'] || !$config[$provider]['client_secret']) {
			return '<h1 class="text-error">Error: no config client_id and client_secret for provider: '.$provider.'</h1>';
		}
		$this->server = $provider;
		$this->redirect_uri = _force_get_url(array('object' => $_GET['object'], 'action' => $_GET['action'], 'id' => $_GET['id']));
		$this->client_id = $config[$provider]['client_id'] ?: ''; $application_line = __LINE__;
		$this->client_secret = $config[$provider]['client_secret'] ?: '';
		$this->client_public = $config[$provider]['client_public'] ?: '';
		$settings = $this->_providers[$provider];
		foreach ((array)$settings as $k => $v) {
			$this->$k = $v;
		}
		$fname = 'login_'.$provider;
		if (method_exists($this, $fname)) {
			return $this->$fname();
		}
		return '<h1 class="text-error">Error: no driver found for provider: '.$provider.'</h1>';
	}

	/**
	*/
	function login_vk() {
		$provider = 'vk';

		$url_authorize = 'https://oauth.vk.com/authorize';
		$url_access_token = 'https://oauth.vk.com/access_token';
		$url_user = 'https://api.vk.com/method/users.get';

		if ($_SESSION['oauth'][$provider]['access_token']) {
			$body = '';
			if ($_SESSION['oauth'][$provider]['user']) {
				$body .= '<h4>user</h4><pre>'.print_r($_SESSION['oauth'][$provider]['user'], 1).'</pre>';
			} else {
				$url = $url_user.'?'.http_build_query(array(
					'user_id'		=> $_SESSION['oauth'][$provider]['access_token_request']['result']['user_id'],
					'access_token'	=> $_SESSION['oauth'][$provider]['access_token'],
					'v'				=> '5.5',
				));
				$result = common()->get_remote_page($url, $cache = false, $opts, $response);
				if (strpos($response['content_type'], 'json') !== false || strpos($response['content_type'], 'javascript') !== false) {
					$result = _class('utils')->object_to_array(json_decode($result));
				}
				$_SESSION['oauth'][$provider]['user_info_request'] = array(
					'result'	=> $result,
					'response'	=> $response,
				);
				$_SESSION['oauth'][$provider]['user'] = $result;
				$body .= '<h4>user</h4><pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
			}
			$user_info_request = $_SESSION['oauth'][$provider]['user_info_request'];
			if ($user_info_request) {
				$arr = $user_info_request;
				$body .= '<h4>user_info_request</h4>Result:<pre>'.print_r($arr['result'], 1).'</pre>Response:<pre>'.print_r($arr['response'], 1).'</pre>';
			}
			$access_token_request = $_SESSION['oauth'][$provider]['access_token_request'];
			if ($access_token_request) {
				$arr = $access_token_request;
				$body .= '<h4>access_token_request</h4>Result:<pre>'.print_r($arr['result'], 1).'</pre>Response:<pre>'.print_r($arr['response'], 1).'</pre>';
			}
			return $body;
		}
		if ($_GET['code'] || $_GET['error']) {
			if ($_GET['error']) {
				return '<h1 class="text-error">Error: '.$_GET['error'].'</h1>';
			} elseif ($_GET['code']) {
				$url = $url_access_token.'?'.http_build_query(array(
					'client_id'		=> $this->client_id,
					'client_secret' => $this->client_secret,
					'code'			=> $_GET['code'],
					'redirect_uri' 	=> $this->redirect_uri,
					'v'				=> '5.5',
				));
				$response = array(); // Will be filled with debug information about request
				$result = common()->get_remote_page($url, $cache = false, $opts, $response);
				if ($response['http_code'] == 401) {
					if (DEBUG_MODE) {
						echo '<pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
					}
					return js_redirect( $this->redirect_uri, $url_rewrite = false );
				} elseif ($response['http_code'] == 200) {
					if (strpos($response['content_type'], 'json') !== false || strpos($response['content_type'], 'javascript') !== false) {
						$result = _class('utils')->object_to_array(json_decode($result));
					}
					$_SESSION['oauth'][$provider]['access_token_request'] = array(
						'result'	=> $result,
						'response'	=> $response,
					);
					$_SESSION['oauth'][$provider]['access_token'] = $result['access_token'];
				}
				return '<pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
			}
		} else {
			$url = $url_authorize.'?'.http_build_query(array(
				'client_id' 		=> $this->client_id,
				'redirect_uri' 		=> $this->redirect_uri,
				'scope'				=> 'offline,wall,friends,email', // Comma or space separated names
				'response_type' 	=> 'code',
				'v'					=> '5.5',
			));
			return js_redirect($url, $url_rewrite = false);
		}
		return false;
	}

	/**
	*/
	function login_github() {
		$provider = 'github';

		$url_authorize = 'https://github.com/login/oauth/authorize';
		$url_access_token = 'https://github.com/login/oauth/access_token';
		$url_user = 'https://api.github.com/user';

		if ($_SESSION['oauth'][$provider]['access_token']) {
			$body = '';
			if ($_SESSION['oauth'][$provider]['user']) {
				$body .= '<h4>user</h4><pre>'.print_r($_SESSION['oauth'][$provider]['user'], 1).'</pre>';
			} else {
				$url = $url_user.'?'.http_build_query(array(
					'access_token'	=> $_SESSION['oauth'][$provider]['access_token'],
				));
				$result = common()->get_remote_page($url, $cache = false, $opts = array(), $response);
					if (strpos($response['content_type'], 'json') !== false || strpos($response['content_type'], 'javascript') !== false) {
					$result = _class('utils')->object_to_array(json_decode($result));
				}
				$_SESSION['oauth'][$provider]['user_info_request'] = array(
					'result'	=> $result,
					'response'	=> $response,
				);
				$_SESSION['oauth'][$provider]['user'] = $result;
				$body .= '<h4>user</h4><pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';

				// Emails
				$url_emails = $url_user.'/emails?'.http_build_query(array(
					'access_token'	=> $_SESSION['oauth'][$provider]['access_token'],
				));
				$result = common()->get_remote_page($url_emails, $cache = false, $opts = array(), $response);
				if (strpos($response['content_type'], 'json') !== false || strpos($response['content_type'], 'javascript') !== false) {
					$result = _class('utils')->object_to_array(json_decode($result));
				}
				$_SESSION['oauth'][$provider]['user']['emails'] = $result;
				$body .= '<h4>user emails</h4><pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
			}
			$user_info_request = $_SESSION['oauth'][$provider]['user_info_request'];
			if ($user_info_request) {
				$arr = $user_info_request;
				$body .= '<h4>user_info_request</h4>Result:<pre>'.print_r($arr['result'], 1).'</pre>Response:<pre>'.print_r($arr['response'], 1).'</pre>';
			}
			$access_token_request = $_SESSION['oauth'][$provider]['access_token_request'];
			if ($access_token_request) {
				$arr = $access_token_request;
				$body .= '<h4>access_token_request</h4>Result:<pre>'.print_r($arr['result'], 1).'</pre>Response:<pre>'.print_r($arr['response'], 1).'</pre>';
			}
			return $body;
		}
		if ($_GET['code'] || $_GET['error']) {
			if ($_GET['error']) {
				return '<h1 class="text-error">Error: '.$_GET['error'].'</h1>';
			} elseif ($_GET['code']) {
				$url = $url_access_token;
				$opts = array(
					'post'	=> array(
						'client_id'		=> $this->client_id,
						'client_secret' => $this->client_secret,
						'code'			=> $_GET['code'],
						'redirect_uri' 	=> $this->redirect_uri,
					),
				);
				$response = array(); // Will be filled with debug information about request
				$result = common()->get_remote_page($url, $cache = false, $opts, $response);
				$raw_result = $result;
				if (strpos($response['content_type'], 'json') !== false || strpos($response['content_type'], 'javascript') !== false) {
					$result = _class('utils')->object_to_array(json_decode($result));
				} elseif (strpos($response['content_type'], 'application/x-www-form-urlencoded') !== false) {
					$try_parsed = array();
					parse_str($result, $try_parsed);
					if (is_array($try_parsed) && count($try_parsed) > 1) {
						$result = $try_parsed;
					}
				}
				if (isset($result['error']) && strlen($result['error']) || !is_array($result)) {
					if (DEBUG_MODE) {
						echo '<pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
					}
					return js_redirect( $this->redirect_uri, $url_rewrite = false );
				} else {
					$_SESSION['oauth'][$provider]['access_token_request'] = array(
						'result'	=> $result,
						'response'	=> $response,
					);
					$_SESSION['oauth'][$provider]['access_token'] = $result['access_token'];
				}
				return '<pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
			}
		} else {
			$url = $url_authorize.'?'.http_build_query(array(
				'client_id' 		=> $this->client_id,
				'redirect_uri' 		=> $this->redirect_uri,
				'scope'				=> 'user', // http://developer.github.com/v3/oauth/#scopes // user Read/write access to profile info only. Note: this scope includes user:email and user:follow.
// TODO save this in session and implement for all other providers too to prevent CSRF
				'state'				=> md5(microtime().rand(1,10000000)), // An unguessable random string. It is used to protect against cross-site request forgery attacks.
			));
			return js_redirect($url, $url_rewrite = false);
		}
	}

	/**
	*/
	function login_odnoklassniki() {
		$provider = 'odnoklassniki';

		$url_authorize = 'http://www.odnoklassniki.ru/oauth/authorize';
		$url_access_token = 'http://api.odnoklassniki.ru/oauth/token.do';
		$url_user = 'http://api.odnoklassniki.ru/fb.do';

		if ($_SESSION['oauth'][$provider]['access_token']) {
			$body = '';
			if ($_SESSION['oauth'][$provider]['user']) {
				$body .= '<h4>user</h4><pre>'.print_r($_SESSION['oauth'][$provider]['user'], 1).'</pre>';
			} else {
				$method = 'users.getCurrentUser';
				$access_token = $_SESSION['oauth'][$provider]['access_token'];
				$sign = md5('application_key='.$this->client_public. 'method='. $method. md5($access_token. $this->client_secret));
				$url = $url_user.'?'.http_build_query(array(
					'access_token'		=> $access_token,
					'application_key'	=> $this->client_public,
					'method'			=> $method,
					'sig'				=> $sign,
				));
				$result = common()->get_remote_page($url, $cache = false, $opts = array(), $response);
				if (strpos($response['content_type'], 'json') !== false || strpos($response['content_type'], 'javascript') !== false) {
					$result = _class('utils')->object_to_array(json_decode($result));
				}
				$_SESSION['oauth'][$provider]['user_info_request'] = array(
					'result'	=> $result,
					'response'	=> $response,
				);
				$_SESSION['oauth'][$provider]['user'] = $result;
				$body .= '<h4>user</h4><pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
			}
			$user_info_request = $_SESSION['oauth'][$provider]['user_info_request'];
			if ($user_info_request) {
				$arr = $user_info_request;
				$body .= '<h4>user_info_request</h4>Result:<pre>'.print_r($arr['result'], 1).'</pre>Response:<pre>'.print_r($arr['response'], 1).'</pre>';
			}
			$access_token_request = $_SESSION['oauth'][$provider]['access_token_request'];
			if ($access_token_request) {
				$arr = $access_token_request;
				$body .= '<h4>access_token_request</h4>Result:<pre>'.print_r($arr['result'], 1).'</pre>Response:<pre>'.print_r($arr['response'], 1).'</pre>';
			}
			return $body;
		}
		if ($_GET['code'] || $_GET['error']) {
			if ($_GET['error']) {
				return '<h1 class="text-error">Error: '.$_GET['error'].'</h1>';
			} elseif ($_GET['code']) {
				$url = $url_access_token;
				$opts = array(
					'post'	=> array(
						'client_id'		=> $this->client_id,
						'client_secret' => $this->client_secret,
						'redirect_uri' 	=> $this->redirect_uri,
						'code'			=> $_GET['code'],
						'grant_type'	=> 'authorization_code',
					),
				);
				$response = array(); // Will be filled with debug information about request
				$result = common()->get_remote_page($url, $cache = false, $opts, $response);
				$raw_result = $result;
				if (strpos($response['content_type'], 'json') !== false || strpos($response['content_type'], 'javascript') !== false) {
					$result = _class('utils')->object_to_array(json_decode($result));
				} elseif (strpos($response['content_type'], 'application/x-www-form-urlencoded') !== false) {
					$try_parsed = array();
					parse_str($result, $try_parsed);
					if (is_array($try_parsed) && count($try_parsed) > 1) {
						$result = $try_parsed;
					}
				}
				if (isset($result['error']) && strlen($result['error']) || !is_array($result)) {
					if (DEBUG_MODE) {
						echo '<pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
					}
					return js_redirect( $this->redirect_uri, $url_rewrite = false );
				} else {
					$_SESSION['oauth'][$provider]['access_token_request'] = array(
						'result'	=> $result,
						'response'	=> $response,
					);
					$_SESSION['oauth'][$provider]['access_token'] = $result['access_token'];
				}
				return '<pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
			}
		} else {
			$url = $url_authorize.'?'.http_build_query(array(
				'client_id' 		=> $this->client_id,
				'redirect_uri' 		=> $this->redirect_uri,
				'scope'				=> 'SET_STATUS;VALUABLE_ACCESS',
#				'scope'				=> 'SET_STATUS',
				'response_type' 	=> 'code',
#				'layout'			=> 'm', // http://apiok.ru/wiki/pages/viewpage.action?pageId=42476652    layout ="m"- мобильная форма авторизации, если не используете iOS или Android интеграцию
			));
			return js_redirect($url, $url_rewrite = false);
		}
	}

	/**
	*/
	function login_facebook() {
		$provider = 'facebook';

		$url_authorize = 'https://www.facebook.com/dialog/oauth';
		$url_access_token = 'https://graph.facebook.com/oauth/access_token';
		$url_user = 'https://graph.facebook.com/me';

		if ($_SESSION['oauth'][$provider]['access_token']) {
			$body = '';
			if ($_SESSION['oauth'][$provider]['user']) {
				$body .= '<h4>user</h4><pre>'.print_r($_SESSION['oauth'][$provider]['user'], 1).'</pre>';
			} else {
				$url = $url_user.'?'.http_build_query(array(
					'access_token'	=> $_SESSION['oauth'][$provider]['access_token'],
				));
				$result = common()->get_remote_page($url, $cache = false, $opts = array(), $response);
				if (strpos($response['content_type'], 'json') !== false || strpos($response['content_type'], 'javascript') !== false) {
					$result = _class('utils')->object_to_array(json_decode($result));
				}
				$_SESSION['oauth'][$provider]['user_info_request'] = array(
					'result'	=> $result,
					'response'	=> $response,
				);
				$_SESSION['oauth'][$provider]['user'] = $result;
				$body .= '<h4>user</h4><pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
			}
			$user_info_request = $_SESSION['oauth'][$provider]['user_info_request'];
			if ($user_info_request) {
				$arr = $user_info_request;
				$body .= '<h4>user_info_request</h4>Result:<pre>'.print_r($arr['result'], 1).'</pre>Response:<pre>'.print_r($arr['response'], 1).'</pre>';
			}
			$access_token_request = $_SESSION['oauth'][$provider]['access_token_request'];
			if ($access_token_request) {
				$arr = $access_token_request;
				$body .= '<h4>access_token_request</h4>Result:<pre>'.print_r($arr['result'], 1).'</pre>Response:<pre>'.print_r($arr['response'], 1).'</pre>';
			}
			return $body;
		}
		if ($_GET['code'] || $_GET['error']) {
			if ($_GET['error']) {
				return '<h1 class="text-error">Error: '.$_GET['error'].'</h1>';
			} elseif ($_GET['code']) {
				$url = $url_access_token.'?'.http_build_query(array(
					'client_id'		=> $this->client_id,
					'client_secret' => $this->client_secret,
					'code'			=> $_GET['code'],
					'redirect_uri' 	=> $this->redirect_uri,
				));
				$response = array(); // Will be filled with debug information about request
				$result = common()->get_remote_page($url, $cache = false, $opts, $response);
				$raw_result = $result;
#				if (strpos($response['content_type'], 'json') !== false) {
#					$result = _class('utils')->object_to_array(json_decode($result));
#				} elseif (strpos($response['content_type'], 'application/x-www-form-urlencoded') !== false) {
					$try_parsed = array();
					parse_str($result, $try_parsed);
					if (is_array($try_parsed) && count($try_parsed) > 1) {
						$result = $try_parsed;
					}
#				}
				if (isset($result['error']) || !is_array($result) || $response['http_code'] == 400) {
					if (DEBUG_MODE) {
						echo '<pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
					}
					return js_redirect( $this->redirect_uri, $url_rewrite = false );
				} else {
					$_SESSION['oauth'][$provider]['access_token_request'] = array(
						'result'	=> $result,
						'response'	=> $response,
					);
					$_SESSION['oauth'][$provider]['access_token'] = $result['access_token'];
				}
				return '<pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
			}
		} else {
			$url = $url_authorize.'?'.http_build_query(array(
				'client_id' 		=> $this->client_id,
				'redirect_uri' 		=> $this->redirect_uri,
				'response_type' 	=> 'code',
#				'scope'				=> '',
			));
			return js_redirect($url, $url_rewrite = false);
		}
	}

	/**
	*/
	function login_twitter() {
		$provider = 'twitter';

		$url_authorize = 'https://api.twitter.com/oauth/authorize';
		$url_access_token = 'https://api.twitter.com/oauth/access_token';
		$url_user = 'https://api.twitter.com/1/users/lookup.json';// ?user_id={user_id}';
// TODO: oauth v1
	}

	/**
	*/
	function login_mailru() {
		$provider = 'mailru';

		$url_authorize = 'https://connect.mail.ru/oauth/authorize';
		$url_access_token = 'https://connect.mail.ru/oauth/token';
		$url_user = 'http://www.appsmail.ru/platform/api';

		if ($_SESSION['oauth'][$provider]['access_token']) {
			$body = '';
			if ($_SESSION['oauth'][$provider]['user']) {
				$body .= '<h4>user</h4><pre>'.print_r($_SESSION['oauth'][$provider]['user'], 1).'</pre>';
			} else {
				$method = 'users.getInfo';
				$access_token = $_SESSION['oauth'][$provider]['access_token'];
				$sign = md5('app_id='.$this->client_id. 'method='. $method. 'secure=1'. 'session_key='.$access_token. $this->client_public);
				$url = $url_user.'?'.http_build_query(array(
					'session_key'	=> $access_token,
					'secure'		=> 1,
					'app_id'		=> $this->client_id,
					'method'		=> $method,
					'sig'			=> $sign,
				));
				$result = common()->get_remote_page($url, $cache = false, $opts = array(), $response);
				if (strpos($response['content_type'], 'json') !== false || strpos($response['content_type'], 'javascript') !== false) {
					$result = _class('utils')->object_to_array(json_decode($result));
				}
				$_SESSION['oauth'][$provider]['user_info_request'] = array(
					'result'	=> $result,
					'response'	=> $response,
				);
				$_SESSION['oauth'][$provider]['user'] = $result;
				$body .= '<h4>user</h4><pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
			}
			$user_info_request = $_SESSION['oauth'][$provider]['user_info_request'];
			if ($user_info_request) {
				$arr = $user_info_request;
				$body .= '<h4>user_info_request</h4>Result:<pre>'.print_r($arr['result'], 1).'</pre>Response:<pre>'.print_r($arr['response'], 1).'</pre>';
			}
			$access_token_request = $_SESSION['oauth'][$provider]['access_token_request'];
			if ($access_token_request) {
				$arr = $access_token_request;
				$body .= '<h4>access_token_request</h4>Result:<pre>'.print_r($arr['result'], 1).'</pre>Response:<pre>'.print_r($arr['response'], 1).'</pre>';
			}
			return $body;
		}
		if ($_GET['code'] || $_GET['error']) {
			if ($_GET['error']) {
				return '<h1 class="text-error">Error: '.$_GET['error'].'</h1>';
			} elseif ($_GET['code']) {
				$url = $url_access_token;
				$opts = array(
					'post'	=> array(
						'client_id'		=> $this->client_id,
						'client_secret' => $this->client_secret,
						'redirect_uri' 	=> $this->redirect_uri,
						'code'			=> $_GET['code'],
						'grant_type'	=> 'authorization_code',
					),
				);
				$response = array(); // Will be filled with debug information about request
				$result = common()->get_remote_page($url, $cache = false, $opts, $response);
				if (substr($response['http_code'], 0, 1) == '4') { // 4xx
					if (DEBUG_MODE) {
						echo '<pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
					}
					return js_redirect( $this->redirect_uri, $url_rewrite = false );
				} elseif ($response['http_code'] == 200) {
					if (strpos($response['content_type'], 'json') !== false || strpos($response['content_type'], 'javascript') !== false) {
						$result = _class('utils')->object_to_array(json_decode($result));
					}
					$_SESSION['oauth'][$provider]['access_token_request'] = array(
						'result'	=> $result,
						'response'	=> $response,
					);
					$_SESSION['oauth'][$provider]['access_token'] = $result['access_token'];
				}
				return '<pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
			}
		} else {
			$url = $url_authorize.'?'.http_build_query(array(
				'client_id' 		=> $this->client_id,
				'redirect_uri' 		=> $this->redirect_uri,
				'response_type' 	=> 'code',
#				'scope'				=> '',
			));
			return js_redirect($url, $url_rewrite = false);
		}
		return false;
	}

	/**
	*/
	function login_yandex() {
		$provider = 'yandex';

		$url_authorize = 'https://oauth.yandex.ru/authorize';
		$url_access_token = 'https://oauth.yandex.ru/token';
		$url_user = 'https://login.yandex.ru/info';

		if ($_SESSION['oauth'][$provider]['access_token']) {
			$body = '';
			if ($_SESSION['oauth'][$provider]['user']) {
				$body .= '<h4>user</h4><pre>'.print_r($_SESSION['oauth'][$provider]['user'], 1).'</pre>';
			} else {
				$url = $url_user.'?'.http_build_query(array(
					'oauth_token'	=> $_SESSION['oauth'][$provider]['access_token'],
					'format'		=> 'json',
				));
				$result = common()->get_remote_page($url, $cache = false, $opts = array(), $response);
				if (strpos($response['content_type'], 'json') !== false || strpos($response['content_type'], 'javascript') !== false) {
					$result = _class('utils')->object_to_array(json_decode($result));
				}
				$_SESSION['oauth'][$provider]['user_info_request'] = array(
					'result'	=> $result,
					'response'	=> $response,
				);
				$_SESSION['oauth'][$provider]['user'] = $result;
				$body .= '<h4>user</h4><pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
			}
			$user_info_request = $_SESSION['oauth'][$provider]['user_info_request'];
			if ($user_info_request) {
				$arr = $user_info_request;
				$body .= '<h4>user_info_request</h4>Result:<pre>'.print_r($arr['result'], 1).'</pre>Response:<pre>'.print_r($arr['response'], 1).'</pre>';
			}
			$access_token_request = $_SESSION['oauth'][$provider]['access_token_request'];
			if ($access_token_request) {
				$arr = $access_token_request;
				$body .= '<h4>access_token_request</h4>Result:<pre>'.print_r($arr['result'], 1).'</pre>Response:<pre>'.print_r($arr['response'], 1).'</pre>';
			}
			return $body;
		}
		if ($_GET['code'] || $_GET['error']) {
			if ($_GET['error']) {
				return '<h1 class="text-error">Error: '.$_GET['error'].'</h1>';
			} elseif ($_GET['code']) {
				$url = $url_access_token;
				$opts = array(
					'post'	=> array(
						'client_id'		=> $this->client_id,
						'client_secret' => $this->client_secret,
						'redirect_uri' 	=> $this->redirect_uri,
						'code'			=> $_GET['code'],
						'grant_type'	=> 'authorization_code',
					),
				);
				$response = array(); // Will be filled with debug information about request
				$result = common()->get_remote_page($url, $cache = false, $opts, $response);
				if (substr($response['http_code'], 0, 1) == '4') { // 4xx
					if (DEBUG_MODE) {
						echo '<pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
					}
					return js_redirect( $this->redirect_uri, $url_rewrite = false );
				} elseif ($response['http_code'] == 200) {
					if (strpos($response['content_type'], 'json') !== false || strpos($response['content_type'], 'javascript') !== false) {
						$result = _class('utils')->object_to_array(json_decode($result));
					}
					$_SESSION['oauth'][$provider]['access_token_request'] = array(
						'result'	=> $result,
						'response'	=> $response,
					);
					$_SESSION['oauth'][$provider]['access_token'] = $result['access_token'];
				}
				return '<pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
			}
		} else {
			$url = $url_authorize.'?'.http_build_query(array(
				'client_id' 		=> $this->client_id,
				'redirect_uri' 		=> $this->redirect_uri,
				'response_type' 	=> 'code',
#				'scope'				=> '',
			));
			return js_redirect($url, $url_rewrite = false);
		}
		return false;
	}

	/**
	*/
	function login_google() {
		$provider = 'google';
// TODO
	}

	/**
	* Example of $this->_providers item:
	*	'github' => array(
	*		'oauth_version' => '2.0',
	*		'dialog_url' => 'https://github.com/login/oauth/authorize?client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&state={STATE}',
	*		'access_token_url' => 'https://github.com/login/oauth/access_token',
	*		'user_info_url' => 'https://api.github.com/user',
	*	),
	*/
	function _load_oauth_providers() {
		if (isset($this->_providers_loaded)) {
			return $this->_providers;
		}
		$paths = array(
			YF_PATH. 'share/oauth_providers/',
			PROJECT_PATH. 'share/oauth_providers/',
		);
		foreach ((array)_class('dir')->scan($paths, 1, '-f /[a-z0-9_-]+\.php$/i') as $path) {
			$name = trim(substr(trim(basename($path)), 0, -strlen('.php')));
			if (!$name) {
				continue;
			}
			require_once $path;
			$this->_providers[$name] = $data;
		}
		$this->_providers_loaded = true;
		return $this->_providers;
	}

	/**
	* Usually client_id and client_secret stored like this:
	* $oauth_config = array(
	*	'github' => array('client_id' => '_put_github_client_id_here_', 'client_secret' => '_put_github_client_secret_here_'),
	*	'google' => array('client_id' => '_put_google_client_id_here_', 'client_secret' => '_put_google_client_secret_here_'),
	*	...
	* )
	*/
	function _load_oauth_config() {
		global $oauth_config;
		return $oauth_config;
	}

	/**
	*/
	function _get_providers() {
		$providers = $this->_load_oauth_providers();
		return $this->_providers;
	}
}