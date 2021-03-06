<?php

_class('payment_api__provider_remote');

class yf_payment_api__provider_privat24 extends yf_payment_api__provider_remote
{
    public $URL = 'https://api.privatbank.ua/p24api/';
    public $KEY_PUBLIC = null; // merchant
    public $KEY_PRIVATE = null; // pass

    public $IS_DEPOSITION = true;
    public $IS_PAYMENT = true;

    public $_api_request_timeout = 30;  // sec
    public $method_allow = [
        'order' => [
            'payin' => [
                'private',
            ],
            'payout' => [
                'pay_pb',
                'pay_visa',
            ],
        ],
        'payin' => [
            'private' => [
                'title' => 'Приват Банк',
                'icon' => 'privat24',
                'fee' => 0, // 0.1%
                'currency' => [
                    'UAH' => [
                        'currency_id' => 'UAH',
                        'active' => true,
                    ],
                ],
            ],
        ],
        'payout' => [
            'pay_pb' => [
                'title' => 'Приват24',
                'icon' => 'privat24',
                'amount_min' => 100,
                'field' => [
                    'b_card_or_acc',
                    'amt',
                    'ccy',
                    'details',
                ],
                'order' => [
                    'account',
                ],
                'option' => [
                    'account' => 'Счет',
                ],
            ],
            'pay_visa' => [
                'title' => 'Visa',
                'icon' => 'visa',
                'amount_min' => 100,
                'field' => [
                    'b_name',
                    'b_card_or_acc',
                    'amt',
                    'ccy',
                    'details',
                ],
                'order' => [
                    'name',
                    'account',
                ],
                'option' => [
                    'name' => 'ФИО получателя',
                    'account' => 'Счет',
                ],
            ],
        ],
    ];

    public $_xml_transform = [
        'amount' => 'amt',
        'currency' => 'ccy',
        'title' => 'details',
        'operation_id' => 'payment_id',
        'account' => 'b_card_or_acc',
        'name' => 'b_name',
    ];

    public $_options_transform = [
        'amount' => 'amt',
        'currency' => 'ccy',
        'title' => 'details',
        'description' => 'ext_details',
        'order_id' => 'order',
        'operation_id' => 'order',
        'url_result' => 'return_url',
        'url_server' => 'server_url',
        'public_key' => 'merchant',
        'key_public' => 'merchant',
    ];

    public $_options_transform_reverse = [
        'amt' => 'amount',
        'ccy' => 'currency',
        'details' => 'title',
        'ext_details' => 'description',
        'order' => 'operation_id',
        'merchant' => 'public_key',
    ];

    public $_status = [
        'ok' => 'success',
        'wait' => 'in_progress',
        'fail' => 'refused',
    ];

    public $currency_default = 'UAH';
    public $currency_allow = [
/*
        'USD' => array(
            'currency_id' => 'USD',
            'active'      => true,
        ),
        'EUR' => array(
            'currency_id' => 'EUR',
            'active'      => true,
        ),
 */
        'UAH' => [
            'currency_id' => 'UAH',
            'active' => true,
        ],
    ];

    public $fee = 2; // 2%

    public $service_allow = [
        'Приват24',
    ];

    public $url_result = null;
    public $url_server = null;

    public function _init()
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $this->payment_api = _class('payment_api');
        // load api
        require_once __DIR__ . '/payment_provider/privat24/Privat24.php';
        $this->api = new Privat24($this->KEY_PUBLIC, $this->KEY_PRIVATE);
        $this->url_result = url_user('/api/payment/provider?name=privat24&operation=response');
        $this->url_server = url_user('/api/payment/provider?name=privat24&operation=response&server=true');
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

