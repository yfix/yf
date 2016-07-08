<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_payeer extends yf_payment_api__provider_remote {

    public $PROVIDER_NAME = 'payeer';

    public $KEY = null;
    public $URL_API = 'https://payeer.com/ajax/api/api.php';
    public $IS_DEPOSITION = true;
    public $IS_PAYMENT    = true;

    public $url_server = '';

    public $SECRET_ADD_STRING= 'HGbfgqov346CBg8dfhGdbjkgtWiv';

    public $MESSAGE_SUCCESS = 'ok';
    public $MESSAGE_FAIL = 'fail';

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
        // var
        if(!empty($_GET['operation_id'])){
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

    public function _external_show_message($message){
        echo $message;
        die();
    }

    public function _external_response(){
        $operation_id = $_GET['operation_id'];
        $secret = $_GET['secret'];
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
                    $response = $operation['options'];
                    if(!empty($response['response']['secret'])){
                        $real_secret = $response['response']['secret'];
                        if($real_secret == $secret){
                            $transaction_hash = $_GET['transaction_hash'];
                            $value_in_satoshi = $_GET['value'];
                            $value_in_btc = $value_in_satoshi / 100000000;

                            $amount = $value_in_btc * $operation['amount']/$response['response']['amount_currency'];
                            //d($payment_api->_operation_balance_update(['operation_id'=>$operation_id]));

                            $ip = common()->get_ip();
                            $response['external_response'] = [
                                'get' => $_GET,
                                'ip' => $ip,
                                'datetime' => $payment_api->sql_datetime(),
                                'action' => 'approve',
                            ];
                            if(abs($amount-$operation['amount'])>0.01){
                                //need update operation amount
                                $action = 'update amount from '.$operation['amount'].' to '.$amount;
                                $response['external_response'] = [
                                    'datetime' => $payment_api->sql_datetime(),
                                    'action' => $action,
                                ];
                                $update_data = [
                                    'operation_id'    => $operation_id,
                                    'status_id'       => $operation['status_id'],
                                    'datetime_update' => $payment_api->sql_datetime(),
                                    'amount'          => $amount,
                                    'options'         => $response,
                                ];
                                $result = $payment_api->operation_update( $update_data );
                                if(!$result['status']){
                                    return $this->_external_show_message($this->MESSAGE_FAIL);
                                }
                            }
                            else {
                                $update_data = [
                                    'operation_id'    => $operation_id,
                                    'status_id'       => $operation['status_id'],
                                    'datetime_update' => $payment_api->sql_datetime(),
                                    'options'         => $response,
                                ];
                                $result = $payment_api->operation_update( $update_data );
                            }
                            $status = 'success';
                            $operation_data = [
                                'operation_id'   => $operation_id,
                                'provider_name'  => $this->PROVIDER_NAME,
                                'state'          => 0,
                                'status_name'    => $status,
                                'status_message' => 'ok',
                                'payment_type'   => 'deposition',
                                'response'       => [],
                            ];
                            $result_update_balance = $this->_api_transaction($operation_data);
                            if($result_update_balance['status'] == $status){
                                return $this->_external_show_message($this->MESSAGE_SUCCESS);
                            }
                        }
                    }
                }
            }
        }
        return $this->_external_show_message($this->MESSAGE_FAIL);
    }


    public function _response_info($options){
        is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
        if(empty($_operation_id)) {
            return false;
        }
        $payment_api = $this->payment_api;
        $secret = hash('sha256', uniqid($this->SECRET_ADD_STRING, true));

        //info from our site
        $shop = [
            'm_shop' => $this->API_ID_MERCHANT,
            'm_orderid' => $_operation_id,
            'm_amount' => $_amount,
            'm_curr' => 'USD',
            'm_desc' => base64_encode('some comment'),
            //'m_sign' => $secret,
            //'operation_id' => $_operation_id,
            //'secret' => $secret,
            //'ip' => common()->get_ip(),
        ];

        $shop['sign'] = strtoupper(hash('sha256', implode(':', array_merge($shop, array($this->API_PASS_MERCHANT)))));
        $shop = json_encode($shop);

        //payment system
        $ps = json_encode([
            'id' => 26808,
            'curr' => 'USD',
        ]);


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

        $url_options = [
            'post' => [
                'account' => $this->ACCOUNT,
                'apiId' => $this->API_ID,
                'apiPass' => $this->API_PASS,
                'action' => 'merchant',
                'lang' => 'ru',
                'shop' => $shop,
                'ps' => $ps,
            ],
        ];
        $request_result = common()->get_remote_page($this->URL_API, false, $url_options);
        $request_result_array = empty($request_result) ? '' : json_decode($request_result, true);

        $result = [
            'response' => [
                'datetime'      => $payment_api->sql_datetime(),
                'provider_name' => $this->PROVIDER_NAME,
                'full_url'      => $this->URL_API,
                'url_options'  => $url_options,
                //'secret'        => $secret,
                'operation_id'  => $_operation_id,
                //'callback'      => $callback,
                'result'        => $request_result,
                'amount'        => $_amount,
                //'amount_currency' => $amount_currency
            ]
        ];
        return $request_result_array;
    }


    public function _get_payment_systems() {
        $url_options = [
            'post' => [
                'account' => $this->ACCOUNT,
                'apiId' => $this->API_ID,
                'apiPass' => $this->API_PASS,
                'action' => 'getPaySystems',
            ],
        ];
        $request_result = common()->get_remote_page($this->URL_API, false, $url_options);
        return json_decode($request_result, true);

    }

    public function _api_deposition($options){
        if( !$this->ENABLE ) { return( null ); }
        $payment_api = $this->payment_api;
        is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
        if(empty($_operation_id)) {
            return false;
        }
        $result = $this->_api_transaction( $options );
        // update operation
        $operation = $payment_api->operation( [
            'operation_id' => $_operation_id,
        ]);
        $response_info = $this->_response_info($options, ['amount'=>$operation['amount']]);
        //$_SESSION['_api_deposition-response-info-'.time()] = $response_info;

        $data = [
            'operation_id'    => $_operation_id,
            'status_id'       => $operation['status_id'],
            'datetime_update' => $payment_api->sql_datetime(),
            'options'         => $response_info,
        ];

        $result = $payment_api->operation_update( $data );
        $payment_api->transaction_commit();
        if($result['status'] === true){
            $response_object = json_decode($response_info['response']['result'], true);
            //$address = $response_object['address'];
            //$result['show_address'] = 1;
            //$result['address'] = $address;
        }
        return( $result );
    }

    public function deposition( $options ) {
        if( !$this->ENABLE ) { return( null ); }
        $result = $this->_api_response( $options );
        return( $result );
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
