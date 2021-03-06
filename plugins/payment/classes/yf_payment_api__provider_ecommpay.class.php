<?php

_class('payment_api__provider_remote');

class yf_payment_api__provider_ecommpay extends yf_payment_api__provider_remote
{
    public $URL = 'https://terminal.ecommpay.com/';
    public $URL_TEST = 'https://terminal-sandbox.ecommpay.com/';

    public $URL_API = 'https://gate.ecommpay.com/%method/json/';
    public $URL_API_TEST = 'https://gate-sandbox.ecommpay.com/%method/json/';
    public $URL_BIN_INFO = 'https://bins.payout.com/api/v1/bins/'; //get Bank info by first 6 digits card number, free request daily limit = 1000
    public $ALLOW_CARDS_COUNTRIES = [];//['UA'=>'Украине','US'=>'США'] or empty array if every country allow

    public $method_allow = [
        'order' => [
            'payin' => [
                'ecommpay',
            ],
            'payout' => [
                'pay_card',
            ],
        ],
        'payin' => [
            'ecommpay' => [
                'title' => 'Visa, MasterCard',
                'icon' => 'ecommpay',
                'amount_min' => 100,
                'fee' => 0, // 0.1%
                'currency' => [
                    'USD' => [
                        'currency_id' => 'USD',
                        'active' => true,
                    ],
                ],
            ],
            'card' => [
                'title' => 'Visa, MasterCard',
                'icon' => 'visa-mastercard',
                'option' => [
                    // 'payment_group_id' => 1,
                    // 'followup'         => 0,
                ],
                'amount_min' => 100,
                'fee' => 0, // 0.1%
                'currency' => [
                    'USD' => [
                        'currency_id' => 'USD',
                        'active' => true,
                    ],
                ],
            ],
            'qiwi' => [
                'title' => 'Qiwi',
                'icon' => 'qiwi',
                'option' => [
                    // 'payment_group_id' => 6,
                    // 'followup'         => 0,
                    // 'phone'                    => '380679041321',
                    // 'external_payment_type_id' => 'qw',
                ],
                'amount_min' => 100,
                'fee' => 0, // 0.1%
                'currency' => [
                    'USD' => [
                        'currency_id' => 'USD',
                        'active' => true,
                    ],
                ],
            ],
            'c24' => [
                'title' => 'C24',
                'icon' => 'c24',
                'option' => [
                    // 'payment_group_id' => 28,
                    // 'followup'         => 1,
                    // 'external_payment_type_id' => 'qw',
                ],
                'amount_min' => 100,
                'fee' => 0, // 0.1%
                'currency' => [
                    'USD' => [
                        'currency_id' => 'USD',
                        'active' => true,
                    ],
                ],
            ],
            'comepay' => [
                'title' => 'Comepay',
                'icon' => 'comepay',
                'option' => [
                    // 'payment_group_id' => 29,
                    // 'followup'         => 0,
                    // 'external_payment_type_id' => 'qw',
                ],
                'amount_min' => 100,
                'fee' => 0, // 0.1%
                'currency' => [
                    'USD' => [
                        'currency_id' => 'USD',
                        'active' => true,
                    ],
                ],
            ],
            'mobile_commerce' => [
                'title' => 'Mobile commerce',
                'icon' => 'mobile-commerce',
                'option' => [
                    // 'payment_group_id' => 7,
                    // 'followup'         => 0,
                    // 'phone' => '1231234567',
                ],
                'amount_min' => 100,
                'fee' => 0, // 0.1%
                'currency' => [
                    'USD' => [
                        'currency_id' => 'USD',
                        'active' => true,
                    ],
                ],
            ],
            'webmoney' => [
                'title' => 'WebMoney',
                'icon' => 'webmoney',
                'option' => [
                    // 'payment_group_id' => 2,
                    // 'followup'         => 0,
                ],
                // 'amount_min'  => 100,
                'fee' => 0, // 0.1%
                'currency' => [
                    'USD' => [
                        'currency_id' => 'USD',
                        'active' => true,
                    ],
                ],
            ],
        ],
        'payout' => [
            'pay_card' => [
                'title' => 'Visa, MasterCard',
                'icon' => 'visa-mastercard',
                'uri' => [
                    '%method' => 'card',
                ],
                'action' => 'payout',
                'amount' => [
                    // 'min' => 10,
                    'max' => 150,
                ],
                // 'fee'        => 0, // 0.1%
                'currency' => [
                    'USD' => [
                        'currency_id' => 'USD',
                        'is_int' => true,
                        'active' => true,
                    ],
                ],
                'field' => [
                    'action',
                    'site_id',
                    'amount',
                    'currency',
                    'external_id',
                    // 'customer_ip',
                    'comment',
                    'card',
                    'sender_first_name',
                    'sender_last_name',
                    'sender_middle_name',
                    'sender_passport_number',
                    'sender_passport_issue_date',
                    'sender_passport_issued_by',
                    'sender_phone',
                    'sender_birthdate',
                    'sender_address',
                    'sender_city',
                    'sender_postindex',
                ],
                'order' => [
                    'card',
                    'sender_first_name',
                    'sender_last_name',
                    'sender_middle_name',
                    'sender_passport_number',
                    'sender_passport_issue_date',
                    'sender_passport_issued_by',
                    'sender_phone',
                    'sender_birthdate',
                    'sender_address',
                    'sender_city',
                    'sender_postindex',
                ],
                'option' => [
                    'card' => 'Номер карты',
                    'sender_first_name' => 'Имя',
                    'sender_last_name' => 'Фамилия',
                    'sender_middle_name' => 'Отчество',
                    'sender_passport_number' => 'Серия и номер паспорта',
                    'sender_passport_issue_date' => 'Дата выдачи паспорта',
                    'sender_passport_issued_by' => 'Орган, выдавший паспорт',
                    'sender_phone' => 'Контактный телефон',
                    'sender_birthdate' => 'Дата рождения',
                    'sender_address' => 'Адрес',
                    'sender_city' => 'Город',
                    'sender_postindex' => 'Почтовый индекс',
                ],
                'option_validation_js' => [
                    'card' => [
                        'type' => 'text',
                        'required' => true,
                        'minlength' => 13,
                        'maxlength' => 19,
                        'pattern' => '^\d+$',
                    ],
                    'sender_first_name' => [
                        'type' => 'text',
                        'required' => true,
                        'minlength' => 2,
                        'maxlength' => 256,
                        'pattern' => '^[a-zA-Zа-яА-Я\s\.\-]+$',
                    ],
                    'sender_last_name' => [
                        'type' => 'text',
                        'required' => true,
                        'minlength' => 2,
                        'maxlength' => 256,
                        'pattern' => '^[a-zA-Zа-яА-Я\s\.\-]+$',
                    ],
                    'sender_middle_name' => [
                        'type' => 'text',
                        'required' => true,
                        'minlength' => 2,
                        'maxlength' => 256,
                        'pattern' => '^[a-zA-Zа-яА-Я\s\.\-]+$',
                    ],
                    'sender_passport_number' => [ // only Russian
                        'type' => 'text',
                        'required' => true,
                        'minlength' => 8,
                        'maxlength' => 10,
                        'pattern' => '^\d+$',
                        'pattern' => '^[\wа-яА-Я]+$',
                    ],
                    'sender_passport_issue_date' => [
                        'type' => 'date',
                        'required' => true,
                        // 'pattern'   => '^\d{4}\-\d{1,2}\-\d{1,2}$',
                    ],
                    'sender_passport_issued_by' => [
                        'type' => 'text',
                        'required' => true,
                        'minlength' => 2,
                        'maxlength' => 256,
                        'pattern' => '^[\wа-яА-Я\s\,\.\-\\/\#№]+$',
                    ],
                    'sender_phone' => [ // only Russian
                        'type' => 'text',
                        'required' => true,
                        'minlength' => 11,
                        'maxlength' => 15,
                        'pattern' => '^\d+$',
                    ],
                    'sender_birthdate' => [
                        'type' => 'date',
                        'required' => true,
                        // 'pattern'   => '^\d{4}\-\d{1,2}\-\d{1,2}$',
                    ],
                    'sender_address' => [
                        'type' => 'text',
                        'required' => true,
                        'minlength' => 2,
                        'maxlength' => 256,
                        'pattern' => '^[\wа-яА-Я\s\,\.\-\\/\#№]+$',
                    ],
                    'sender_city' => [
                        'type' => 'text',
                        'required' => true,
                        'minlength' => 2,
                        'maxlength' => 256,
                        'pattern' => '^[a-zA-Zа-яА-Я\s\-\.]+$',
                    ],
                    'sender_postindex' => [
                        'type' => 'text',
                        'required' => true,
                        'minlength' => 5,
                        'maxlength' => 6,
                        'pattern' => '^[\d]+$',
                    ],
                ],
                'option_validation' => [
                    'card' => 'required|is_natural|length[13,19]',
                    'sender_first_name' => 'required|regex:~^[\pL\pM\s\.\-]+$~u|length[2,256]',
                    'sender_last_name' => 'required|regex:~^[\pL\pM\s\.\-]+$~u|length[2,256]',
                    'sender_middle_name' => 'required|regex:~^[\pL\pM\s\.\-]+$~u|length[2,256]',
                    'sender_passport_number' => 'required|regex:~^[\pL\pM\pN]+$~u|length[8,10]',
                    'sender_passport_issue_date' => 'required|valid_date_format[Y-m-d]',
                    'sender_passport_issued_by' => 'required|regex:~^[\pL\pM\pN\s\,\.\-\\/\#№]+$~u|length[2,256]',
                    'sender_phone' => 'required|is_natural|length[11,15]',
                    'sender_birthdate' => 'required|valid_date_format[Y-m-d]',
                    'sender_address' => 'required|regex:~^[\pL\pM\pN\s\,\.\-\\/\#]+$~u|length[2,256]',
                    'sender_city' => 'required|regex:~^[\pL\pM\s\-\.]+$~u|length[2,256]',
                    'sender_postindex' => 'required|is_natural|length[5,6]',
                ],
                'option_validation_message' => [
                    'card' => 'обязательное поле от 13 до 19 цифр',
                    'sender_first_name' => 'обязательное поле от 2 символов',
                    'sender_last_name' => 'обязательное поле от 2 символов',
                    'sender_middle_name' => 'обязательное поле от 2 символов',
                    'sender_passport_number' => 'обязательное поле от 8 до 10 символов',
                    'sender_passport_issue_date' => 'обязательное поле дата "год-месяц-число" (пример: 1999-01-22)',
                    'sender_passport_issued_by' => 'обязательное поле от 2 символов',
                    'sender_phone' => 'обязательное поле от 11 цифр, без "+"',
                    'sender_birthdate' => 'обязательное поле дата "год-месяц-число" (пример: 1977-01-22)',
                    'sender_address' => 'обязательное поле от 2 символов',
                    'sender_city' => 'обязательное поле от 2 символов',
                    'sender_postindex' => 'обязательное поле от 5 цифр',
                ],
            ],
            'qiwi' => [
                'title' => 'Qiwi',
                'icon' => 'qiwi',
                'uri' => [
                    '%method' => 'qiwi',
                ],
                'action' => 'qiwi_payout',
                'amount' => [
                    // 'min' => 10,
                    'max' => 150,
                ],
                // 'fee'        => 0, // 0.1%
                'currency' => [
                    'USD' => [
                        'currency_id' => 'USD',
                        'is_int' => true,
                        'active' => true,
                    ],
                ],
                'field' => [
                    'action',
                    'site_id',
                    'amount',
                    'currency',
                    'external_id',
                    // 'customer_ip',
                    'comment',
                    'account_number',
                ],
                'order' => [
                    'account_number',
                ],
                'option' => [
                    'account_number' => 'Кошелек',
                ],
                'option_validation_js' => [
                    'account_number' => [
                        'type' => 'text',
                        'required' => true,
                        'minlength' => 11,
                        'maxlength' => 14,
                        'pattern' => '^(?!8)\d+$',
                    ],
                ],
                'option_validation' => [
                    'account_number' => 'required|is_natural|length[11,14]',
                ],
                'option_validation_message' => [
                    'account_number' => 'обязательное поле от 11 до 14 цифр, без "+"',
                ],
            ],
            'webmoney' => [
                'title' => 'WebMoney',
                'icon' => 'webmoney',
                'uri' => [
                    '%method' => 'webmoney',
                ],
                'action' => 'wmpayout',
                'amount' => [
                    // 'min' => 10,
                    'max' => 150,
                ],
                // 'fee'        => 0, // 0.1%
                'currency' => [
                    'USD' => [
                        'currency_id' => 'USD',
                        'is_int' => true,
                        'active' => true,
                    ],
                ],
                'field' => [
                    'action',
                    'site_id',
                    'amount',
                    'currency',
                    'external_id',
                    'comment',
                    'customer_purse',
                ],
                'order' => [
                    'customer_purse',
                ],
                'option' => [
                    'customer_purse' => 'Кошелек',
                ],
                'option_validation_js' => [
                    'customer_purse' => [
                        'type' => 'text',
                        'required' => true,
                        'minlength' => 13,
                        'maxlength' => 13,
                        'pattern' => '^[ZR]\d{12}$',
                    ],
                ],
                'option_validation' => [
                    'customer_purse' => 'required|length[13]|regex:~^[ZR]\d{12}$~',
                ],
                'option_validation_message' => [
                    'customer_purse' => 'обязательное поле: буква и 12 цифр',
                ],
            ],
        ],
    ];

