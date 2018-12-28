<?php

load('oauth_driver2', '', 'classes/oauth/');
class yf_oauth_driver_facebook extends yf_oauth_driver2
{
    public $scope = '';
    public $get_access_token_method = 'GET';

    // Register for API client_id and client_secret here: https://developers.facebook.com/apps

    protected $url_authorize = 'https://www.facebook.com/dialog/oauth';
    protected $url_access_token = 'https://graph.facebook.com/oauth/access_token';
    protected $url_user = 'https://graph.facebook.com/me';

    /**
     * @param mixed $raw
     */
    public function _get_user_info_for_auth($raw = [])
    {
        $user_info = [
            'user_id' => $raw['id'],
            'login' => $raw['email'],
            'name' => $raw['name'],
            'email' => $raw['email'],
            'avatar_url' => '',
            'profile_url' => $raw['link'],
            'locale' => $raw['locale'],
            'timezone' => $raw['timezone'],
            'gender' => $raw['gender'],
        ];
        return $user_info;
    }

    /**
     * @param mixed $result
     * @param mixed $response
     * @param mixed $for_method
     */
    public function _decode_result($result, $response, $for_method = '')
    {
        // Force content_type here as facebook return text/plain, but in form urlencoded format
        if ($for_method == 'get_access_token') {
            $response['content_type'] = 'application/x-www-form-urlencoded';
        }
        return parent::_decode_result($result, $response, $for_method);
    }
}
