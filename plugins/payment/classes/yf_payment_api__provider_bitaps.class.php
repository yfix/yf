<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_bitaps extends yf_payment_api__provider_remote {

    public $PROVIDER_NAME = 'bitaps';
    public $URL_API = 'https://bitaps.com/api/';

    public $PAYOUT_ADDRESS = '';//your bitcoin address for get bitcoins
    public $CONFIRMATIONS = 6;
    public $FEE_LEVEL = 'low';
    public $MARKET_NAME = 'average';

    public $IS_DEPOSITION = true;
    public $IS_PAYMENT    = true;

    public $url_server = '';

    public $MESSAGE_SUCCESS = 'ok';
    public $MESSAGE_FAIL = 'fail';

    public $service_allow = array(
        'bitaps',
    );

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
                'icon'  => 'btc',
                'currency' => [
                    'BTC' => [
                        'currency_id' => 'BTC',
                        'active'      => true,
                    ],
                ],
                'fee' => 0.0002,
                'amount_min' => 0.00003,
            ],
        ],

        'payout' => [
            'bitaps' => [
                'title' => 'Bitaps',
                'icon'  => 'btc',
                'currency' => [
                    'BTC' => [
                        'currency_id' => 'BTC',
                        'active'      => true,
                    ],
                ],
                'field' => [
                    '$to',
                    '$amount',
                ],
                'order' => [
                    'to',
                ],
                'option' => [
                    'to' => 'Адрес кошелька'
                ],
                'option_validation_js' => [
                    'to' => [
                        'type'      => 'text',
                        'required'  => true,
                        'minlength' => 26,
                        'maxlength' => 35,
                        'pattern'   => '^[13][A-Za-z0-9]{25,34}$',
                    ],
                ],
                'option_validation' => [
                    'to' => 'required|regex:~^[13][A-Za-z0-9]{25,34}$~u|xss_clean',
                ],
                'option_validation_message' => [
                    'to' => 'вы должны указать верный Bitcoin кошелёк',
                ],

            ],
        ],
    ];

    public $currency_default = 'BTC';
    public $currency_allow = array(
        'BTC' => array(
            'currency_id' => 'BTC',
            'active'      => true,
        ),
    );

    public function _init() {
        if( !$this->ENABLE ) { return( null ); }
        if(empty($this->url_server)){
            $this->url_server = url_user( '/api/payment/provider?name=bitaps&operation=response&server=true');
        }
        $allow = $this->allow();
        if( !$allow ) { return( false ); }
        parent::_init();
    }

    public function _api_response( $options ) {
        // import options
        is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );

        $options['id'] = $_POST['id'] ? : '';
        $options['posData'] = $_POST['posData'] ? : '';
        $options['status'] = $_POST['status'] ? : '';
        $options['btcPrice'] = $_POST['btcPrice'] ? : '';
        $options['currency'] = $_POST['currency'] ? : '';
        $options['btcPaid'] = $_POST['btcPaid'] ? : '';
        $options['rate'] = $_POST['rate'] ? : '';
        $options['exceptionStatus'] = !empty($_POST['exceptionStatus'])  && $_POST['exceptionStatus'] != 'false' ? $_POST['exceptionStatus'] : false;

        $is_response_error = false;
        if(!empty($options['id'])) {
            $pos_data = json_decode($options['posData'], true);
            if(!empty($pos_data['orderId']) && !empty($pos_data['secret'])){
                $this->_external_response($options);
            }
        }
        else {
            $is_response_error = true;
        }

        if($is_response_error){
            $this->external_response_errors[] = 'operation_id not found';
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

    public function _external_show_message($message){
        echo $message;
        die();
    }

    //process response from bitaps, where we get info about transaction
    public function _external_response($options){
        $pos_data = json_decode($options['posData'], true);
        $operation_id = intval($pos_data['orderId']);
        $ip = common()->get_ip();
        $secret = $pos_data['secret'];
        $this->payment_api->dump([ 'name' => 'Bitpay', 'operation_id' => $operation_id, 'ip' => $ip ]);
        $payment_api = $this->payment_api;
        $operation = $payment_api->operation( [
            'operation_id' => $operation_id,
        ]);

        if(!empty($operation['operation_id'])){
            // update status only in_progress
            $object = $payment_api->get_status( [ 'status_id' => $operation[ 'status_id' ] ] );
            list( $status_id, $status ) = $object;

            if( empty( $status_id ) ) {
                return $this->_external_show_message($this->MESSAGE_FAIL);
            }

            if($status[ 'name' ] == 'in_progress'){
                $provider_id = $operation['provider_id'];
                $provider = $payment_api->provider(['provider_id'=>$provider_id]);
                if(!empty($provider[$provider_id]['name']) && $provider[$provider_id]['name'] == $this->PROVIDER_NAME){
                    $operation_add_options  = [
                        'external_response' => [
                            'get' => $_GET,
                            'post' => $_POST,
                            'options' => $options,
                            'ip' => $ip,
                            'datetime' => $payment_api->sql_datetime()
                        ]
                    ];
                    if(!empty($operation['options']['request']['secret'])){

                        $real_secret = $operation['options']['request']['secret'];
                        if($real_secret == $secret){
                            if(in_array($options['status'], $this->success_statuses)) {
                                $operation_add_options['external_response']['action'] = 'approve';
                                $update_data = [
                                    'operation_id'    => $operation_id,
                                    'options'         => $operation_add_options,
                                ];
                                $payment_api->operation_update( $update_data );
                                $status_name = 'success';
                                $status_message = 'ok';
                            }
                            if(in_array($options['status'], $this->fail_statuses)) {
                                $status_name = $options['status'] == 'expired'? $options['status']: 'cancelled';
                                $status_message = 'fail';
                                $operation_add_options['external_response']['action'] = $status_name;
                                $update_data = [
                                    'operation_id'    => $operation_id,
                                    'options'         => $operation_add_options,
                                ];
                                $payment_api->operation_update( $update_data );
                            }
                            if(in_array($options['status'], $this->partial_statuses)){
                                $currency = $options['currency'] ? : 'USD';
                                $currency_rate = $this->payment_api->currency_rate(['from'=>$currency, 'to'=>'UNT']);

                                $amount = $options['btcPaid']*$options['rate']*$currency_rate;
                                //need update operation amount
                                $action = 'update amount from '.$operation['amount'].' to '.$amount;
                                $operation_add_options['external_response']['action'] = $action;
                                $update_data = [
                                    'operation_id'    => $operation_id,
                                    'status_id'       => $operation['status_id'],
                                    //'datetime_update' => $payment_api->sql_datetime(),
                                    'amount'          => $amount,
                                    'options'         => $operation_add_options,
                                ];
                                $result = $payment_api->operation_update( $update_data );
                                if(!$result['status']){
                                    return $this->_external_show_message($this->MESSAGE_FAIL);
                                }
                                else {
                                    $status_name = 'success';
                                    $status_message = 'ok';
                                }
                            }
                            $operation_data = [
                                'operation_id'   => $operation_id,
                                'provider_name'  => $this->PROVIDER_NAME,
                                'state'          => 0,
                                'status_name'    => $status_name,
                                'status_message' => $status_message,
                                'payment_type'   => 'deposition',
                                'response'       => [],
                            ];
                            $result_update_balance = $this->_api_transaction($operation_data);
                            if($result_update_balance['status'] == $status_name){
                                return $this->_external_show_message($this->MESSAGE_SUCCESS);
                            }
                        }
                    }
                }
            }
        }
        return $this->_external_show_message($this->MESSAGE_FAIL);
    }
    public function _form( $invoice_id, $url ) {
        if( !$this->ENABLE ) { return( null ); }
        // START DUMP
        $payment_api = $this->payment_api;

        if( empty( $invoice_id ) || empty($url) ) { return( null ); }
        $form = '';
        return $form ;
    }

    public function deposition( $options ) {
        if( !$this->ENABLE ) { return( null ); }
        $payment_api = $this->payment_api;
        $_              = $options;
        $data           = &$_[ 'data'           ];
        $options        = &$_[ 'options'        ];
        $operation_data = &$_[ 'operation_data' ];
        // prepare data
        $user_id      = (int)$operation_data[ 'user_id' ];
        $operation_id = (int)$data[ 'operation_id' ];
        $account_id   = (int)$data[ 'account_id'   ];
        $provider_id  = (int)$data[ 'provider_id'  ];

        $amount       = $payment_api->_number_float( $data[ 'amount' ] );
        $currency_id  = $this->get_currency( $options );
        if( empty( $operation_id ) ) {
            $result = [
                'status'         => false,
                'status_message' => 'Не определен код операции',
            ];
            return( $result );
        }
        // currency conversion
        $amount_currency = $payment_api->currency_conversion( [
            'type'        => 'buy',
            'currency_id' => $currency_id,
            'amount'      => $amount,
        ]);
        if( empty( $amount_currency ) ) {
            $result = [
                'status'         => false,
                'status_message' => 'Невозможно произвести конвертацию валют',
            ];
            return( $result );
        }
        // fee
        $fee = $this->fee;
        $amount_currency_total = $payment_api->fee( $amount_currency, $fee );


        $invoice_data = $this->_create_invoice();
        $usd_to_unt = $payment_api->currency_conversion( [
            'type'        => 'buy',
            'currency_id' => 'USD',
            'amount'      => 1,
        ]);
        $data = [
            'operation_id'    => $operation_id,
            //'status_id'       => $operation['status_id'],
            'datetime_update' => $payment_api->sql_datetime(),
            'options'         => ['request'=>['invoice'=>$invoice_data,'usd_to_unt'=>$usd_to_unt, 'usd_to_btc'=>$amount*$usd_to_unt/$amount_currency, 'course_date'=>$payment_api->sql_datetime()]],
        ];

        $result = $payment_api->operation_update( $data );

        if(!empty($invoice_data['address']) && $result['status'] === true){
            $address = $invoice_data['address'];
            $wallet_url = 'bitcoin:'.$address.'?amount='.$amount_currency_total.'&amp;r='.urlencode(url('/payments'));
            $result = [
                'show_bitcoin_address' => 1,
                'qrcode'  => $this->_qrcode($wallet_url),
                'address' => $address,
                'amount'  => $amount_currency_total,
                'wallet_url' => $wallet_url,
                'status'  => true,
                'status_message' => t( 'Заявка на ввод средств принята' ),
            ];
        }
        else {
            $result = [
                'status'         => false,
                'status_message' => t( 'При создании заявки на приём средст возникла ошибка' ),
            ];
        }
        return( $result );
    }








    public function _create_invoice() {
        $result = '';
        $callback_url = urlencode($this->url_server);
        $url = $this->URL_API.'create/payment/'.$this->PAYOUT_ADDRESS.'/'.$callback_url.'confirmations='.$this->CONFIRMATIONS.'&fee_level='.$this->FEE_LEVEL;
        $request_result = common()->get_remote_page($url);
        if(!empty($request_result)){
            $data = @json_decode($request_result, true);
            if(is_array($data) && !empty($data['address'])) {
                $result = $data;
            }
        }
        return $result;
    }

    public function _usd_to_btc_rate() {
        $url = $this->URL_API.'ticker';
        $request_result = common()->get_remote_page($url);
        if(!empty($request_result)){
            $data = @json_decode($request_result, true);
            if(is_array($data) && !empty($data['usd'])) {
                return $data['usd'];
            }
        }
        return false;
    }


    public function _qrcode($message){
        $url=$this->URL_API.'qrcode/'.urlencode($message);
        $qrcode_data = common()->get_remote_page($url);
        $qrcode_data_decoded = @json_decode($qrcode_data,true);
        $qrcode = !empty($qrcode_data_decoded['qrcode']) ?$qrcode_data_decoded['qrcode'] : ''; // QR code in base64 encoded svg
        return $qrcode;
    }

    public function api_payout( $options ) {
        $result = $this->_api_response( $options );
        return( $result );
    }


    public function payment( $options ) {
        if( !$this->ENABLE ) { return( null ); }
        // import options
        is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
        // class
        $payment_api = $this->payment_api;
        // var
        $operation_id  = $_data[ 'operation_id' ];
        // payment
        $result = parent::payment( $options );
        // confirmation is ok
        $confirmation_ok_options = array(
            'operation_id' => $operation_id,
        );
        $result = $payment_api->confirmation_ok( $confirmation_ok_options );
        // payout
        $result = $this->api_payout( $options );
        return( $result );
    }
}
