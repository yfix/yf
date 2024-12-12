<?php

if ( ! function_exists('array_replace_recursive')) {
    trigger_error('Not exists function "array_replace_recursive ( PHP 5 >= 5.3.0 )"', E_USER_ERROR);
}

/*
    default currency:
    exchange:
        1 unit = 1 cent
    options:
        id          : UNT
        name        : балл(ы|ов)
        sign        : б.
        number      : 0
        minor_units : 0 { Number of digits after the decimal separator }

    db:
        payment_account
            account_id
            account_type_id
            user_id
            currency_id
            balance
            title
            description
            datetime_create
            datetime_update

        payment_account_type
                { main, deposit, credit, etc }
            account_type_id
            name
            title
            options
                { icon, image }

        payment_currency_rate
            currency_rate_id
            datetime
            from
            to
            from_value
            to_value

        payment_operation
            operation_id
            account_id
            provider_id
                { system, administration, webmoney, privat24 }
            direction
                { in (приход), out (расход) }
            type_id
            status_id
            amount
            title
            description
            datetime_start
            datetime_finish
            datetime_update
            options

        payment_provider
                { system, administration, webmoney, privat24 }
            provider_id
            name
            title
            options
                { icon, image }
            system
            active
            order

        payment_status
                { in_progress, success, refused }
            status_id
            name
            title
            options
                { icon, image }

        payment_type
                {
                    deposition : пополнение счета (приход)
                    payment    : платежи со счета (расход)
                    exchange   : обмен валют
                    transfers  : переводы p2p
                }
            type_id
            name
            title
            options
                { icon, image }
            active

*/

class yf_payment_api
{
    public $user_id_default = null;
    public $user_id = null;

    public $account_id = null;
    public $account = null;

    public $account_type_id_default = 'main';
    public $account_type_id = null;
    public $account_type = null;

    public $currency_id_default = 'UNT';
    public $currency_id = null;
    public $currency = null;
    public $currencies = null;
    public $currencies_default = [
        'UNT' => [
            'currency_id' => 'UNT',
            'name' => 'балл',
            'short' => 'балл.',
            'sign' => 'б.',
            'number' => 0,
            'minor_units' => 0,      // Number of digits after the decimal separator
        ],
        'USD' => [
            'currency_id' => 'USD',
            'name' => 'Доллар США',
            'short' => 'доллар',
            'sign' => '$',
            'number' => 840,
            'minor_units' => 2,
        ],
        'EUR' => [
            'currency_id' => 'EUR',
            'name' => 'Евро',
            'short' => 'евро',
            'sign' => '€',
            'number' => 978,
            'minor_units' => 2,
        ],
        'UAH' => [
            'currency_id' => 'UAH',
            'name' => 'Украинская гривна',
            'short' => 'грн',
            'sign' => '₴',
            'number' => 980,
            'minor_units' => 2,
        ],
        'RUB' => [
            'currency_id' => 'RUB',
            'name' => 'Российский рубль',
            'short' => 'руб',
            'sign' => 'р.',
            'number' => 643,
            'minor_units' => 2,
        ],
    ];

    public $payout_currency_allow = [
        'USD',
        'UAH',
        'RUB',
    ];

    public $provider_id = null;
    public $provider = null;
    public $provider_index = null;

    public $type_id = null;
    public $type_name = null;
    public $type = null;
    public $type_index = null;

    public $status = null;
    public $status_index = null;

    public $DECIMALS = 2;
    public $DECIMAL_POINT = ',';
    public $THOUSANDS_SEPARATOR = '&nbsp;';

    public $CONFIG = null;
    public $OPERATION_LIMIT = 10;
    public $IS_BALANCE_LIMIT_LOWER = true;
    public $BALANCE_LIMIT_LOWER = 0;
    public $PAYOUT_LIMIT_MIN = 0;

    public $IS_PAYOUT_CONFIRMATION = false;
    public $CONFIRMATION_TIME = '-6 hour';

    public $DEPOSITION_TIME = '-6 hour';

    public $SECURITY_CODE = '';

    public $MAIL_COPY_TO = null;
    public $TX_VAR = 'tx_isolation';
    //  example:
    // array(
    // 'all' => array(
    // 'all'   => 'larv.job+payment@gmail.com',
    // 'payin' => array(
    // 'larv.job+payin@gmail.com',
    // ),
    // 'payout' => array(
    // 'larv.job+payout@gmail.com',
    // ),
    // 'success' => array(
    // 'larv.job+payment.success@gmail.com',
    // ),
    // 'refused' => array(
    // 'larv.job+payment.refused@gmail.com',
    // ),
    // 'request' => array(
    // 'larv.job+payment.request@gmail.com',
    // ),
    // 'error' => array(
    // 'larv.job+payment.error@gmail.com',
    // ),
    // ),
    // 'payin' => array(
    // 'all' => array(
    // 'larv.job+payin@gmail.com',
    // ),
    // 'success' => array(
    // 'larv.job+payin.success@gmail.com',
    // ),
    // 'refused' => array(
    // 'larv.job+payin.refused@gmail.com',
    // ),
    // 'error' => array(
    // 'larv.job+payin.error@gmail.com',
    // ),
    // ),
    // 'payout' => array(
    // 'all' => array(
    // 'larv.job+payin@gmail.com',
    // ),
    // 'success' => array(
    // 'larv.job+payin.success@gmail.com',
    // ),
    // 'refused' => array(
    // 'larv.job+payin.refused@gmail.com',
    // ),
    // 'request' => array(
    // 'larv.job+payin.request@gmail.com',
    // ),
    // 'error' => array(
    // 'larv.job+payin.error@gmail.com',
    // ),
    // ),
    // );

    public $transaction = null;

    public $_class_path = '';

    public $DUMP_PATH = '/tmp';
    public $dump = null;

    public function _init()
    {
        $this->config();
        $this->user_id_default = (int) main()->USER_ID;
        $this->user_id($this->user_id_default);
        $mysql_version = db()->get_one('SELECT VERSION()');
        if($mysql_version >= 8){
            $this->TX_VAR = 'transaction_isolation';
        }
    }

    public function config($options = null)
    {
        ! empty($options) && $this->CONFIG = $options;
        $config = &$this->CONFIG;
        if (is_array($config)) {
            foreach ($config as $key => $item) {
                if (is_array($this->$key)) {
                    $this->$key = $this->_replace($this->$key, $item);
                } else {
                    $this->$key = $item;
                }
            }
        }
        // setup currencies
        $this->currencies = $this->_replace($this->currencies_default, $this->currencies);
    }

    public function user_id($value = -1)
    {
        $object = &$this->user_id;
        if ($value !== -1 && $this->check_user_id($value)) {
            $object = $value;
        }
        return  $object;
    }

    public function check_user_id($value = null)
    {
        if (empty($value) || $value != (int) $value || $value < 0) {
            return  null;
        }
        return  $value;
    }

    public function account_type($value = -1)
    {
        $id = &$this->account_type_id;
        $object = &$this->account_type;
        if ($value !== -1) {
            list($id, $object) = $value;
        }
        return  [$id, $object];
    }

    public function currency($value = -1)
    {
        $id = &$this->currency_id;
        $object = &$this->currency;
        if ($value !== -1) {
            list($id, $object) = $value;
        }
        return  [$id, $object];
    }

    public function get_account_type__by_name($options = null)
    {
        $_ = &$options;
        $name = $_['name'] ?: $this->account_type_id_default;
        $result = db()->table('payment_account_type')
            ->where('name', '=', _es($name))
            ->get_deep_array(1);
        if (empty($result)) {
            $account_type = null;
            $account_type_id = null;
        } else {
            $account_type = reset($result);
            $account_type_id = $account_type['account_type_id'];
        }
        return  [$account_type_id, $account_type];
    }

    public function get_currency__by_id($options = null)
    {
        $_ = &$options;
        $to_set = $_['to_set'] ?? null;
        $currency_id = ( $_['currency_id'] ?? null )
            ?: $this->currency_id
            ?: $this->currency_id_default;
        $result = $this->currencies[$currency_id];
        if (empty($result)) {
            $currency = null;
            $currency_id = null;
        } else {
            $currency = $result;
        }
        if ($to_set) {
            $this->currency = $currency;
            $this->currency_id = $currency_id;
        }
        return  [$currency_id, $currency];
    }