    public $_api_transform = [
        // '+' - required
        // '-' - not required
        // '?' - may be by conditions
        // 'amount'                 => 'amount',                     // + Numeric Сумма к выплате в валюте сайта
        'currency' => 'currency',                   // - Enum Валюта
        'operation_id' => 'external_id',                // + String Идентификатор заказа в системе продавца
        'transaction_id' => 'transaction_id',             // ? Numeric ID успешного платежа по той банковской карте, на которую нужно сделать выплату.
        'force_disable_callback' => 'force_disable_callback',     // - Enum Отключить оповещения (callback) об операции. Допустимые значения - 1 (да, отключить), 0 (нет, высылать оповещения)
        'first_callback_delay' => 'first_callback_delay',       // - Numeric Задержка перед отправкой первого оповещения
        'account' => 'card',                       // ? Numeric(13,19) Номер банковской карт, на которую совершается выплата.
        'title' => 'comment',                    // + String(4096) Комментарий к запросу example_comment
        'first_name' => 'sender_first_name',          // + String(255) Имя пользователя
        'last_name' => 'sender_last_name',           // + String(255) Фамилия пользователя
        'middle_name' => 'sender_middle_name',         // + String(255) Отчество пользователя
        'passport_number' => 'sender_passport_number',     // + String(255) Серия и номер паспорта пользователя
        'passport_issue_date' => 'sender_passport_issue_date', // + String(255) Дата выдачи паспорта: 2002-01-31
        'passport_issued_by' => 'sender_passport_issued_by',  // + String(255) Орган, выдавший паспорт
        'phone' => 'sender_phone',               // + String(11) Контактный телефон пользователя.
        'birthdate' => 'sender_birthdate',           // + Date Дата рождения пользователя: 1980-01-31
        'address' => 'sender_address',             // + String(255) Адрес пользователя
        'city' => 'sender_city',                // ? String(255) Город пользователя.
        'postindex' => 'sender_postindex',           // ? String(255) Почтовый индекс пользователя.
    ];

