<?php

/**
 * Users registration.
 */
class yf_register
{
    public function show()
    {
        $validate_rules = [
            '__form_id__' => 'register_form',
            'login' => ['trim|required|min_length[2]|max_length[12]|ajax_is_unique[user.login]|xss_clean', function ($in) {
                return module('register')->_login_not_exists($in);
            }],
            'email' => ['trim|required|valid_email|ajax_is_unique[user.email]', function ($in) {
                return module('register')->_email_not_exists($in);
            }],
            'emailconf' => 'trim|required|valid_email|matches[email]',
            'password' => 'trim|required', //|md5
            'pswdconf' => 'trim|required|matches[password]', // |md5
            'captcha' => 'trim|captcha',
        ];
        $a = $_POST;
        $a['redirect_link'] = url('/@object/success');
        // TODO: generate confirmation code and send emails
        return form($a, ['legend' => 'Registration', 'class_add' => 'col-md-6'])
            ->validate($validate_rules)
            ->db_insert_if_ok('user', ['login', 'email', 'password'], null, ['on_success_text' => 'Your account was created successfully!'])
            ->login(['pattern' => '^[a-zA-Z0-9]{4,32}$', 'title' => 'Only letters and numbers allowed, min 4, max 32 symbols'])
            ->email()
            ->email('emailconf')
            ->password()
            ->password('pswdconf')
            ->captcha()
            ->save()
            ->container(module('login_form')->oauth(['only_icons' => 1]), ['wide' => 0]);
    }


    public function success()
    {
        return common()->show_notices();
    }

    /**
     * @param mixed $in
     */
    public function _login_not_exists($in = '')
    {
        return ! db()->get_one('SELECT id FROM ' . db('user') . ' WHERE login="' . db()->es($in) . '"');
    }

    /**
     * @param mixed $in
     */
    public function _email_not_exists($in = '')
    {
        return ! db()->get_one('SELECT id FROM ' . db('user') . ' WHERE email="' . db()->es($in) . '"');
    }

