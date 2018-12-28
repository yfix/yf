<?php

load('oauth_driver2', '', 'classes/oauth/');
class yf_oauth_driver_mailru extends yf_oauth_driver2
{
    public $scope = '';
    public $get_access_token_method = 'POST';
    public $url_params_access_token = [
        'grant_type' => 'authorization_code',
    ];

    // Register for API client_id and client_secret here: http://api.mail.ru/sites/my/

    protected $url_authorize = 'https://connect.mail.ru/oauth/authorize';
    protected $url_access_token = 'https://connect.mail.ru/oauth/token';
    protected $url_user = 'http://www.appsmail.ru/platform/api';

    /**
     * @param mixed $raw
     */
    public function _get_user_info_for_auth($raw = [])
    {
        $user_info = [
            'user_id' => $raw[0]['uid'],
            'login' => $raw[0]['email'],
            'name' => $raw[0]['nick'],
            'email' => $raw[0]['email'],
            'avatar_url' => $raw[0]['pic'],
            'profile_url' => $raw[0]['link'],
            'birthday' => $raw[0]['birthday'],
        ];
        return $user_info;
    }


    public function get_user_info()
    {
        $access_token = $this->_storage_get('access_token');
        if ( ! $access_token) {
            $access_token = $this->get_access_token();
            if ( ! $access_token) {
                $this->_storage_clean();
                js_redirect($this->redirect_uri, $url_rewrite = false);
                return false;
            }
        }
        if ( ! $this->_storage_get('user')) {
            $method = 'users.getInfo';
            $sign = md5('app_id=' . $this->client_id . 'method=' . $method . 'secure=1' . 'session_key=' . $access_token . $this->client_public);
            $url = $this->url_user . '?' . http_build_query([
                'session_key' => $access_token,
                'secure' => 1,
                'app_id' => $this->client_id,
                'method' => $method,
                'sig' => $sign,
            ]);
            $result = common()->get_remote_page($url, $cache = false, $opts, $response);
            $result = $this->_decode_result($result, $response, __FUNCTION__);
            if (isset($result['error']) || substr($response['http_code'], 0, 1) == '4') {
                $this->_storage_clean();
                js_redirect($this->redirect_uri, $url_rewrite = false);
                return false;
            }
            $this->_storage_set('user_info_request', ['result' => $result, 'response' => $response]);
            $this->_storage_set('user', $result);
        }
        return $this->_storage_get('user');
    }
}
