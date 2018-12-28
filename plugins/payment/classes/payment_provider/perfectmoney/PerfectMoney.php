<?php

// https://perfectmoney.is/

class PerfectMoney
{
    protected $_signature_allow = [
        'PAYMENT_ID',
        'PAYEE_ACCOUNT',
        'PAYMENT_AMOUNT',
        'PAYMENT_UNITS',
        'PAYMENT_BATCH_NUM',
        'PAYER_ACCOUNT',
        'key_private',
        'TIMESTAMPGMT',
    ];

    private $_key_public = null;
    private $_key_private = null;

    public function __construct($key_public, $key_private)
    {
        if (empty($key_public)) {
            throw new InvalidArgumentException('key_public is empty');
        }
        if (empty($key_private)) {
            throw new InvalidArgumentException('key_private is empty');
        }
        $this->_key_public = $key_public;
        $this->_key_private = $key_private;
    }

    public function key($name = 'public', $value = null)
    {
        if ( ! in_array($name, ['public', 'private'])) {
            return  null;
        }
        $_name = '_key_' . $name;
        $_value = &$this->{ $_name };
        // set
        if ( ! empty($value) && is_string($value)) {
            $_value = $value;
        }
        // get
        return  $_value;
    }

    public function signature($options, $is_request = true)
    {
        $_ = &$options;
        $data = [];
        // add allow fields
        foreach ((array) $this->_signature_allow as $key) {
            if ($key == 'key_private') {
                $data[$key] = $this->hash($this->key('private'));
                continue;
            }
            if (isset($_[$key])) {
                $data[$key] = &$_[$key];
            } else {
                $data[$key] = 'NULL';
            }
        }
        // DEBUG
        // var_dump( $data ); exit;
        // compile string
        $str = implode(':', $data);
        // create signature
        $result = $this->str_to_sign($str);
        return  $result;
    }

    public function hash($str)
    {
        $result = hash('md5', $str, false);
        $result = strtoupper($result);
        return  $result;
    }

    public function str_to_sign($str)
    {
        $result = $this->hash($str);
        // DEBUG
        // var_dump( $str, $result  );
        return  $result;
    }
}
