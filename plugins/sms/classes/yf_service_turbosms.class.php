<?php

class yf_service_turbosms
{
    public $allowed_countries = [
        'UA' => '38',
    ];
    public $default_conf = [
        'timeout' => 5,
        'db_host' => '94.249.146.189',
        'db_port' => '3306',
        'db_name' => 'users',
        'db_user' => '__override_user_here__',
        'db_pswd' => '__override_pswd_here__',
        'text' => '__override_default_text_here__',
        'sign' => '__override_sign_here__',
    ];
    public $conf = [
    ];
    public $copy_to = [
    ];

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        // Support for driver-specific methods
        if (is_object($this->_connection) && method_exists($this->_connection, $name)) {
            return call_user_func_array([$this->_connection, $name], $args);
        }
        return main()->extend_call($this, $name, $args);
    }

    /**
     * @param mixed $phone
     * @param mixed $text
     * @param mixed $params
     */
    public function send($phone, $text, $params = [])
    {
        if ( ! $this->_is_enabled()) {
            return false;
        }
        if ( ! strlen($phone) || ! strlen($text)) {
            throw new Exception('ERROR SMS: phone: ' . $phone . ', error: ' . $phone_error . ')');
            return false;
        }
        $phone_error = '';
        $phone = $this->_phone_cleanup($phone, $phone_error);
        // Example correct: +380 63 123 45 67 (spaces here for example readability)
        if (strlen($phone) != 13 || $phone_error) {
            throw new Exception('ERROR TURBOSMS: phone: ' . $phone . ', error: ' . $phone_error);
            return false;
        }
        if ($text == '') {
            $text = $this->conf['text'] ?: $this->default_conf['text'];
        }
        $sign = $params['sign'] ?: $this->conf['sign'] ?: $this->default_conf['sign'];
        $data[$phone] = [
            'sign' => $sign,
            'number' => $phone,
            'message' => $text,
        ];
        $sms_copy_to = $this->copy_to;
        if ($sms_copy_to) {
            if ( ! is_array($sms_copy_to)) {
                $sms_copy_to = [$sms_copy_to];
            }
            foreach ((array) $sms_copy_to as $phone_copy_to) {
                $phone_copy_error = '';
                $phone_copy_to = $this->_phone_cleanup($phone_copy_to, $phone_copy_error);
                if ( ! $phone_copy_to || $phone_copy_error) {
                    continue;
                }
                if ($phone_copy_to == $phone || isset($data[$phone])) {
                    continue;
                }
                $data[$phone] = [
                    'sign' => $sign,
                    'number' => $phone_copy_to,
                    'message' => $text,
                ];
            }
        }
        return $this->insert_data_with_sql($data);
    }

    /**
     * @param mixed $data
     */
    public function insert_data_with_sql($data = [])
    {
        if ( ! $data) {
            return false;
        }
        $conf = $this->default_conf;
        foreach ($this->conf as $k => $v) {
            $conf[$k] = $v;
        }
        $db = mysqli_init();
        mysqli_options($db, MYSQLI_OPT_CONNECT_TIMEOUT, $conf['timeout']);
        mysqli_real_connect($db, $conf['db_host'], $conf['db_user'], $conf['db_pswd'], $conf['db_name'], $conf['db_port']);
        mysqli_set_charset($db, 'utf8');
        mysqli_select_db($db, $conf['db_name']);
        // TODO: create separate db connection for this
        // !!! DO not use insert_safe here, it cannot check fields for existance in turbosms and SMS will NOT send
        $sql = str_replace('INSERT INTO `' . DB_PREFIX . $conf['db_name'] . '`', 'INSERT INTO `' . $conf['db_name'] . '`', db()->insert($conf['db_name'], db()->es($data), $as_sql = true));
        $result = mysqli_query($db, $sql);
        if (DEBUG_MODE) {
            debug(__FUNCTION__ . '[]', ['result' => (int) $result, 'data' => $data]);
        }
        mysqli_close($db);
        return $result;
    }

    /**
     * @param mixed $phone
     */
    public function _phone_cleanup($phone = '', &$error = '')
    {
        $error = false;
        $phone = preg_replace('/[^0-9]+/ims', '', strip_tags($phone));
        // TODO: implement $this->allowed_countries
        // 063 123 45 67 (spaces here for example readability)
        if (strlen($phone) == 10) {
            $phone = '+38' . $phone;
        // 63 123 45 67 (spaces here for example readability)
        } elseif (strlen($phone) == 9) {
            $phone = '+380' . $phone;
        } elseif (strlen($phone) == 12) {
            if (substr($phone, 0, 2) != '38') {
                $error = 'phone error: incorrect country: ' . $phone;
            } else {
                $phone = '+' . $phone;
            }
        } else {
            $error = 'phone error: number is incorrect: ' . $phone;
        }
        $phone = '+' . ltrim($phone, '+');
        return $phone;
    }

    /**
     * @param mixed $params
     */
    public function _is_enabled($params = [])
    {
        if ((DEBUG_MODE || (defined('TEST_MODE') && TEST_MODE)) && ! (DEBUG_MODE && main()->_get('object') == 'test_sms')) {
            return false;
        }
        return true;
    }
}