    public function get_account__by_id($options = null)
    {
        $account = &$this->account;
        $account_id = $this->account_id;
        // cache
        if (empty($options['force']) && ! empty($account[$account_id])) {
            return  [$account_id, $account];
        }
        // get from db
        $_ = &$options;
        $account_id = (int) ( $_['account_id'] ?? 0 );
        if (empty($account_id)) {
            return  null;
        }
        $result = db()->table('payment_account')
            ->where('account_id', '=', $account_id)
            ->order_by('account_id')
            ->get_deep_array(1);
        if (empty($result)) {
            $account = null;
            $account_id = null;
        } else {
            $account = &$result[$account_id];
        }
        $this->account = $account;
        $this->account_id = $account_id;
        // get currency
        $account_id && $this->get_currency__by_id([
            'to_set' => true,
            'currency_id' => $account['currency_id'],
        ]);
        return  [$account_id, $account];
    }

    public function currency_rates__buy($options = null)
    {
        $_ = (array) $options;
        $_['type'] = 'buy';
        $result = $this->currency_rates($_);
        return  $result;
    }

    public function currency_rates__sell($options = null)
    {
        $_ = (array) $options;
        $_['type'] = 'sell';
        $result = $this->currency_rates($_);
        return  $result;
    }

    public function currency_rates($options = null)
    {
        $_ = &$options;
        // default
        $currency_id = $_['currency_id']
            ?: $this->currency_id
            ?: $this->currency_id_default;
        $_['currency_id'] = &$currency_id;
        $type = @$_['type'] == 'sell' ? 'sell' : 'buy';
        $_['type'] = &$type;
        // start
        $currency__api = _class('payment_api__currency');
        $result = $currency__api->rates($options);
        return  $result;
    }

    public function currency_rates__provider($options = null)
    {
        $currency__api = _class('payment_api__currency');
        $result = $currency__api->provider($options);
        return  $result;
    }

    public function currency_rate($options = null)
    {
        $currency__api = _class('payment_api__currency');
        $result = $currency__api->rate($options);
        return  $result;
    }

    public function currency_load_rate($options = null)
    {
        $currency__api = _class('payment_api__currency');
        $result = $currency__api->load_rate($options);
        return  $result;
    }

    public function fee($amount, $fee)
    {
        $rt = 0;
        $fix = 0;
        $min = 0;
        if (is_array($fee)) {
            $rt = @$fee['rt'] ?: $rt;
            $fix = @$fee['fix'] ?: $fix;
            $min = @$fee['min'] ?: $min;
        } else {
            $rt = @$fee ?: $rt;
        }
        $result = $amount + $amount * ($rt / 100) + $fix;
        ($min > 0 && $min > $result) && $result = $min;
        return  $result;
    }

    public function currency_conversion($options = null)
    {
        $_ = &$options;
        $conversion_type = @$_['type'] == 'sell' ? 'sell' : 'buy';
        $currency_id = @$_['currency_id'];
        $amount = @$_['amount'];
        if (empty($currency_id) || empty($amount)) {
            return  null;
        }
        // rate
        $currency_rate = $this->currency_rates([
            'type' => $conversion_type,
        ]);
        if (empty($currency_rate[$currency_id])) {
            return  null;
        }
        // calc
        $rate = $currency_rate[$currency_id]['rate'];
        $value = $currency_rate[$currency_id]['value'];
        $result = $amount * $rate / $value;
        // round
        list($currency_id, $currency) = $this->get_currency__by_id([
            'currency_id' => $currency_id,
        ]);
        if (empty($currency_id)) {
            return  null;
        }
        $result = $this->_number_float($result, $currency['minor_units']);
        return  $result;
    }

    public function sql_datetime($timestamp = null)
    {
        $tpl = 'Y-m-d H:i:s';
        if (is_int($timestamp)) {
            $result = date($tpl, $timestamp);
        } else {
            $result = date($tpl);
        }
        return  $result;
    }

    public function account_create($options = null)
    {
        // options
        $_ = &$options;
        $data = [];
        // user_id
        $value = $this->_default([
            $_['user_id'],
            $this->user_id,
        ]);
        $value = $this->check_user_id($value);
        if (empty($value)) {
            return  null;
        }
        $data['user_id'] = $value;
        // account_type_id
        $value = (int) $_['account_type_id'] ?: $this->account_type_id;
        empty($value) && (list($value) = $this->get_account_type__by_name($options));
        if (empty($value)) {
            return  null;
        }
        $data['account_type_id'] = $value;
        // currency_id
        $value = (int) $_['currency_id'] ?: $this->currency_id;
        empty($value) && list($value) = $this->get_currency__by_id();
        if (empty($value)) {
            return  null;
        }
        $data['currency_id'] = $value;
        // balance
        $data['balance'] = 0;
        // date
        $value = $this->sql_datetime();
        $data['datetime_create'] = $value;
        $data['datetime_update'] = $value;
        // create
        $id = db()->table('payment_account')->insert(_es($data));
        if ($id < 1) {
            return  null;
        }
        $result = $this->get_account__by_id(['account_id' => $id]);
        return  $result;
    }

    public function account($options = null)
    {
        // by account_id
        $result = $this->get_account__by_id($options);
        if ( ! empty($result)) {
            return  $result;
        }
        // options
        $_ = &$options;
        $db = db()->table('payment_account')->order_by('account_id');
        // user_id
        $value = $this->_default([
            ( $_['user_id'] ?? null ),
            $this->user_id,
        ]);
        $value = $this->check_user_id($value);
        if (empty($value)) {
            return  null;
        }
        $this->user_id($value);
        $db->where('user_id', '=', _es($value));
        $options['user_id'] = $value;
        // by account_type_id
        $value = (int) $_['account_type_id'] ?: $this->account_type_id;
        empty($value) && (list($value) = $this->get_account_type__by_name($options));
        if (empty($value)) {
            return  null;
        }
        $db->where('account_type_id', '=', _es($value));
        $options['account_type_id'] = $value;
        // by currency_id
        $value = $_['currency_id'] ?: $this->currency_id;
        empty($value) && list($value) = $this->get_currency__by_id();
        if (empty($value)) {
            return  null;
        }
        $db->where('currency_id', '=', _es($value));
        $options['currency_id'] = $value;
        // get from db
        $result = $db->get_deep_array(1);
        if (empty($result)) {
            // account not exists
            list($account_id, $account) = $this->account_create($options);
        } else {
            $account = reset($result);
            $account_id = $account['account_id'];
        }
        $this->account = $account;
        $this->account_id = (int) $account_id;
        // get currency
        $account_id && $result = $this->get_currency__by_id([
            'currency_id' => $account['currency_id'],
        ]);
        return  [$account_id, $account];
    }

    public function get_account($options = null)
    {
        $result = $this->account($options);
        list($account_id, $account) = $result;
        if ($account_id <= 0) {
            $result = [
                'status' => false,
                'status_message' => 'Счет не существует',
            ];
        }
        return  $result;
    }

    public function get_balance($options = null)
    {
        $result = $this->get_account($options);
        list($account_id, $account) = $result;
        // need user authentication
        if ($account_id <= 0) {
            return  $result;
        }
        $value = $account['balance'];
        // prepare balance
        $decimals = $this->currency['minor_units'];
        $balance = $this->_number_float($value, $decimals);
        return  [$balance, $result];
    }

    public function type($options = null)
    {
        // get type
        $type = $this->type;
        $type_index = $this->type_index;
        if (empty($type)) {
            $type = db()->table('payment_type')->where('active', 1)->get_deep_array(1);
            foreach ((array) $type as $index => $item) {
                $id = (int) $item['type_id'];
                $name = $item['name'];
                $type_index['name'][$name][$id] = &$type[$id];
            }
        }
        // options
        $_ = &$options;
        $exists = $_['exists'];
        $type_id = $_['type_id'];
        $name = $_['name'];
        // test: exists by type_id
        if ( ! empty($exists)) {
            $result = ! empty($type[$exists]);
        }
        // by type_id
        elseif ( ! empty($type_id)) {
            $result = [$type_id => $type[$type_id]];
        }
        // by name
        elseif ( ! empty($name)) {
            $result = $type_index['name'][$name];
        }
        // by default: all
        else {
            $result = $type;
        }
        return  $result;
    }

    public function get_type($options = null)
    {
        $_ = &$options;
        $object = $this->type($options);
        if (empty($object)) {
            $name = $_['exists'] ?: $_['type_id'] ?: $_['name'];
            $result = [
                'status' => false,
                'status_message' => 'Тип платежей не существует: "' . $name . '"',
            ];
            return  $result;
        }
        if (count($object) == 1) {
            $object = reset($object);
            $object_id = (int) $object['type_id'];
            $result = [$object_id, $object];
        } else {
            $result = $object;
        }
        return  $result;
    }