    public $_api_transform_reverse = [
        'external_id' => 'operation_id',
        'code' => 'state',
        'card' => 'account',
    ];

    public $_options_transform = [
        'title' => 'description',
        'operation_id' => 'external_id',
        'public_key' => 'site_id',
        'key_public' => 'site_id',
        'test' => 'test_mode',
    ];

    public $_options_transform_reverse = [
        'description' => 'title',
        'external_id' => 'operation_id',
        'site_id' => 'key_public',
    ];

    public $_status_response = [
        '1' => 'success',
        '2' => 'refused',
    ];
    public $_status_server = [
        '1' => 'processing', // initiated
        '2' => 'processing', // external processing
        '3' => 'processing', // awaiting confirmation
        '4' => 'success',    // success
        '5' => 'refused',    // void
        '6' => 'refused',    // processor decline
        '7' => 'refused',    // fraudstop decline
        '8' => 'refused',    // mpi decline
        '9' => 'refused',
        '10' => 'refused',    // system failure
        '11' => 'refused',    // unsupported protocol operation
        '12' => 'refused',    // protocol configuration error
        '13' => 'expired',    // transaction is expired
        '14' => 'refused',    // transaction rejected by user
        '15' => 'refused',    // internal decline
    ];

    public $_type_server = [
        // deposition         payout
        // 1 (authorization)  4  (void)
        // 3 (purchase)       5  (refund)
        // 6 (rebill)         11 (payout)
        '1' => 'deposition',     // authorization Авторизация
        // '2'  => 'payment', // confirm Подтверждение авторизации
        '3' => 'deposition',     // purchase Прямое списание
        '4' => 'payment', // void Отмена авторизации
        '5' => 'payment', // refund Возврат
        '6' => 'deposition',     // rebill Рекуррентный платеж
        // '7'  => 'payment', // chargeback Опротестование платежа
        // '8'  => 'payment', // complete3ds Завершение платежа 3ds
        // '9'  => 'payment',
        // '10' => 'payment',
        '11' => 'payment', // payout Выплата
    ];

