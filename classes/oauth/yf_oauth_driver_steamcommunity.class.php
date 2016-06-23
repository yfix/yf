<?php

load('oauth_driver2', 'framework', 'classes/oauth/');

class yf_oauth_driver_steamcommunity extends yf_oauth_driver2 {
    public $url_rsa_key = 'https://steamcommunity.com/login/getrsakey';
    public $url_captcha = 'https://steamcommunity.com/public/captcha.php?gid=';
    public $url_user_info = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/';

    function _get_user_info_for_auth($raw = array()) {
        $user_info = array(
            'user_id'		=> $raw['steamid'],
            'login'			=> $raw['personaname'],
            'name'			=> $raw['personaname'],
            'email'			=> '',
            'avatar_url'	=> $raw['avatar'],
            'profile_url'	=> $raw['profileurl'],
            'locale'		=> '',
            'timezone'		=> '',
            'gender'		=> '',
        );
        return $user_info;
    }

    function get_user_info() {
        if (DEBUG_MODE && $_GET['oauth_clean']) {
            $this->_storage_clean();
        }
        $access_token = $this->_storage_get('access_token');
        if (!$access_token) {
            $access_token = $this->get_access_token();
            if (!$access_token) {
                $this->_storage_clean();
                js_redirect( $this->redirect_uri, $url_rewrite = false );
                return false;
            }
        }
        return $this->_storage_get('user');
    }

    function get_access_token() {
        $access_token = $this->_storage_get('access_token');
        if ($access_token) {
            return $access_token;
        }
        $code = $_GET['code'];
        if (!$code) {
            echo $this->login_form();
            die();
        }
    }

    function _storage_clean() {
        if (isset($_SESSION['oauth'][$this->provider])) {
           $_SESSION['oauth'][$this->provider] = '';
        }
    }

    function login_form(){
        $a = [
            'login' => !empty($_POST['login']) ? $_POST['login'] : '',
            'password' => !empty($_POST['password']) ? $_POST['password'] : '',
        ];
        if(!($this->_storage_get('captcha_gid') || !empty($_POST['captcha_gid']))) {
            $form = form($a)
                ->validate(array(
                    '__form_id__' => 'steamcommunity_login_form',
                    'login' => 'trim|required|xss_clean',
                    'password' => 'trim|required|xss_clean',
                ))
                ->on_validate_ok(function () {
                    $this->authorize();
                })
                ->login('login')
                ->password('password')
                ->submit(array('value' => 'Login'));
        }
        else {
            $form = $this->login_captcha_form();
        }
        return $form;
    }

    function login_captcha_form(){
        $a = [
            'captcha_gid' =>$this->_storage_get('captcha_gid') ? $this->_storage_get('captcha_gid') : $_POST['captcha_gid'],
            'captcha_text' =>$_POST['captcha_text'],
        ];
        $this->_storage_set('captcha_gid', '');
        $url_captcha_image = '<img src="'.$this->url_captcha.$a['captcha_gid'].'">"';
        $form = form($a)
            ->validate(array(
                '__form_id__' => 'steamcommunity_login_captcha_form',
                'captcha_gid' => 'trim|required|xss_clean',
                'captcha_text' => 'trim|required|xss_clean',
            ))
            ->on_validate_ok(function() use ($a){
                $this->authorize();
            })
            ->container($url_captcha_image, array('desc' => t('Captcha')))
            ->hidden('captcha_gid')
            ->text('captcha_text')
            ->submit(array('value' => 'Submit'));
        return $form;
    }

    function authorize() {
        $login = !empty($_POST['login']) ? $_POST['login'] : '';
        $password = !empty($_POST['password']) ? $_POST['password'] : '';
        $captcha_gid = '-1';
        $captcha_text = '';

        if(!empty($login) && !empty($password)) {
            $rsaResponse = $this->cURL($this->url_rsa_key, null, ['username' => $login]);
            $rsaJson = json_decode($rsaResponse, true);

            if ($rsaJson == null) {
                return LoginResult::GeneralFailure;
            }

            if (!$rsaJson['success']) {
                return false;
            }

            require_php_lib('phpseclib');

            $rsa = new \phpseclib\Crypt\RSA();

            $key = [
                'n' => new \phpseclib\Math\BigInteger($rsaJson['publickey_mod'], 16),
                'e' => new \phpseclib\Math\BigInteger($rsaJson['publickey_exp'], 16)
            ];

            $rsa->load($key);

            $encryptedPassword = base64_encode($rsa->encrypt($password, \phpseclib\Crypt\RSA::PADDING_PKCS1));
            $params = [
                'username' => urlencode($login),
                'password' => urlencode($encryptedPassword),
                'twofactorcode' => '',
                'captchagid' => urlencode($captcha_gid),
                'captcha_text' => urlencode($captcha_text),
                'emailsteamid' => urlencode($this->client_id),
                'emailauth' => '',
                'rsatimestamp' => urlencode($rsaJson['timestamp']),
                'remember_login' => 'false',
                "loginfriendlyname" => "eloplay"
            ];
        }


        if(!empty($_POST['captcha_text'])) {
            $params = $this->_storage_get('params');
            $params['captchagid'] = $_POST['captcha_gid'];
            $params['captcha_text'] = $_POST['captcha_text'];
        }


        $loginResponse = $this->cURL('https://steamcommunity.com/login/dologin/', null, $params);
        $loginResponseObj = [];
        if (!empty($loginResponse)) {
            $loginResponseObj = json_decode($loginResponse, true);
        }

        if(!empty($_POST['captcha_text'])) {
            $this->_storage_set('loginResponseObj', $loginResponseObj);
        }
        if ($loginResponseObj['captcha_needed']) {
            $this->_storage_set('captcha_gid', $loginResponseObj['captcha_gid']);
            $this->_storage_set('params', $params);

            return js_redirect();

        }
        if($loginResponseObj['success']) {
            $this->_storage_set('access_token_request', ['response'=>$loginResponseObj]);
            $this->_storage_set('access_token', $loginResponseObj['transfer_parameters']['token']);

            $steam_id = $loginResponseObj['transfer_parameters']['steamid'];
            $user_info = $this->_get_user_info($steam_id);
            $this->_storage_set('user_info_request', $user_info);
            $this->_storage_set('user', $user_info['response']['players'][0]);
            return js_redirect();
        }
    }

    function _get_user_info($steam_id){
        $url = $this->url_user_info.'?key='.$this->client_id.'&steamids='.$steam_id;
        $user_info = $this->cURL($url);
        $user_info = !empty($user_info) ? json_decode($user_info, true) : false;
        return $user_info;
    }



    public function cURL($url, $ref = null, $postData = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0');
        if (isset($ref)) {
            curl_setopt($ch, CURLOPT_REFERER, $ref);
        }
        if (isset($postData)) {
            curl_setopt($ch, CURLOPT_POST, true);
            $postStr = "";
            foreach ($postData as $key => $value) {
                if ($postStr)
                    $postStr .= "&";
                $postStr .= $key . "=" . $value;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
        }
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}