    public function status($options = null)
    {
        // get status
        $status = $this->status;
        $status_index = $this->status_index;
        if (empty($status)) {
            $status = db()->table('payment_status')->get_deep_array(1);
            if (empty($status)) {
                $status = null;
                $status_index = null;
                return  $status;
            }
            foreach ((array) $status as $index => $item) {
                $id = (int) $item['status_id'];
                $name = $item['name'];
                $status_index['name'][$name][$id] = &$status[$id];
            }
        }
        // options
        $_ = &$options;
        $exists = ( $_['exists'] ?? null );
        $status_id = ( $_['status_id'] ?? null );
        $name = ( $_['name'] ?? null );
        // test: exists by status_id
        if ( ! empty($exists)) {
            $result = ! empty($status[$exists]);
        }
        // by status_id
        elseif ( ! empty($status_id)) {
            $result = [$status_id => $status[$status_id]];
        }
        // by name
        elseif ( ! empty($name)) {
            $result = $status_index['name'][$name];
        }
        // by default: all
        else {
            $result = $status;
        }
        return  $result;
    }

    public function get_status($options = null)
    {
        $_ = &$options;
        $payment_status = $this->status($options);
        if (empty($payment_status)) {
            $name = $_['exists'] ?: $_['status_id'] ?: $_['name'];
            $result = [
                'status' => false,
                'status_message' => 'Статус не существует: "' . $name . '"',
            ];
            return  $result;
        }
        if (count($payment_status) == 1) {
            $payment_status = reset($payment_status);
            $payment_status_id = (int) $payment_status['status_id'];
            $result = [$payment_status_id, $payment_status];
        } else {
            $result = $payment_status;
        }
        return  $result;
    }

    public function provider_class($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        $result = null;
        if ( ! empty($_provider_name)) {
            $class_name = 'provider_' . $_provider_name;
            $class = $this->_class($class_name);
            if ( ! ($class && $provider_class->ENABLE)) {
                $result = $class;
            }
        }
        return  $result;
    }

    public function provider($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // get providers
        $provider = $this->provider;
        $provider_index = $this->provider_index;
        if (empty($provider)) {
            $is_admin = main()->ADMIN_ID > 0;
            $active = $is_admin || $_is_service ? 1 : 2;
            $provider = db()->table('payment_provider')
                ->where('active', '>=', $active)
                ->order_by('order')
                ->get_deep_array(1);
            if (empty($provider)) {
                $provider = null;
                $provider_index = null;
                return  $provider;
            }
            foreach ((array) $provider as $index => $item) {
                $name = $item['name'];
                $id = (int) $item['provider_id'];
                $provider_index['all'][$id] = &$provider[$id];
                $class = 'provider_' . $name;
                $provider_class = $this->_class($class);
                if ( ! ($provider_class && $provider_class->ENABLE)) {
                    unset($provider[$index]);
                    continue;
                }
                $system = (int) $item['system'];
                $provider_index['system'][$system][$id] = &$provider[$id];
                $provider_index['name'][$name][$id] = &$provider[$id];
            }
        }
        /**
         * options
         * $_all
         * $_exists
         * $_provider_id
         * $_name
         * $_system.
         */
        // test: exists by provider_id
        if ( ! empty($_exists)) {
            $result = ! empty($provider[$exists]);
        }
        // all
        elseif ( ! empty($_all)) {
            $result = $provider_index['all'];
        }
        // by provider_id
        elseif (isset($_provider_id)) {
            $provider[$_provider_id] && $result = [$_provider_id => $provider[$_provider_id]];
        }
        // by name
        elseif ( ! empty($_name)) {
            $result = $provider_index['name'][$_name];
        }
        // by system
        elseif (isset($_system)) {
            $result = $provider_index['system'][(int) $_system];
        }
        // by default: all, not system
        else {
            $result = $provider_index['system'][0];
        }
        if (is_array($result)) {
            foreach ($result as $index => $item) {
                $_options = &$result[$index]['options'];
                $_options && $_options = (array) json_decode($_options, true);
            }
        }
        return  $result;
    }

    public function is_provider($options = null)
    {
        $result = null;
        $object = $this->provider($options);
        if (is_array($object) && count($object) == 1) {
            return  $object;
        }
        return  $result;
    }

    public function get_provider($options = null)
    {
        $_ = &$options;
        $object = $this->provider($options);
        $object_id_name = 'provider_id';
        if (empty($object)) {
            $name = $_['exists'] ?: $_[$object_id_name] ?: $_['name'];
            $result = [
                'status' => false,
                'status_message' => 'Провайдер не существует: "' . $name . '"',
            ];
            return  $result;
        }
        if (count($object) == 1) {
            $object = reset($object);
            $object_id = (int) $object[$object_id_name];
            $result = [$object_id, $object];
        } else {
            $result = $object;
        }
        return  $result;
    }

    public function provider_options(&$provider, $options = null)
    {
        if ( ! isset($options) || ! is_array($provider)) {
            return  false;
        }
        foreach ($provider as $id => $item) {
            $name = $item['name'];
            $class = 'provider_' . $name;
            $provider_class = $this->_class($class);
            if (empty($provider_class)) {
                continue;
            }
            foreach ($options as $item) {
                $provider[$id]['_' . $item] = &$provider_class->{$item};
            }
        }
        return  true;
    }

    /*
        account_id by user_id
        provider_id
        direction             { in (приход) }
        type_id               { deposition : пополнение счета (приход) }
        status_id             { in_progress, success, refused }

        example:
            $payment_api = _class( 'payment_api' );
            $options = array(
                'user_id'         => $user_id,
                'amount'          => '10',
                'operation_title' => 'Пополнение счета',
                'operation'       => 'deposition', // or 'payment'
                'provider_name'   => 'system', // or 'administration', etc
            );
            $result = $payment_api->transaction( $options );
     */
    public function transaction($options = null)
    {
        $_ = &$options;
        $operation = $_['operation'];
        if ( ! in_array($operation, ['payment', 'deposition'])) {
            $result = [
                'status' => false,
                'status_message' => 'Неизвестная операция: ' . $operation,
            ];
            return  $result;
        }
        $result = $this->$operation($options);
        return  $result;
    }

    /*
        example:
            $payment_api = _class( 'payment_api' );
            $options = array(
                'user_id'         => $user_id,
                'amount'          => '10',
                'operation_title' => 'Пополнение счета',
                'operation'       => 'deposition', // or 'payment'
            );
            $result = $payment_api->transaction_system( $options );
     */
    public function transaction_system($options = null)
    {
        $_ = &$options;
        $operation = $_['operation'] . '_system';
        if ( ! in_array($operation, ['payment_system', 'deposition_system'])) {
            $result = [
                'status' => false,
                'status_message' => 'Неизвестная операция: ' . $operation,
            ];
            return  $result;
        }
        $result = $this->$operation($options);
        return  $result;
    }

    /*
        example:
            $payment_api = _class( 'payment_api' );
            $options = array(
                'user_id'         => $user_id,
                'amount'          => '10',
                'operation_title' => 'Пополнение счета',
            );
            $result = $payment_api->deposition_system( $options );
     */
    public function deposition_system($options = null)
    {
        $options['provider_name'] = 'system';
        $result = $this->deposition($options);
        return  $result;
    }
    public function deposition_user($options = null)
    {
        $options['user_mode'] = true;
        $result = $this->deposition($options);
        return  $result;
    }
    public function deposition($options = null)
    {
        $_ = &$options;
        $is_transaction = isset( $_['is_transaction'] ) ? $_['is_transaction'] : true;
        $_['type_name'] = __FUNCTION__;
        $_['operation_title'] = $_['operation_title'] ?: 'Пополнение счета';
        $result = $this->_operation_check($options);
        list($status, $data, $operation_data) = $result;
        if (empty($status)) {
            return  $result;
        }
        // update payment operation
        $title = $_['operation_title'];
        $data += [
            'direction' => 'in',
            'title' => $title,
        ];
        // create operation
        $status = db()->table('payment_operation')->insert($data);
        if (empty($status)) {
            $result = [
                'status' => false,
                'status_message' => 'Ошибка при создании операции',
            ];
            return  $result;
        }
        $operation_id = (int) $status;
        $data['operation_id'] = $operation_id;
        // try provider operation
        $provider_class = 'provider_' . $operation_data['provider']['name'];
        $result = $this->_class($provider_class, __FUNCTION__, [
            'is_transaction' => $is_transaction,
            'options' => $options,
            'provider' => $operation_data['provider'],
            'data' => $data,
            'operation_data' => $operation_data,
        ]);
        $result['operation_id'] = $operation_id;
        return  $result;
    }