    public $currency_default = 'USD';
    public $currency_allow = [
        'USD' => [
            'currency_id' => 'USD',
            'active' => true,
        ],
        /* 'EUR' => array(
            'currency_id' => 'EUR',
            'active'      => true,
        ),
        'UAH' => array(
            'currency_id' => 'UAH',
            'active'      => true,
        ),
        'RUB' => array(
            'currency_id' => 'RUB',
            'active'      => true,
        ), */
    ];

    // public $fee = 5; // 5%

    public $service_allow = [
        'Visa, MasterCard',
    ];

    public $ip_filter = [
        '78.140.183.154' => true,
        '204.26.61.98' => true,
    ];

    public $url_result = null;
    public $url_server = null;

    public function _init()
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        // load api
        require_once __DIR__ . '/payment_provider/ecommpay/EcommPay.php';
        $this->api = new EcommPay($this->KEY_PUBLIC, $this->KEY_PRIVATE);
        $this->url_result = url_user('/api/payment/provider?name=ecommpay&operation=response');
        $this->url_server = url_user('/api/payment/provider?name=ecommpay&operation=response&server=true');
        // translation
        $strs = &$this->method_allow['payout']['pay_card']['option'];
        foreach ($strs as $key => &$str) {
            $str = t($str);
        }
        // payout default option
        $user_id = main()->USER_ID;
        if ($user_id) {
            $user = user($user_id);
            $option_default = &$this->method_allow['payout']['pay_card']['option_default'];
            $option_default = [
                'card' => $user['card'],
                'sender_first_name' => $user['first_name'],
                'sender_last_name' => $user['last_name'],
                'sender_middle_name' => $user['middle_name'] ?: $user['patronymic'],
                'sender_passport_number' => $user['passport_num'],
                'sender_passport_issue_date' => $user['passport_issue_date'],
                'sender_passport_issued_by' => $user['passport_issued_by'] ?: $user['passport_released'],
                'sender_phone' => @str_replace([' ', '-', '+'], '', $user['phone']),
                'sender_birthdate' => $user['birthdate'] ?: $user['birth_date'],
                'sender_address' => $user['address'] ?: $user['address2'],
                'sender_city' => $user['city'] ?: $user['city2'],
                'sender_postindex' => $user['zip_code'] ?: $user['zip_code2'],
            ];
            $option_default = &$this->method_allow['payout']['qiwi']['option_default'];
            $option_default = [
                'account_number' => @str_replace([' ', '-', '+'], '', $user['phone']),
            ];
        }


