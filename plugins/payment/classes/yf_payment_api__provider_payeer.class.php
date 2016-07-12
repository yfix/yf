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
        parent::_init();
    }

    public function _api_response( $options ) {
        // import options
        is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );

        $this->payment_api->dump([ 'response' =>
            [
                'POST' => $_POST,
                'GET' => $_GET,
                'SERVER' => $_SERVER
            ]]);

        if(!empty($_GET['m_orderid'])){
            $this->_external_response();
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
        echo $_GET['m_orderid'].'|'.$status;
        die();
    }

    public function _external_response(){
        $ip = common()->get_ip();
        if (!in_array($ip, array('185.71.65.92', '185.71.65.189'))) return;

        $response_status = 'fail';
        if (isset($_GET['m_operation_id']) && isset($_GET['m_sign']))
        {
            $arHash = array($_GET['m_operation_id'],
                $_GET['m_operation_ps'],
                $_GET['m_operation_date'],
                $_GET['m_operation_pay_date'],
                $_GET['m_shop'],
                $_GET['m_orderid'],
                $_GET['m_amount'],
                $_GET['m_curr'],
                $_GET['m_desc'],
                $_GET['m_status'],
                $this->API_PASS_MERCHANT);
            $sign_hash = strtoupper(hash('sha256', implode(':', $arHash)));
            if ($_GET['m_sign'] == $sign_hash && $_GET['m_status'] == 'success')
            {
                $response_status = 'success';
            }
        }
        if($response_status == 'success') {
            $operation_id = $_GET['m_orderid'];
            $payment_api = $this->payment_api;
            $operation = $payment_api->operation([
                'operation_id' => $operation_id,
            ]);
            if (!empty($operation['operation_id'])) {
                // update status only in_progress
                $object = $payment_api->get_status(['status_id' => $operation['status_id']]);
                list($status_id, $status) = $object;

                if (empty($status_id)) {
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
                    }
                }
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
        $request_result = common()->get_remote_page($this->URL_API, false, $url_options);
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
}