    /*
        example:
            $payment_api = _class( 'payment_api' );
            $options = array(
                'user_id'         => $user_id,
                'amount'          => '10',
                'operation_title' => 'Пополнение счета',
            );
            $result = $payment_api->payment_system( $options );
     */
    public function payment_system($options = null)
    {
        $options['provider_name'] = 'system';
        $result = $this->payment($options);
        return  $result;
    }
    public function payment_user($options = null)
    {
        $options['user_mode'] = true;
        $result = $this->payment($options);
        return  $result;
    }
    public function payment($options = null)
    {
        $_ = &$options;
        $is_transaction = isset( $_['is_transaction'] ) ? $_['is_transaction'] : true;
        $_['type_name'] = __FUNCTION__;
        $_['operation_title'] = $_['operation_title'] ?: 'Оплата';
        $result = $this->_operation_check($options);
        list($status, $data, $operation_data) = $result;
        if (empty($status)) {
            return  $result;
        }
        // update payment operation
        $title = $_['operation_title'];
        $data += [
            'direction' => 'out',
            'title' => $title,
        ];
        // create operation
        $is_transaction && db()->begin();
        $status = db()->table('payment_operation')->insert($data);
        if (empty($status)) {
            $result = [
                'status' => false,
                'status_message' => 'Ошибка при создании операции',
            ];
            return  $result;
        }
        $operation_id = (int) $status;
        $data['operation_id'] = $operation_id;
        // user confirmation
        $result = $this->confirmation($options, $data, $operation_data);
        if ( ! @$result['status']) {
            $is_transaction && db()->rollback();
            return  $result;
        }
        $is_transaction && db()->commit();
        // try provider operation
        $provider_class = 'provider_' . $operation_data['provider']['name'];
        $result = $this->_class($provider_class, __FUNCTION__, [
            'is_transaction' => $is_transaction,
            'options' => $options,
            'provider' => $operation_data['provider'],
            'data' => $data,
            'operation_data' => $operation_data,
        ]);
        $result['operation_id'] = $operation_id;
        return  $result;
    }

    /*
        example:
            $payment_api = _class( 'payment_api' );
            $options = array(
                'user_id'         => $user_id,
                'amount'          => '10',
                'operation_title' => 'Перевод',
            );
            $result = $payment_api->transfer_system( $options );
     */
    public function transfer_system($options = null)
    {
        $options['provider_name'] = 'system';
        $result = $this->transfer($options);
        return  $result;
    }
    public function transfer_user($options = null)
    {
        $options['user_mode'] = true;
        $result = $this->transfer($options);
        return  $result;
    }
    public function transfer($options = null)
    {
        $_ = &$options;
        $is_transaction = isset( $_['is_transaction'] ) ? $_['is_transaction'] : true;
        // check: from, to
        if ( ! @$_['from'] || ! @$_['to']) {
            $result = [
                'status' => false,
                'status_message' => 'Недостаточно данных',
            ];
            return  $result;
        }
        // prepare
        $_['type_name'] = __FUNCTION__;
        $_['operation_title'] = $_['operation_title'] ?: 'Перевод';
        $data = $_;
        unset($data['from'], $data['to']);
        // prepare operation
        $options_from = $data;
        $options_from['user_id'] = (int) $_['from']['user_id'];
        $options_from['direction'] = 'out';
        $options_to = $data;
        $options_to['user_id'] = (int) $_['to']['user_id'];
        $options_to['direction'] = 'in';
        // prepare to operation
        $result = $this->_operation_check($options_from);
        list($status, $data_from, $operation_data_from) = $result;
        if (empty($status)) {
            return  $result;
        }
        $result = $this->_operation_check($options_to);
        list($status, $data_to, $operation_data_to) = $result;
        if (empty($status)) {
            return  $result;
        }
        // check currency
        $currency_id_from = &$operation_data_from['account']['currency_id'];
        $currency_id_to = &$operation_data_to['account']['currency_id'];
        if ($currency_id_from != $currency_id_to) {
            $result = [
                'status' => false,
                'status_message' => 'Ошибка: валюты должны, быть одинаковые',
            ];
            return  $result;
        }
        // update payment operation
        $title = $_['operation_title'];
        $data_from += [
            'direction' => 'out',
            'title' => $title,
        ];
        $data_to += [
            'direction' => 'in',
            'title' => $title,
        ];
        // create operation
        $is_transaction && db()->begin();
        $status_from = db()->table('payment_operation')->insert($data_from);
        $status_to = db()->table('payment_operation')->insert($data_to);
        if ( ! @$status_from || ! @$status_to) {
            $is_transaction && db()->rollback();
            $result = [
                'status' => false,
                'status_message' => 'Ошибка при создании операции',
            ];
            return  $result;
        }
        // get operation_id
        $operation_id_from = (int) $status_from;
        $data_from['operation_id'] = $operation_id_from;
        $operation_id_to = (int) $status_to;
        $data_to['operation_id'] = $operation_id_to;
        $is_transaction && db()->commit();
        // try provider operation
        $object = $this->provider_class($options);
        if (empty($object)) {
            $result = [
                'status' => false,
                'status_message' => 'Неизвестный класс провайдера',
            ];
            return  $result;
        }
        $result = $object->{ __FUNCTION__ }([
            'is_transaction' => $is_transaction,
            'options' => $options,
            'provider' => $operation_data_from['provider'],
            'data' => [
                'from' => $data_from,
                'to' => $data_to,
            ],
            'operation_data' => [
                'from' => $operation_data_from,
                'to' => $operation_data_to,
            ],
        ]);
        $result['operation_id_from'] = $operation_id_from;
        $result['operation_id_to'] = $operation_id_to;
        return  $result;
    }

    public function cancel_user($options = null)
    {
        $options['user_mode'] = true;
        $result = $this->cancel($options);
        return  $result;
    }

    public function expired($options = null)
    {
        $options['status_name'] = 'expired';
        $result = $this->cancel($options);
        return  $result;
    }

    public function cancel($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // var
        if (@$_operation_id < 1) {
            $result = [
                'status' => false,
                'status_message' => 'Неверный код операции',
            ];
            return  $result;
        }
        $this->transaction_start($options);
        // operation
        $operation = $this->operation([
            'operation_id' => $_operation_id,
        ]);
        if (empty($operation)) {
            $result = [
                'status' => false,
                'status_message' => 'Операция отсутствует: ' . $_operation_id,
            ];
            $this->transaction_rollback();
            return  $result;
        }
        if (@$_user_mode) {
            $account_result = $this->get_account($options);
            if (empty($account_result)) {
                return  $account_result;
            }
            list($account_id, $account) = $account_result;
            // check user account
            if ($operation['account_id'] != $account_id) {
                $result = [
                    'status' => false,
                    'status_message' => 'Операция пользователя отсутствует: ' . $_operation_id,
                ];
                $this->transaction_rollback();
                return  $result;
            }
        }
        // status
        $object = $this->get_status(['status_id' => $operation['status_id']]);
        list($status_id, $status) = $object;
        if (empty($status_id)) {
            return  $object;
        }
        // check in_progress
        if ($status['name'] == 'cancelled') {
            $result = [
                'status' => true,
                'status_message' => 'Операция уже отменена',
            ];
            $this->transaction_rollback();
            return  $result;
        }
        if ($status['name'] != 'in_progress' && $status['name'] != 'confirmation') {
            $result = [
                'status' => false,
                'status_message' => 'Операцию невозможно отменить',
            ];
            $this->transaction_rollback();
            return  $result;
        }
        // check provider
        $provider_options = [];
        if ( ! @$_user_mode) {
            $provider_options['all'] = true;
        }
        $providers = $this->provider($provider_options);
        if ( ! @$providers[$operation['provider_id']]) {
            $result = [
                'status' => false,
                'status_message' => 'Операцию данного провайдера невозможно отменить',
            ];
            $this->transaction_rollback();
            return  $result;
        }
        // revert
        if ($operation['direction'] == 'out') {
            $options['is_revert'] = true;
        }
        $result = $this->_cancel($options);
        if (@$result['status']) {
            $result['status_message'] = 'Операция отменена';
            $this->transaction_commit();
        } else {
            $this->transaction_rollback();
        }
        return  $result;
    }

    public function _cancel($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // var
        if (@$_operation_id < 1) {
            $result = [
                'status' => false,
                'status_message' => 'Неверный код операции',
            ];
            return  $result;
        }
        // start transaction
        $this->transaction_start($options);
        // revert funds
        if (@$_is_revert) {
            $result = $this->_amount_revert($options);
            if (empty($result['status'])) {
                $this->transaction_rollback();
                return  $result;
            }
        }
        // update operation balance
        $options = [
                'status_name' => @$_status_name ?: 'cancelled',
                'is_finish' => true,
            ] + $options;
        $result = $this->_operation_balance_update($options);
        if (empty($result['status'])) {
            $this->transaction_rollback();
            return  $result;
        }
        // finish transaction
        $this->transaction_commit();
        return  $result;
    }

