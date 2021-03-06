<?php

/**
 * YF Login Form.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_login_form
{
    /** @var string */
    public $DEF_REDIRECT_URL = './?object=account';
    /** @var string */
    public $LOGIN_FIELD = 'login';
    /** @var string */
    public $OAUTH_LIST_PROVIDERS = true;

    /**
     * Default function.
     */
    public function show()
    {
        if (main()->USER_ID) {
            return js_redirect('./');
        }
        return $this->_show_form();
    }


    public function _admin_login_form()
    {
        return form(['form_action' => './?task=login'], ['no_label' => 1, 'class' => 'form-horizontal col-md-4 col-md-offset-3 form-no-label'])
            ->validate(['login' => 'trim|required', 'password' => 'trim|required'])
            ->login(['class' => 'input-medium'])
            ->password(['class' => 'input-medium'])
            ->submit(['value' => 'Enter']);
    }


    public function _small_form()
    {
        if (conf('_login_form_displayed')) {
            return '';
        }
        return form(['form_action' => './?task=login'], [
                'class' => 'form-horizontal form-condensed form-no-labels col-md-10',
                'no_label' => 1,
                'hide_empty' => 1,
            ])
            ->validate([
                '__form_id__' => 'login_small_form',
                $this->LOGIN_FIELD => 'trim|required',
                'password' => 'trim|required',
            ])
            ->login($this->LOGIN_FIELD, '', ['class' => 'input-medium', 'type' => $this->LOGIN_FIELD != 'login' ? $this->LOGIN_FIELD : 'text'])
            ->password(['class' => 'input-medium'])
            ->check_box('remember_me')
            ->container($this->oauth(['only_icons' => 1]), ['wide' => 1])
            ->submit(['value' => 'Login', 'link_name' => 'Register', 'link_url' => './?object=register']);
    }

    /**
     * Login form.
     */
    public function _show_form()
    {
        // Already logged in users not needed to login again
        if (main()->USER_ID) {
            return js_redirect($this->DEF_REDIRECT_URL);
        }
        // Default resirect after login
        $_url_after_login = $this->DEF_REDIRECT_URL;
        // Process special redirect format
        if ( ! empty($_GET['go_url'])) {
            if (false !== ($pos1 = strpos($_GET['go_url'], ';'))) {
                $_GET['go_url'] = substr($_GET['go_url'], 0, $pos1) . '&action=' . substr($_GET['go_url'], $pos1 + 1);
            }
            $_url_after_login = './?object=' . str_replace(';', '&', $_GET['go_url']);
        } elseif (conf('_force_login_go_url')) {
            $_url_after_login = conf('_force_login_go_url');
        }
        if (strlen($_url_after_login) > 3 && ! $_SESSION['user_go_url']) {
            if (substr($_url_after_login, 0, 3) == './?') {
                $_url_after_login = substr($_url_after_login, 3);
            }
            $_SESSION['user_go_url'] = $_url_after_login;
        }
        // To prevent multiple login forms displayed on one page
        conf('_login_form_displayed', true);

        return form(['form_action' => './?task=login'], [
                'class' => 'form-horizontal',
                'legend' => 'Member Login',
                'hide_empty' => 1,
            ])
            ->validate([
                '__form_id__' => 'login_full_form',
                $this->LOGIN_FIELD => 'trim|required',
                'password' => 'trim|required',
            ])
            ->login($this->LOGIN_FIELD, '', ['class' => 'input-medium', 'type' => $this->LOGIN_FIELD != 'login' ? $this->LOGIN_FIELD : 'text'])
            ->password(['class' => 'input-medium'])
            ->check_box('remember_me', '', ['no_label' => 1])
            ->submit(['value' => 'Login', 'link_name' => 'Register', 'link_url' => './?object=register'])
            ->container($this->oauth(['only_icons' => 1]), ['wide' => 0])
            ->link('Retrieve lost password', './?object=get_pswd', ['class' => 'btn btn-mini btn-xs'])
            ->hidden('action', null, ['value' => 'login']);
    }

    /**
     * Wrong Login.
     */
    public function wrong_login()
    {
        common()->message_error('Sorry, but some info you have entered is wrong.');
        return js_redirect('./?object=login_form');
    }

    /**
     * Account Inactive.
     */
    public function account_inactive()
    {
        $replace = [
            'login_form_url' => process_url('./?object=login_form'),
        ];
        return tpl()->parse(__CLASS__ . '/account_inactive', $replace);
    }

    /**
     * Endpoint for oauth logins.
     * @param mixed $params
     */
    public function oauth($params = [])
    {
        if ( ! isset($params['only_icons'])) {
            $params['only_icons'] = 1;
        }
        if ( ! $_GET['id'] && ! $this->OAUTH_LIST_PROVIDERS && empty($params)) {
            if (main()->USER_ID) {
                return js_redirect('./');
            }
            return js_redirect('./?object=login_form');
        }
        //		$allowed_objects = array('login','login_form','register','user_profile','profile')
        $allowed_objects = ['login_form', 'user_profile', 'profile'];
        $def_object = 'login_form';
        $url_object = in_array($_GET['object'], $allowed_objects) ? $_GET['object'] : 'login_form';
        $url_action = __FUNCTION__;
        if (in_array($_GET['object'], $allowed_objects) && $_GET['id'] && preg_match('/^[a-z0-9_-]+$/ims', $_GET['id'])) {
            return _class('oauth')->login($_GET['id']);
        }
        $body = [];
        foreach ((array) $providers as $name => $settings) {
            if ($name[0] == '_') {
                continue;
            }
            $href = url_user('/' . $url_object . '/' . $url_action . '/' . $name);
            $img_web_path = 'https://s3-eu-west-1.amazonaws.com/yfix/oauth/providers/' . $name . '.png';
            $body[] = '<a href="' . $href . '">' . '<img src="' . $img_web_path . '" style="height:32px;padding-right:2px;">' . ( ! $params['only_icons'] ? ' ' . $name : '') . '</a>';
        }
        return implode(PHP_EOL, $body);
    }
}
