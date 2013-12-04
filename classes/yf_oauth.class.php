<?php

class yf_oauth {

	private $_providers = array(
		'__default__' => array(
			'request_token_url' => '',
			'append_state_to_redirect_uri' => '',
			'authorization_header' => true,
			'url_parameters' => false,
			'token_request_method' => 'GET',
			'signature_method' => 'HMAC-SHA1',
		),
		'github' => array(
			'oauth_version' => '2.0',
			'dialog_url' => 'https://github.com/login/oauth/authorize?client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&state={STATE}',
			'access_token_url' => 'https://github.com/login/oauth/access_token',
			'user_info_url' => 'https://api.github.com/user',
			'dev_register_url' => '',
		),
	);
	private $error = '';
	private $debug = false;
	private $debug_http = false;
	private $exit = false;
	private $debug_output = '';
	private $debug_prefix = 'OAuth client: ';
	private $server = '';
	private $request_token_url = '';
	private $dialog_url = '';
	private $offline_dialog_url = '';
	private $append_state_to_redirect_uri = '';
	private $access_token_url = '';
	private $oauth_version = '2.0';
	private $url_parameters = false;
	private $authorization_header = true;
	private $token_request_method = 'GET';
	private $signature_method = 'HMAC-SHA1';
	private $redirect_uri = '';
	private $client_id = '';
	private $client_secret = '';
	private $api_key = '';
	private $get_token_with_api_key = false;
	private $scope = '';
	private $offline = false;
	private $access_token = '';
	private $access_token_secret = '';
	private $access_token_expiry = '';
	private $access_token_type = '';
	private $default_access_token_type = '';
	private $access_token_parameter = '';
	private $access_token_response;
	private $store_access_token_response = false;
	private $refresh_token = '';
	private $access_token_error = '';
	private $authorization_error = '';
	private $response_status = 0;
	private $oauth_user_agent = 'PHP-OAuth-API';
	private $session_started = false;

	/**
	*/
	function initialize($provider) {
		require(PROJECT_PATH. '.dev/config.php');
		require(YF_PATH. 'libs/oauth-api/http/http.php');
#		require(YF_PATH. 'libs/oauth-api/oauth/oauth_client.php');

#		$client = new oauth_client_class();
		$client = $this;

		$client->debug = true;
		$client->debug_http = true;
		$client->server = $provider;
		$client->redirect_uri = _force_get_url('./?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id']);
		$client->client_id = $config[$provider]['client_id'] ?: ''; $application_line = __LINE__;
		$client->client_secret = $config[$provider]['client_secret'] ?: '';
		if (strlen($client->client_id) == 0 || strlen($client->client_secret) == 0) {
			die('Please set the client_id with Key and client_secret with Secret. The URL must be '.$client->redirect_uri);
		}

		$settings = $this->_providers[$provider] + $this->_providers['__default__'];
		foreach ($settings as $k => $v) {
			$client->$k = $v;
		}

#		if ($_SESSION['oauth'][$provider]['token']) {
#			$success = $_SESSION['oauth'][$provider]['token'];
#			$user = $_SESSION['oauth'][$provider]['user_info'];
#		} else {
			$error = 'Cannot process';
			if (($success = $client->process())) {
				if (strlen($client->access_token)) {
					$_SESSION['oauth'][$provider]['token'] = $client->access_token;
					$error = '';
					$success = $client->call_api($settings['user_info_url'], 'GET', array(), array('FailOnAccessError' => true), $user);
	
					$_SESSION['oauth'][$provider]['user_info'] = $user;
				} else {
					$error = $client->authorization_error;
				}
			}
#		}

		$body = $client->output();

