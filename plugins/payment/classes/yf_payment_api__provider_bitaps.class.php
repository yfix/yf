<?php

_class('payment_api__provider_remote');

class yf_payment_api__provider_bitaps extends yf_payment_api__provider_remote
{
    public $PROVIDER_NAME = 'bitaps';
    public $URL_API = 'https://bitaps.com/api/';

    public $PAYOUT_ADDRESS = '';//your bitcoin address for get bitcoins
    public $REDEEM_ADDRESS = '';//your bitcoin address for payout bitcoins
    public $REDEEM_CODE = '';//code for make payouts
    public $REDEEM_INVOICE = '';
    public $CONFIRMATIONS = 6;
    public $FEE_LEVEL = 'low';//bitcoin network fee
    public $CUSTOM_FEE = 1;//bitcoin network custom fee
    public $DEFAULT_FEE_LEVEL_LOW = 80;//satoshi per byte
    public $AVERAGE_TRANSACTION_SIZE = 226;//bytes
    public $MARKET_NAME = 'average';
    public $SERVICE_FEE = 20000;//satoshi
    public $SERVICE_AMOUNT_MIN = 30000;//satoshi
    public $SATOSHI_TO_BTC = 100000000;
    public $RATE_VARIATION_PC = 1;//%
    public $FEE_VARIATION_PC = 5;//%
    public $AMOUNT_SATOSHI_DEVIATION = 100;

    public $IS_DEPOSITION = true;
    public $IS_PAYMENT = true;

    public $url_server = '';

    public $MESSAGE_SUCCESS = 'ok';
    public $MESSAGE_FAIL = 'fail';

    public $service_allow = [
        'bitaps',
    ];

    public $method_allow = [
        'order' => [
            'payin' => [
                'bitaps',
            ],
            'payout' => [
                'bitaps',
            ],
        ],
        'payin' => [
            'bitaps' => [
                'title' => 'Bitaps',
                'icon' => 'btc',
                'currency' => [
                    'BTC' => [
                        'currency_id' => 'BTC',
                        'active' => true,
                    ],
                ],
                'fee' => 0.0002,
                'amount_min' => 0.0003,
            ],
        ],

        'payout' => [
            'bitaps' => [
                'title' => 'Bitaps',
                'icon' => 'btc',
                'currency' => [
                    'BTC' => [
                        'currency_id' => 'BTC',
                        'active' => true,
                    ],
                ],
                'field' => [
                    'address',
                    'amount',
                ],
                'order' => [
                    'address',
                ],
                'option' => [
                    'address' => 'Адрес кошелька',
                ],
                'option_validation_js' => [
                    'address' => [
                        'type' => 'text',
                        'required' => true,
                        'minlength' => 26,
                        'maxlength' => 35,
                        'pattern' => '^[13][A-Za-z0-9]{25,34}$',
                    ],
                ],
                'option_validation' => [
                    'address' => 'required|regex:~^[13][A-Za-z0-9]{25,34}$~u|xss_clean',
                ],
                'option_validation_message' => [
                    'address' => 'вы должны указать верный Bitcoin кошелёк',
                ],
            ],
        ],
    ];

    public $currency_default = 'BTC';
    public $currency_allow = [
        'BTC' => [
            'currency_id' => 'BTC',
            'active' => true,
        ],
    ];