    public function _amount_revert($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // operation
        $operation = $this->operation(['operation_id' => $_operation_id]);
        if (empty($operation)) {
            $result = [
                'status' => false,
                'status_message' => 'Операция отсутствует: ' . $_operation_id,
            ];
            return  $result;
        }
        // amount revert
        $account_id = $operation['account_id'];
        $amount = $operation['amount'];
        // update account
        $sql_amount = $this->_number_mysql($amount);
        $sql_datetime = $this->sql_datetime();
        $data = [
            'account_id' => $account_id,
            'datetime_update' => db()->escape_val($sql_datetime),
            'balance' => '( balance + ' . $sql_amount . ' )',
        ];
        $result = $this->balance_update($data, ['is_escape' => false]);
        return  $result;
    }

    public function _operation_balance_update($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // operation
        $operation = $this->operation($options);
        if (empty($operation)) {
            $result = [
                'status' => false,
                'status_message' => 'Операция отсутствует: ' . $_operation_id,
            ];
            return  $result;
        }
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // update balance
        $account_id = $operation['account_id'];
        $object = $this->get_account__by_id(['account_id' => $account_id, 'force' => true]);
        if (empty($object)) {
            $result = [
                'status' => false,
                'status_message' => 'Ошибка при обновлении счет',
            ];
            return  $result;
        }
        list($account_id, $account) = $object;
        $balance = $account['balance'];
        // status
        $status_id = &$_status_id;
        if (@$_status_name) {
            $object = $this->get_status(['name' => $_status_name]);
            list($status_id, $status) = $object;
            if (empty($status_id)) {
                return  $object;
            }
        }
        // prepare
        $sql_datetime = $this->sql_datetime();
        $data = [
            'operation_id' => $_operation_id,
            'balance' => $balance,
            'datetime_update' => $sql_datetime,
        ];
        @$status_id && $data['status_id'] = $status_id;
        @$_is_finish && $data['datetime_finish'] = $sql_datetime;
        $result = $this->operation_update($data);
        return  $result;
    }

    public function _operation_check($options = null)
    {
        $result = [];
        $data = [];
        // options
        $_ = &$options;
        // check user_id
        $value = $this->_default([
            $_['user_id'],
            $this->user_id,
        ]);
        $value = $this->check_user_id($value);
        if (empty($value)) {
            $result = [
                'status' => -1,
                'status_message' => 'Требуется авторизация',
            ];
            return  $result;
        }
        $data['user_id'] = $value;
        // check direction
        $direction = null;
        switch (@$_['direction']) {
            case 'in':
                $direction = 'in';
                break;
            case 'out':
                $direction = 'out';
                break;
        }
        $direction && $data['direction'] = $direction;
        // check type
        $object = [];
        if ( ! empty($_['type_name'])) {
            $object['name'] = $_['type_name'];
        } elseif ( ! empty($_['type_id'])) {
            $object['type_id'] = (int) $_['type_id'];
        }
        $object = $this->type($object);
        if (empty($object)) {
            $result = [
                'status' => false,
                'status_message' => 'Данная операция недоступна',
            ];
            return  $result;
        }
        $type = reset($object);
        $type_id = (int) $type['type_id'];
        $data['type'] = $type;
        // check status
        $status = $this->status(['name' => 'in_progress']);
        if (empty($status)) {
            $result = [
                'status' => false,
                'status_message' => 'Статус не существует: "in_progress"',
            ];
            return  $result;
        }
        $status = reset($status);
        $status_id = (int) $status['status_id'];
        $data['status'] = $status;
        // check account
        $_options = $options;
        unset($_options['currency_id']);
        list($balance, $account_result) = $this->get_balance($_options);
        if (empty($account_result)) {
            return  $account_result;
        }
        list($account_id, $account) = $account_result;
        list($currency_id, $currency) = $this->get_currency__by_id([
            'currency_id' => $account['currency_id'],
        ]);
        $data['account'] = $account;
        // check amount
        $decimals = $currency['minor_units'];
        $amount = $this->_number_float($_['amount'], $decimals);
        if ($amount <= 0) {
            $result = [
                'status' => false,
                'status_message' => 'Сумма должна быть больше нуля',
            ];
            return  $result;
        }
        $sql_amount = $this->_number_mysql($amount, $decimals);
        $data['sql_amount'] = $sql_amount;
        // check balance limit lower
        ! isset($_['is_balance_limit_lower']) && $_['is_balance_limit_lower'] = $this->IS_BALANCE_LIMIT_LOWER;
        $balance_limit_lower = $this->_default([
            $_['balance_limit_lower'],
            $account['options']['balance_limit_lower'],
            $this->BALANCE_LIMIT_LOWER,
            0,
        ]);
        $balance_limit_lower = $this->_number_float($balance_limit_lower);
        if (@$_['user_mode'] && (
            ($type['name'] == 'payment' && $_['is_balance_limit_lower'] && ($balance - $amount < $balance_limit_lower))
            || ($type['name'] == 'transfer' && $direction === 'out' && $_['is_balance_limit_lower'] && ($balance - $amount < $balance_limit_lower))
        )) {
            $result = [
                'status' => false,
                'status_code' => 'BALANCE_LIMIT_LOW',
                'status_message' => 'Недостаточно средств на счету',
            ];
            return  $result;
        }
        // check payout limit min
        $payout_limit_min = @$this->PAYOUT_LIMIT_MIN;
        $payout_limit_min = $this->_number_float($payout_limit_min);
        if (@$_['user_mode'] &&
            ($type['name'] == 'payment' && ($balance < $payout_limit_min))
            ) {
            $result = [
                'status' => false,
                'status_message' => 'Минимальная сумма для вывода: ' . $payout_limit_min . $currency['sign'],
            ];
            return  $result;
        }
        // prepare provider
        $object = [];
        if ( ! empty($_['provider_name'])) {
            $object['name'] = $_['provider_name'];
        } elseif ( ! empty($_['provider_id'])) {
            $object['provider_id'] = (int) $_['provider_id'];
        }
        $object = $this->provider($object);
        if (empty($object)) {
            $result = [
                'status' => false,
                'status_message' => 'Неизвестный провайдер',
            ];
            return  $result;
        }
        $provider = reset($object);
        if (@$_['user_mode'] && (bool) $provider['system']) {
            $result = [
                'status' => false,
                'status_message' => 'Неизвестный провайдер',
            ];
            return  $result;
        }
        $provider_id = (int) $provider['provider_id'];
        $data['provider'] = $provider;
        // provider class
        $object = $this->provider_class([
            'provider_name' => $provider['name'],
        ]);
        if (empty($object)) {
            $result = [
                'status' => false,
                'status_message' => 'Неизвестный класс провайдера',
            ];
            return  $result;
        }
        // $data[ 'provider_class' ] = $object;
        // provider validate
        $result = $object->validate($options);
        if (empty($result['status'])) {
            return  $result;
        }
        // prepare result
        $sql_datetime = $this->sql_datetime();
        $data['sql_datetime'] = $sql_datetime;
        $result = [
            'account_id' => $account_id,
            'provider_id' => $provider_id,
            'status_id' => $status_id, // in_progress
            'type_id' => $type_id,   // deposition, payment, etc
            'amount' => $sql_amount,
            'datetime_start' => $sql_datetime,
            'datetime_update' => $sql_datetime,
        ];
        return  [true, $result, $data];
    }

    public function operation($options = null)
    {
        $_ = &$options;
        $is_no_count = &$_['no_count'];
        $is_sql = &$_['sql'];
        $is_where = &$_['where'];
        $is_no_limit = &$_['no_limit'];
        $is_no_order_by = &$_['no_order_by'];
        // by operation_id
        $result = null;
        $operation_id = &$_['operation_id'];
        $is_array_operation_id = false;
        if (isset($operation_id)) {
            if ((is_int($operation_id) || ctype_digit($operation_id)) && $operation_id > 0) {
                $operation_id = (int) $operation_id;
            } elseif (is_array($operation_id) && count($operation_id) > 0) {
                $is_array_operation_id = true;
            } else {
                return  null;
            }
        }
        $db = db()->table('payment_operation');
        if ($operation_id > 0) {
            if ($is_array_operation_id) {
                $db->where('operation_id', 'in', _es($operation_id));
            } else {
                $db->where('operation_id', $operation_id);
            }
            // sql only or fetch
            if ($is_sql) {
                $result = $db->sql();
            } else {
                $result = $db->all();
                if ( ! $result) {
                    return  $result;
                }
                $this->_operation_fetch(['data' => &$result]);
                if ( ! $is_array_operation_id) {
                    $result = reset($result);
                }
            }
            return  $result;
        }
        // by account
        $account_result = $this->get_account($options);
        if (empty($account_result)) {
            return  $account_result;
        }
        list($account_id, $account) = $account_result;
        $db->where('account_id', $account_id);
        if ($is_where) {
            $db->where_raw($is_where);
        }
        if ( ! $is_no_order_by) {
            $db->order_by('datetime_update', 'DESC');
        }
        // limit
        if ( ! $is_no_limit) {
            $limit = (int) $_['limit'] ?: $this->OPERATION_LIMIT;
            $limit_from = $_['limit_from'];
            if (empty($limit_from)) {
                $page = (int) $_['page'];
                $page = $page < 1 ? 1 : $page;
                $limit_from = ($page - 1) * $limit;
            }
            $db->limit($limit, $limit_from);
        }
        // sql only or fetch
        if ($is_sql) {
            $result = $db->sql();
        } else {
            $result = $db->all();
        }
        $count = null;
        if ( ! $is_no_count) {
            $count = $db->order_by()->limit(null)->count('*', $is_sql);
        }
        $this->_operation_fetch(['data' => &$result]);
        return  [$result, $count];
    }