		if ($error) {
			return $body.'<h1 class="text-error">Error: '.$error.'</h1>'.(DEBUG_MODE ? '<pre>'.print_r($client, 1).'</pre>' : '');
		} elseif ($success) {
			return $body.'<h1 class="text-success">Success</h1><pre>'.print_r($user, 1).'</pre>';
		}
#		return $this;
	}

	/**
	*/
	function _get_providers() {
		return $this->_providers;
	}

	/**
	*/
	function _set_error($error) {
		$this->error = $error;
		if ($this->debug) {
			$this->_output_debug('Error: '.$error);
		}
		return false;
	}

	/**
	*/
	function _set_php_error($error, &$php_error_message) {
		if (isset($php_error_message) && strlen($php_error_message)) {
			$error .= ": ".$php_error_message;
		}
		return ($this->_set_error($error));
	}

	/**
	*/
	function _output_debug($message) {
		if ($this->debug) {
			$message = $this->debug_prefix.$message;
			$this->debug_output .= $message."\n";
			error_log($message);
		}
		return true;
	}

	/**
	*/
	function _get_request_token_url(&$request_token_url) {
		$request_token_url = $this->request_token_url;
		return true;
	}

	/**
	*/
	function _get_dialog_url(&$url, $redirect_uri = '', $state = '') {
		$url = (($this->offline && strlen($this->offline_dialog_url)) ? $this->offline_dialog_url : $this->dialog_url);
		if (strlen($url) === 0) {
			return $this->_set_error('the dialog URL '.($this->offline ? 'for offline access ' : '').'is not defined for this server');
		}
		$url = str_replace(
			'{REDIRECT_URI}', urlencode($redirect_uri), str_replace(
			'{STATE}', urlencode($state), str_replace(
			'{CLIENT_ID}', urlencode($this->client_id), str_replace(
			'{API_KEY}', urlencode($this->api_key), str_replace(
			'{SCOPE}', urlencode($this->scope),
			$url)))));
		return true;
	}

	/**
	*/
	function _get_access_token_url(&$access_token_url) {
		$access_token_url = str_replace('{API_KEY}', $this->api_key, $this->access_token_url);
		return true;
	}

	/**
	*/
	function _get_stored_state(&$state) {
		if (!$this->session_started) {
			if (!function_exists('session_start')) {
				return $this->_set_error('Session variables are not accessible in this PHP environment');
			}
		}
		if (isset($_SESSION['OAUTH_STATE'])) {
			$state = $_SESSION['OAUTH_STATE'];
		} else {
			$state = $_SESSION['OAUTH_STATE'] = time().'-'.substr(md5(rand().time()), 0, 6);
		}
		return true;
	}

	/**
	*/
	function _get_request_state(&$state) {
		$check = (strlen($this->append_state_to_redirect_uri) ? $this->append_state_to_redirect_uri : 'state');
		$state = (isset($_GET[$check]) ? $_GET[$check] : null);
		return true;
	}

	/**
	*/
	function _get_request_code(&$code) {
		$code = (isset($_GET['code']) ? $_GET['code'] : null);
		return true;
	}

	/**
	*/
	function _get_request_error(&$error) {
		$error = (isset($_GET['error']) ? $_GET['error'] : null);
		return true;
	}

	/**
	*/
	function _get_request_denied(&$denied) {
		$denied = (isset($_GET['denied']) ? $_GET['denied'] : null);
		return true;
	}

	/**
	*/
	function _get_request_token(&$token, &$verifier) {
		$token = (isset($_GET['oauth_token']) ? $_GET['oauth_token'] : null);
		$verifier = (isset($_GET['oauth_verifier']) ? $_GET['oauth_verifier'] : null);
		return true;
	}

	/**
	*/
	function _get_redirect_uri(&$redirect_uri) {
		if (strlen($this->redirect_uri)) {
			$redirect_uri = $this->redirect_uri;
		} else {
			$redirect_uri = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		}
		return true;
	}

	/**
	*/
	function _redirect($url) {
		header('HTTP/1.0 302 OAuth _redirection');
		header('Location: '.$url);
	}

	/**
	*/
	function _store_access_token($access_token) {
		if (!$this->session_started) {
			if (!function_exists('session_start')) {
				return $this->_set_error('Session variables are not accessible in this PHP environment');
			}
		}
		if (!$this->_get_access_token_url($access_token_url)) {
			return false;
		}
		$_SESSION['OAUTH_ACCESS_TOKEN'][$access_token_url] = $access_token;
		return true;
	}

	/**
	*/
	function _get_access_token(&$access_token) {
		if (!$this->session_started) {
			if (!function_exists('session_start')) {
				return $this->_set_error('Session variables are not accessible in this PHP environment');
			}
			if (!session_start()) {
				return ($this->_set_php_error('it was not possible to start the PHP session', $php_error_message));
			}
			$this->session_started = true;
		}
		if (!$this->_get_access_token_url($access_token_url)) {
			return false;
		}
		if (isset($_SESSION['OAUTH_ACCESS_TOKEN'][$access_token_url])) {
			$access_token = $_SESSION['OAUTH_ACCESS_TOKEN'][$access_token_url];
		} else {
			$access_token = array();
		}
		return true;
	}

	/**
	*/
	function _reset_access_token() {
		if (!$this->_get_access_token_url($access_token_url)) {
			return false;
		}
		if ($this->debug) {
			$this->_output_debug('Resetting the access token status for OAuth server located at '.$access_token_url);
		}
		if (!$this->session_started) {
			if (!function_exists('session_start')) {
				return $this->_set_error('Session variables are not accessible in this PHP environment');
			}
			if (!session_start()) {
				return ($this->_set_php_error('it was not possible to start the PHP session', $php_error_message));
			}
		}
		$this->session_started = true;
		if (isset($_SESSION['OAUTH_ACCESS_TOKEN'][$access_token_url])) {
			unset($_SESSION['OAUTH_ACCESS_TOKEN'][$access_token_url]);
		}
		return true;
	}

	/**
	*/
	function _encode($value) {
		return (is_array($value) ? $this->_encode_array($value) : str_replace('%7E', '~', str_replace('+',' ', Rawurlencode($value))));
	}

	/**
	*/
	function _encode_array($array) {
		foreach($array as $key => $value) {
			$array[$key] = $this->_encode($value);
		}
		return $array;
	}

	/**
	*/
	function _hmac($function, $data, $key) {
		if ($function == 'sha1') {
			$pack = 'H40';
		} else {
			if ($this->debug) {
				$this->_output_debug($function.' is not a supported an HMAC hash type');
			}
			return false;
		}
		if (strlen($key) > 64) {
			$key = pack($pack, $function($key));
		}
		if (strlen($key) < 64) {
			$key = str_pad($key, 64, "\0");
		}
		return (pack($pack, $function((str_repeat("\x5c", 64) ^ $key). pack($pack, $function((str_repeat("\x36", 64) ^ $key). $data)))));
	}

	/**
	*/
	function send_api_request($url, $method, $parameters, $oauth, $options, &$response) {
		$this->response_status = 0;
		$http = new http_class;
		$http->debug = ($this->debug && $this->debug_http);
		$http->log_debug = true;
		$http->sasl_authenticate = 0;
		$http->user_agent = $this->oauth_user_agent;
		$http->redirection_limit = (isset($options['Follow_redirection']) ? intval($options['Follow_redirection']) : 0);
		$http->follow_redirect = ($http->redirection_limit != 0);
		if ($this->debug) {
			$this->_output_debug('Accessing the '.$options['Resource'].' at '.$url);
		}
		$post_files = array();
		$method = strtoupper($method);
		$authorization = '';
		$type = (isset($options['RequestContentType']) ? strtolower(trim(strtok($options['RequestContentType'], ';'))) : 'application/x-www-form-urlencoded');
		if (isset($oauth)) {
			$values = array(
				'oauth_consumer_key' => $this->client_id,
				'oauth_nonce' => md5(uniqid(rand(), true)),
				'oauth_signature_method' => $this->signature_method,
				'oauth_timestamp' => time(),
				'oauth_version' => '1.0',
			);
			$files = (isset($options['Files']) ? $options['Files'] : array());
			if (count($files)) {
				foreach($files as $name => $value) {
					if (!isset($parameters[$name])) {
						return ($this->_set_error('it was specified an file parameters named '.$name));
					}
					$file = array();
					$type = isset($value['Type']) ? $value['Type'] : 'FileName';
					if ($type == 'FileName') {
						$file['FileName'] = $parameters[$name];
					} elseif ($type == 'Data') {
						$file['Data'] = $parameters[$name];
					} else {
						return ($this->_set_error($value['Type'].' is not a valid type for file '.$name));
					}
					$file['ContentType'] = (isset($value['Content-Type']) ? $value['Content-Type'] : 'automatic/name');
					$post_files[$name] = $file;
				}
				unset($parameters[$name]);
				if ($method !== 'POST') {
					$this->_output_debug('For uploading files the method should be POST not '.$method);
					$method = 'POST';
				}
				if ($type !== 'multipart/form-data') {
					if (isset($options['RequestContentType'])) {
						return ($this->_set_error('the request content type for uploading files should be multipart/form-data'));
					}
					$type = 'multipart/form-data';
				}
				$value_parameters = array();
			} else {
				if ($this->url_parameters && $type === 'application/x-www-form-urlencoded' && count($parameters)) {
					$first = (strpos($url, '?') === false);
					foreach($parameters as $parameter => $value) {
						$url .= ($first ? '?' : '&').urlencode($parameter).'='.urlencode($value);
						$first = false;
					}
					$parameters = array();
				}
				$value_parameters = ($type !== 'application/x-www-form-urlencoded' ? array() : $parameters);
			}
			$values = array_merge($values, $oauth, $value_parameters);
			$key = $this->_encode($this->client_secret).'&'.$this->_encode($this->access_token_secret);
			if ($this->signature_method == 'HMAC-SHA1') {
				$uri = strtok($url, '?');
				$sign = $method.'&'.$this->_encode($uri).'&';
				$first = true;
				$sign_values = $values;
				$u = parse_url($url);
				if (isset($u['query'])) {
					parse_str($u['query'], $q);
					foreach($q as $parameter => $value) {
						$sign_values[$parameter] = $value;
					}
				}
				ksort($sign_values);
				foreach($sign_values as $parameter => $value) {
					$sign .= $this->_encode(($first ? '' : '&').$parameter.'='.$this->_encode($value));
					$first = false;
				}
				$values['oauth_signature'] = base64_encode($this->_hmac('sha1', $sign, $key));
			} elseif ($this->signature_method == 'PLAINTEXT') {
				$values['oauth_signature'] = $key;
			} else {
				return $this->_set_error($this->signature_method.' signature method is not yet supported');
			}
			if ($this->authorization_header)
			{
				$authorization = 'OAuth';
				$first = true;
				foreach($values as $parameter => $value) {
					$authorization .= ($first ? ' ' : ',').$parameter.'="'.$this->_encode($value).'"';
					$first = false;
				}
			} else {
				if ($method === 'GET' || (isset($options['PostValuesInURI']) && $options['PostValuesInURI'])) {
					$first = (strcspn($url, '?') == strlen($url));
					foreach($values as $parameter => $value) {
						$url .= ($first ? '?' : '&').$parameter.'='.$this->_encode($value);
						$first = false;
					}
					$post_values = array();
				} else {
					$post_values = $values;
				}
			}
		}
		if (strlen($authorization) === 0 && !strcasecmp($this->access_token_type, 'Bearer')) {
			$authorization = 'Bearer '.$this->access_token;
		}
		if (strlen($error = $http->GetRequestArguments($url, $arguments))) {
			return ($this->_set_error('it was not possible to open the '.$options['Resource'].' URL: '.$error));
		}
		if (strlen($error = $http->Open($arguments))) {
			return ($this->_set_error('it was not possible to open the '.$options['Resource'].' URL: '.$error));
		}
		if (count($post_files)) {
			$arguments['PostFiles'] = $post_files;
		}
		$arguments['RequestMethod'] = $method;
		if ($type == 'application/x-www-form-urlencoded' || $type == 'multipart/form-data') {
			if (isset($options['RequestBody'])) {
				return ($this->_set_error('the request body is defined automatically from the parameters'));
			}
			$arguments['PostValues'] = $parameters;
		} elseif ($type == 'application/json') {
			$arguments['Headers']['Content-Type'] = $options['RequestContentType'];
			if (!isset($options['RequestBody'])) {
				$arguments['Body'] = json_encode($parameters);
			}
		} else {
			if (!isset($options['RequestBody'])) {
				return ($this->_set_error('it was not specified the body value of the of the API call request'));
			}
			$arguments['Headers']['Content-Type'] = $options['RequestContentType'];
			$arguments['Body'] = $options['RequestBody'];
		}
		$arguments['Headers']['Accept'] = (isset($options['Accept']) ? $options['Accept'] : '*/*');
		if (strlen($authorization)) {
			$arguments['Headers']['Authorization'] = $authorization;
		}
		if (strlen($error = $http->SendRequest($arguments)) || strlen($error = $http->ReadReplyHeaders($headers))) {
			$http->Close();
			return ($this->_set_error('it was not possible to retrieve the '.$options['Resource'].': '.$error));
		}
		$error = $http->ReadWholeReplyBody($data);
		$http->Close();
		if (strlen($error)) {
			return ($this->_set_error('it was not possible to access the '.$options['Resource'].': '.$error));
		}
		$this->response_status = intval($http->response_status);
		$content_type = (isset($options['ResponseContentType'])
			? $options['ResponseContentType'] 
			: (isset($headers['content-type']) 
				? strtolower(trim(strtok($headers['content-type'], ';'))) 
				: 'unspecified'
			)
		);
		if ($content_type == 'text/javascript' || $content_type == 'application/json') {
			if (!function_exists('json_decode')) {
				return ($this->_set_error('the JSON extension is not available in this PHP setup'));
			}
			$object = json_decode($data);
			$obj_type = gettype($object);
			if ($obj_type == 'object') {
				if (!isset($options['ConvertObjects']) || !$options['ConvertObjects']) {
					$response = $object;
				} else {
					$response = array();
					foreach($object as $property => $value) {
						$response[$property] = $value;
					}
				}
			} elseif ($obj_type == 'array') {
				$response = $object;
			} else {
				if (!isset($object)) {
					return ($this->_set_error('it was not returned a valid JSON definition of the '.$options['Resource'].' values'));
				}
				$response = $object;
			}
		} elseif ($content_type == 'application/x-www-form-urlencoded' || $content_type == 'text/plain' || $content_type == 'text/html') {
			parse_str($data, $response);
		} else {
			$response = $data;
		}
		if ($this->response_status >= 200 && $this->response_status < 300) {
			$this->access_token_error = '';
		} else {
			$this->access_token_error = 'it was not possible to access the '.$options['Resource'].': it was returned an unexpected response status '.$http->response_status.' Response: '.$data;
			if ($this->debug) {
				$this->_output_debug('Could not retrieve the OAuth access token. Error: '.$this->access_token_error);
			}
			if (isset($options['FailOnAccessError']) && $options['FailOnAccessError']) {
				$this->error = $this->access_token_error;
				return false;
			}
		}
		return true;
	}

	/**
	*/
	function process_token($code, $refresh) {
		if ($refresh) {
			$values = array(
				'client_id' => $this->client_id,
				'client_secret' => ($this->get_token_with_api_key ? $this->api_key : $this->client_secret),
				'refresh_token' => $this->refresh_token,
				'grant_type' => 'refresh_token'
			);
		} else {
			if (!$this->_get_redirect_uri($redirect_uri)) {
				return false;
			}
			$values = array(
				'code' => $code,
				'client_id' => $this->client_id,
				'client_secret' => ($this->get_token_with_api_key ? $this->api_key : $this->client_secret),
				'redirect_uri' => $redirect_uri,
				'grant_type' => 'authorization_code'
			);
		}
		if (!$this->_get_access_token_url($access_token_url)) {
			return false;
		}
		if (!$this->send_api_request($access_token_url, 'POST', $values, null, array(
			'Resource' => 'OAuth '.($refresh ? 'refresh' : 'access').' token', 
			'ConvertObjects' => true
		), $response)) {
			return false;
		}
		if (strlen($this->access_token_error)) {
			$this->authorization_error = $this->access_token_error;
			return true;
		}
		if (!isset($response['access_token'])) {
			if (isset($response['error'])) {
				$this->authorization_error = 'it was not possible to retrieve the access token: it was returned the error: '.$response['error'];
				return true;
			}
			return ($this->_set_error('OAuth server did not return the access token'));
		}
		$access_token = array(
			'value' => ($this->access_token = $response['access_token']),
			'authorized' => true,
		);
		if ($this->store_access_token_response) {
			$access_token['response'] = $this->access_token_response = $response;
		}
		if ($this->debug) {
			$this->_output_debug('Access token: '.$this->access_token);
		}
		if (isset($response['expires_in']) && $response['expires_in'] == 0) {
			if ($this->debug) {
				$this->_output_debug('Ignoring access token expiry set to 0');
			}
			$this->access_token_expiry = '';
		} elseif (isset($response['expires']) || isset($response['expires_in'])) {
			$expires = (isset($response['expires']) ? $response['expires'] : $response['expires_in']);
			if (strval($expires) !== strval(intval($expires)) || $expires <= 0) {
				return ($this->_set_error('OAuth server did not return a supported type of access token expiry time'));
			}
			$this->access_token_expiry = gmstrftime('%Y-%m-%d %H:%M:%S', time() + $expires);
			if ($this->debug) {
				$this->_output_debug('Access token expiry: '.$this->access_token_expiry.' UTC');
			}
			$access_token['expiry'] = $this->access_token_expiry;
		} else {
			$this->access_token_expiry = '';
		}
		if (isset($response['token_type'])) {
			$this->access_token_type = $response['token_type'];
			if (strlen($this->access_token_type) && $this->debug) {
				$this->_output_debug('Access token type: '.$this->access_token_type);
			}
			$access_token['type'] = $this->access_token_type;
		} else {
			$this->access_token_type = $this->default_access_token_type;
			if (strlen($this->access_token_type) && $this->debug) {
				$this->_output_debug('Assumed the default for OAuth access token type which is '.$this->access_token_type);
			}
		}
		if (isset($response['refresh_token'])) {
			$this->refresh_token = $response['refresh_token'];
			if ($this->debug) {
				$this->_output_debug('New refresh token: '.$this->refresh_token);
			}
			$access_token['refresh'] = $this->refresh_token;
		} elseif (strlen($this->refresh_token)) {
			if ($this->debug) {
				$this->_output_debug('Reusing previous refresh token: '.$this->refresh_token);
			}
			$access_token['refresh'] = $this->refresh_token;
		}
		if (!$this->_store_access_token($access_token)) {
			return false;
		}
		return true;
	}

	/**
	*/
	function RetrieveToken(&$valid) {
		$valid = false;
		if (!$this->_get_access_token($access_token)) {
			return false;
		}
		if (isset($access_token['value'])) {
			$this->access_token_expiry = '';
			if (isset($access_token['expiry']) && strcmp($this->access_token_expiry = $access_token['expiry'], gmstrftime('%Y-%m-%d %H:%M:%S')) < 0) {
				if ($this->debug) {
					$this->_output_debug('The OAuth access token expired in '.$this->access_token_expiry);
				}
			}
			$this->access_token = $access_token['value'];
			if ($this->debug) {
				$this->_output_debug('The OAuth access token '.$this->access_token.' is valid');
			}
			if (isset($access_token['type'])) {
				$this->access_token_type = $access_token['type'];
				if (strlen($this->access_token_type) && $this->debug) {
					$this->_output_debug('The OAuth access token is of type '.$this->access_token_type);
				}
			} else {
				$this->access_token_type = $this->default_access_token_type;
				if (strlen($this->access_token_type) && $this->debug) {
					$this->_output_debug('Assumed the default for OAuth access token type which is '.$this->access_token_type);
				}
			}
			if (isset($access_token['secret'])) {
				$this->access_token_secret = $access_token['secret'];
				if ($this->debug) {
					$this->_output_debug('The OAuth access token secret is '.$this->access_token_secret);
				}
			}
			if (isset($access_token['refresh'])) {
				$this->refresh_token = $access_token['refresh'];
			} else {
				$this->refresh_token = '';
			}
			$this->access_token_response = (($this->store_access_token_response && isset($access_token['response'])) ? $access_token['response'] : null);
			$valid = true;
		}
		return true;
	}

	/**
	*/
	function call_api($url, $method, $parameters, $options, &$response) {
		if (!isset($options['Resource'])) {
			$options['Resource'] = 'API call';
		}
		if (!isset($options['ConvertObjects'])) {
			$options['ConvertObjects'] = false;
		}
		if (strlen($this->access_token) === 0) {
			if (!$this->RetrieveToken($valid)) {
				return false;
			}
			if (!$valid) {
				return $this->_set_error('the access token is not set to a valid value');
			}
		}
		$oauth_version = intval($this->oauth_version);
		if ($oauth_version == 1) {
			$oauth = array(
				(strlen($this->access_token_parameter) ? $this->access_token_parameter : 'oauth_token')=>((isset($options['2Legged']) && $options['2Legged']) ? '' : $this->access_token)
			);
		} elseif ($oauth_version == 2) {
			if (strlen($this->access_token_expiry) && strcmp($this->access_token_expiry, gmstrftime('%Y-%m-%d %H:%M:%S')) <= 0) {
				if (strlen($this->refresh_token) === 0) {
					return ($this->_set_error('the access token expired and no refresh token is available'));
				}
				if ($this->debug) {
					$this->_output_debug('The access token expired on '.$this->access_token_expiry);
					$this->_output_debug('Refreshing the access token');
				}
				if (!$this->process_token(null, true)) {
					return false;
				}
			}
			$oauth = null;
			if (strcasecmp($this->access_token_type, 'Bearer')) {
				$url .= (strcspn($url, '?') < strlen($url) ? '&' : '?').(strlen($this->access_token_parameter) ? $this->access_token_parameter : 'access_token').'='.urlencode($this->access_token);
			}
		} else {
			return ($this->_set_error($this->oauth_version.' is not a supported version of the OAuth protocol'));
		}
		return ($this->send_api_request($url, $method, $parameters, $oauth, $options, $response));
	}

	/**
	*/
	function process() {
		$oauth_version = intval($this->oauth_version);
		if ($oauth_version == 1) {
			return $this->_process_v1();
		} elseif ($oauth_version == 2) {
			return $this->_process_v2();
		} else {
			return ($this->_set_error($this->oauth_version.' is not a supported version of the OAuth protocol'));
		}
		return true;
	}

	/**
	*/
	function _process_v1() {
		$one_a = ($this->oauth_version === '1.0a');
		if ($this->debug) {
			$this->_output_debug('Checking the OAuth token authorization state');
		}
		if (!$this->_get_access_token($access_token)) {
			return false;
		}
		if (isset($access_token['authorized']) && isset($access_token['value'])) {
			$expired = (isset($access_token['expiry']) && strcmp($access_token['expiry'], gmstrftime('%Y-%m-%d %H:%M:%S')) <= 0);
			if (!$access_token['authorized'] || $expired) {
				if ($this->debug) {
					if ($expired) {
						$this->_output_debug('The OAuth token expired on '.$access_token['expiry'].'UTC');
					} else {
						$this->_output_debug('The OAuth token is not yet authorized');
					}
					$this->_output_debug('Checking the OAuth token and verifier');
				}
				if (!$this->_get_request_token($token, $verifier)) {
					return false;
				}
				if (!isset($token) || ($one_a && !isset($verifier))) {
					if (!$this->_get_request_denied($denied)) {
						return false;
					}
					if (isset($denied) && $denied === $access_token['value']) {
						if ($this->debug) {
							$this->_output_debug('The authorization request was denied');
						}
						$this->authorization_error = 'the request was denied';
						return true;
					} else {
						if ($this->debug) {
							$this->_output_debug('Reset the OAuth token state because token and verifier are not both set');
						}
						$access_token = array();
					}
				} elseif ($token !== $access_token['value']) {
					if ($this->debug) {
						$this->_output_debug('Reset the OAuth token state because token does not match what as previously retrieved');
					}
					$access_token = array();
				} else {
					if (!$this->_get_access_token_url($url)) {
						return false;
					}
					$oauth = array(
						'oauth_token'=>$token,
					);
					if ($one_a) {
						$oauth['oauth_verifier'] = $verifier;
					}
					$this->access_token_secret = $access_token['secret'];
					$options = array('Resource'=>'OAuth access token');
					$method = strtoupper($this->token_request_method);
					if ($method == 'GET') {
					} elseif ($method == 'POST') {
						$options['PostValuesInURI'] = true;
					} else {
						$this->error = $method.' is not a supported method to request tokens';
					}
					if (!$this->send_api_request($url, $method, array(), $oauth, $options, $response)) {
						return false;
					}
					if (strlen($this->access_token_error)) {
						$this->authorization_error = $this->access_token_error;
						return true;
					}
					if (!isset($response['oauth_token']) || !isset($response['oauth_token_secret'])) {
						$this->authorization_error= 'it was not returned the access token and secret';
						return true;
					}
					$access_token = array(
						'value' => $response['oauth_token'],
						'secret' => $response['oauth_token_secret'],
						'authorized' => true,
					);
					if (isset($response['oauth_expires_in']) && $response['oauth_expires_in'] == 0) {
						if ($this->debug) {
							$this->_output_debug('Ignoring access token expiry set to 0');
						}
						$this->access_token_expiry = '';
					} elseif (isset($response['oauth_expires_in'])) {
						$expires = $response['oauth_expires_in'];
						if (strval($expires) !== strval(intval($expires)) || $expires <= 0) {
							return ($this->_set_error('OAuth server did not return a supported type of access token expiry time'));
						}
						$this->access_token_expiry = gmstrftime('%Y-%m-%d %H:%M:%S', time() + $expires);
						if ($this->debug) {
							$this->_output_debug('Access token expiry: '.$this->access_token_expiry.' UTC');
						}
						$access_token['expiry'] = $this->access_token_expiry;
					} else {
						$this->access_token_expiry = '';
					}
					if (!$this->_store_access_token($access_token)) {
						return false;
					}
					if ($this->debug) {
						$this->_output_debug('The OAuth token was authorized');
					}
				}
			} elseif ($this->debug) {
				$this->_output_debug('The OAuth token was already authorized');
			}
			if (isset($access_token['authorized']) && $access_token['authorized']) {
				$this->access_token = $access_token['value'];
				$this->access_token_secret = $access_token['secret'];
				return true;
			}
		} else {
			if ($this->debug) {
				$this->_output_debug('The OAuth access token is not set');
			}
			$access_token = array();
		}
		if (!isset($access_token['authorized'])) {
			if ($this->debug) {
				$this->_output_debug('Requesting the unauthorized OAuth token');
			}
			if (!$this->_get_request_token_url($url)) {
				return false;
			}
			$url = str_replace('{SCOPE}', urlencode($this->scope), $url); 
			if (!$this->_get_redirect_uri($redirect_uri)) {
				return false;
			}
			$oauth = array(
				'oauth_callback'=>$redirect_uri,
			);
			$options = array(
				'Resource' => 'OAuth request token',
				'FailOnAccessError' => true,
			);
			$method = strtoupper($this->token_request_method);
			if ($method == 'GET') {
			} elseif ($method == 'POST') {
				$options['PostValuesInURI'] = true;
			} else {
				$this->error = $method.' is not a supported method to request tokens';
			}
			if (!$this->send_api_request($url, $method, array(), $oauth, $options, $response)) {
				return false;
			}
			if (strlen($this->access_token_error)) {
				$this->authorization_error = $this->access_token_error;
				return true;
			}
			if (!isset($response['oauth_token']) || !isset($response['oauth_token_secret'])) {
				$this->authorization_error = 'it was not returned the requested token';
				return true;
			}
			$access_token = array(
				'value' => $response['oauth_token'],
				'secret' => $response['oauth_token_secret'],
				'authorized' => false,
			);
			if (!$this->_store_access_token($access_token)) {
				return false;
			}
		}
		if (!$this->_get_dialog_url($url)) {
			return false;
		}
		$url .= (strpos($url, '?') === false ? '?' : '&').'oauth_token='.$access_token['value'];
		if (!$one_a) {
			if (!$this->_get_redirect_uri($redirect_uri)) {
				return false;
			}
			$url .= '&oauth_callback='.urlencode($redirect_uri);
		}
		if ($this->debug) {
			$this->_output_debug('_redirecting to OAuth authorize page '.$url);
		}
		$this->_redirect($url);
		$this->exit = true;
		return true;
	}

	/**
	*/
	function _process_v2() {
		if ($this->debug) {
			if (!$this->_get_access_token_url($access_token_url)) {
				return false;
			}
			$this->_output_debug('Checking if OAuth access token was already retrieved from '.$access_token_url);
		}
		if (!$this->RetrieveToken($valid)) {
			return false;
		}
		if ($valid) {
			return true;
		}
		if ($this->debug) {
			$this->_output_debug('Checking the authentication state in URI '.$_SERVER['REQUEST_URI']);
		}
		if (!$this->_get_stored_state($stored_state)) {
			return false;
		}
		if (strlen($stored_state) == 0) {
			return ($this->_set_error('it was not set the OAuth state'));
		}
		if (!$this->_get_request_state($state)) {
			return false;
		}
		if ($state === $stored_state) {
			if ($this->debug) {
				$this->_output_debug('Checking the authentication code');
			}
			if (!$this->_get_request_code($code)) {
				return false;
			}
			if (strlen($code) == 0) {
				if (!$this->_get_request_error($this->authorization_error)) {
					return false;
				}
				if (isset($this->authorization_error)) {
					if ($this->debug) {
						$this->_output_debug('Authorization failed with error code '.$this->authorization_error);
					}
					if (in_array($this->authorization_error, array(
						'invalid_request',
						'unauthorized_client',
						'access_denied',
						'unsupported_response_type',
						'invalid_scope',
						'server_error',
						'temporarily_unavailable',
						'user_denied',
					))) {
						return true;
					} else {
						return ($this->_set_error('it was returned an unknown OAuth error code'));
					}
				}
				return ($this->_set_error('it was not returned the OAuth dialog code'));
			}
			if (!$this->process_token($code, false)) {
				return false;
			}
		} else {
			if (!$this->_get_redirect_uri($redirect_uri)) {
				return false;
			}
			if (strlen($this->append_state_to_redirect_uri)) {
				$redirect_uri .= (strpos($redirect_uri, '?') === false ? '?' : '&').$this->append_state_to_redirect_uri.'='.$stored_state;
			}
			if (!$this->_get_dialog_url($url, $redirect_uri, $stored_state)) {
				return false;
			}
			if (strlen($url) == 0) {
				return ($this->_set_error('it was not set the OAuth dialog URL'));
			}
			if ($this->debug) {
				$this->_output_debug('_redirecting to OAuth Dialog '.$url);
			}
			$this->_redirect($url);
			$this->exit = true;
		}
		return true;
	}

	/**
	*/
	function finalize($success) {
		return $success;
	}

	/**
	*/
	function output() {
		if (strlen($this->authorization_error) || strlen($this->access_token_error) || strlen($this->access_token)) {
			$body .= '<h1>OAuth client result</h1>';
			if (strlen($this->authorization_error)) {
				$body .= '<p>It was not possible to authorize the application.';
				if ($this->debug) {
					$body .= '<br>Authorization error: '.htmlspecialchars($this->authorization_error);
				}
				$body .= '</p>';
			} elseif (strlen($this->access_token_error)) {
				$body .= '<p>It was not possible to use the application access token.';
				if ($this->debug) {
					$body .= '<br>Error: '.htmlspecialchars($this->access_token_error);
				}
				$body .= '</p>';
			} elseif (strlen($this->access_token)) {
				$body .= '<p>The application authorization was obtained successfully.';
				if ($this->debug) {
					$body .= '<br>Access token: '.htmlspecialchars($this->access_token);
					if (isset($this->access_token_secret)) {
						$body .= '<br>Access token secret: '.htmlspecialchars($this->access_token_secret);
					}
				}
				$body .= '</p>';
				if (strlen($this->access_token_expiry)) {
					$body .= '<p>Access token expiry: '.$this->access_token_expiry.' UTC</p>';
				}
			}
		}
		return $body;
	}
}