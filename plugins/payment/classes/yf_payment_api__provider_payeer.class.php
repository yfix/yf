<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_payeer extends yf_payment_api__provider_remote {

    public $PROVIDER_NAME = 'payeer';

    public $KEY = null;
    public $URL_API = 'https://payeer.com/ajax/api/api.php';
    public $URL_MERCHANT_API = 'https://payeer.com/merchant/';
    public $IS_DEPOSITION = true;
    public $IS_PAYMENT    = true;

    public $url_server = '';

    public $MESSAGE_SUCCESS = 'success';
    public $MESSAGE_FAIL = 'error';

    public $ACCOUNT = '';
    public $API_ID = '';
    public $API_PASS = '';
    public $API_ID_MERCHANT = '';
    public $API_PASS_MERCHANT = '';
    public $PARTNER_ID = '';

    public $external_response_errors = [];

    public $service_allow = array(
        'payeer',
    );

    public $method_allow = array(
        'order' => array(
            'payin' => array(
                'payeer',
            ),
            'payout' => array(
                'payeer',
            ),
        ),
        'payin' => array(
            'payeer' => array(
                'title' => 'Payeer',
                'icon'  => 'payeer',
                'currency' => [
                    'USD' => [
                        'currency_id' => 'USD',
                        'active'      => true,
                    ],
                ],
            ),
        ),
        'payout' => array(
            'payeer' => array(
                'title' => 'Payeer',
                'icon'  => 'payeer',
                'currency' => [
                    'USD' => [
                        'currency_id' => 'USD',
                        'active'      => true,
                    ],
                ],
                'action'     => 'transfer',
                'ps' => '1136053',
                'field' => [
                    'action',
                    'sum',
                    'curIn',
                    'curOut',
                    'to',
                ],
                'order' => [
                    'to',
                ],
                'option' => [
                    'to' => 'Аккаунт Payeer',
                ],
                'option_validation_js' => [
                    'to' => [
                        'type'      => 'text',
                        'required'  => true,
                        'minlength' => 2,
                        'maxlength' => 200,
                        'pattern'   => '^.{2,200}$',
                    ],
                ],
                'option_validation' => [
                    'to' => 'required|regex:~^.{2,200}$~u|xss_clean',
                ],
                'option_validation_message' => [
                    'to' => 'вы должны указать верный Payeer аккаунт',
                ],
            ),
        ),
    );

    public $currency_default = 'USD';
    public $currency_allow = array(
        'USD' => array(
            'currency_id' => 'USD',
            'active'      => true,
        ),
    );

    public function _init() {
        if( !$this->ENABLE ) { return( null ); }
        if(empty($this->url_server)){
            $this->url_server = url_user( '/api/payment/provider?name=payeer&operation=response&server=true');
        }
        $allow = $this->allow();
        if( !$allow ) { return( false ); }
        if(!empty($this->PARTNER_ID)){
            $this->URL_MERCHANT_API .= '?partner='.$this->PARTNER_ID;
            //$this->URL_API .= '?partner='.$this->PARTNER_ID;
        }
        parent::_init();
    }

    public function _api_response( $options ) {
        // import options
        is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
        $this->payment_api->dump([ 'name' => 'Payeer', 'operation_id' => @(int)$_POST['m_orderid'], 'ip' => common()->get_ip() ]);

        if(!empty($_POST['m_orderid'])){
            $this->_external_response();
        }
        else{
            $this->external_response_errors[] = 'm_orderid not found';
            $this->_dump_error_message($this->external_response_errors);
        }
        $operation_id  = $_data[ 'operation_id' ];
        $provider_name = $_provider[ 'name' ];
        $payment_type  = $_options[ 'type_name' ];
        $state         = 0;
        $status        = 'success';
        $datetime      = $_data[ 'datetime_update' ];
        // status
        list( $status_name, $status_message ) = $this->_state( $state );
        // response
        $response = array(
            'operation_id' => $operation_id,
        );
        $operation_data = array(
            'operation_id'   => $operation_id,
            'provider_name'  => $provider_name,
            'state'          => $state,
            'status_name'    => $status_name,
            'status_message' => $status_message,
            'payment_type'   => $payment_type,
            'response'       => $response,
        );
        $result = $this->{ '_api_' . $payment_type }( $operation_data );
        return( $result );
    }

    public function _external_show_message($status){
        $operation_id = !empty($_POST['m_orderid']) ? intval($_POST['m_orderid']) : false;
        $message = $operation_id.'|'.$status;
        $this->payment_api->dump([ 'name' => 'Payeer',
            'operation_id' => @(int)$_POST['m_orderid'],
            'message' => $message, ]);
        $this->_dump_error_message($this->external_response_errors);
        echo $message;
        die();
    }

    public function _dump_error_message($errors){
        $this->payment_api->dump(['name' => 'Payeer',
            'operation_id' => @(int)$_POST['m_orderid'],
            'errors' => $errors,
        ]);
    }

    public function _external_response(){
        $ip = common()->get_ip();
        if (!in_array($ip, array('185.71.65.92', '185.71.65.189'))) {
            $this->external_response_errors[] = 'Invalid sender IP address '.$ip;
            return false;
        }

        $response_status = 'fail';
        if (isset($_POST['m_operation_id']) && isset($_POST['m_sign']))
        {
            $arHash = array($_POST['m_operation_id'],
                $_POST['m_operation_ps'],
                $_POST['m_operation_date'],
                $_POST['m_operation_pay_date'],
                $_POST['m_shop'],
                $_POST['m_orderid'],
                $_POST['m_amount'],
                $_POST['m_curr'],
                $_POST['m_desc'],
                $_POST['m_status'],
                $this->API_PASS_MERCHANT);
            $sign_hash = strtoupper(hash('sha256', implode(':', $arHash)));
            if ($_POST['m_sign'] == $sign_hash && $_POST['m_status'] == 'success')
            {
                $response_status = 'success';
            }
            else {
                $this->external_response_errors[] = 'Invalid signature';
            }
        }
        if($response_status == 'success') {
            $operation_id = intval($_POST['m_orderid']);
            $payment_api = $this->payment_api;
            $operation = $payment_api->operation([
                'operation_id' => $operation_id,
            ]);
            if (!empty($operation['operation_id'])) {
                // update status only in_progress
                $object = $payment_api->get_status(['status_id' => $operation['status_id']]);
                list($status_id, $status) = $object;

                if (empty($status_id)) {
                    $this->external_response_errors[] = 'Unknown status_id';
                    return $this->_external_show_message($this->MESSAGE_FAIL);
                }

                if ($status['name'] == 'in_progress') {
                    $provider_id = $operation['provider_id'];
                    $provider = $payment_api->provider(['provider_id' => $provider_id]);
                    if (!empty($provider[$provider_id]['name']) && $provider[$provider_id]['name'] == $this->PROVIDER_NAME) {
                        $response['external_response'] = [
                            'post' => $_POST,
                            'get' => $_GET,
                            'ip' => $ip,
                            'datetime' => $payment_api->sql_datetime(),
                            'action' => 'approve',
                        ];
                        $update_data = [
                            'operation_id' => $operation_id,
                            'status_id' => $operation['status_id'],
                            'datetime_update' => $payment_api->sql_datetime(),
                            'options' => $response,
                        ];
                        $result = $payment_api->operation_update($update_data);
                        $operation_data = [
                            'operation_id' => $operation_id,
                            'provider_name' => $this->PROVIDER_NAME,
                            'state' => 0,
                            'status_name' => $response_status,
                            'payment_type' => 'deposition',
                            'response' => [],
                        ];
                        $result_update_balance = $this->_api_transaction($operation_data);
                        if ($result_update_balance['status'] == $response_status) {
                            return $this->_external_show_message($this->MESSAGE_SUCCESS);
                        }
                        else {
                            $this->external_response_errors[] = 'Invalid transaction status, we have '.$result_update_balance['status'].', needed '.$response_status;
                        }
                    }
                    else {
                        $this->external_response_errors[] = 'Invalid provider, we have '.
                            (!empty($provider[$provider_id]['name']) ? $provider[$provider_id]['name'] : '').
                            ', needed '.$this->PROVIDER_NAME;
                    }
                }
                else {
                    $this->external_response_errors[] = 'Invalid operation status, now it\'s '.$status['name'].', needed in_progress';
                }
            }
            else {
                $this->external_response_errors[] = 'Unknown operation_id '.$_POST['m_orderid'];
            }
        }
        return $this->_external_show_message($this->MESSAGE_FAIL);
    }


    public function _form_options( $options ) {
        if( !$this->ENABLE ) { return( null ); }
        is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
        if(empty($_operation_id)) { return( null ); }

        //payment systems
        /*
         * 26808 QIWI
         * 1136053 Payeer
         * 57378077 Yandex.Money
         * 57644634 MASTERCARD (account)
         * 27322260 Mastercard (account, person)
         * 27313794 Visa (account, person)
         * 57568699 Visa (account)
         */

        $_currency = !empty($_currency) ?$_currency : 'USD';
        $_title = !empty($_title) ?$_title : 'Пополнение счёта';
        $_ = [
            'm_shop' => $this->API_ID_MERCHANT,
            'm_orderid' => $_operation_id,
            'm_amount' => number_format($_amount, 2, '.', ''),
            'm_curr' => $_currency,
            'm_desc' => base64_encode($_title),
        ];
        $arHash = [];
        foreach($_ as $key=>$value){
            $arHash[] = $value;
        }
        $arHash[] = $this->API_PASS_MERCHANT;
        $_['m_sign'] = strtoupper(hash('sha256', implode(':', $arHash)));
        $_['lang'] = 'ru';
        return( $_ );
    }


    public function _form( $data, $options = null ) {
        if( !$this->ENABLE ) { return( null ); }
        // START DUMP
        $payment_api = $this->payment_api;
        $payment_api->dump([ 'name' => 'Payeer', 'operation_id' => @(int)$_[ 'data' ][ 'operation_id' ] ]);
        if( empty( $data ) ) { return( null ); }
        $is_array = (bool)$_[ 'is_array' ];
        $form_options = $this->_form_options( $data );
        // DUMP
        $payment_api->dump([ 'var' => $form_options ]);
        if( empty( $form_options ) ) { return( null ); }
        $url = $this->URL_MERCHANT_API;
        $result = [];
        if( $is_array ) {
            $result[ 'url' ] = $url;
        } else {
            $result[] = '<form id="_js_provider_payeer_form" method="get" accept-charset="utf-8" action="' . $url . '" class="display: none;">';
        }
        foreach ((array)$form_options as $key => $value ) {
            if( $is_array ) {
                $result[ 'data' ][ $key ] = $value;
            } else {
                $result[] = sprintf( '<input type="hidden" name="%s" value="%s" />', $key, $value );
            }
        }
        if( !$is_array ) {
            $result[] = '</form>';
            $result = implode( PHP_EOL, $result );
        }
        return( $result );
    }

    public function signature( $options ) {
        return strtoupper(hash('sha256', implode(':', array_merge($options, array($this->API_PASS_MERCHANT)))));
    }


    /*public function _payin_response($options){
        is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
        if(empty($_operation_id)) {
            return false;
        }
        $payment_api = $this->payment_api;

        //info from our site
        $_currency = !empty($_currency) ?$_currency : 'USD';
        $_title = !empty($_title) ?$_title : 'Пополнение счёта';
        $url_options = [
            'm_shop' => $this->API_ID_MERCHANT,
            'm_orderid' => $_operation_id,
            'm_amount' => $_amount,
            'm_curr' => $_currency,
            'm_desc' => base64_encode($_title),
        ];


        $arHash = [];
        foreach($url_options as $key=>$value){
            $arHash[] = $value;
        }
        $arHash[] = $this->API_PASS_MERCHANT;
        $url_options['m_sign'] = strtoupper(hash('sha256', implode(':', $arHash)));
        $url_options['form[ps]'] = $_ps_id ? $_ps_id : 26808;
        $url_options['form[curr['.$url_options['form[ps]'].']]'] = $_currency;
        $url = $this->_create_url($this->URL_MERCHANT_API, $url_options);

        //$request_result = common()->get_remote_page($this->URL_API, false, $url_options);
        $request_result = common()->get_remote_page($url);
        $request_result_array = empty($request_result) ? '' : json_decode($request_result, true);

        //$url_options['m_key'] = substr($this->API_PASS, 0, 4).preg_replace('~(.)~u', '*', substr($this->API_PASS, 4));
        $result = [
            'response' => [
                'datetime'      => $payment_api->sql_datetime(),
                'provider_name' => $this->PROVIDER_NAME,
                'url_options'  => $url_options,
                'operation_id'  => $_operation_id,
                'result'        => $request_result_array,
                'url'           => $url,
                'amount'        => $_amount,
            ]
        ];
        return $result;
    }*/

    public function _create_api_response($options){
        $url_options = [
            'account' => $this->ACCOUNT,
            'apiId' => $this->API_ID,
            'apiPass' => $this->API_PASS,
        ];
        $url_options = array_merge($url_options, $options);
        $request_result = common()->get_remote_page($this->URL_API, false, ['post' => $url_options]);
        $request_result_array = empty($request_result) ? '' : json_decode($request_result, true);
        return $request_result_array;
    }

    public function _create_url($url, $data = []){
        $add_string = '';
        if(count($data)>0) {
            foreach ($data as $key => $value) {
                //$add_string[] = urlencode($key).'='.urlencode($value);
                $add_string[] = "$key=$value";
            }
            $add_string = implode('&', $add_string);
            $first_separator = '?';
            if(stripos($url, '?') > 0){
                $first_separator = '&';
            }
            $add_string = $first_separator.$add_string;
        }
        return $url.$add_string;
    }

    public function _get_payment_systems() {
        return $this->_create_api_response(['action' => 'getPaySystems']);
    }

    /*public function _api_deposition($options){
        if( !$this->ENABLE ) { return( null ); }
        $payment_api = $this->payment_api;
        is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
        $payment_api->dump([ 'name' => 'Payeer', 'operation_id' => @(int)$_operation_id]);
        if(empty($_operation_id)) {
            return false;
        }
        $result = $this->_api_transaction( $options );
        // update operation
        $operation = $payment_api->operation( [
            'operation_id' => $_operation_id,
        ]);
        $payin_response = $this->_payin_response(array_merge($options, ['amount'=>$operation['amount']]));
        $payment_api->dump([ 'response_info' => $payin_response ]);

        $data = [
            'operation_id'    => $_operation_id,
            'status_id'       => $operation['status_id'],
            'datetime_update' => $payment_api->sql_datetime(),
            'options'         => $payin_response,
        ];

        $result = $payment_api->operation_update( $data );
        $payment_api->transaction_commit();
        if($result['status'] === true){
            $response_result = $payin_response['response']['result'];
            if(empty($response_result['errors'])){
                $result = $response_result;
            }
            else {
                $result = ['error_message' => 'Невозможно подключиться по апи, возможно доступ по апи для вашего сайта не подтверждён.'];
            }
        }
        return( $result );
    }

    public function deposition( $options ) {
        if( !$this->ENABLE ) { return( null ); }
        $result = $this->_api_response( $options );
        return( $result );
    }*/



    public function api_payout( $options = null ) {
        if( !$this->ENABLE ) { return( null ); }
        // import options
        is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
        // method
        $method = $this->api_method( [
            'type'      => 'payout',
            'method_id' => $_method_id,
        ]);
        if( empty( $method ) ) {
            $result = [
                'status'         => false,
                'status_message' => 'Метод запроса не найден',
            ];
            return( $result );
        }
        $payment_api = &$this->payment_api;
        // operation_id
        $_operation_id = (int)$_operation_id;
        //$operation_id = $_operation_id;
        if( empty( $_operation_id ) ) {
            $result = [
                'status'         => false,
                'status_message' => 'Не определен код операции',
            ];
            return( $result );
        }
        // currency_id
        $currency_id = $this->get_currency_payout( $options );
        if( empty( $currency_id ) ) {
            $result = [
                'status'         => false,
                'status_message' => 'Неизвестная валюта',
            ];
            return( $result );
        }
        // amount min/max
        $result = $this->amount_limit( [
            'amount'      => $_amount,
            'currency_id' => $currency_id,
            'method'      => $method,
        ]);
        if( ! @$result[ 'status' ] ) { return( $result ); }
        // currency conversion
        $amount_currency = $payment_api->currency_conversion( [
            'type'        => 'sell',
            'currency_id' => $currency_id,
            'amount'      => $_amount,
        ]);
        if( empty( $amount_currency ) ) {
            $result = [
                'status'         => false,
                'status_message' => 'Невозможно произвести конвертацию валют',
            ];
            return( $result );
        }
        // fee
        $fee = $this->get_fee_payout( $options );
        $amount_currency_total = $payment_api->fee( $amount_currency, $fee );


        // default
        // $_currency = $currency_id;
        // $_amount = $this->_amount_payout( $amount_currency_total, $_currency, $is_request = true );
        $_amount = $this->_amount_payout( $_amount, $currency_id, $method, $is_request = true );

        !isset( $_comment ) && $_comment = t( 'Вывод средств (id: ' . $_operation_id . ')' );
        !isset( $_action  ) && $_action = $method[ 'action' ];
        $_sum = $_amount;
        $_curIn = $_curOut = $currency_id;

        // check required
        $request = [];
        foreach( $method[ 'field' ] as $key ) {
            $value = @${ '_'.$key };
            if( !isset( $value ) ) {
                $result = [
                    'status'         => false,
                    'status_message' => 'Отсутствуют данные запроса: '. $key,
                ];
                continue;
                // return( $result );
            }
            $request[ $key ] = &${ '_'.$key };
        }
        // START DUMP
        $payment_api->dump( [ 'name' => 'Payeer', 'operation_id' => $operation_id,
            'var' => [ 'request' => $request ]
        ]);

        $response = $this->_create_api_response($request);
        // DUMP
        $payment_api->dump( [ 'var' => [ 'response'=> $response ]]);

        if( empty( $response ) ) {
            $result = [
                'status'         => false,
                'status_message' => 'Невозможно отправить запрос',
            ];
            return( $result );
        }
        if(empty($response['errors'])){
            // result
            $status         = 'in_progress';
            $status_message = null;
            if( intval($response[ 'historyId' ]) > 0 ) {
                $status         = 'success';
                $status_message = 'Выполнено';
            } else {
                $status         = 'processing';
                $status_message = 'Не удалось осуществить перевод.';
            }
            $result = [
                'status'         => $status,
                'status_message' => $status_message,
            ];

            $payment_api->dump( [ 'var' => [ 'result'=> $result ]]);

            $object = $payment_api->get_status(['name' => $status]);
            list($status_id, $status) = $object;
            if (empty($status_id)) {
                $result = [
                    'status'         => false,
                    'status_message' => 'Неизвестный статус операции',
                ];
                return( $result );
            }

            // save response
            $sql_datetime = $payment_api->sql_datetime();
            $operation_options = [
                'processing' => [ [
                    'provider_name' => $this->PROVIDER_NAME,
                    'datetime'      => $sql_datetime,
                ]],
                'response' => [ [
                    'datetime'       => $sql_datetime,
                    'provider_name'  => $this->PROVIDER_NAME,
                    'state'          => 0,
                    'status_name'    => $status,
                    'status_message' => $status_message,
                    'data'           => $response,
                ]],
            ];
            $operation_update_data = [
                'operation_id'    => $_operation_id,
                'datetime_update' => $sql_datetime,
                'status_id'       => $status_id,
                'options'         => $operation_options,
            ];
            $payment_api->operation_update( $operation_update_data );
        }
        else {
            $result = [
                'status'         => false,
                'status_message' => json_encode($response['errors']),
            ];
        }
        return( $result );
    }

}