    public function _operation_fetch($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if (is_array($_data)) {
            $datetime_key = ['start', 'finish', 'update'];
            foreach ($_data as $index => &$item) {
                $_item_options = &$item['options'];
                $_item_options && $_item_options = (array) json_decode($_item_options, true);
                foreach ($datetime_key as $key) {
                    $item['_ts_' . $key] = strtotime($item['datetime_' . $key]);
                }
            }
        }
    }

    public function balance_update($data, $options = null)
    {
        $result = $this->_object_update('account', $data, $options);
        return  $result;
    }

    public function operation_update($data, $options = null)
    {
        $result = $this->_object_update('operation', $data, $options);
        return  $result;
    }

    public function provider_update($data, $options = null)
    {
        $result = $this->_object_update('provider', $data, $options);
        return  $result;
    }

    // user confirmation
    public function confirmation(&$options = null, &$data = null, &$operation_data = null)
    {
        $status = true;
        $result = [
            'status' => &$status,
            'status_message' => &$status_message,
        ];
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // user mode
        if ( ! $_user_mode
            || ! ($_type_name == 'payment' && @$this->IS_PAYOUT_CONFIRMATION)
        ) {
            return  $result;
        }
        // check user mail
        $user_mail = $this->is_user_mail($operation_data);
        if ( ! @$user_mail['status']) {
            return  $user_mail;
        }
        // code by operation_id, amount
        $operation_id = &$data['operation_id'];
        $amount = &$data['amount'];
        list($code, $salt, $raw) = $this->confirmation_code(['data' => [
            'operation_id' => $operation_id,
            'amount' => $amount,
        ]]);
        // status: confirmation
        $object = $this->get_status(['name' => 'confirmation']);
        list($status_id, $status) = $object;
        if (empty($status_id)) {
            return  $object;
        }
        $data['status_id'] = $status_id;
        $operation_data['status'] = $status;
        // store confirmation data
        $operation_data['options'] = ['confirmation' => [
            'code' => $code,
            'salt' => $salt,
        ]];
        // message
        $status_message = t('Требуется подтверждение операции. Вам было отправлено письмо с руководством для подтверждения вывода средств.');
        $operation_data['status_message'] = &$status_message;
        return  $result;
    }

    public function confirmation_code($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // processing
        $salt = @$_salt ?: $this->_hash(time() . @$this->SECURITY_CODE);
        $raw = implode('-', (array) @$_data);
        $code = $this->_hash($raw . $salt);
        $result = [$code, $salt, $raw];
        return  $result;
    }

    public function _hash($str)
    {
        $hash = hash('sha256', @$str, true);
        $base64 = base64_encode($hash);
        $clean = str_replace(['+', '=', '/'], '', $base64);
        $result = substr($clean, 0, 16);
        return  $result;
    }

    public function confirmation_code_check($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // operation
        $operation = $this->operation([
            'operation_id' => $_operation_id,
        ]);
        if ( ! $operation) {
            $result = [
                'status' => false,
                'status_message' => 'Операция отсутствует',
            ];
            return  $result;
        }
        // check status
        $object = $this->get_status(['status_id' => $operation['status_id']]);
        list($status_id, $status) = $object;
        if (empty($status_id)) {
            return  $object;
        }
        if ($status['name'] != 'confirmation') {
            $result = [
                'status' => true,
                'status_message' => 'Операция не нуждается в подтверждении',
            ];
            return  $result;
        }
        // check
        $confirmation = &$operation['options']['confirmation'];
        // check already confirmed
        $is_confirmed = @$confirmation['status'][0] ?: @$confirmation['status'];
        if ($is_confirmed) {
            $result = [
                'status' => true,
                'status_message' => 'Операция уже подтверждена',
            ];
            return  $result;
        }
        // check datetime
        $time_code = strtotime($operation['datetime_update']);
        $time = time();
        // DEBUG
        // $time_code = $time - 1;
        $is_expired = ($time - $time_code) > strtotime($this->CONFIRMATION_TIME);
        if ($is_expired) {
            $result = [
                'status' => false,
                'status_message' => 'Код подтверждения просрочен',
            ];
            return  $result;
        }
        // check code
        $code = &$confirmation['code'];
        $salt = &$confirmation['salt'];
        if ($code !== @$_code) {
            $result = [
                'status' => false,
                'status_message' => 'Неверный код',
            ];
            return  $result;
        }
        // confirmation is ok
        $confirmation_ok_options = [
            'operation_id' => $_operation_id,
        ];
        $result = $this->confirmation_ok($confirmation_ok_options);
        return  $result;
    }

    public function confirmation_ok($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // operation
        $operation = $this->operation([
            'operation_id' => $_operation_id,
        ]);
        if ( ! $operation) {
            $result = [
                'status' => false,
                'status_message' => 'Операция отсутствует',
            ];
            return  $result;
        }
        // status to in_progress
        $object = $this->get_status(['name' => 'in_progress']);
        list($status_id, $status) = $object;
        if (empty($status_id)) {
            return  $object;
        }
        // update operation
        $sql_datetime = $this->sql_datetime();
        $update_options = [
            'confirmation' => [
                'status' => true,
            ],
        ];
        $update_data = [
            'operation_id' => $_operation_id,
            'status_id' => $status_id,
            'datetime_update' => $sql_datetime,
            'options' => $update_options,
        ];
        $result = $this->operation_update($update_data);
        if (@$result['status']) {
            $result['status_message'] = 'Операция подтверждена';
            // mail
            $this->mail([
                'tpl' => 'payout_request',
                'user_id' => $this->user_id(),
                'admin' => true,
                'data' => [
                    'operation_id' => $_operation_id,
                    'amount' => $operation['amount'],
                ],
            ]);
        }
        return  $result;
    }

    // transaction
    public function transaction_isolation($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // var
        $result = null;
        $new_level = null;
        if (@$_level) {
            $level = strtoupper($_level);
            switch ($_level) {
                case 'READ UNCOMMITTED':
                case 'READ COMMITTED':
                case 'REPEATABLE READ':
                case 'SERIALIZABLE':
                    $new_level = $level;
                    break;
            }
            if ($new_level) {
                $result = db()->query('SET SESSION TRANSACTION ISOLATION LEVEL ' . $new_level);
                return  $result;
            }
        }
        // get currency level
        $r = db()->get_2d('SHOW VARIABLES LIKE "%'.$this->TX_VAR.'%"');
        @$r[$this->TX_VAR] && $result = str_replace('-', ' ', $r[$this->TX_VAR]);
        return  $result;
    }

    public function transaction_start($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        $tx = &$this->transaction;
        $result = null;
        // save last transaction isolation level
        if ( ! @$tx['level']) {
            $tx['level'] = $this->transaction_isolation();
            // set highest level of isolation
            $result = $this->transaction_isolation(['level' => 'SERIALIZABLE']);
            $result &= db()->query('START TRANSACTION');
            // lock operation id
            if ($result) {
                $items = (array) $_operation_id;
                foreach ($items as $item) {
                    if (@(int) $item > 0) {
                        $sql_datetime = $this->sql_datetime();
                        $operation_id = (int) $item;
                        $data = [
                            'operation_id' => $operation_id,
                            'datetime_update' => $sql_datetime,
                        ];
                        $_status_id && $data['status_id'] = $_status_id;
                        $r = $this->operation_update($data);
                        $result &= @(bool) $r['status'];
                    }
                }
            }
        }
        return  $result;
    }

    public function transaction_finish($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        $tx = &$this->transaction;
        $result = null;
        if (@$_state) {
            $state = strtoupper($_state);
            switch ($state) {
                case 'COMMIT':
                case 'ROLLBACK':
                    break;
                default:
                    $state = null;
                    break;
            }
        }
        if ($state && @$tx['level']) {
            $result = db()->query($state);
            $result &= $this->transaction_isolation(['level' => $tx['level']]);
            $result && $tx['level'] = null;
        }
        return  $result;
    }