    /**
     * Send email with verification code.
     * @param mixed $code
     * @param mixed $extra
     */
    public function _send_email_with_code($code = '', $extra = false)
    {
        $identify = ! empty($extra['identify']) ? $extra['identify'] : $_POST['email'];
        $replace = [
            'nick' => $identify,
            'confirm_code' => $code,
            'conf_link' => url('/@object/confirm/' . $code),
            'conf_form_url' => url('/login_form/account_inactive'),
            'admin_name' => SITE_ADVERT_NAME,
            'advert_url' => SITE_ADVERT_URL,
        ];
        if (isset($extra['add_replace']) && is_array($extra['add_replace'])) {
            $replace = array_merge($replace, $extra['add_replace']);
        }
        $text = tpl()->parse('@object/email_confirm' . ( ! empty($_POST['account_type']) ? '_' . $_POST['account_type'] : ''), $replace);
        // prepare email data
        $email_from = SITE_ADMIN_EMAIL;
        $name_from = SITE_ADVERT_NAME;
        $email_to = ! empty($extra['email']) ? $extra['email'] : $_POST['email'];
        $name_to = $identify;
        $subject = ! empty($extra['subject']) ? $extra['subject'] : t('Membership confirmation required!');
        return common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $text, nl2br($text));
    }

    /**
     * Send success email.
     * @param mixed $extra
     */
    public function _send_success_email($extra = false)
    {
        $identify = ! empty($extra['identify']) ? $extra['identify'] : $_POST['email'];
        $replace = [
            'code' => $code,
            'nick' => $identify,
            'password' => $_POST['password'],
            'advert_name' => SITE_ADVERT_NAME,
            'advert_url' => SITE_ADVERT_URL,
        ];
        $text = tpl()->parse('@object/email_success' . ( ! empty($_POST['account_type']) ? '_' . $_POST['account_type'] : ''), $replace);
        // prepare email data
        $email_from = SITE_ADMIN_EMAIL;
        $name_from = SITE_ADVERT_NAME;
        $email_to = ! empty($extra['email']) ? $extra['email'] : $_POST['email'];
        $name_to = $identify;
        $subject = ! empty($extra['subject']) ? $extra['subject'] : t('Membership confirmation required!');
        return common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $text, nl2br($text));
    }

    /**
     * Confirm registration for common users.
     */
    // TODO: convert into form()
    public function confirm()
    {
        // Send registration confirmation email
        if ( ! $this->CONFIRM_REGISTER) {
            return tpl()->parse('@object/confirm_messages', ['msg' => 'confirm_not_needed']);
        }
        // Check confirmation code
        if ( ! strlen($_GET['id'])) {
            return _e('Confirmation ID is required!');
        }
        // Decode confirmation number
        list($user_id, $member_date) = explode('wvcn', trim(base64_decode($_GET['id'])));
        $user_id = (int) $user_id;
        $member_date = (int) $member_date;
        // Get target user info
        if ( ! empty($user_id)) {
            $target_user_info = user($user_id);
        }
        // User id is required
        if (empty($target_user_info['id'])) {
            return _e('Wrong user ID');
        }
        // Check if user already confirmed
        if ($target_user_info['active']) {
            return tpl()->parse('@object/confirm_messages', ['msg' => 'already_confirmed']);
        }
        // Check if code is expired
        if ( ! common()->_error_exists()) {
            if ( ! empty($member_date) && (time() - $member_date) > $this->CONFIRM_TTL) {
                _re('Confirmation code has expired.');
            }
        }
        if ( ! common()->_error_exists()) {
            if ($_GET['id'] != $target_user_info['verify_code']) {
                _re('Wrong confirmation code');
            }
        }
        if ( ! common()->_error_exists()) {
            db()->update('user', ['active' => 1], $user_id);
            return tpl()->parse('@object/confirm_messages', ['msg' => 'confirm_success']);
        }
        $body .= _e();
        $body .= tpl()->parse('@object/enter_code', $replace3);
        $body .= tpl()->parse('@object/resend_code', $replace4);
        return $body;
    }

    /**
     * Manually enter code.
     */
    // TODO: convert into form()
    public function enter_code()
    {
        // Do activate
        if (isset($_POST['confirm_code'])) {
            $_GET['id'] = $_POST['confirm_code'];
            return $this->confirm();
        }
        // Display form
        $replace = [
        ];
        return tpl()->parse('@object/enter_code', $replace);
    }

    /**
     * Re-send confirmation code.
     */
    // TODO: convert into form()
    public function resend_code()
    {
        if ( ! empty($_POST['email'])) {
            $user_info = db()->query_fetch('SELECT * FROM ' . db('user') . ' WHERE email="' . _es($_POST['email']) . '"');
            if (empty($user_info)) {
                return _e('No such user');
            }
            if ($user_info['active']) {
                return 'Your account is already activated.';
            }
            if ( ! common()->_error_exists()) {
                $code = base64_encode($user_info['id'] . 'wvcn' . time());
                db()->update('user', ['verify_code' => $code], $user_info['id']);
                $replace = [
                    'nick' => $user_info['nick'],
                    'confirm_code' => $code,
                    'conf_link' => url('/@object/confirm&id=' . $code),
                    'conf_form_url' => url('/login_form/account_inactive'),
                    'admin_name' => SITE_ADVERT_NAME,
                    'advert_url' => SITE_ADVERT_URL,
                ];
                $text = tpl()->parse('@object/email_confirm_' . $this->_account_types[$user_info['group']], $replace);
                // Prepare email
                $email_from = SITE_ADMIN_EMAIL;
                $name_from = SITE_ADVERT_NAME;
                $email_to = $user_info['email'];
                $name_to = $user_info['nick'];
                $subject = t('Membership confirmation required!');
                $send_result = common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $text, nl2br($text));
                if ($send_result) {
                    return 'Code sent. Please check your email.';
                }
                return 'Error sending mail. Please contact site admin.';
            }
        }
        return tpl()->parse('@object/resend_code', $replace);
    }
}
