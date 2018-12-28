<?php

// https://wiki.webmoney.ru/projects/webmoney/wiki/Web_Merchant_Interface

class WebMoney
{
    protected $_currency_allow = ['USD', 'EUR', 'UAH', 'RUB'];

    protected $_signature_allow = [
        'LMI_PAYEE_PURSE',    // Кошелек продавца
        'LMI_PAYMENT_AMOUNT', // Сумма платежа
        'LMI_PAYMENT_NO',     // Внутренний номер покупки продавца
        'LMI_MODE',           // Флаг тестового режима
        'LMI_SYS_INVS_NO',    // Внутренний номер счета в системе WebMoney Transfer
        'LMI_SYS_TRANS_NO',   // Внутренний номер платежа в системе WebMoney Transfer
        'LMI_SYS_TRANS_DATE', // Дата и время выполнения платежа
        'LMI_SECRET_KEY',     // Secret Key
        'LMI_PAYER_PURSE',    // Кошелек покупателя
        'LMI_PAYER_WM',       // WMId покупателя
    ];

    private $_hash_method_allow = [
        // 'md5', // deprecated: not supported by WebMoney
        'sha256',
    ];

    private $_key_public = null;
    private $_key_private = null;
    private $_hash_method = null;

    public function __construct($key_public, $key_private, $hash_method = 'sha256')
    {
        if (empty($key_public)) {
            throw new InvalidArgumentException('key_public (payee purse) is empty');
        }
        if (empty($key_private)) {
            throw new InvalidArgumentException('key_private (secret key) is empty');
        }
        if ( ! $this->hash_method($hash_method)) {
            throw new InvalidArgumentException('hash method allow is not allow');
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

    public function hash_method_allow($value)
    {
        $result = is_string($value) && in_array($value, $this->_hash_method_allow);
        return  $result;
    }

    public function hash_method($value = null)
    {
        $result = null;
        if ( ! empty($value)) {
            if ($this->hash_method_allow($value)) {
                $this->_hash_method = $value;
            } else {
                return  $result;
            }
        }
        $result = $this->_hash_method;
        return  $result;
    }

    public function signature($options, $is_request = true)
    {
        $_ = &$options;
        $keys = $this->_signature_allow;
        $request = array_combine($keys, $keys);
        // add allow fields
        foreach ((array) $keys as $key) {
            isset($_[$key]) && $request[$key] = &$_[$key];
        }
        // compile string
        $key = $this->_key_private;
        $request['LMI_SECRET_KEY'] = $key;
        $str = implode('', $request);
        // create signature
        $result = $this->str_to_sign($str);
        return  $result;
    }

    public function str_to_sign($str)
    {
        $hash_method = $this->hash_method();
        $result = strtoupper(hash($hash_method, $str));
        // var_dump( $hash_method, $str, $result  );
        return  $result;
    }
}