        if (count((array) $this->ALLOW_CARDS_COUNTRIES)) {
            $countries = [];
            foreach ($this->ALLOW_CARDS_COUNTRIES as $country) {
                $countries[] = t($country);
            }
            $countries_validation_message = 'карта должна быть выпущена в ' . implode(', ', $countries);
            $this->method_allow['payout']['pay_card']['option_validation_message']['card'] .= ', ' . $countries_validation_message;
            $this->method_allow['payout']['pay_card']['option_validation']['card'] = [
                '__before__' => 'required|is_natural|length[13,19]',
                '__after__' => function ($in, $tmp1 = null, $tmp2 = null, &$error_msg = null) use ($countries_validation_message) {
                    $bin = substr($in, 0, 6);
                    $url = $this->URL_BIN_INFO . $bin;
                    $request_result = common()->get_remote_page($url);

                    $request_data = ! empty($request_result) ? @json_decode($request_result, true) : false;
                    if ($request_data && isset($request_data['country_code'])) {
                        if (isset($this->ALLOW_CARDS_COUNTRIES[$request_data['country_code']])) {
                            return true;
                        }

                        $error_msg = $countries_validation_message;
                        return false;
                    }
                    return true;
                }, ];
        }

        // parent
        parent::_init();
    }

    public function key($name = 'public', $value = null)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $value = $this->api->key($name, $value);
        return  $value;
    }

    public function key_reset()
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $this->key('public', $this->KEY_PUBLIC);
        $this->key('private', $this->KEY_PRIVATE);
    }

    public function signature($options, $request = true)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $result = $this->api->signature($options, $request);
        return  $result;
    }

    public function _amount($amount, $currency, $is_request = true)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $currency_id = $this->get_currency([
            'currency_id' => $currency,
        ]);
        if ($currency_id != $currency) {
            return  null;
        }
        $payment_api = $this->payment_api;
        list($_currency_id, $currency) = $payment_api->get_currency__by_id([
            'currency_id' => $currency_id,
        ]);
        if (empty($_currency_id)) {
            return  null;
        }
        $units = pow(10, $currency['minor_units']);
        if ($is_request) {
            $result = (int) ($amount * $units);
        } else {
            $result = (float) $amount / $units;
        }
        return  $result;
    }

    public function _form_options($options)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $_ = $options;
        // transform
        foreach ((array) $this->_options_transform as $from => $to) {
            if (isset($_[$from]) && $from != $to) {
                $_[$to] = $_[$from];
                unset($_[$from]);
            }
        }
        // url
        if ( ! empty($_['url_result'])
            || empty($_['success_url'])
        ) {
            $url = $this->_url($options);
            $_['success_url'] = $url . '&status=success';
            $_['callback_method'] = 4;
            unset($_['url_result']);
        }
        if ( ! empty($_['url_result'])
            || empty($_['decline_url'])
        ) {
            $url = $this->_url($options);
            $_['decline_url'] = $url . '&status=fail';
            $_['callback_method'] = 4;
            unset($_['url_result']);
        }
        unset($_['url_server']);
        // default
        empty($_['language']) && $_['language'] = 'ru';
        // amount
        $_['amount'] = $this->_amount($_['amount'], $_['currency'], $is_request = true);
        // site id
        empty($_['site_id']) && $_['site_id'] = $this->KEY_PUBLIC;
        if (empty($_['amount']) || empty($_['site_id'])) {
            $_ = null;
        }
        return  $_;
    }

    public function _url($options, $is_server = false)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if ($is_server) {
            $url = $_url_server ?: $this->url_server;
        } else {
            $url = $_url_result ?: $this->url_result;
        }
        $result = $url . '&operation_id=' . $_operation_id;
        return  $result;
    }

    public function _form($data, $options = null)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $_ = &$options;
        // START DUMP
        $payment_api = $this->payment_api;
        $payment_api->dump(['name' => 'EcommPay', 'operation_id' => @(int) $_['data']['operation_id']]);
        if (empty($data)) {
            return  null;
        }
        $is_array = (bool) $_['is_array'];
        $form_options = $this->_form_options($data);
        // DUMP
        $payment_api->dump(['var' => $form_options]);
        if (empty($form_options)) {
            return  null;
        }
        $signature = $this->api->signature($form_options);
        if (empty($signature)) {
            return  null;
        }
        $form_options['signature'] = $signature;
        if ( ! empty($this->TEST_MODE) || ! empty($_['test_mode'])) {
            $url = &$this->URL_TEST;
        } else {
            $url = &$this->URL;
        }
        $result = [];
        if ($is_array) {
            $result['url'] = $url;
        } else {
            $result[] = '<form id="_js_provider_ecommpay_form" method="post" accept-charset="utf-8" action="' . $url . '" class="display: none;">';
        }
        foreach ((array) $form_options as $key => $value) {
            if ($is_array) {
                $result['data'][$key] = $value;
            } else {
                $result[] = sprintf('<input type="hidden" name="%s" value="%s" />', $key, $value);
            }
        }
        if ( ! $is_array) {
            $result[] = '</form>';
            $result = implode(PHP_EOL, $result);
        }
        return  $result;
    }

    public function _api_response()
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $payment_api = $this->payment_api;
        $test_mode = &$this->TEST_MODE;
        $is_server = ! empty($_GET['server']);
        $result = null;
        // check operation
        $operation_id = @(int) $_GET['operation_id'];
        // START DUMP
        $payment_api->dump(['name' => 'EcommPay', 'operation_id' => $operation_id]);
        // TEST DATA
        /*
        $_POST = array (
            // notification url
            'type_id'               => '3',
            'status_id'             => '4',
            'transaction_id'        => '36876',
            'external_id'           => '4488',
            'acquirer_id'           => '5522470251c72',
            'payment_type_id'       => '2',
            'site_id'               => '2415',
            'amount'                => '1100',
            'currency'              => 'USD',
            'real_amount'           => '1100',
            'real_currency'         => 'USD',
            'extended_info_enabled' => '1',
            'customer_purse'        => '555555...4444',
            'completed_at'          => '2015-04-06T08:42:42+00:00',
            'processor_date'        => '2015-04-06T08:42:42+00:00',
            'source_type'           => '2',
            'holder_name'           => 'AA AA',
            'expiry_date'           => '11/16',
            'authcode'              => '3O0X9R',
            'recurring_allowed'     => '0',
            'recurring_valid_thru'  => '',
            'processor_id'          => '1',
            'processor_code'        => '00',
            'processor_message'     => 'SUCCESS',
            'signature'             => '4d6d96a20e8e0864a464703ec33b399d0ab4c176',
            // success_url
            'site_id'         => '2415',
            'payment_type_id' => '2',
            'transaction_id'  => '36886',
            'external_id'     => '4497',
            'description'     => 'Пополнение счета',
            'amount'          => '200',
            'currency'        => 'USD',
            'real_amount'     => '200',
            'real_currency'   => 'USD',
            'language'        => 'ru',
            'sign'            => 'e21f3eb88ea9de9b10a8d9371e32757d4e31a6cc',
            'signature'       => 'a4565dac9ae8333a9da92ac514e5053fcc07d14f',
            'type'            => '1',
        ); // */
        // response
        $response = $_POST;
        // check signature
        isset($response['signature']) && $signature = $response['signature'];
        // check signature
        if (empty($signature)) {
            $result = [
                'status' => false,
                'status_message' => 'Пустая подпись',
            ];
            // DUMP
            $payment_api->dump(['var' => $result]);
            return  $result;
        }
        $signature_options = $response;
        $_signature = $this->signature($signature_options);
        // DEBUG
        // var_dump( $response, $signature, $signature_options, 'calc: ', $_signature );
        // exit;
        if ($signature != $_signature) {
            $result = [
                'status' => false,
                'status_message' => 'Неверная подпись',
            ];
            // DUMP
            $payment_api->dump(['var' => $result]);
            return  $result;
        }
        // user success or fail
        if ( ! $is_server) {
            // check status
            $state = isset($response['type']) && $response['type'] == '1' ? true : false;
            $status = isset($_GET['status'])
                && $_GET['status'] == 'success'
                && $state
                ? true : false;
            $status_message = $status ? 'Операция выполнена успешно' : 'Операция не выполнена';
            $result = [
                'status' => $status,
                'status_message' => $status_message,
            ];
            return  $result;
        }
        // server notification
        // check ip
        $ip_allow = $this->_check_ip();
        if ($ip_allow === false) {
            // DUMP
            $payment_api->dump(['var' => 'ip not allow']);
            return  null;
        }
        // update operation
        $_response = $this->_response_parse($response);
        // check public key (site_id)
        $key_public = $_response['key_public'];
        $_key_public = $this->key('public');
        if ($key_public != $_key_public) {
            $result = [
                'status' => false,
                'status_message' => 'Неверный ключ (site_id)',
            ];
            // DUMP
            $payment_api->dump(['var' => $result]);
            return  $result;
        }
        // check operation_id
        if ($operation_id && $operation_id != $_response['operation_id']) {
            $result = [
                'status' => false,
                'status_message' => 'Не соответствует код операции',
            ];
            // DUMP
            $payment_api->dump(['var' => $result]);
            return  $result;
        }
        // START NEW DUMP
        $operation_id = @(int) $_response['operation_id'];
        $payment_api->dump(['is_new' => true, 'name' => 'EcommPay', 'operation_id' => $operation_id]);
        // deposition or payout
        $state = $_response['type_id'];
        $status = $this->_type_server;
        list($payment_type) = $this->_state($state, $status);
        if (empty($payment_type)) {
            // DUMP
            $payment_api->dump(['var' => 'type: ' . $state]);
            return  null;
        }
        // check status
        $state = $_response['status_id'];
        $status = $this->_status_server;
        list($status_name, $status_message) = $this->_state($state, $status);
        // amount
        // $_response[ 'amount' ] = $this->_amount( $_response[ 'amount' ], $_response[ 'currency' ], $is_request = false );
        // update account, operation data
        $operation_data = [
            'operation_id' => $operation_id,
            'provider_name' => 'ecommpay',
            'state' => $state,
            'status_name' => $status_name,
            'status_message' => $status_message,
            'payment_type' => $payment_type,
            'response' => $_response,
        ];
        // DUMP
        $payment_api->dump(['var' => ['payment_type' => $payment_type, 'update operation' => $operation_data]]);
        $result = $this->{ '_api_' . $payment_type }($operation_data);
        // DUMP
        $payment_api->dump(['var' => ['update result' => $result]]);
        return  $result;
    }

    public function _response_parse($response)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $_ = $response;
        // transform
        foreach ((array) $this->_options_transform_reverse as $from => $to) {
            if (isset($_[$from])) {
                $_[$to] = $_[$from];
                unset($_[$from]);
            }
        }
        return  $_;
    }

    public function api_payout($options = null)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // method
        $method = $this->api_method([
            'type' => 'payout',
            'method_id' => $_method_id,
        ]);
        if (empty($method)) {
            $result = [
                'status' => false,
                'status_message' => 'Метод запроса не найден',
            ];
            return  $result;
        }
        $payment_api = &$this->payment_api;
        // operation_id
        $_operation_id = (int) $_operation_id;
        $operation_id = $_operation_id;
        if (empty($_operation_id)) {
            $result = [
                'status' => false,
                'status_message' => 'Не определен код операции',
            ];
            return  $result;
        }
        // currency_id
        $currency_id = $this->get_currency_payout($options);
        if (empty($currency_id)) {
            $result = [
                'status' => false,
                'status_message' => 'Неизвестная валюта',
            ];
            return  $result;
        }
        // amount min/max
        $result = $this->amount_limit([
            'amount' => $_amount,
            'currency_id' => $currency_id,
            'method' => $method,
        ]);
        if ( ! @$result['status']) {
            return  $result;
        }
        // currency conversion
        $amount_currency = $payment_api->currency_conversion([
            'type' => 'sell',
            'currency_id' => $currency_id,
            'amount' => $_amount,
        ]);
        if (empty($amount_currency)) {
            $result = [
                'status' => false,
                'status_message' => 'Невозможно произвести конвертацию валют',
            ];
            return  $result;
        }
        // fee
        $fee = $this->get_fee_payout($options);
        $amount_currency_total = $payment_api->fee($amount_currency, $fee);
        // transform
        foreach ($this->_api_transform as $from => $to) {
            $_from = '_' . $from;
            $_to = '_' . $to;
            $f = &${ $_from };
            $t = &${ $_to   };
            if (isset($f) && $_from != $_to) {
                $t = $f;
                unset(${ $_from });
            }
        }
        // default
        // $_currency = $currency_id;
        // $_amount = $this->_amount_payout( $amount_currency_total, $_currency, $is_request = true );
        $_amount = $this->_amount_payout($_amount, $_currency_id, $method, $is_request = true);
        ! isset($_site_id) && $_site_id = $this->key('public');
        ! isset($_comment) && $_comment = t('Вывод средств (id: ' . $_external_id . ')');
        ! isset($_action) && $_action = $method['action'];
        is_string($_sender_phone) && $_sender_phone = str_replace([' ', '-', '+'], '', $_sender_phone);
        // check required
        $request = [];
        foreach ($method['field'] as $key) {
            $value = @${ '_' . $key };
            if ( ! isset($value)) {
                $result = [
                    'status' => false,
                    'status_message' => 'Отсутствуют данные запроса: ' . $key,
                ];
                continue;
                // return( $result );
            }
            $request[$key] = &${ '_' . $key };
        }
        // signature
        $signature = $this->api->signature($request);
        if (empty($signature)) {
            $result = [
                'status' => false,
                'status_message' => 'Ошибка генерации подписи',
            ];
            return  $result;
        }
        $request['signature'] = $signature;
        // DEBUG
        // var_dump( $request );
        // START DUMP
        $payment_api->dump(['name' => 'EcommPay', 'operation_id' => $operation_id,
            'var' => ['request' => $request],
        ]);
        // url
        $object = $this->api_url($method, $options);
        if (is_array($object) && $object['status'] === false) {
            return  $object;
        }
        $url = $object;
        // request
        $request_options = [];
        @$_is_debug && $request_options['is_debug'] = true;
        $result = $this->_api_request($url, $request, $request_options);
        // DUMP
        $payment_api->dump(['var' => ['response' => $result]]);
        if (empty($result)) {
            $result = [
                'status' => false,
                'status_message' => 'Невозможно отправить запрос',
            ];
            return  $result;
        }
        list($status, $response) = $result;
        // DEBUG
        /*
        $response = '
            {
            "code"              : 0,
            "message"           : "Success.",
            "acquirer_id"       : "552e1df177a9e",
            "transaction_id"    : "42169",
            "processor_id"      : "1",
            "processor_code"    : "00",
            "processor_message" : "SUCCESS",
            "amount"            : "89",
            "currency"          : "RUB",
            "real_amount"       : "89",
            "real_currency"     : "RUB",
            "external_id"       : "24563",
            "authcode"          : "6Y8A0C"
            }
        '; //*/
        // $response = json_decode( $response, true );
        // DEBUG
        // var_dump( $result, $response );
        if ($response === null) {
            $result = [
                'status' => false,
                'status_message' => 'Невозможно декодировать ответ: ' . var_export($response, true),
            ];
            return  $result;
        }
        // transform reverse
        foreach ($this->_api_transform_reverse as $from => $to) {
            if ($from != $to && isset($response[$from])) {
                $response[$to] = $response[$from];
                unset($response[$from]);
            }
        }
        // result
        $result = [
            'status' => &$status,
            'status_message' => &$status_message,
        ];
        $status = 'in_progress';
        $status_message = null;
        $state = (int) $response['state'];
        switch ($state) {
            // success
            case 0:
                if ($response['amount'] == $_amount
                    && $response['operation_id'] == $operation_id
                ) {
                    $status = 'success';
                    $status_message = 'Выполнено';
                } else {
                    $status = 'processing';
                    $status_message = 'Выполнено, но сумма или код операции не совпадают';
                }
                break;
            // processing
            case 50:
                $status = 'processing';
                $status_message = 'В процессе';
                break;
            // fails...
            case 2:
                $status_message = 'Доступ запрещен';
                break;
            case 101:
                $status_message = 'Неверный номер карты ' . $request['card'];
                break;
            case 121:
                $status_message = 'Обработка заказа невозможна с данным id: ' . $operation_id;
                break;
            case 126:
                $status_message = 'Неверный номер телефона ' . $request['sender_phone'];
                break;
            case 421:
                $status_message = 'Неверные данные запроса';
                break;
            case 113:
                $status_message = 'Выплата отключена';
                break;
            case 908:
                $status_message = 'Выплата уже произведена ранее';
                break;
            default:
                $status_message = 'Ошибка: ' . $response['message'] . ' (' . $state . ')';
                break;
        }
        @$status_message && $response['message'] = $status_message;
        // save response
        $sql_datetime = $payment_api->sql_datetime();
        $provider_name = 'ecommpay';
        $operation_options = [
            'processing' => [[
                'provider_name' => $provider_name,
                'datetime' => $sql_datetime,
            ]],
            'response' => [[
                'datetime' => $sql_datetime,
                'provider_name' => $provider_name,
                'state' => $state,
                'status_name' => $status,
                'status_message' => $status_message,
                'data' => $response,
            ]],
        ];
        $operation_update_data = [
            'operation_id' => $operation_id,
            'datetime_update' => $sql_datetime,
            'options' => $operation_options,
        ];
        $payment_api->operation_update($operation_update_data);
        return  $result;
    }
}