    public function transaction_commit($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        $result = $this->transaction_finish(['state' => 'COMMIT']);
        return  $result;
    }

    public function transaction_rollback($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        $result = $this->transaction_finish(['state' => 'ROLLBACK']);
        return  $result;
    }

    // mail
    public function is_user_mail($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // var
        $status = false;
        $result = [
            'status' => &$status,
            'status_message' => &$status_message,
        ];
        // check user
        if (@$_user_id < 1) {
            $status_message = 'Пользователь не найден для отправки почты';
            return  $result;
        }
        $user = user($_user_id);
        if (empty($user)) {
            $status_message = 'Пользователь не найден для отправки почты';
            return  $result;
        }
        // check mail
        if ( ! @$user['email']) {
            $status_message = 'Укажите и подтвердите ваш email адрес в личном кабинете';
            return  $result;
        }
        // check mail verification
        if (@$user['email'] != @$user['email_validated']) {
            $status_message = 'Подтвердите ваш email адрес в личном кабинете';
            return  $result;
        }
        $status = true;
        $result['user'] = $user;
        $result['mail'] = $user['email'];
        $result['name'] = $user['name'] ?: $user['login'];
        return  $result;
    }

    public function mail($options = null)
    {
        // DEBUG
        // ini_set( 'html_errors', 0 );
        // var_dump( $options );
        $result = true;
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // tpl by type, status
        if (empty($_tpl) && ! (empty($_type) && empty($_status))) {
            $_tpl = $_type . '_' . $_status;
        }
        if (empty($_tpl)) {
            return  null;
        }
        if (empty($_type) || empty($_status)) {
            list($type, $status) = @explode('_', $_tpl);
            if ( ! @$_type && @$type) {
                $_type = $type;
                $options['type'] = $_type;
            }
            if ( ! @$_status && @$status) {
                $_status = $status;
                $options['status'] = $_status;
            }
        }
        // DEBUG
        // ini_set( 'html_errors', 0 );
        // var_dump( $options );
        // var
        $payment_api = $this;
        $mail_class = _class('email');
        // error off
        /*
                $mail_debug = $mail_class->MAIL_DEBUG;
                $mail_class->MAIL_DEBUG = false;
                $send_mail_class = _class( 'send_mail' );
                $send_mail_debug = $send_mail_class->MAIL_DEBUG;
                $send_mail_class->MAIL_DEBUG = false;
         */
        // check user
        if (@$_user_id > 0) {
            $user_mail = $this->is_user_mail($options);
            // check email, validate email
            if ( ! @$_force && ! @$user_mail['status']) {
                return  $user_mail;
            }
            $user = $user_mail['user'];
            $mail_to = $user['email'];
            $mail_name = $user['name'];
        }
        // DEBUG
        // ini_set( 'html_errors', 0 );
        // var_dump( $mail_to, $mail_name );
        // check data
        $data = [];
        if ( ! empty($_data)) {
            // import data
            is_array($_data) && extract($_data, EXTR_PREFIX_ALL | EXTR_REFS, '_');
            // amount
            if ( ! empty($__amount)) {
                $__amount = $payment_api->money_text($__amount);
            }
            $data = $_data;
        }
        // url
        $url = [
            'user_payments' => url_user('/payments'),
        ];
        switch ($status) {
            case 'confirmation':
                $url['user_confirmation'] = url_user([
                    'object' => 'payment',
                    'operation_id' => @$__operation_id,
                    'code' => @$__code,
                    'is_confirmation' => 1,
                ]);
                $url['user_confirmation_cancel'] = url_user([
                    'object' => 'payment',
                    'operation_id' => @$__operation_id,
                    'is_cancel' => 1,
                ]);
                break;
        }
        // mail
        $mail_admin_to = $mail_class->ADMIN_EMAIL;
        $mail_admin_name = $mail_class->ADMIN_NAME;
        $mail = [
            'support_mail' => $mail_admin_to,
            'support_name' => $mail_admin_name,
        ];
        // compile
        $data = array_replace_recursive($data, [
            'url' => $url,
            'mail' => $mail,
        ]);
        $is_admin = ! empty($_is_admin);
        $admin = ! empty($_admin);
        // user
        if ( ! $is_admin) {
            $r = @$mail_class->_send_email_safe($mail_to, $mail_name, $_tpl, $data);
            // mail fail
            ! $r && $this->mail_log([
                'name' => 'mail_user',
                'data' => [
                    'status' => 'fail',
                    'operation_id' => $__operation_id,
                    'user_id' => $_user_id,
                    'mail' => $mail_to,
                    'name' => $mail_name,
                    'tpl' => $_tpl,
                ],
            ]);
            $result &= $r;
            // mail copy
            ! $admin && $this->mail_copy(['tpl' => $_tpl, 'type' => $_type, 'status' => $_status, 'subject' => @$_subject, 'data' => $data]);
        }
        // admin
        if ($admin || $is_admin) {
            $url = [
                'user_manage' => $this->url_admin([
                    'object' => 'members',
                    'action' => 'edit',
                    'id' => $_user_id,
                ]),
                'user_balance' => $this->url_admin([
                    'object' => 'manage_payment',
                    'action' => 'balance',
                    'user_id' => $_user_id,
                ]),
                'manage_payin' => $this->url_admin([
                    'object' => 'manage_deposit',
                    'action' => 'view',
                    'operation_id' => $__operation_id,
                ]),
                'manage_payout' => $this->url_admin([
                    'object' => 'manage_payout',
                    'action' => 'view',
                    'operation_id' => $__operation_id,
                ]),
            ];
            // compile
            $data = array_replace_recursive($data, [
                'url' => $url,
                'user_title' => $user['name'] . ' (id: ' . $_user_id . ')',
            ]);
            $tpl = $_tpl . '_admin';
            $r = @$mail_class->_send_email_safe($mail_admin_to, $mail_admin_name, $tpl, $data);
            // mail fail
            ! $r && $this->mail_log([
                'name' => 'mail_admin',
                'data' => [
                    'status' => 'fail',
                    'operation_id' => $__operation_id,
                    'user_id' => $_user_id,
                    'mail' => $mail_admin_to,
                    'name' => $mail_admin_name,
                    'tpl' => $tpl,
                ],
            ]);
            // mail copy
            $result_copy = $this->mail_copy(['tpl' => $tpl, 'type' => $_type, 'status' => $_status, 'subject' => @$_subject, 'data' => $data]);
            ! $result_copy && $this->mail_copy(['tpl' => $_tpl, 'type' => $_type, 'status' => $_status, 'subject' => @$_subject, 'data' => $data]);
        }
        /*
                $mail_class->MAIL_DEBUG      = $mail_debug;
                $send_mail_class->MAIL_DEBUG = $send_mail_debug;
         */
        return  $result;
    }

    public function mail_copy_find(&$mails, $options = null)
    {
        if (empty($this->MAIL_COPY_TO)) {
            return;
        }
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // var
        $mail_copy = &$this->MAIL_COPY_TO;
        // add: all, type, status
        if (@$mail_copy['all']) {
            $mail_ref = &$mail_copy['all'];
            foreach (@['all', $_type, $_status] as $key) {
                foreach ((array) @$mail_ref[$key] as $value) {
                    $mails[$value] = $value;
                }
            }
        }
        // add by type: all, status
        if (@$mail_copy[$_type]) {
            $mail_ref = &$mail_copy[$_type];
            foreach (@['all', $_status] as $key) {
                foreach ((array) @$mail_ref[$key] as $value) {
                    $mails[$value] = $value;
                }
            }
        }
    }

    public function mail_copy($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if (empty($_tpl) || empty($_data)) {
            return  null;
        }
        // prepare admin mail
        $mails = [];
        $this->mail_copy_find($mails, $options);
        // processing
        $result = true;
        if (is_array($mails)) {
            $mail_class = _class('email');
            $override = [];
            @$_subject && $override['subject'] = $_subject;
            $name = 'Payment admin';
            $instant_send = true;
            foreach ($mails as $mail) {
                $r = @$mail_class->_send_email_safe($mail, $name, $_tpl, $_data, $instant_send, $override);
                ! $r && $this->mail_log([
                    'name' => 'mail_copy',
                    'data' => [
                        'status' => 'fail',
                        'operation_id' => $_data['operation_id'],
                        'mail' => $mail,
                        'name' => $name,
                        'tpl' => $_tpl,
                    ],
                ]);
                $result &= $r;
            }
        }
        return  $result;
    }

    public function mail_log($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if ( ! @$_name || ! @$_data) {
            return  null;
        }
        // log
        $this->dump([
            'is_transaction' => true,
            'name' => $_name,
            'var' => $_data,
        ]);
        return  true;
    }

