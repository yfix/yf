<?php

load('oauth_driver2', '', 'classes/oauth/');
class yf_oauth_driver_foursquare extends yf_oauth_driver2
{
    public $scope = '';
    public $url_params_access_token = [
        'grant_type' => 'authorization_code',
    ];
    public $get_access_token_method = 'POST';

    protected $url_authorize = 'https://foursquare.com/oauth2/authorize';
    protected $url_access_token = 'https://foursquare.com/oauth2/access_token';
    protected $url_user = 'https://api.foursquare.com/v2/users/self';

    /**
     * @param mixed $raw
     */
    public function _get_user_info_for_auth($raw = [])
    {
        $user_info = [
            'user_id' => $raw['response']['user']['id'],
            'login' => $raw['response']['user']['contact']['email'],
            'name' => $raw['response']['user']['firstName'],
            'email' => $raw['response']['user']['contact']['email'],
            'avatar_url' => $raw['response']['user']['photo'],
            'profile_url' => '',
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
            $url = $this->url_user . '?' . http_build_query([
                'oauth_token' => $access_token,
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
