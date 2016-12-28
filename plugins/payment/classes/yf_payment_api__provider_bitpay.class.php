<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_bitpay extends yf_payment_api__provider_remote {

    public $PROVIDER_NAME = 'bitpay';
    public $XPUB = null;
    public $KEY = null;
    public $URL_API = 'https://bitpay.com/';
    public $IS_DEPOSITION = true;
    public $IS_PAYMENT    = true;

    public $url_server = '';

    public $SECRET_ADD_STRING= 'GDbfvheas66hnvdFGgsokrtvz';

    private $BITPAY_API_KEY = 'G7jtluR5fWv76QTENLjOCkUHbcBABwPe8kFJt7vU';
    private $BITPAY_API_TOKEN = '5fFzwFCw7ouLbNJVEKqsQew1gbLxDYcUcoJumvW8jafB';
    private $BITPAY_API_LABEL = 'Eloplay';

    public $MESSAGE_SUCCESS = 'ok';
    public $MESSAGE_FAIL = 'fail';
    public $IS_TESTNET = true;
    public $REDIRECT_URL_TESTNET = 'https://test.bitpay.com:443/invoice';
    public $REDIRECT_URL_LIVENET = 'https://bitpay.com:443/invoice';


    public $PUBLIC_KEY_FILE = '/var/www/default/config/bitpay.pub';
    public $PRIVATE_KEY_FILE = '/var/www/default/config/bitpay.pri';

    public $success_statuses = ['paid', 'confirmed','complete'];
    public $fail_statuses = ['false', 'invalid','expired'];
    public $partial_statuses = ['paidPartial', 'paidOver','paidLate'];

    public $service_allow = array(
        'bitpay',
    );

    public $method_allow = array(
        'order' => array(
            'payin' => array(
                'bitpay',
            ),
            'payout' => array(
                'bitpay',
            ),
        ),
        'payin' => array(
            'bitpay' => array(
                'title' => 'Bitpay',
                'icon'  => 'btc',
                'currency' => [
                    'USD' => [
                        'currency_id' => 'USD',
                        'active'      => true,
                    ],
                ],
            ),
        ),

        'payout' => array(
            'bitpay' => array(
                'title' => 'Bitpay',
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
                        'pattern'   => '^[13][A-Za-z0-9]{25,34}$',
                    ],
                ],
                'option_validation' => [
                    'to' => 'required|regex:~^[13][A-Za-z0-9]{25,34}$~u|xss_clean',
                ],
                'option_validation_message' => [
                    'to' => 'вы должны указать верный Bitcoin кошелёк',
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
            $this->url_server = url_user( '/api/payment/provider?name=bitpay&operation=response&server=true');
        }
        $allow = $this->allow();
        if( !$allow ) { return( false ); }
        parent::_init();
    }

    public function _api_response( $options ) {
        // import options
        is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
        /*
        invoice callback data example
        $_POST = [
            "id"=>"5d7SVfzbWpX6E5XRWDMQo6",
            "url"=>"https://bitpay.com/invoice?id=5d7SVfzbWpX6E5XRWDMQo6",
            "posData"=>json_encode(["secret"=>"fe4eac8b952fa873bed51166173f53e2","orderId"=>"40535"]),
            "status"=>"false",
            "btcPrice"=>"0.001044",
            "price"=>1.01,
            "currency"=>"USD",
            "invoiceTime"=>1111111111111,
            "expirationTime"=>1111111111111,
            "currentTime"=>1111111111111,
            "btcPaid"=>"0.0024",
            "rate"=>945,
            "exceptionStatus"=>false,
        ];
        */

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

    //process response from bitpay, where we get info about transaction
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
        $form =
            '<form id="_js_provider_bitpay_form" method="get" accept-charset="utf-8" action="' . $url . '" class="display: none;">
                <input type="hidden" name="id" value="'.$invoice_id.'" />
            </form>';
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


        $invoice_options = [
            'amount' => $amount_currency_total,
            'operation_id' => $operation_id,
            'operation_title' => $options['operation_title'],
            'currency' => $options['currency_id'],
        ];

        $invoice_id = $this->_create_invoice($invoice_options);

        if(!empty($invoice_id)){
            $form_url = $this->IS_TESTNET ? $this->REDIRECT_URL_TESTNET : $this->REDIRECT_URL_TESTNET;
            $form = $this->_form($invoice_id, $form_url);
            $result = [
                'form'           => $form,
                'status'         => true,
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



    public function _create_invoice($options) {
        $operation_id = $options['operation_id'];
        require_php_lib('bitpay');
        $storageEngine = new \Bitpay\Storage\FilesystemStorage();

        $privateKey = $storageEngine->load($this->PRIVATE_KEY_FILE);
        $publicKey = $storageEngine->load($this->PUBLIC_KEY_FILE);

        $client = new \Bitpay\Client\Client();

        //Use Testnet for test and Livenet for live application
        //Also you need register at test.bitpay.com, fill your account,
        //for get test bitcoin create wallet using https://copay.io/ application
        //and get bitcoins from https://testnet.coinfaucet.eu/en/

        if($this->IS_TESTNET) {
            $network = new \Bitpay\Network\Testnet();
        }
        else {
            $network = new \Bitpay\Network\Livenet();
        }
        $adapter = new \Bitpay\Client\Adapter\CurlAdapter();
        $client->setPrivateKey($privateKey);
        $client->setPublicKey($publicKey);
        $client->setNetwork($network);
        $client->setAdapter($adapter);


        $sin = $this->_get_sin();


        /*$token = $client->createToken(
            array(
                'id'          => (string) $sin,
                'pairingCode' => 'RbD5X1A',
                'label'       => 'test',
            )
        );

        //pairingCode get from https://bitpay.com/api-tokens (https://test.bitpay.com/dashboard/merchant/api-tokens)
        //it's need for api activation.
        //Then you need to save token into your app.

        */


        /*$token = $client->createToken(
            array(
                'facade'      => 'pos',
                'label'       => 'test',
                'id'          => (string)$sin,
            )
        );

        //This way create token and pairing code. In the token object token yo need get pairing code and activate in at server -
        //https://bitpay.com/api-tokens (https://test.bitpay.com/dashboard/merchant/api-tokens)
        //Then you need to save token into your app.
        */
        $token = new \Bitpay\Token();
        $token->setToken($this->BITPAY_API_TOKEN);
        $client->setToken($token);

        $invoice = new \Bitpay\Invoice();
        $item = new \Bitpay\Item();

        $secret = md5(uniqid($this->SECRET_ADD_STRING));
        $item->setCode($operation_id);
        $item->setDescription($options['operation_title']);
        $item->setPrice(floatval($options['amount']));

        $invoice->setNotificationUrl($this->url_server);
        $invoice->setOrderId($operation_id);
        $pos_data = ['secret'=>$secret, 'orderId'=>$operation_id];
        $invoice->setPosData(json_encode($pos_data));
        $invoice->setItem($item);
        $invoice->setCurrency(new \Bitpay\Currency($options['currency']));

        @$client->createInvoice($invoice);
        $payment_api = $this->payment_api;

        $id = $invoice->getId() ? :'';

        $operation_options = [
            'request' => [
                'invoice' => (array)$invoice,
                'secret'=>$secret,
                'invoice_id'=>$id,
                'datetime_update' => $payment_api->sql_datetime(),
            ]
        ];
        $update_data = [
            'operation_id'    => $operation_id,
            'options'         => $operation_options,
        ];
        $payment_api->operation_update( $update_data );
        return $id;
    }



    public function _create_keys() {
        require_php_lib('bitpay');
        $private_key = new \Bitpay\PrivateKey($this->PRIVATE_KEY_FILE);
        $public_key = new \Bitpay\PublicKey($this->PUBLIC_KEY_FILE);
        $private_key->generate();
        $public_key->setPrivateKey($private_key);
        $public_key->generate();
        $manager = new \Bitpay\KeyManager(new \Bitpay\Storage\FilesystemStorage());
        $manager->persist($private_key);
        $manager->persist($public_key);
    }

    public function _get_sin() {
        require_php_lib('bitpay');
        $storageEngine = new \Bitpay\Storage\FilesystemStorage();
        $publicKey = $storageEngine->load($this->PUBLIC_KEY_FILE);
        $sin = \Bitpay\SinKey::create()->setPublicKey($publicKey)->generate();
        return $sin;
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