    public function url_admin($options = null)
    {
        if (empty($options)) {
            return  null;
        }
        $result = url_admin($options);
        if (substr($result, 0, 2) == '//') {
            $result = str_replace('//', 'http://', $result);
        }
        return  $result;
    }

    public function money_text($options = null)
    {
        ! is_array($options) && $options = [
            'value' => $options,
        ];
        $options += [
            'sign' => true,
        ];
        $result = $this->money_format($options);
        return  $result;
    }

    public function money_html($options = null)
    {
        ! is_array($options) && $options = [
            'value' => $options,
        ];
        $options += [
            'format' => 'html',
            'sign' => true,
        ];
        $result = $this->money_format($options);
        return  $result;
    }

    public function money_format($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // currency
        list($currency_id, $currency) = $this->get_currency__by_id(['currency_id' => $_currency_id]);
        $decimals = $currency['minor_units'];
        $sign = $currency['sign'];
        switch ($_format) {
            case 'html':
                $thousands_separator = '&nbsp;';
                // no break
            default:
                $thousands_separator = ' ';
                break;
        }
        // format number
        isset($_decimals) && $decimals = $_decimals;
        is_string($_decimal_point) && $decimal_point = $_decimal_point;
        is_string($_thousands_separator) && $thousands_separator = $_thousands_separator;
        $value = $this->_number_round($_value, $decimals);
        $value = $this->_number_format($value, $decimals, $decimal_point, $thousands_separator);
        // decoration
        $nbsp = '';
        switch ($_format) {
            case 'html':
                $sign = '<span class="currency">' . $sign . '</span>';
                $value = '<span class="money">' . $value . '</span>';
                $nbsp = '&nbsp;';
                $thousands_separator = '&nbsp;';
                // no break
            default:
                $nbsp = ' ';
                $thousands_separator = ' ';
                break;
        }
        // render
        $result = $value;
        $_nbsp && $result .= $nbsp;
        $_sign && $result .= $sign;
        return  $result;
    }

    // simple route: class__sub_class->method
    public function _class($class, $method = null, $options = null)
    {
        $_path = $this->_class_path;
        $_class_name = __CLASS__ . '__' . $class;
        $_class = _class_safe($_class_name, $_path);
        $status = $_class instanceof $_class_name;
        if ( ! $status) {
            return  null;
        }
        $status = method_exists($_class, $method);
        if ( ! $status) {
            return  $_class;
        }
        $result = $_class->{ $method }($options);
        return  $result;
    }

    // helpers
    public function _number_round($float = 0, $precision = null, $mode = null)
    {
        $precision = $precision ?: $this->DECIMALS;
        $mode = $mode ?: $this->ROUND_MODE;
        $result = round($float, $precision, $mode);
        $result = $result == 0 ? 0.0 : $result; // fix php round bug: -0.00
        return  $result;
    }

    public function _number_float($float = 0, $decimals = null, $decimal_point = null, $thousands_separator = '', $decimal_point_force = null)
    {
        return  (float) $this->_number_format($float, $decimals, $decimal_point ?: '.', $thousands_separator, $decimal_point_force);
    }

    public function _number_mysql($float = 0, $decimals = null, $decimal_point = null, $thousands_separator = '', $decimal_point_force = null)
    {
        return  $this->_number_format($float, $decimals, $decimal_point ?: '.', $thousands_separator, $decimal_point_force);
    }

    public function _number_api($float = 0, $decimals = null, $decimal_point = null, $thousands_separator = '', $decimal_point_force = null)
    {
        return  $this->_number_format($float, $decimals, $decimal_point ?: '.', $thousands_separator, $decimal_point_force);
    }

    public function _number_from_mysql($float = 0)
    {
        return  $this->_number_format($float);
    }

    public function _number_format($float = 0, $decimals = null, $decimal_point = null, $thousands_separator = null, $decimal_point_force = null)
    {
        if (isset($decimal_point_force)) {
            $_decimal_point = $decimal_point_force;
        } else {
            $locale_info = localeconv();
            $_decimal_point = $locale_info['decimal_point'];
        }
        $float = (float) str_replace($_decimal_point, '.', $float);
        ! isset($decimals) && $decimals = @$this->DECIMALS ?: 2;
        ! isset($decimal_point) && $decimal_point = @$this->DECIMAL_POINT ?: ',';
        ! isset($thousands_separator) && $thousands_separator = @$this->THOUSANDS_SEPARATOR ?: '&nbsp;';
        if (empty($this->FORCE_DECIMALS) && (int) $float == $float) {
            $decimals = 0;
        }
        $float = number_format($float, $decimals, $decimal_point, '`');
        $float = str_replace('`', $thousands_separator, $float);
        return  $float;
    }

    public function _default($list = null)
    {
        $result = null;
        foreach ($list as $index => $value) {
            if ($value !== null) {
                $result = &$list[$index];
                break;
            }
        }
        return  $result;
    }

    public function _merge()
    {
        $options = func_get_args();
        $data = [];
        foreach ($options as $option) {
            if (is_array($option) && ! empty($option)) {
                $data[] = $option;
            }
        }
        $result = call_user_func_array('array_merge_recursive', $data);
        return  $result;
    }

    public function _replace()
    {
        $options = func_get_args();
        $data = [];
        foreach ($options as $option) {
            if (is_array($option) && ! empty($option)) {
                $data[] = $option;
            }
        }
        $result = call_user_func_array('array_replace_recursive', $data);
        return  $result;
    }

    public function dump($options = null)
    {
        static $is_first = true;
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // time
        $ts = &$this->dump['ts'];
        ! $ts && $ts = microtime(true);
        $time = &$this->dump['time'];
        ! $time && $time = date('Y-m-d_H-i-s', $ts);
        // name
        ! @$_is_transaction && $name = &$this->dump['name'];
        @$_name && $name = $_name;
        $name = @$name ?: 'payment_api_dump';
        // number
        ! @$_is_transaction && $number = &$this->dump['number'];
        if ( ! @$number || @$_operation_id) {
            $number = [];
            @$_operation_id && $number[] = $_operation_id;
            $number[] = $time;
            $number = implode('_', $number);
        }
        // path
        $path = @$_path ?: @$this->DUMP_PATH ?: '/tmp';
        // file path
        $file = @$_file_path ?: sprintf('%s/%s__%s.txt', $path, $name, $number);
        $html_errors = ini_get('html_errors');
        ini_set('html_errors', 0);
        $result = '';
        if ($is_first) {
            $result .= 'SERVER:' . PHP_EOL . var_export($_SERVER, true) . PHP_EOL . PHP_EOL;
            $result .= 'GET:' . PHP_EOL . var_export($_GET, true) . PHP_EOL . PHP_EOL;
            $result .= 'POST:' . PHP_EOL . var_export($_POST, true) . PHP_EOL . PHP_EOL;
        }
        isset($_var) && $result .= 'VAR:' . PHP_EOL . var_export($_var, true) . PHP_EOL . PHP_EOL;
        ! empty($result) && file_put_contents($file, $result, FILE_APPEND);
        $is_first = false;
        @$_is_new && $is_first = true;
        ini_set('html_errors', $html_errors);
        return  $result;
    }

    protected function _object_update($name, $data, $options = null)
    {
        if (empty($name)) {
            return  null;
        }
        // import options
        is_array($data) && extract($data, EXTR_PREFIX_ALL | EXTR_REFS, '');
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '_');
        // check object id
        $status = false;
        $status_message = '';
        $result = [
            'status' => &$status,
            'status_message' => &$status_message,
        ];
        $id_name = $name . '_id';
        $id = (int) ${ '_' . $id_name };
        if ($id < 1) {
            $status_message = 'Ошибка при обновлении "' . $name . '": ' . $id;
            return  $result;
        }
        $table = 'payment_' . $name;
        // extend options
        if (is_array($_options)) {
            // get operation
            $operation = db()->table($table)
                ->where($id_name, $id)
                ->get();
            $operation_options = (array) json_decode($operation['options'], true);
            $array_method = @$__is_replace ? 'replace' : 'merge';
            $array_method = sprintf('array_%s_recursive', $array_method);
            $json_options = json_encode($array_method(
                $operation_options,
                $_options
            ));
            $json_options && $_options = $json_options;
        }
        // remove id by update
        unset($data[$id_name]);
        // escape sql data
        $sql_data = $data;
        $is_escape = isset($__is_escape) ? (bool) $__is_escape : true;
        $is_escape && $sql_data = _es($data);
        // query
        $sql_status = db()->table($table)
            ->where($id_name, $id)
            ->update($sql_data, ['escape' => $__is_escape]);
        // status
        if (empty($sql_status)) {
            $status_message = 'Ошибка при обновлении "' . $name . '": ' . $id;
            return  $result;
        }
        $status = true;
        $status_message = 'Выполнено обновление "' . t($name) . '"';
        return  $result;
    }
}
