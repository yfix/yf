<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_bitcoin extends yf_payment_api__provider_remote {

    public $PROVIDER_NAME = 'bitcoin';
    public $XPUB = null;
    public $KEY = null;
    public $URL_API = 'https://api.blockchain.info/v2/receive';
    public $IS_DEPOSITION = true;
    public $IS_PAYMENT    = true;

    public $url_server = '';

    public $SECRET_ADD_STRING= 'GDbfvheas66hnvdFGgsokrtvz';

    public $MESSAGE_SUCCESS = 'ok';
    public $MESSAGE_FAIL = 'fail';

    public $service_allow = array(
        'bitcoin',
    );

    public $method_allow = array(
        'order' => array(
            'payin' => array(
                'blockchain',
            ),
            'payout' => array(
                'blockchain',
            ),
        ),
        'payin' => array(
            'blockchain' => array(
                'title' => 'Bitcoin',
                'icon'  => 'btc',
                'currency' => [
                    'BTC' => [
                        'currency_id' => 'BTC',
                        'active'      => true,
                    ],
                ],
            ),
        ),

        /*
         * http://localhost:3000/merchant/$guid/payment?password=$main_password&second_password=$second_password&to=$address&amount=$amount&from=$from&fee=$fee&note=$note

$main_password Your Main Blockchain Wallet password
$second_password Your second Blockchain Wallet password if double encryption is enabled.
$to Recipient Bitcoin Address.
$amount Amount to send in satoshi.
$from Send from a specific Bitcoin Address (Optional)
$fee Transaction fee value in satoshi (Must be greater than default fee) (Optional)
$note A public note to include with the transaction -- can only be attached when outputs are greater than 0.005 BTC. (Optional)
RESPONSE:
{ "message" : "Response Message" , "tx_hash": "Transaction Hash", "notice" : "Additional Message" }
{ "message" : "Sent 0.1 BTC to 1A8JiWcwvpY7tAopUkSnGuEYHmzGYfZPiq" , "tx_hash" : "f322d01ad784e5deeb25464a5781c3b20971c1863679ca506e702e3e33c18e9c" , "notice" : "Some funds are pending confirmation and cannot be spent yet (Value 0.001 BTC)" }
         */

        'payout' => array(
            'blockchain' => array(
                'title' => 'Bitcoin',
                'icon'  => 'btc',
                'currency' => array(
                    'BTC' => array(
                        'currency_id' => 'BTC',
                        'active'      => true,
                    ),
                ),
                'field' => [
                    '$main_password',
                    '$second_password',
                    '$to',
                    '$amount',
                    '$from',
                    '$fee',
                    '$note',
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
                        'pattern'   => '^[13][A-Za-z0-9]{25.34}$',
                    ],
                ],
                'option_validation' => [
                    'to' => 'required|minlength[26]maxlength[35]|xss_clean',
                ],
                'option_validation_message' => [
                    'to' => 'обязательное поле: адрес вашего Bitcoin кошелька',
                ],

            ),
        ),
    );

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
            $this->url_server = url_user( '/api/payment/provider?name=bitcoin&operation=response&server=true');
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

    //process response from blockchain.info, where we get info about transaction
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

    //create at blockchain.info address for transfer
    public function _get_wallet($options){
        //$_SESSION['wallet_options'] = $options;
        is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
        if(empty($_operation_id)) {
            return false;
        }
        $payment_api = $this->payment_api;
        $secret = hash('sha256', uniqid($this->SECRET_ADD_STRING, true));
        $callback = $this->url_server.'&operation_id='.$_operation_id.'&secret='.$secret;
        //$callback = 'https://eloplay.com/payment/bitcoin?operation_id='.$operation_id.'&secret='.$secret;
        $full_url = $this->URL_API.'?xpub='.urlencode($this->XPUB).'&callback='.urlencode($callback).'&key='.urlencode($this->KEY);
        $request_result = common()->get_remote_page($full_url, false);
        $request_result_array = empty($request_result) ? '' : json_decode($request_result, true);
        $amount_currency = $payment_api->currency_conversion( [
            'type'        => 'buy',
            'currency_id' => 'BTC',
            'amount'      => $_amount,
        ]);
        $result = [
            'response' => [
                'datetime'      => $payment_api->sql_datetime(),
                'provider_name' => $this->PROVIDER_NAME,
                'full_url'      => $full_url,
                'secret'        => $secret,
                'operation_id'  => $_operation_id,
                'callback'      => $callback,
                'result'        => $request_result,
                'amount'        => $_amount,
                'amount_currency' => $amount_currency
            ]
        ];
        return $result;
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
        $wallet = $this->_get_wallet(array_merge($options, ['amount'=>$operation['amount']]));

        $data = [
            'operation_id'    => $_operation_id,
            'status_id'       => $operation['status_id'],
            'datetime_update' => $payment_api->sql_datetime(),
            'options'         => $wallet,
        ];

        //$_SESSION['_api_deposition-wallet-'.time()] = $wallet;
        $result = $payment_api->operation_update( $data );
        //$_SESSION['_api_deposition-result-'.time()] = $result;
        $payment_api->transaction_commit();
        if($result['status'] === true){
            $wallet_object = json_decode($wallet['response']['result'], true);
            $address = $wallet_object['address'];
            $result['show_address'] = 1;
            $result['address'] = $address;
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