    public function _init()
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        if (empty($this->url_server)) {
            $this->url_server = url_user('/api/payment/provider?name=bitaps&operation=response&server=true');
        }
        $allow = $this->allow();
        if ( ! $allow) {
            return  false;
        }
        parent::_init();
    }

    public function _api_response($options)
    {
        $operation_id = isset($_GET['operation_id']) ? (int) ($_GET['operation_id']) : '';
        $this->payment_api->dump(['name' => ucfirst($this->PROVIDER_NAME), 'operation_id' => $operation_id]);
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');

        $is_response_error = false;
        $options['operation_id'] = $operation_id;
        $options['address'] = $_POST['address'] ?: '';
        $options['invoice'] = $_POST['invoice'] ?: '';
        $options['code'] = $_POST['code'] ?: '';
        $options['amount'] = $_POST['amount'] ?: '';
        $options['confirmations'] = $_POST['confirmations'] ?: '';
        $options['payout_service_fee'] = $_POST['payout_service_fee'] ?: '';
        $options['payout_miner_fee'] = $_POST['payout_miner_fee'] ?: '';
        if ( ! empty($operation_id)) {
            $this->_external_response($options);
        } else {
            $is_response_error = true;
        }

        if ($is_response_error) {
            $this->external_response_errors[] = 'operation_id not found';
            $this->_dump_error_message($this->external_response_errors);
        }
        $operation_id = $_data['operation_id'];
        $provider_name = $_provider['name'];
        $payment_type = $_options['type_name'];
        $state = 0;
        $status = 'success';
        $datetime = $_data['datetime_update'];
        // status
        list($status_name, $status_message) = $this->_state($state);
        // response
        $response = [
            'operation_id' => $operation_id,
        ];
        $operation_data = [
            'operation_id' => $operation_id,
            'provider_name' => $provider_name,
            'state' => $state,
            'status_name' => $status_name,
            'status_message' => $status_message,
            'payment_type' => $payment_type,
            'response' => $response,
        ];
        $result = $this->{ '_api_' . $payment_type }($operation_data);
        return  $result;
    }

    public function _external_show_message($message)
    {
        echo $message;
        die();
    }

    //process response from bitaps, where we get info about transaction
    public function _external_response($options)
    {
        $operation_id = $options['operation_id'];
        $payment_code = $options['code'] ?: '';
        $address = $options['address'] ?: '';
        $confirmations = $options['confirmations'] ?: '';
        $ip = common()->get_ip();

        $payment_api = $this->payment_api;
        $operation = $payment_api->operation([
            'operation_id' => $operation_id,
        ]);

        if ( ! empty($operation['operation_id'])) {
            // update status only in_progress
            $object = $payment_api->get_status(['status_id' => $operation['status_id']]);
            list($status_id, $status) = $object;

            if (empty($status_id)) {
                return $this->_external_show_message($this->MESSAGE_FAIL);
            }

            if ($status['name'] == 'in_progress') {
                $provider_id = $operation['provider_id'];
                $provider = $payment_api->provider(['provider_id' => $provider_id]);
                if ( ! empty($provider[$provider_id]['name']) && $provider[$provider_id]['name'] == $this->PROVIDER_NAME) {
                    $operation_add_options = [
                        'external_response' => [
                            'get' => $_GET,
                            'post' => $_POST,
                            'options' => $options,
                            'ip' => $ip,
                            'datetime' => $payment_api->sql_datetime(),
                        ],
                    ];
                    if ( ! empty($operation['options']['request']['invoice']['payment_code'])) {
                        $real_payment_code = $operation['options']['request']['invoice']['payment_code'];
                        $real_address = $operation['options']['request']['invoice']['address'];
                        if ($real_payment_code == $payment_code && $confirmations >= $this->CONFIRMATIONS && $real_address == $address) {
                            $need_update_amount = false;
                            $request_amount_currency_satoshi = $operation['options']['request']['amount_currency_satoshi'];

                            $request_fee = $operation['options']['request']['fee'];
                            $request_fee_satoshi = $request_fee * $this->SATOSHI_TO_BTC;

                            $request_amount_currency_total = $operation['options']['request']['amount_currency_total'];
                            $request_amount_currency_total_satoshi = $request_amount_currency_total * $this->SATOSHI_TO_BTC;

                            $payout_service_fee = $options['payout_service_fee'] ? (int) ($options['payout_service_fee']) : 0;
                            $payout_miner_fee = $options['payout_miner_fee'] ? (int) ($options['payout_miner_fee']) : 0;
                            $real_amount_currency_satoshi = $options['amount'] - $payout_service_fee - $payout_miner_fee;
                            $real_amount_currency_total_satoshi = $options['amount'];

                            $fee_variation_pc = abs(1 - $request_fee_satoshi / ($payout_miner_fee + $payout_service_fee)) * 100;
                            if ($fee_variation_pc >= $this->FEE_VARIATION_PC) {
                                //fee variation
                                $need_update_amount = true;
                            }
                            if (abs($request_amount_currency_total_satoshi - $real_amount_currency_total_satoshi) >= $this->AMOUNT_SATOSHI_DEVIATION) {
                                //amount variation
                                $need_update_amount = true;
                            }

                            $current_unt_to_btc = $this->payment_api->currency_rate(['from' => 'BTC', 'to' => 'UNT']);
                            $request_unt_to_btc = $operation['options']['request']['unt_to_btc'];
                            $rate_variation_pc = abs(1 - $current_unt_to_btc / $request_unt_to_btc) * 100;
                            if ($rate_variation_pc > $this->RATE_VARIATION_PC) {
                                //BTC rate variation
                                $need_update_amount = true;
                            }

                            if ($need_update_amount) {
                                $amount = $real_amount_currency_satoshi * $current_unt_to_btc / $this->SATOSHI_TO_BTC;
                                //need update operation amount
                                $action = 'update amount from ' . $operation['amount'] . ' to ' . $amount;
                                $operation_add_options['external_response']['action'] = $action;
                                $update_data = [
                                    'operation_id' => $operation_id,
                                    'status_id' => $operation['status_id'],
                                    //'datetime_update' => $payment_api->sql_datetime(),
                                    'amount' => $amount,
                                    'options' => $operation_add_options,
                                ];
                                $result = $payment_api->operation_update($update_data);
                                if ( ! $result['status']) {
                                    return $this->_external_show_message($this->MESSAGE_FAIL);
                                }

                                $status_name = 'success';
                                $status_message = 'ok';
                            } else {
                                $status_name = 'success';
                                $status_message = 'ok';
                            }
                            $operation_data = [
                                'operation_id' => $operation_id,
                                'provider_name' => $this->PROVIDER_NAME,
                                'state' => 0,
                                'status_name' => $status_name,
                                'status_message' => $status_message,
                                'payment_type' => 'deposition',
                                'response' => [],
                            ];
                            $result_update_balance = $this->_api_transaction($operation_data);
                            if ($result_update_balance['status'] == $status_name) {
                                return $this->_external_show_message($this->MESSAGE_SUCCESS);
                            }
                        }
                    }
                }
            }
        }
        return $this->_external_show_message($this->MESSAGE_FAIL);
    }
    public function _form($invoice_id, $url)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $payment_api = $this->payment_api;
        if (empty($invoice_id) || empty($url)) {
            return  null;
        }
        $form = '';
        return $form;
    }

    public function deposition($options)
    {
        if ( ! $this->ENABLE) {
            return  null;
        }
        $payment_api = $this->payment_api;
        $_ = $options;
        $data = &$_['data'];
        $options = &$_['options'];
        $operation_data = &$_['operation_data'];
        // prepare data
        $user_id = (int) $operation_data['user_id'];
        $operation_id = (int) $data['operation_id'];
        $account_id = (int) $data['account_id'];
        $provider_id = (int) $data['provider_id'];

        $amount = $payment_api->_number_float($data['amount']);
        $currency_id = $this->get_currency($options);
        if (empty($operation_id)) {
            $result = [
                'status' => false,
                'status_message' => 'Не определен код операции',
            ];
            return  $result;
        }
        // currency conversion
        $amount_currency = $payment_api->currency_conversion([
            'type' => 'buy',
            'currency_id' => $currency_id,
            'amount' => $amount,
        ]);
        if (empty($amount_currency)) {
            $result = [
                'status' => false,
                'status_message' => 'Невозможно произвести конвертацию валют',
            ];
            return  $result;
        }
        // fee
        $fee = ($this->SERVICE_FEE + $this->_fee_by_level($this->FEE_LEVEL) * $this->AVERAGE_TRANSACTION_SIZE) / $this->SATOSHI_TO_BTC;
        $amount_currency_satoshi = $amount_currency * $this->SATOSHI_TO_BTC;
        $amount_currency_total = $amount_currency + $fee;

        $invoice_data = $this->_create_invoice($operation_id);
        $unt_to_btc = $this->payment_api->currency_rate(['from' => 'BTC', 'to' => 'UNT']);
        $data = [
            'operation_id' => $operation_id,
            //'status_id'       => $operation['status_id'],
            'datetime_update' => $payment_api->sql_datetime(),
            'options' => ['request' => [
                'invoice' => $invoice_data,
                'fee' => $fee,
                'unt_to_btc' => $unt_to_btc,
                'course_date' => $payment_api->sql_datetime(),
                'amount_currency' => $amount_currency,
                'amount_currency_satoshi' => $amount_currency_satoshi,
                'amount_currency_total' => $amount_currency_total,
                'fee' => $fee,
            ]],
        ];

        $result = $payment_api->operation_update($data);

        if ( ! empty($invoice_data['address']) && $result['status'] === true) {
            $address = $invoice_data['address'];
            $wallet_url = 'bitcoin:' . $address . '?amount=' . $amount_currency_total . '&amp;r=' . urlencode(url('/payments'));
            $result = [
                'show_bitcoin_address' => 1,
                'qrcode' => $this->_qrcode($wallet_url),
                'address' => $address,
                'amount' => $amount_currency_total,
                'wallet_url' => $wallet_url,
                'status' => true,
                'status_message' => t('Заявка на ввод средств принята'),
            ];
        } else {
            $result = [
                'status' => false,
                'status_message' => t('При создании заявки на приём средст возникла ошибка'),
            ];
        }
        return  $result;
    }

    public function _create_invoice($operation_id)
    {
        $result = '';
        $callback_url = urlencode($this->url_server . '&operation_id=' . $operation_id);
        $url = $this->URL_API . 'create/payment/' . $this->PAYOUT_ADDRESS . '/' . $callback_url . '?confirmations=' . $this->CONFIRMATIONS . '&fee_level=' . $this->FEE_LEVEL;
        $request_result = common()->get_remote_page($url);
        if ( ! empty($request_result)) {
            $data = @json_decode($request_result, true);
            if (is_array($data) && ! empty($data['address'])) {
                $result = $data;
            }
        }
        return $result;
    }

    public function _fee_by_level($fee_level)
    {
        $url = $this->URL_API . 'fee';
        $request_result = common()->get_remote_page($url);
        $request_data = @json_decode($request_result, true);
        if (is_array($request_data) && isset($request_data[$fee_level])) {
            return $request_data[$fee_level];
        }

        return $this->DEFAULT_FEE_LEVEL_LOW;
    }

    public function _usd_to_btc_rate()
    {
        $url = $this->URL_API . 'ticker';
        $request_result = common()->get_remote_page($url);
        if ( ! empty($request_result)) {
            $data = @json_decode($request_result, true);
            if (is_array($data) && ! empty($data['usd'])) {
                return $data['usd'];
            }
        }
        return false;
    }


    public function _qrcode($message)
    {
        $url = $this->URL_API . 'qrcode/' . urlencode($message);
        $qrcode_data = common()->get_remote_page($url);
        $qrcode_data_decoded = @json_decode($qrcode_data, true);
        $qrcode = ! empty($qrcode_data_decoded['qrcode']) ? $qrcode_data_decoded['qrcode'] : ''; // QR code in base64 encoded svg
        return $qrcode;
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
        //$operation_id = $_operation_id;
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
        $miners_fee = $this->_fee_by_level($this->FEE_LEVEL) * $this->AVERAGE_TRANSACTION_SIZE / $this->SATOSHI_TO_BTC;
        $_amount = $amount_currency_total - $miners_fee;

        if ($_amount <= 0) {
            $result = [
                'status' => false,
                'status_message' => 'Сумма для вывода меньше комиссии сети',
            ];
            return  $result;
        }

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
        // START DUMP
        $payment_api->dump(['name' => ucfirst($this->PROVIDER_NAME), 'operation_id' => $operation_id,
            'var' => ['request' => $request],
        ]);


        $response = $this->_create_payout($request);
        // DUMP
        $payment_api->dump(['var' => ['response' => $response]]);
        $sql_datetime = $payment_api->sql_datetime();

        if (empty($response)) {
            $result = [
                'status' => false,
                'status_message' => 'Невозможно отправить запрос',
            ];
            return  $result;
        }

        if ( ! empty($response['error'])) {
            $result = [
                'status' => false,
                'status_message' => $response['error'],
            ];
            return  $result;
        }

        if ($response['status'] == 'success') {
            $status = 'processing';
            $object = $payment_api->get_status(['name' => $status]);
            list($status_id, $status) = $object;
            $status_message = 'Выплата средств в обаботке.';
            $result = [
                    'status' => $response['status'],
                    'status_message' => $status_message,
                ];

            $payment_api->dump(['var' => ['result' => $result]]);
            $operation_options = [
                    'processing' => [[
                        'provider_name' => $this->PROVIDER_NAME,
                        'datetime' => $sql_datetime,
                    ]],
                    'response' => [[
                        'datetime' => $sql_datetime,
                        'provider_name' => $this->PROVIDER_NAME,
                        'state' => 0,
                        'status_name' => $status,
                        'status_message' => $status_message,
                        'data' => $response,
                    ]],
                ];
            $operation_update_data = [
                    'operation_id' => $_operation_id,
                    'datetime_update' => $sql_datetime,
                    'status_id' => $status_id,
                    'options' => $operation_options,
                ];
            $payment_api->operation_update($operation_update_data);
        } else {
            $result = [
                    'status' => false,
                    'status_message' => $response['status'],
                ];
            return  $result;
        }

        return  $result;
    }



    public function _create_payout($options)
    {
        $amount_currency_satoshi = $options['amount'] * $this->SATOSHI_TO_BTC;
        $url_options = [
            'redeemcode' => $this->REDEEM_CODE,
            'address' => $options['address'],
            'amount' => $amount_currency_satoshi,
            'fee_level' => $this->FEE_LEVEL,
            //'custom_fee'=>$this->CUSTOM_FEE,
        ];
        $url = $this->URL_API . 'use/redeemcode';
        $result = '';
        $result = @common()->get_remote_page($url, false, ['post' => json_encode($url_options)]);
        /* Example:
         * $result = '{"fee": 31200, "tx_hash": "0b9474f58a2f2069b12e2ae32fba252d0d303fafdef3a6ed26a21c2f94085fb0", "status": "success"}';
         */
        if ( ! empty($result)) {
            $result = @json_decode($result, true);
        }
        return $result;
    }

    public function _check_payout_operation($operation_id)
    {
        $payment_api = &$this->payment_api;
        $operation = $payment_api->operation(['operation_id' => $operation_id]);
        $address = $operation['options']['request'][0]['options']['address'] ?: false;
        $tx_hash = $operation['options']['response'][count((array) $operation['options']['response']) - 1]['data']['tx_hash'] ?: false;
        if ($address && $tx_hash) {
            $url = $this->URL_API . 'address/transactions/' . $address . '/0/received/confirmed';
            $result = @common()->get_remote_page($url, false);
            if ( ! empty($result)) {
                $result = @json_decode($result, true);
                if ($result && count((array) $result)) {
                    foreach ($result as $transaction) {
                        if ($transaction[1] == $tx_hash && $transaction[4] == 'confirmed' && $transaction[5] >= $this->CONFIRMATIONS) {
                            $payment_api->dump(['name' => ucfirst($this->PROVIDER_NAME), 'operation_id' => $operation_id]);

                            $status = 'success';
                            $object = $payment_api->get_status(['name' => $status]);
                            list($status_id, $status) = $object;
                            $status_message = 'Транзакция прошла успешно';

                            $result = [
                                'status' => $status,
                                'status_message' => $status_message,
                            ];

                            $payment_api->dump(['var' => ['result' => $result]]);
                            $sql_datetime = $payment_api->sql_datetime();

                            $operation_options = [
                                'response' => [[
                                    'datetime' => $sql_datetime,
                                    'provider_name' => $this->PROVIDER_NAME,
                                    'state' => 0,
                                    'status_name' => $status,
                                    'status_message' => $status_message,
                                    'data' => $transaction,
                                ]],
                            ];

                            $operation_update_data = [
                                'operation_id' => $operation_id,
                                'datetime_update' => $sql_datetime,
                                'status_id' => $status_id,
                                'options' => $operation_options,
                            ];

                            $payment_api->operation_update($operation_update_data);
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function _check_operations($type = 'payout')
    {
        $payment_api = &$this->payment_api;
        $successful_operations = [];
        switch ($type) {
            case 'payout':
                $provider = $payment_api->get_provider(['name' => $this->PROVIDER_NAME]);
                $provider_id = $provider[1]['provider_id'] ?: false;
                $status = 'processing';
                $object = $payment_api->get_status(['name' => $status]);
                list($status_id, $status) = $object;
                if ($provider_id && $status_id) {
                    $where = 'direction=\'out\' and provider_id=' . $provider_id . ' and status_id=' . $status_id;
                    $operations = $payment_api->operation(['where' => $where, 'no_limit' => true]);
                    if ($operations && count((array) $operations) > 1 && $operations[1] > 0) {
                        foreach ($operations[0] as $operation) {
                            $operation_id = (int) ($operation['operation_id']);
                            $operation_is_success = $this->_check_payout_operation($operation_id);
                            if ($operation_is_success) {
                                $successful_operations[] = $operation_id;
                            }
                        }
                    }
                } else {
                    return common()->_show_error_message('Провайдер ' . $this->PROVIDER_NAME . ' не найден');
                }
                break;
            case 'payin':
                break;
        }
        if (count((array) $successful_operations)) {
            $result = 'Операции ' . implode(', ', $successful_operations) . ' успешно завершены';
        } else {
            $result = 'Нет новых успешно завершённых операций';
        }
        return $result;
    }
}