    public function signature($options, $is_request = true)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $result = $this->api->signature($options, $is_request);
        return  $result;
    }

    public function _api_request($uri, $data)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $url = $this->URL . $uri;
        $result = $this->_api_post($url, $data);
        return  $result;
    }

    public function api_payout($method, $options)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $method = $this->api_method([
            'type' => 'payout',
            'method_id' => $method,
        ]);
        if ( ! is_array($method)) {
            return  null;
        }
        $payment_api = &$this->payment_api;
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // transform
        foreach ($this->_xml_transform as $from => $to) {
            $f = &${ '_' . $from };
            $t = &${ '_' . $to   };
            if (isset($f)) {
                $t = $f;
                unset(${ '_' . $from });
            }
        }
        // default
        $_amt = number_format($_amt, 2, '.', '');
        $_ccy = $payment_api->_default([$_ccy, $this->currency_default]);
        $_wait = $_wait ?: $this->_api_request_timeout;
        $_test = $payment_api->_default([$_test, $this->TEST_MODE]);
        // $_test = (int)$_test;
        foreach ($method['field'] as $name) {
            $value = &${ '_' . $name };
            if ( ! isset($value)) {
                $result = [
                    'status' => false,
                    'status_message' => 'Отсутствуют данные запроса',
                ];
                return  $result;
            }
        }
        // build xml
        $xml_request = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<request version="1.0">'
            . '</request>'
        );
        $xml_merchant = $xml_request->addChild('merchant');
        $xml_data = $xml_request->addChild('data');
        // oper, wait, test
        $xml_data->addChild('oper', 'cmt');
        $xml_data->addChild('wait', $_wait);
        $xml_data->addChild('test', $_test);
        // payment
        $xml_payment = $xml_data->addChild('payment');
        // payment id
        isset($_payment_id) && $xml_payment->addAttribute('id', $_payment_id);
        // data
        $data = '';
        foreach ($method['field'] as $name) {
            $value = ${ '_' . $name };
            $value = htmlentities($value, ENT_COMPAT | ENT_XML1, 'UTF-8', $double_encode = false);
            $prop = $xml_payment->addChild('prop');
            $prop->addAttribute('name', $name);
            $prop->addAttribute('value', $value);
        }
        // signature
        $key_public = $this->KEY_PUBLIC;
        $data = '';
        foreach ($xml_data->children() as $_xml) {
            $data .= $_xml->asXML();
        }
        $signature = $this->api->str_to_sign($data);
        // merchant
        $xml_merchant->addChild('id', $key_public);
        $xml_merchant->addChild('signature', $signature);
        // request
        $data = $xml_request->asXML();
        $result = $this->_api_request($method, $data);
        list($status, $response) = $result;
        if ( ! $status) {
            return  $result;
        }
        libxml_use_internal_errors(true);
        $xml_response = simplexml_load_string($response);
        // debug
        // ini_set( 'html_errors', 0 );
        // var_dump( $response, $xml_response );
        // error?
        $error = libxml_get_errors();
        if ($error) {
            libxml_clear_errors();
            $result = [
                'status' => null,
                'status_message' => 'Ошибка ответа: неверная структура данных',
            ];
            return  $result;
        }
        if ($xml_response->getName() == 'error') {
            $result = [
                'status' => null,
                'status_message' => 'Ошибка ответа: неверные данные - ' . (string) $xml_response,
            ];
            return  $result;
        }
        // ----- check response
        // key public - merchant
        $value = $key_public;
        $r_value = (string) $xml_response->merchant->id;
        if ($value != $r_value) {
            $result = [
                'status' => null,
                'status_message' => 'Ошибка ответа: неверный публичный ключ (merchant)',
            ];
            return  $result;
        }
        // signature
        $data = '';
        foreach ($xml_response->data->children() as $_xml) {
            $data .= $_xml->asXML();
        }
        $value = $this->api->str_to_sign($data);
        $r_value = (string) $xml_response->merchant->signature;
        if ($value != $r_value) {
            $result = [
                'status' => null,
                'status_message' => 'Ошибка ответа: неверный подпись (signature)',
            ];
            return  $result;
        }
        // payment
        $xml_response_payment = $xml_response->data->payment->attributes();
        // id
        $value = $_payment_id;
        $r_value = (string) $xml_response_payment->id;
        if ($value != $r_value) {
            $result = [
                'status' => null,
                'status_message' => 'Ошибка ответа: неверный номер операции (operation_id)',
            ];
            return  $result;
        }
        // amt
        $value = $_amt;
        $r_value = (string) $xml_response_payment->amt;
        if ($value != $r_value) {
            $result = [
                'status' => null,
                'status_message' => 'Ошибка ответа: неверная сумма (amt)',
            ];
            return  $result;
        }
        // ccy
        $value = $_ccy;
        $r_value = (string) $xml_response_payment->ccy;
        if ($value != $r_value) {
            $result = [
                'status' => null,
                'status_message' => 'Ошибка ответа: неверная валюта (ccy)',
            ];
            return  $result;
        }
        // state
        $status = (bool) $xml_response_payment->state;
        $status_message = (string) $xml_response_payment->message;
        if ($status) {
            if ($status_message == 'payment added to the queue') {
                $status_message = 'Платеж добавлен в очередь';
            } else {
                $status_message = 'Платеж выполнен успешно';
            }
        } else {
            $status_message = 'Платеж забракован';
        }
        return  [$status, $status_message];
    }

    public function _form_options($options)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $_ = $options;
        // transform
        foreach ((array) $this->_options_transform as $from => $to) {
            if (isset($_[$from])) {
                $_[$to] = $_[$from];
                unset($_[$from]);
            }
        }
        // default
        $_['amt'] = number_format($_['amt'], 2, '.', '');
        empty($_['merchant']) && $_['merchant'] = $this->KEY_PUBLIC;
        empty($_['pay_way']) && $_['pay_way'] = 'privat24';
        if (empty($_['return_url'])) {
            $_['return_url'] = $this->url_result
                . '&operation_id=' . (int) $options['operation_id'];
        }
        if (empty($_['server_url'])) {
            $_['server_url'] = $this->url_server
                . '&operation_id=' . (int) $options['operation_id'];
        }
        if (empty($_['amt']) || empty($_['merchant'])) {
            $_ = null;
        }
        return  $_;
    }

    public function _form($data, $options = null)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        if (empty($data)) {
            return  null;
        }
        $_ = &$options;
        // START DUMP
        $payment_api = $this->payment_api;
        $payment_api->dump(['name' => 'Privat24', 'operation_id' => @(int) $_['data']['operation_id']]);
        $is_array = (bool) $_['is_array'];
        $form_options = $this->_form_options($data);
        // DUMP
        $payment_api->dump(['var' => $form_options]);
        $signature = $this->signature($form_options);
        if (empty($signature)) {
            return  null;
        }
        $form_options['signature'] = $signature;
        $url = $this->URL . 'ishop';
        $result = [];
        if ($is_array) {
            $result['url'] = $url;
        } else {
            $result[] = '<form id="_js_provider_privat24_form" method="post" accept-charset="utf-8" action="' . $url . '" class="display: none;">';
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
        $is_server = ! empty($_GET['server']);
        $result = null;
        // check operation
        $operation_id = (int) $_GET['operation_id'];
        // response POST:
        $payment = $_POST['payment'];
        $signature = $_POST['signature'];
        // test data
        // $payment = 'amt=16.00&ccy=UAH&details=Поплнение счета (Приват 24)&ext_details=3#71#9#3#16&pay_way=privat24&order=71&merchant=104702&state=test&date=171214180311&ref=test payment&payCountry=UA';
        // $signature = '585b0c173ec36300a5ff77f6cbd9f195492f0c0d';
        // check signature
        if (empty($signature)) {
            $result = [
                'status' => false,
                'status_message' => 'Пустая подпись',
            ];
            return  $result;
        }
        $_signature = $this->signature($payment, $is_request = false);
        if ($signature != $_signature) {
            $result = [
                'status' => false,
                'status_message' => 'Неверная подпись',
            ];
            return  $result;
        }
        // update operation
        $response = $this->_response_parse($payment);
        // check public key (merchant)
        $public_key = $response['public_key'];
        $_public_key = $this->KEY_PUBLIC;
        if ($public_key != $_public_key) {
            $result = [
                'status' => false,
                'status_message' => 'Неверный ключ (merchant)',
            ];
            return  $result;
        }
        // check status
        // ok, fail, test, wait
        $state = $response['state'];
        if ($this->TEST_MODE && $state == 'test') {
            $state = 'ok';
        }
        list($status_name, $status_message) = $this->_state($state);
        // update account, operation data
        $result = $this->_api_deposition([
            'provider_name' => 'privat24',
            'response' => $response,
            'status_name' => $status_name,
            'status_message' => $status_message,
        ]);
        return  $result;
    }

    public function _response_parse($response)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $options = explode('&', $response);
        $_ = [];
        foreach ((array) $options as $option) {
            list($key, $value) = explode('=', $option);
            $_[$key] = $value;
        }
        // transform
        foreach ((array) $this->_options_transform_reverse as $from => $to) {
            if (isset($_[$from])) {
                $_[$to] = $_[$from];
                unset($_[$from]);
            }
        }
        return  $_;
    }
}
