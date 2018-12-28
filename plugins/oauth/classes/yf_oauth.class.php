<?php

class yf_oauth
{
    private $auto_email_prefix = 'oauth.';
    private $_providers = [];

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args);
    }


    public function _init()
    {
        conf('USE_CURL_DEBUG', true);
    }

    /**
     * @param mixed $provider
     * @param mixed $params
     */
    public function login($provider, $params = [])
    {
        if ( ! $provider) {
            return false;
        }
        $need_merge_accounts = isset($params['need_merge_accounts']) ? $params['need_merge_accounts'] : true;
        if ( ! $need_merge_accounts && main()->USER_ID) {
            return false;
        }
        _class('core_events')->fire('oauth.before_login', [
            'provider' => $provider,
            'params' => $params,
        ]);
        $normalized_info = [];
        $driver = _class('oauth_driver_' . $provider, 'classes/oauth/');
        $oauth_user_info = $driver->login($params);
        if ($oauth_user_info) {
            $normalized_info = $driver->_get_user_info_for_auth($oauth_user_info);
        }
        if ($normalized_info['user_id']) {
            $oauth_registration = db()->get('SELECT * FROM ' . db('oauth_users') . ' WHERE provider="' . _es($provider) . '" AND provider_uid="' . _es($normalized_info['user_id']) . '"');
            if ( ! $oauth_registration) {
                db()->insert_safe('oauth_users', [
                    'provider' => $provider,
                    'provider_uid' => $normalized_info['user_id'],
                    'login' => $normalized_info['user_id'],
                    'email' => $normalized_info['email'],
                    'name' => $normalized_info['name'],
                    'avatar_url' => $normalized_info['avatar_url'],
                    'profile_url' => $normalized_info['profile_url'],
                    'json_normalized' => json_encode($normalized_info),
                    'json_raw' => json_encode($oauth_user_info),
                    'add_date' => time(),
//					'user_id'		=> 0, // Here it is 0, will be updated later if OK
                    'user_id' => 'NULL', // Here it is NULL, will be updated later if OK
                ]);
                $oauth_user_id = db()->insert_id();
                if ($oauth_user_id) {
                    $oauth_registration = db()->get('SELECT * FROM ' . db('oauth_users') . ' WHERE provider="' . _es($provider) . '" AND provider_uid="' . _es($normalized_info['user_id']) . '" AND id=' . (int) $oauth_user_id);
                }
                _class('core_events')->fire('oauth.insert', [
                    'provider' => $provider,
                    'params' => $params,
                    'oauth_id' => $oauth_user_id,
                    'oauth_info' => $oauth_registration,
                ]);
            }
            $sys_user_info = [];
            // merge oauth if user is logged in
            if (main()->USER_ID && $need_merge_accounts) {
                $sys_user_info = db()->get('SELECT * FROM ' . db('user') . ' WHERE id=' . (int) (main()->USER_ID));
                // TODO: try to merge accounts by email if it is not empty
                if ($sys_user_info && $oauth_registration && ! $oauth_registration['user_id']) {
                    $try_other_oauths = db()->get_all('SELECT * FROM ' . db('oauth_users') . ' WHERE user_id=' . (int) (main()->USER_ID));
                    foreach ((array) $try_other_oauths as $v) {
                        if (substr($v['email'], 0, strlen($this->auto_email_prefix)) == $this->auto_email_prefix) {
                            continue;
                        }
                        // TODO
                    }
                    //print_r($try_other_oauths);
                }
            }
            if ($oauth_registration && ! $oauth_registration['user_id']) {
                if ( ! $sys_user_info) {
                    // TODO: auto-login user if email exists or show dialog to enter email
                    $self_host = parse_url(WEB_PATH, PHP_URL_HOST);
                    if ( ! $self_host) {
                        $self_host = $_SERVER['HTTP_HOST'];
                    }
                    if (isset($params['set_user_info']) && is_callable($params['set_user_info'])) {
                        $set_user_info = $params['set_user_info'];
                        $sys_user_id = $set_user_info($normalized_info);
                    } else {
                        $login = $normalized_info['login'] ?: $this->auto_email_prefix . $provider . '.' . $normalized_info['user_id'];

                        $email = $normalized_info['email'] ?: $login . '@' . $self_host;
                        db()->insert_safe('user', [
                            'group' => 2,
                            'login' => $login,
                            'email' => $email,
                            'name' => $normalized_info['name'] ?: $login,
                            'nick' => $normalized_info['name'] ?: $login,
                            'password' => md5(time() . 'some_salt' . uniqid()),
// TODO: make verification by email
                            'active' => 1,
                            'add_date' => time(),
                            'verify_code' => md5(time() . 'some_salt' . uniqid()),
// TODO: add other fields: locale, lang, age, gender, location, birthday, avatar_url, profile_url
                        ]);
                        $sys_user_id = db()->insert_id();
                    }

                    if ($sys_user_id) {
                        $sys_user_info = db()->get('SELECT * FROM ' . db('user') . ' WHERE id=' . (int) $sys_user_id);
                    }
                    _class('core_events')->fire('oauth.user_added', [
                        'provider' => $provider,
                        'params' => $params,
                        'oauth_info' => $oauth_registration,
                        'user_id' => $sys_user_id,
                        'user_info' => $sys_user_info,
                    ]);
                }
                // Link oauth record with system user account
                if ($sys_user_info['id']) {
                    db()->update_safe('oauth_users', ['user_id' => $sys_user_info['id']], 'id=' . (int) ($oauth_registration['id']));
                    $oauth_registration['user_id'] = $sys_user_info['id'];
                }
            }
            if ($oauth_registration['user_id'] && ! $sys_user_info['id']) {
                //login omly active user
                $sys_user_info = db()->get('SELECT * FROM ' . db('user') . ' WHERE active = 1 and id=' . (int) ($oauth_registration['user_id']));
            }
            // Auto-login user if everything fine
            if ($oauth_registration['user_id'] && $sys_user_info['id'] && ! main()->USER_ID) {
                _class('auth_user', 'classes/auth/')->_save_login_in_session($sys_user_info);
            } else {
                common()->message_error('Sorry, but some info you have entered is wrong.');
            }
        }
        if (DEBUG_MODE) {
            if ($oauth_user_info) {
                $body .= '<h1 class="text-success">User info</h1><pre><small>' . print_r($normalized_info, 1) . '</small></pre>';
                $body .= '<h1 class="text-success">Raw user info</h1><pre><small>' . print_r($oauth_user_info, 1) . '</small></pre>';
            } else {
                $body .= '<h1 class="text-error">Error</h1>';
            }
            $body .= '<pre><small>' . print_r($_SESSION['oauth'][$provider], 1) . '</small></pre>';
        }
        return $body;
    }

    /**
     * Example of $this->_providers item (can also be empty):
     *	'github' => array(
     *		'user_info_url' => 'https://api.github.com/user',
     *	),.
     */
    public function _load_oauth_providers()
    {
        $config = $this->_load_oauth_config();
        if (isset($this->_providers_loaded)) {
            return $this->_providers;
        }
        // Load event listeners from supported locations
        $ext = '.class.php';
        $prefix = 'oauth_driver_';
        $pattern = '{,plugins/*/}classes/oauth/*' . $prefix . '*' . $ext;
        $globs = [
            'framework' => YF_PATH . $pattern,
            'app' => APP_PATH . $pattern,
        ];
        $ext_len = strlen($ext);
        $names = [];
        foreach ($globs as $gname => $glob) {
            foreach (glob($glob, GLOB_BRACE) as $path) {
                $name = substr(basename($path), 0, -$ext_len);
                if (substr($name, 0, 3) == 'yf_') {
                    $name = substr($name, 3);
                }
                $name = substr($name, strlen($prefix));
                if ( ! $name) {
                    continue;
                }
                if ( ! isset($config[$name])) {
                    continue;
                }
                $p_config = $config[$name];
                if ( ! strlen($p_config['client_id']) || ! strlen($p_config['client_secret'])) {
                    continue;
                }
                $names[$name] = $path;
                $locations[$name][$gname] = $path;
            }
        }
        ksort($names);
        $this->_providers = $names;
        $this->_providers_loaded = true;
        return $this->_providers;
    }

    /**
     * Usually client_id and client_secret stored like this:
     * $oauth_config = array(
     *	'github' => array('client_id' => '_put_github_client_id_here_', 'client_secret' => '_put_github_client_secret_here_'),
     *	'google' => array('client_id' => '_put_google_client_id_here_', 'client_secret' => '_put_google_client_secret_here_'),
     *	...
     * ).
     */
    public function _load_oauth_config()
    {
        global $oauth_config;
        return $oauth_config;
    }


    public function _get_providers()
    {
        $providers = $this->_load_oauth_providers();
        return $this->_providers;
    }
}
