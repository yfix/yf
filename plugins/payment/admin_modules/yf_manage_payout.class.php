<?php

class yf_manage_payout
{
    public $IS_PAYOUT_INTERKASSA = null;

    public $payment_api = null;
    public $manage_payment_lib = null;

    protected $object = null;
    protected $action = null;
    protected $id = null;
    protected $filter_name = null;
    protected $filter = null;
    protected $url = null;


    public function _init()
    {
        // class
        $this->payment_api = _class('payment_api');
        $this->manage_payment_lib = module('manage_payment_lib');
        // property
        $object = &$this->object;
        $action = &$this->action;
        $id = &$this->id;
        $filter_name = &$this->filter_name;
        $filter = &$this->filter;
        $url = &$this->url;
        // setup property
        $object = $_GET['object'];
        $action = $_GET['action'];
        $id = $_GET['id'];
        $filter_name = $object . '__' . $action;
        $filter = $_SESSION[$filter_name];
        // url
        $url = [
            'request' => url_admin([
                'object' => $object,
                'action' => 'request',
                'operation_id' => '%operation_id',
            ]),
            'yandexmoney_authorize' => url_admin([
                'object' => 'manage_payment_yandexmoney',
                'action' => 'authorize',
            ]),
            'request_interkassa' => url_admin([
                'object' => $object,
                'action' => 'request_interkassa',
                'operation_id' => '%operation_id',
            ]),
            'check_interkassa' => url_admin([
                'object' => $object,
                'action' => 'check_interkassa',
                'operation_id' => '%operation_id',
            ]),
            'check_all_interkassa' => url_admin([
                'object' => $object,
                'action' => 'check_all_interkassa',
            ]),
            'confirmation_update_expired' => url_admin([
                'object' => $object,
                'action' => 'confirmation_update_expired',
            ]),
            'expired' => url_admin([
                'object' => $object,
                'action' => 'expired',
                'operation_id' => '%operation_id',
            ]),
            'cancel' => url_admin([
                'object' => $object,
                'action' => 'cancel',
                'operation_id' => '%operation_id',
            ]),
            'recreate' => url_admin([
                'object' => $object,
                'action' => 'recreate',
                'operation_id' => '%operation_id',
            ]),
            'recreate_items' => url_admin([
                'object' => $object,
                'action' => 'recreate_items',
                'operation_id' => '%operation_id',
                'items' => '%items',
            ]),
            'status_processing' => url_admin([
                'object' => $object,
                'action' => 'status',
                'status' => 'processing',
                'operation_id' => '%operation_id',
            ]),
            'status_success' => url_admin([
                'object' => $object,
                'action' => 'status',
                'status' => 'success',
                'operation_id' => '%operation_id',
            ]),
            'status_refused' => url_admin([
                'object' => $object,
                'action' => 'status',
                'status' => 'refused',
                'operation_id' => '%operation_id',
            ]),
            'csv' => url_admin([
                'object' => $object,
                'action' => 'csv',
                'operation_id' => '%operation_id',
            ]),
            'csv_request' => url_admin([
                'object' => $object,
                'action' => 'csv_request',
                'operation_id' => '%operation_id',
            ]),
            'list' => url_admin([
                'object' => $object,
            ]),
            'view' => url_admin([
                'object' => $object,
                'action' => 'view',
                'operation_id' => '%operation_id',
            ]),
            'balance' => url_admin([
                'object' => 'manage_payment',
                'action' => 'balance',
                'user_id' => '%user_id',
                'account_id' => '%account_id',
            ]),
            'user' => url_admin([
                'object' => 'members',
                'action' => 'edit',
                'id' => '%user_id',
            ]),
        ];
    }

    /**
     * @param mixed $name
     * @param null|mixed $replace
     */
    public function _url($name, $replace = null)
    {
        $url = &$this->url;
        $result = null;
        if (empty($url[$name])) {
            return  $result;
        }
        if ( ! is_array($replace)) {
            return  $url[$name];
        }
        $result = str_replace(array_keys($replace), array_values($replace), $url[$name]);
        return  $result;
    }

    /**
     * @param mixed $filter
     * @param mixed $replace
     */
    public function _filter_form_show($filter, $replace)
    {
        $order_fields = [
            'o.operation_id' => 'номер операций',
            'o.amount' => 'сумма',
            'o.balance' => 'баланс',
            'o.datetime_start' => 'дата создания',
            'o.datetime_update' => 'дата обновления',
        ];
        $payment_api = &$this->payment_api;
        $providers = $payment_api->provider();
        $providers__select_box = [];
        foreach ($providers as $id => $item) {
            $providers__select_box[$id] = $item['title'];
        }
        $payment_status = $payment_api->get_status();
        $payment_status__select_box = [];
        $payment_status__select_box[-1] = 'ВСЕ СТАТУСЫ';
        foreach ($payment_status as $id => $item) {
            $payment_status__select_box[$id] = $item['title'];
        }
        $min_date = from('payment_operation')->one('UNIX_TIMESTAMP(MIN(datetime_start))');
        return form($replace, [
                'filter' => true,
                'selected' => $filter,
            ])
            ->daterange('datetime_start', [
                'format' => 'YYYY-MM-DD',
                'min_date' => date('Y-m-d', $min_date ?: (time() - 86400 * 30)),
                'max_date' => date('Y-m-d', time() + 86400),
                'autocomplete' => 'off',
                'desc' => 'Дата создания',
                'no_label' => 1,
            ])
            ->daterange('datetime_update', [
                'format' => 'YYYY-MM-DD',
                'min_date' => date('Y-m-d', $min_date ?: (time() - 86400 * 30)),
                'max_date' => date('Y-m-d', time() + 86400),
                'autocomplete' => 'off',
                'desc' => 'Дата обновления',
                'no_label' => 1,
            ])
            ->daterange('datetime_finish', [
                'format' => 'YYYY-MM-DD',
                'min_date' => date('Y-m-d', $min_date ?: (time() - 86400 * 30)),
                'max_date' => date('Y-m-d', time() + 86400),
                'autocomplete' => 'off',
                'desc' => 'Дата окончания',
                'no_label' => 1,
            ])
            ->text('name', 'Имя или номер пользователя', ['no_label' => 1])
            ->text('title', 'Название, номер или детали операции', ['no_label' => 1])
            ->row_start()
                ->number('amount', 'Сумма от')
                ->number('amount__and', 'Сумма до')
            ->row_end()
            ->row_start()
                ->number('balance', 'Баланс от')
                ->number('balance__and', 'Баланс до')
            ->row_end()
            ->select_box('status_id', $payment_status__select_box, ['no_label' => 1, 'show_text' => '= Статус =', 'desc' => 'Статус'])
            ->select_box('provider_id', $providers__select_box, ['no_label' => 1, 'show_text' => '= Провайдер =', 'desc' => 'Провайдер'])
            ->row_start()
                ->select_box('order_by', $order_fields, ['show_text' => '= Сортировка =', 'desc' => 'Сортировка'])
                ->select_box('order_direction', ['asc' => '⇑', 'desc' => '⇓'])
            ->row_end()
            ->save_and_clear();
    }


    public function _show_filter()
    {
        $object = &$this->object;
        $action = &$this->action;
        $filter_name = &$this->filter_name;
        $filter = &$this->filter;
        if ( ! in_array($action, ['show'])) {
            return  false;
        }
        // url
        $url_base = [
            'object' => $object,
            'action' => 'filter_save',
            'id' => $filter_name,
        ];
        $result = '';
        switch ($action) {
            case 'show':
                $url_filter = url_admin($url_base);
                $url_filter_clear = url_admin($url_base + [
                    'page' => 'clear',
                ]);
                $replace = [
                    'form_action' => $url_filter,
                    'clear_url' => $url_filter_clear,
                ];
                $result = $this->_filter_form_show($filter, $replace);
            break;
        }
        return  $result;
    }


    public function filter_save()
    {
        $object = &$this->object;
        $id = &$this->id;
        switch ($id) {
            case 'manage_payout__show':
                $url_redirect_url = url_admin([
                    'object' => $object,
                ]);
                // no break
            case 'clear':
                $url_redirect_url = url_admin([
                    'object' => $object,
                    'action' => 'show',
                ]);
                $id = 'manage_payout__show';
            break;
        }
        $options = [
            'filter_name' => $id,
            'redirect_url' => $url_redirect_url,
        ];
        return  _class('admin_methods')->filter_save($options);
    }


    public function _show_quick_filter()
    {
        $a = [];
        $status_names = from('payment_status')->get_2d('status_id, title');
        $count_by_status = select(['status_id', 'COUNT(*) AS num'])->from('payment_operation')
            ->where('direction', '=', 'out')->group_by('status_id')->get_2d();
        $statuses_display = [
            1 => 'text-warning',
            2 => 'text-success',
            5 => 'text-warning',
            3 => 'text-danger',
            6 => 'text-danger',
            4 => 'text-muted',
            7 => 'text-info',
        ];
        foreach ((array) $statuses_display as $status_id => $css_class) {
            if ($count_by_status[$status_id]) {
                $name = $status_names[$status_id];
                $a[] = a('/@object/filter_save/clear/?filter=status_id:' . $status_id, $name, 'fa fa-filter', $name, $css_class, '');
            }
        }
        $a[] = a('/@object/filter_save/clear/', 'Clear filter', 'fa fa-close', '', '', '');
        return $a ? '<div class="pull-right">' . implode(PHP_EOL, $a) . '</div>' : '';
    }

    /**
     * @param null|mixed $days
     */
    public function _get_daily_data($days = null)
    {
        $time = time();
        $days = $days ?: 60;
        $min_time = $time - $days * 86400;
        $data = [];
        $sql = select('FROM_UNIXTIME(UNIX_TIMESTAMP(datetime_start), "%Y-%m-%d") AS day', 'COUNT(*) AS count')
            ->from('payment_operation')->where('datetime_start', '>', $min_time)
            ->group_by('FROM_UNIXTIME(UNIX_TIMESTAMP(datetime_start), "%Y-%m-%d")')
            ->where('direction', '=', 'out');
        foreach ((array) $sql->all() as $a) {
            $data[$a['day']] = $a['count'];
        }
        if ( ! $data) {
            return false;
        }
        $dates = [];
        foreach (range($days, 0) as $days_ago) {
            $date = date('Y-m-d', $time - $days_ago * 86400);
            $dates[$date] = $days_ago;
        }
        $out = [];
        $_data = null;
        foreach ($dates as $date => $days_ago) {
            $_data = $data[$date];
            // Trim empty values from left side
            if ( ! $out && ! $_data) {
                continue;
            }
            $out[$date] = $_data;
        }
        // Trim values from the right side too
        foreach (array_reverse($out, $preserve_keys = true) as $k => $v) {
            if ($v) {
                break;
            }
            unset($out[$k]);
        }
        return $out;
    }


    public function show()
    {
        $object = &$this->object;
        $action = &$this->action;
        $filter_name = &$this->filter_name;
        $filter = &$this->filter;
        $url = &$this->url;
        // class
        $payment_api = &$this->payment_api;
        $manage_lib = &$this->manage_payment_lib;
        // mass actions
        if (main()->is_post()) {
            $is_mass = @$_POST['mass'];
            switch ($is_mass) {
                case 'payout': return  $this->mass_payout(); break;
                case 'cancel': return  $this->mass_cancel(); break;
                case 'remove': return  $this->mass_remove(); break;
                case 'CSV_ECommPay': return  $this->_user_message($this->csv_ecommpay()); break;
            }
        }
        // payment providers
        $providers = $payment_api->provider();
        $payment_api->provider_options($providers, [
            'method_allow',
        ]);
        // payment status
        $payment_status = $payment_api->get_status();
        $name = 'in_progress';
        $item = $payment_api->get_status(['name' => $name]);
        list($payment_status_in_progress_id, $payment_status_in_progress) = $item;
        if (empty($payment_status_in_progress_id)) {
            $result = [
                'status_message' => 'Статус платежей не найден: ' . $name,
            ];
            return  $this->_user_message($result);
        }
        // prepare sql
        $db = db()->select(
            'o.operation_id',
            'o.account_id',
            'o.provider_id',
            'o.options',
            'a.user_id',
            'o.amount',
            // 'a.balance',
            'o.balance',
            'p.title as provider_title',
            'o.status_id as status_id',
            'o.datetime_start',
            'u.name as user_name',
            'u.login as user_login',
            'u.nick as user_nick',
            'u.email as user_email'
            )
            ->table('payment_operation as o')
                ->left_join('payment_provider as p', 'p.provider_id = o.provider_id')
                ->left_join('payment_account  as a', 'a.account_id  = o.account_id')
                ->left_join('user as u', 'u.id = a.user_id')
            ->where('p.system', 'in', 0)
            ->where('p.active', '>=', 1)
            ->where('o.direction', 'out');
        $sql = $db->sql();
        $result = table($sql, [
                'filter' => $filter,
                'filter_params' => [
                    'status_id' => function ($a) use ($payment_status_in_progress_id) {
                        $result = null;
                        $value = $a['value'];
                        // default status_id = in_progress
                        if (empty($value)) {
                            $value = $payment_status_in_progress_id;
                        } elseif ($value == -1) {
                            $value = null;
                        }
                        isset($value) && $result = ' o.status_id = ' . $value;
                        return  $result;
                    },
                    'provider_id' => ['cond' => 'eq',      'field' => 'o.provider_id'],
                    'balance' => ['cond' => 'between', 'field' => 'o.balance'],
                    'amount' => ['cond' => 'between', 'field' => 'o.amount'],
                    'name' => function ($a) {
                        $v = $a['value'];
                        $like = 'LIKE "' . _es($v) . '%"';
                        if (is_numeric($v)) {
                            return 'u.id = ' . (int) $v;
                        } elseif (false !== strpos($v, ',')) {
                            return 'u.id IN(' . _es($v) . ')';
                        }
                        return '(u.name ' . $like . ' OR u.nick ' . $like . ' OR u.login ' . $like . ' OR u.email ' . $like . ')';
                    },
                    'title' => function ($a) {
                        $v = $a['value'];
                        $like = 'LIKE "' . _es($v) . '%"';
                        if (is_numeric($v)) {
                            return 'o.operation_id = ' . (int) $v;
                        } elseif (false !== strpos($v, ',')) {
                            return 'o.operation_id IN(' . _es($v) . ')';
                        }
                        return '(o.title ' . $like . ' OR o.options ' . $like . ')';
                    },
                    'datetime_start' => 'daterange_dt_between',
                    'datetime_update' => 'daterange_dt_between',
                    'datetime_finish' => 'daterange_dt_between',
                    '__default_order' => 'ORDER BY o.datetime_update DESC',
                ],
            ])
            ->check_box('operation_id', ['desc' => 'отметка', 'no_desc' => true])
            ->text('operation_id', 'операция')
            ->text('provider_title', 'провайдер')
            ->func('options', function ($value, $extra, $row) use ($providers) {
                if (empty($row['options'])) {
                    return  null;
                }
                // options
                $options = @json_decode($row['options'], true);
                if (empty($options)) {
                    return  null;
                }
                // request
                is_array($options['request']) && $request = @reset($options['request']);
                if (empty($request)) {
                    return  null;
                }
                // method
                $provider_id = @$request['options']['provider_id'];
                if (empty($provider_id)) {
                    return  null;
                }
                $method_id = @$request['options']['method_id'];
                if (empty($method_id)) {
                    return  null;
                }
                $method = @$providers[$provider_id]['_method_allow']['payout'][$method_id];
                if (empty($method)) {
                    return  null;
                }
                $method_title = @$method['title'];
                if (empty($method_title)) {
                    return  null;
                }
                $result = $method_title;
                return  $result;
            }, ['desc' => 'метод'])
            ->text('amount', 'сумма')
            ->text('balance', 'баланс')
            ->func('user_name', function ($value, $extra, $row) {
                $name = $row['user_name'] ?: $row['user_login'] ?: $row['user_nick'] ?: $row['user_email'];
                $result = a('/members/edit/' . $row['user_id'], $name . ' (id: ' . $row['user_id'] . ')');
                return  $result;
            }, ['desc' => 'пользователь'])
            ->func('status_id', function ($value, $extra, $row) use ($manage_lib, $payment_status) {
                $status_name = $payment_status[$value]['name'];
                $title = $payment_status[$value]['title'];
                $css = $manage_lib->css_by_status([
                    'status_name' => $status_name,
                ]);
                $result = sprintf('<span class="%s">%s</span>', $css, $title);
                return  $result;
            }, ['desc' => 'статус'])
            ->text('datetime_start', 'дата создания')
            ->btn('Вывод средств', $url['view'], ['icon' => 'fa fa-sign-out', 'class_add' => 'btn-primary', 'target' => '_blank'])
            // ->btn( 'Пользователь' , $url[ 'user'    ], array( 'icon' => 'fa fa-user'    , 'class_add' => 'btn-info'   ) )
            // ->btn( 'Счет'         , $url[ 'balance' ], array( 'icon' => 'fa fa-money'   , 'class_add' => 'btn-info'   ) )
;
        // mass actions
        $result->footer_submit(['name' => 'mass', 'value' => 'payout', 'desc' => 'Вывод средств', 'class' => 'btn btn-xs btn-success', 'icon' => 'fa fa-sign-out']);
        $result->footer_submit(['name' => 'mass', 'value' => 'cancel', 'desc' => 'Отменить', 'class' => 'btn btn-xs btn-warning', 'icon' => 'fa fa-ban']);
        $result->footer_submit(['name' => 'mass', 'value' => 'remove', 'desc' => 'Удалить', 'class' => 'btn btn-xs btn-danger', 'icon' => 'fa fa-remove']);
        // ECommPay
        $provider = $payment_api->is_provider(['name' => 'ecommpay']);
        if ($provider) {
            $result->footer_submit(['name' => 'mass', 'value' => 'CSV_ECommPay', 'desc' => 'CSV ECommPay', 'class' => 'btn btn-xs btn-info', 'icon' => 'fa fa-file-excel-o']);
        }
        // other actions
        $result->header_link('Обновить статусы операций Интеркассы', $url['check_all_interkassa'], ['class' => 'btn btn-xs btn-primary', 'icon' => 'fa fa-refresh']);
        $result->header_link('Обновить статусы операций Подтверждения', $url['confirmation_update_expired'], ['class' => 'btn btn-xs btn-primary', 'icon' => 'fa fa-refresh']);

        $data_daily = $this->_get_daily_data($last_days = 180);
        $data_chart = _class('charts')->jquery_sparklines($data_daily);

        $quick_filter = $this->_show_quick_filter();

        return
            '<div class="col-md-12">' .
                ($data_chart ? '<div class="col-md-6" title="' . t('Транзакции по дням') . '">' . $data_chart . '</div>' : '') .
                ($quick_filter ? '<div class="col-md-6 pull-right" title="' . t('Быстрый фильтр') . '">' . $quick_filter . '</div>' : '') .
            '</div>' .
            $result;
    }

    /**
     * @param null|mixed $options
     */
    public function csv_ecommpay($options = null)
    {
        $operation_id = &$_POST['operation_id'];
        if ( ! is_array($operation_id) || count($operation_id) < 1) {
            common()->message_info('Отсутствуют данные');
            return  null;
        }
        // class
        $payment_api = &$this->payment_api;
        // status: in_progress, processing
        $object = $payment_api->get_status(['name' => 'in_progress']);
        list($status_id_in_progress, $status_in_progress) = $object;
        if ( ! @$status_id_in_progress) {
            return  $object;
        }
        $object = $payment_api->get_status(['name' => 'processing']);
        list($status_id_processing, $status_processing) = $object;
        if ( ! @$status_id_processing) {
            return  $object;
        }
        // var
        $operation_id = array_keys($operation_id);
        // start transaction
        $result = $payment_api->transaction_start(['operation_id' => $operation_id]);
        if ( ! $result) {
            $message = 'Ошибка установки уровня изоляции транзакции';
            $result = [
                'status' => false,
                'status_message' => &$message,
            ];
            $payment_api->transaction_rollback();
            return  $result;
        }
        $items = $payment_api->operation(['operation_id' => $operation_id]);
        if ( ! is_array($items) || count($items) < 1) {
            $message = 'Отсутствуют данные';
            $result = [
                'status' => false,
                'status_message' => &$message,
            ];
            $payment_api->transaction_rollback();
            return  $result;
        }
        // data
        $service = '19'; // ECommPay WebMoney
        $fields = ['service', 'account', 'amount', 'currency', 'comment'];
        $data = [];
        $data[] = $fields;
        // title
        switch (true) {
            case defined('SITE_ADVERT_TITLE'): $title = SITE_ADVERT_TITLE; break;
            case defined('WEB_PATH'): $title = parse_url(WEB_PATH, PHP_URL_HOST); break;
            case @$_SERVER['HTTP_HOST']: $title = $_SERVER['HTTP_HOST']; break;
            default: $title = 'Payment'; break;
        }
        $sql_datetime = $payment_api->sql_datetime();
        foreach ($items as $index => $item) {
            $request = @$item['options']['request'][0];
            if (@$request['options']['method_id'] != 'webmoney') {
                continue;
            }
            // status check
            if ($item['status_id'] != $status_id_in_progress) {
                continue;
            }
            // operation_id
            $operation_id = (int) $request['data']['operation_id'];
            // update status
            $data_update = [
                'operation_id' => $operation_id,
                'status_id' => $status_id_processing,
                'datetime_update' => $sql_datetime,
                'options' => [
                    'processing' => [[
                        'provider_name' => 'administration',
                        'datetime' => $sql_datetime,
                    ]],
                ],
            ];
            $r = $payment_api->operation_update($data_update);
            if ( ! @(bool) $r['status']) {
                $payment_api->transaction_rollback();
                return  $r;
            }
            // data
            $account = $request['options']['customer_purse'];
            $amount = $request['data']['amount'];
            $currency = $request['data']['currency_id'];
            $comment = $title . ': ' .
                $request['options']['operation_title']
                . ' (id: ' . $request['data']['operation_id'] . ')';
            $data[] = [$service, $account, $amount, $currency, $comment];
        }
        if (count($data) <= 1) {
            $message = 'Отсутствуют операции со статусом: ' . $status_in_progress['title'];
            $result = [
                'status' => false,
                'status_message' => &$message,
            ];
            $payment_api->transaction_rollback();
            return  $result;
        }
        $file_name = 'ECommPay-WebMoney__' . date('Y-m-d_H-i-s') . '.csv';
        // output
        $result = $this->_http_csv([
            'file_name' => $file_name,
            'data' => $data,
            'is_return' => true,
            // 'debug'     => true,
        ]);
        if (@$result['status']) {
            $payment_api->transaction_commit();
            exit;
        }
        $payment_api->transaction_rollback();
        $result['operation_id'] = $_operation_id;
        return  $result;
    }

    /**
     * @param null|mixed $options
     */
    public function csv_request($options = null)
    {
        // check operation
        $operation = $this->_operation();
        // import options
        is_array($operation) && extract($operation, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if (empty($_is_valid)) {
            return  $this->_user_message($operation);
        }
        // var
        $payment_api = &$this->payment_api;
        // prepare view: request options
        $content = [];
        foreach ($_method['option'] as $key => $title) {
            if ( ! empty($_request['options'][$key])) {
                $content[$title] = $_request['options'][$key];
            }
        }
        // prepare data
        $data = [];
        $data[] = array_keys($content);
        $data[] = array_values($content);
        $file_name = 'operation_' . $_operation_id . '__' . date('Y-m-d_H-i-s') . '.csv';
        // output
        $result = $this->_http_csv([
            'file_name' => $file_name,
            'data' => $data,
            // 'debug'     => true,
        ]);
        $result['operation_id'] = $_operation_id;
        return  $this->_user_message($result);
    }

    /**
     * @param mixed $file_name
     * @param null|mixed $data
     */
    public function _save_csv($file_name, $data = null)
    {
        // setlocale( LC_ALL, 'ru_RU.utf8' )
        // || setlocale( LC_ALL, 'ru_UA.utf8' )
        // || setlocale( LC_ALL, 'en_US.utf8' );
        if (is_array($data) && ($file = fopen($file_name, 'w')) !== false) {
            foreach ($data as $id => $item) {
                if ( ! is_array($item)) {
                    fclose($file);
                    $result = [
                        'status' => false,
                        'status_header' => 'Экспорт в CSV',
                        'status_message' => 'Требуется массив, неверные данные: ' . $item,
                    ];
                    return  $result;
                }
                $_data = array_values($item);
                $result = fputcsv($file, $_data, ';', '"');
                if (false === $result) {
                    fclose($file);
                    $result = [
                        'status' => false,
                        'status_header' => 'Экспорт в CSV',
                        'status_message' => 'Ошибка при конвертацию данных: ' . $_data,
                    ];
                    return  $result;
                }
            }
            fclose($file);
            $result = [
                'status' => true,
            ];
        } else {
            $result = [
                'status' => false,
                'status_header' => 'Экспорт в CSV',
                'status_message' => 'Ошибка при открытие потока данных',
            ];
        }
        return  $result;
    }

    /**
     * @param null|mixed $options
     */
    public function _http_csv($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if ( ! is_array($_data) || empty($_file_name)) {
            $result = [
                'status' => false,
                'status_header' => 'Экспорт в CSV',
                'status_message' => 'Нет данных',
            ];
            return  $result;
        }
        // start
        ob_start();
        $result = $this->_save_csv('php://output', $_data);
        $csv = ob_get_clean();
        if (empty($result['status'])) {
            return  $result;
        }
        if ( ! empty($_debug)) {
            return  $csv;
        }
        header('Content-type: text/csv');
        header('Content-disposition: attachment; filename=' . $_file_name);
        echo $csv;
        if (@$_is_return === false) {
            exit;
        }
        $result = [
            'csv' => $csv,
            'status' => true,
            'status_header' => 'Экспорт в CSV',
            'status_message' => 'Выполнено',
        ];
        return  $result;
    }

    /**
     * @param null|mixed $options
     */
    public function _operation($options = null)
    {
        // var
        $is_valid = true;
        $is_options = true;
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // var
        $payment_api = &$this->payment_api;
        $manage_lib = &$this->manage_payment_lib;
        // check operation
        $operation_id = isset($_operation_id) ? $_operation_id : (int) $_GET['operation_id'];
        $operation = $payment_api->operation([
            'operation_id' => $operation_id,
        ]);
        if (empty($operation)) {
            $result = [
                'status' => false,
                'status_message' => 'Ошибка: операция не найдена',
            ];
            return  $result;
        }
        // import operation
        is_array($operation) && extract($operation, EXTR_PREFIX_ALL | EXTR_REFS, 'o');
        // check account
        $account_result = $payment_api->get_account(['account_id' => $o_account_id]);
        if (empty($account_result)) {
            $result = [
                'status' => false,
                'status_message' => 'Счет пользователя не найден',
            ];
            return  $result;
        }
        list($account_id, $account) = $account_result;
        // check user
        $user_id = $account['user_id'];
        $user = user($user_id);
        if (empty($user)) {
            $result = [
                'status' => false,
                'status_message' => 'Пользователь не найден: ' . $user_id,
            ];
            return  $result;
        }
        $online_users = _class('online_users', null, null, true);
        $user_is_online = $online_users->_is_online($user_id);
        // check provider
        $providers_user = $payment_api->provider();
        $payment_api->provider_options($providers_user, [
            'method_allow',
        ]);
        if (empty($providers_user[$o_provider_id])) {
            $result = [
                'status' => false,
                'status_message' => 'Неизвестный провайдер',
            ];
            return  $result;
        }
        $provider = &$providers_user[$o_provider_id];
        // providers by name
        $providers_user__by_name = [];
        foreach ($providers_user as &$item) {
            $provider_name = $item['name'];
            $providers_user__by_name[$provider_name] = &$item;
        }
        $provider_name = &$provider['name'];
        $provider_class = $payment_api->provider_class([
            'provider_name' => $provider_name,
        ]);
        if (empty($provider_class)) {
            $result = [
                'status_message' => 'Провайдер недоступный: ' . $provider['title'],
            ];
            return  $result;
        }
        // check request
        if (
            empty($o_options['request'])
            || ! is_array($o_options['request'])
        ) {
            $is_options = false;
            $result = [
                'status_message' => 'Параметры запроса отсутствует',
            ];
            return  $result;
        }
        if ($is_options) {
            $request = reset($o_options['request']);
            // check method
            if (empty($request['options']['method_id'])) {
                $result = [
                    'status_message' => 'Метод вывода средств отсутствует',
                ];
                return  $result;
            }
            $method_id = $request['options']['method_id'];
            $method = $provider_class->api_method([
                'type' => 'payout',
                'method_id' => $method_id,
            ]);
            // detect card
            $card = @$request['options']['card'];
            $result = $this->interkassa_detect_card([
                'card' => $card,
            ]);
            @list($card_method_id, $card_method) = $result;
            $html_card_title = null;
            if ($card_method_id) {
                $html_card_title = $card_method['title'];
            }
        }
        // check operation status
        $statuses = $payment_api->get_status();
        if (empty($statuses[$o_status_id])) {
            $result = [
                'status_message' => 'Неизвестный статус операции: ' . $o_status_id,
            ];
            return  $result;
        }
        $o_status = $statuses[$o_status_id];
        // status css
        $status_name = $o_status['name'];
        $status_title = $o_status['title'];
        $css = $manage_lib->css_by_status([
            'status_name' => $status_name,
        ]);
        $html_status_title = $status_title;
        // is
        $is_refused = $o_status['name'] == 'refused';
        $is_expired = $o_status['name'] == 'expired';
        $is_progressed = $o_status['name'] == 'in_progress';
        $is_processing = $o_status['name'] == 'processing';
        $is_confirmation = $o_status['name'] == 'confirmation';
        $is_finish = ! ($is_progressed || $is_processing || $is_confirmation);
        $is_recreate = $is_refused || $is_expired;
        $is_payout_yandexmoney = $provider_name == 'yandexmoney';
        if ($is_payout_yandexmoney) {
            $is_yandexmoney_authorize = $provider_class->is_authorization();
        }
        $is_payout_interkassa = (bool) $this->IS_PAYOUT_INTERKASSA && $card_method_id;
        // processing
        $processing = [];
        $is_processing_self = false;
        if (is_array($o_options['processing'])) {
            $processing_log = array_reverse($o_options['processing']);
            $processing = reset($processing_log);
            if (@$processing['provider_name'] && $processing['provider_name'] != $provider_name) {
                @list($processing_provider_id, $processing_provider) = $payment_api->get_provider([
                    'name' => $processing['provider_name'],
                ]);
                if ($is_processing && $processing_provider) {
                    $html_status_title = $status_title . ' (' . $processing_provider['title'] . ')';
                }
            } else {
                $is_processing_self = $is_processing;
            }
        }
        $is_confirmation && $is_processing_self = true;
        $html_status_title = sprintf('<span class="%s">%s</span>', $css, $html_status_title);
        $is_processing_interkassa = $is_processing && $processing['provider_name'] == 'interkassa';
        $is_processing_administration = $is_processing && $processing['provider_name'] == 'administration';
        // check response
        $response = null;
        if (
            ! empty($o_options['response'])
            && is_array($o_options['response'])
        ) {
            $response = $o_options['response'];
        }
        // misc
        $html_amount = $payment_api->money_html($o_amount);
        $html_datetime_start = $o_datetime_start;
        $html_datetime_update = $o_datetime_update;
        $html_datetime_finish = $o_datetime_finish;
        // result
        $result = [
            'is_valid' => &$is_valid,
            'is_options' => &$is_options,
            'operation_id' => &$operation_id,
            'operation' => &$operation,
            'processing_log' => &$processing_log,
            'processing' => &$processing,
            'statuses' => &$statuses,
            'status' => &$o_status,
            'status_id' => &$o_status_id,
            'status_name' => &$status_name,
            'status_title' => &$status_title,
            'html_status_title' => &$html_status_title,
            'account_id' => &$account_id,
            'account' => &$account,
            'user_id' => &$user_id,
            'user' => &$user,
            'user_is_online' => &$user_is_online,
            'provider_id' => &$o_provider_id,
            'provider' => &$provider,
            'provider_name' => &$provider_name,
            'provider_class' => &$provider_class,
            'providers_user' => &$providers_user,
            'providers_user__by_name' => &$providers_user__by_name,
            'request' => &$request,
            'method_id' => &$method_id,
            'method' => &$method,
            'card_method_id' => &$card_method_id,
            'card_method' => &$card_method,
            'html_card_title' => &$html_card_title,
            'response' => &$response,
            'is_refused' => &$is_refused,
            'is_expired' => &$is_expired,
            'is_progressed' => &$is_progressed,
            'is_processing' => &$is_processing,
            'is_confirmation' => &$is_confirmation,
            'is_processing_self' => &$is_processing_self,
            'is_processing_administration' => &$is_processing_administration,
            'is_processing_interkassa' => &$is_processing_interkassa,
            'is_payout_interkassa' => &$is_payout_interkassa,
            'is_payout_yandexmoney' => &$is_payout_yandexmoney,
            'is_yandexmoney_authorize' => &$is_yandexmoney_authorize,
            'is_finish' => &$is_finish,
            'is_recreate' => &$is_recreate,
            'html_amount' => &$html_amount,
            'html_datetime_start' => &$html_datetime_start,
            'html_datetime_update' => &$html_datetime_update,
            'html_datetime_finish' => &$html_datetime_finish,
        ];
        return  $result;
    }

    /**
     * operation options:
     *   'operation_id'
     *   'operation'
     *   'account_id'
     *   'account'
     *   'user_id'
     *   'user'
     *   'user_is_online'
     *   'provider_id'
     *   'provider'
     *   'provider_class'
     *   'providers_user'
     *   'request'
     *   'method'
     *   etc: see _operation().
     */
    public function view()
    {
        // check operation
        $operation = $this->_operation();
        // import options
        is_array($operation) && extract($operation, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if (empty($_is_valid)) {
            return  $this->_user_message($operation);
        }
        // var
        $url = &$this->url;
        $html = _class('html');
        $payment_api = &$this->payment_api;
        $manage_lib = &$this->manage_payment_lib;
        // prepare view: request options
        $content = [];
        if ($_is_options) {
            foreach ($_method['option'] as $key => $title) {
                if ( ! empty($_request['options'][$key])) {
                    $content[$title] = $_request['options'][$key];
                }
            }
            $html_request_options = $html->simple_table($content, ['no_total' => true]);
            $html_request_options_csv = $html->a([
                'href' => $this->_url('csv_request', ['%operation_id' => $_operation_id]),
                'icon' => 'fa fa-file-excel-o',
                'title' => 'Опции запроса: экспорт в CSV файл',
                'text' => 'CSV файл',
                'target' => '_blank',
            ]);
        }
        // prepare view: response options
        $content = null;
        if ( ! empty($_response)) {
            $response = array_reverse($_response);
            $content = table($response, ['no_total' => true])
                ->text('datetime', 'дата')
                ->func('data', function ($value, $extra, $row) use ($_provider_name, $_providers_user__by_name) {
                    // message
                    $message = @$row['status_message'] ?: @$row['data']['message'];
                    $result = t(trim(trim($message), '.,:'));
                    // provider
                    $provider_name = @$row['provider_name'];
                    if ($provider_name && $provider_name != $_provider_name) {
                        $provider_title = @$_providers_user__by_name[$provider_name]['title'];
                        $result .= ' (' . $provider_title . ')';
                    }
                    return  $result;
                }, ['desc' => 'сообщение'])
                ->func('data', function ($value, $extra, $row) {
                    $result = @$row['state'] ?: @$row['data']['state'] ?: null;
                    return  $result;
                }, ['desc' => 'статус']);
            $response_last = reset($response);
            $response_last = $response_last['data'];
        }
        $html_response = $content;
        // prepare view: operations by method
        list($data, $count) = $payment_api->operation([
            'where' => 'account_id = ' . $_account_id
                . ' AND provider_id = ' . $_provider_id
                . ' AND operation_id != ' . $_operation_id
                . ' AND direction = "out"',
            'limit' => 50,
        ]);
        $html_operations_by_method = null;
        if (@count($data) > 0) {
            $content = [];
            foreach ($data as $item) {
                $request = &$item['options']['request'][0];
                // match method
                if (@$request['options']['method_id'] == $_method_id) {
                    $request_options = &$request['options'];
                    $account_number = @$request_options['account_number'] ?:
                            @$request_options['account'] ?:
                            @$request_options['card'] ?:
                            @$request_options['to'] ?:
                            @$request_options['purse'] ?:
                            @$request_options['customer_purse'] ?:
                            '-';
                    $content[$item['operation_id']] = [
                        'operation_id' => $item['operation_id'],
                        'account_number' => $account_number,
                        'amount' => $item['amount'],
                        'status_id' => $item['status_id'],
                        'date' => $item['datetime_update'],
                    ];
                }
            }
            $content && $html_operations_by_method = table($content, ['no_total' => true])
                ->text('operation_id', 'операция')
                ->text('account_number', 'счет, номер карты, кошелек')
                ->func('amount', function ($value, $extra, $row) use ($payment_api) {
                    $result = $payment_api->money_html($value);
                    return  $result;
                }, ['desc' => 'сумма'])
                ->func('status_id', function ($value, $extra, $row) use ($manage_lib, $_statuses) {
                    $status_name = $_statuses[$value]['name'];
                    $title = $_statuses[$value]['title'];
                    $css = $manage_lib->css_by_status([
                        'status_name' => $status_name,
                    ]);
                    $result = sprintf('<span class="%s">%s</span>', $css, $title);
                    return  $result;
                }, ['desc' => 'статус'])
                ->text('date', 'дата')
                ->btn('Вывод средств', $url['view'], ['icon' => 'fa fa-sign-out', 'class_add' => 'btn-xs btn-primary', 'target' => '_blank']);
        }
        // prepare view: operation options
        $user_link = $html->a([
            'href' => $this->_url('user', ['%user_id' => $_user_id]),
            'icon' => 'fa fa-user',
            'title' => 'Профиль',
            'text' => $_user['name'],
        ]);
        $balance_link = $html->a([
            'href' => $this->_url('balance', ['%user_id' => $_user_id, '%account_id' => $_account_id]),
            'title' => 'Баланс',
            'text' => $payment_api->money_text($_account['balance']),
        ]);
        // compile
        $content = [
            'Операция' => $_operation_id,
            'Пользователь' => $user_link . $balance_link,
            'Провайдер' => $_provider['title'],
            'Метод' => $_method['title'],
            'Тип карты' => $_html_card_title,
            'Сумма' => $_html_amount,
            'Статус' => $_html_status_title,
            'Дата создания' => $_html_datetime_start,
            'Дата обновления' => $_html_datetime_update,
            'Дата завершения' => $_html_datetime_finish,
        ];
        if ( ! $_is_options) {
            unset($content['Метод']);
            $content['Предупреждение'] = '<span class="text-danger">параметры вывода отсутствуют</span>';
        }
        if ( ! @$_html_card_title) {
            unset($content['Тип карты']);
        }
        $html_operation_options = $html->simple_table($content, ['no_total' => true]);
        $url_view = $this->_url('view', ['%operation_id' => $_operation_id]);
        // manual mode
        $is_manual = false;
        $is_test = $_provider_class->is_test();
        switch ($_provider_name) {
            case 'ecommpay':
                $is_manual = true;
                $url_base = 'https://cliff.ecommpay.com/';
                $url_provider_operations = $url_base . 'operations/searchPayout';
                $url_provider_payouts = $url_base . 'payouts';
                if ($is_test) {
                    $url_base = 'https://cliff-sandbox.ecommpay.com/';
                    $url_provider_operations = $url_base . 'operations';
                    $url_provider_payouts = $url_provider_operations;
                }
                $url_provider_operation_detail = empty($response_last['transaction_id']) ? null : $url_base . 'operations/detail/' . $response_last['transaction_id'];
                break;
        }
        // render
        $replace = $operation + [
            'is_manual' => $is_manual,
            'header_data' => $html_operation_options,
            'request_data' => $html_request_options,
            'request_data_csv' => $html_request_options_csv,
            'response_data' => $html_response,
            'operations_by_method' => $html_operations_by_method,
            'url' => [
                'list' => $this->_url('list'),
                'recreate' => $this->_url('recreate', ['%operation_id' => $_operation_id]),
                'cancel' => $this->_url('cancel', ['%operation_id' => $_operation_id]),
                'expired' => $this->_url('expired', ['%operation_id' => $_operation_id]),
                'view' => $this->_url('view', ['%operation_id' => $_operation_id]),
                'request' => $this->_url('request', ['%operation_id' => $_operation_id]),
                'request_interkassa' => $this->_url('request_interkassa', ['%operation_id' => $_operation_id]),
                'check_interkassa' => $this->_url('check_interkassa', ['%operation_id' => $_operation_id]),
                'status_processing' => $this->_url('status_processing', ['%operation_id' => $_operation_id]),
                'status_success' => $this->_url('status_success', ['%operation_id' => $_operation_id]),
                'status_refused' => $this->_url('status_refused', ['%operation_id' => $_operation_id]),
                'csv' => $this->_url('csv', ['%operation_id' => $_operation_id]),
                'yandexmoney_authorize' => $this->_url('yandexmoney_authorize'),
                'provider_operation_detail' => @$url_provider_operation_detail,
                'provider_operations' => @$url_provider_operations,
                'provider_payouts' => @$url_provider_payouts,
            ],
        ];
        $result = tpl()->parse('manage_payout/view', $replace);
        return  $result;
    }


    public function recreate()
    {
        // var
        $payment_api = &$this->payment_api;
        $url = &$this->url;
        $is_post = main()->is_post();
        $operation_id = (int) @$_REQUEST['operation_id'];
        // check
        if ($operation_id < 1) {
            $url_list = $this->_url('list');
            return  js_redirect($url_list, false, 'Empty operation_id');
        }
        // operation
        $operation = $payment_api->operation(['operation_id' => $operation_id]);
        $_operation_id = (int) @$operation['operation_id'];
        if ($operation_id != $_operation_id) {
            $result = [
                'status_header' => 'Пересоздание операции',
                'status_message' => 'Операция не найдена',
            ];
            return  $this->_user_message($result);
        }
        // amount
        $amount = (float) $operation['amount'];
        // form
        $url_view = $this->_url('view', ['%operation_id' => $operation_id]);
        $replace = [
            'operation_id' => $operation_id,
            'amount_text' => $payment_api->money_text($amount),
            'amounts' => @$_POST['amounts'],
        ];
        $result = form($replace)
            ->on_post(function ($data, $extra, $rules) {
                $is_recreate = @$_POST['operation'] == 'recreate';
                if ($is_recreate) {
                    $result = $this->_recreate($_POST);
                    if (@$result['status']) {
                        $level = 'success';
                        $message = 'Выполнено пересоздание: ';
                    } else {
                        $level = 'error';
                        $message = 'Ошибка пересоздания: ';
                    }
                    $message .= $result['status_message'];
                    common()->add_message($message, $level);
                    // recreated items
                    if (@$result['status']) {
                        $operation_id = (int) $result['operation_id'];
                        $items = implode(',', $result['items']);
                        $url = $this->_url('recreate_items', ['%operation_id' => $operation_id, '%items' => $items]);
                        return  js_redirect($url, false, 'recreated items: ', $items);
                    }
                }
            })
            ->info('operation_id', 'Операция', ['class' => 'text-warning', 'class_add_controls' => 'form-control-static'])
            ->info('amount_text', 'Сумма', ['class' => 'text-success', 'class_add_controls' => 'form-control-static'])
            ->hidden('operation_id')
            ->input('amounts', ['desc' => 'Сумма', 'label_tip' => 'Величина не должна превышать сумму операции. Для разбиения суммы на части используйте любой не числовой символ: 10-20-30 для суммы 50'])
            ->row_start()
                ->submit('operation', 'recreate', ['desc' => 'Пересоздать', 'icon' => 'fa fa-refresh'])
                ->link('Назад', $url_view, ['class' => 'btn btn-xs btn-default', 'icon' => 'fa fa-chevron-left'])
            ->row_end();
        return  $result;
    }


    public function recreate_items()
    {
        // options
        $operation_id = @$_GET['operation_id'];
        $items = explode(',', @$_GET['items']);
        $items = array_filter($items, 'trim');
        // table
        $html = _class('html');
        $text = 'Операция';
        $content = [];
        $link = $html->a([
                'href' => $this->_url('view', ['%operation_id' => $operation_id]),
                'class_add' => 'btn-xs btn-danger',
                'target' => '_blank',
                'icon' => 'fa fa-sign-out',
                'title' => $text,
                'text' => $text,
            ]);
        $content['Пересоздана операция №' . $operation_id] = $link;
        $text = 'Вывод средств';
        $title = 'Новая операция №';
        foreach ($items as $operation_id) {
            $header = $title . $operation_id;
            $link = $html->a([
                'href' => $this->_url('view', ['%operation_id' => $operation_id]),
                'class_add' => 'btn-xs btn-success',
                'target' => '_blank',
                'icon' => 'fa fa-sign-out',
                'title' => $text,
                'text' => $text,
            ]);
            $content[$header] = $link;
        }
        $result = $html->simple_table($content, ['no_total' => true]);
        return  $result;
    }

    /**
     * @param null|mixed $options
     */
    public function _request($options = null)
    {
        // start
        db()->begin();
        // check operation
        $operation = $this->_operation($options);
        // import options
        is_array($operation) && extract($operation, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if (empty($_is_valid)) {
            return  $operation;
        }
        // is_processing
        if (@$_is_processing) {
            $result = [
                'status_message' => 'Операция уже обрабатывается',
            ];
            return  $result;
        }
        // is_progressed
        if ( ! @$_is_progressed) {
            $result = [
                'status_message' => 'Операция не может быть обработана, так как изменился статус',
            ];
            return  $result;
        }
        // var
        $payment_api = &$this->payment_api;
        $data = $_request['options'] + [
            'operation_id' => $_operation_id,
        ];
        $result = $_provider_class->api_payout($data);
        // DEBUG
        // var_dump( $result ); exit;
        $result['operation_id'] = $_operation_id;
        if (@$result['status'] == 'success') {
            // processing
            $_provider_class->_update_status([
                'operation_id' => $_operation_id,
                'name' => 'processing',
            ]);
        }
        // finish
        db()->commit();
        return  $result;
    }


    public function request()
    {
        $result = $this->_request();
        return  $this->_user_message($result);
    }


    public function request_interkassa()
    {
        // start
        db()->begin();
        // check operation
        $operation = $this->_operation();
        // import options
        is_array($operation) && extract($operation, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if (empty($_is_valid)) {
            return  $this->_user_message($operation);
        }
        // is_processing
        if (@$_is_processing) {
            $result = [
                'status_message' => 'Операция уже обрабатывается',
            ];
            return  $this->_user_message($result);
        }
        // is_progressed
        if ( ! @$_is_progressed) {
            $result = [
                'status_message' => 'Операция не может быть обработана, так как изменился статус',
            ];
            return  $this->_user_message($result);
        }
        // var
        $payment_api = &$this->payment_api;
        $data = $_request['options'] + [
            'operation_id' => $_operation_id,
        ];
        // provider
        $provider_class = $payment_api->provider_class([
            'provider_name' => 'interkassa',
        ]);
        if (empty($provider_class)) {
            $result = [
                'status_message' => 'Провайдер Интеркасса не доступен',
            ];
            return  $this->_user_message($result);
        }
        // detect card type
        $card = @$data['card'];
        $result = $this->interkassa_detect_card([
            'card' => $card,
        ]);
        @list($method_id, $method) = $result;
        if (empty($method_id)) {
            return  $this->_user_message($result);
        }
        $data['method_id'] = $method_id;
        $data['provider_force'] = true;
        // result
        $result = $provider_class->api_payout($data);
        // finish
        db()->commit();
        return  $this->_user_message($result);
    }

    /**
     * @param null|mixed $options
     */
    public function interkassa_detect_card($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if (empty($_card)) {
            $result = [
                'status_message' => 'Не задан номер карты',
            ];
            return  $result;
        }
        // var
        $payment_api = &$this->payment_api;
        $provider_class = $payment_api->provider_class([
            'provider_name' => 'interkassa',
        ]);
        if (empty($provider_class)) {
            $result = [
                'status_message' => 'Провайдер Интеркасса не доступен',
            ];
            return  $result;
        }
        // find by card
        $validate = _class('validate');
        $methods = &$provider_class->method_allow['payout'];
        if ( ! @$methods) {
            $result = [
                'status_message' => 'Провайдер Интеркасса: методы вывода не найдены',
            ];
            return  $result;
        }
        $is_method_id = null;
        foreach ($methods as $method_id => $method) {
            if (empty($method['option_validation']['card'])) {
                continue;
            }
            $rules = &$method['option_validation']['card'];
            $result = $validate->_input_is_valid($_card, $rules);
            if ($result) {
                $is_method_id = $method_id;
                break;
            }
        }
        if (empty($is_method_id)) {
            $result = [
                'status_message' => 'Карта не опознана: ' . $_card,
            ];
        } else {
            $result = [$method_id, $method];
        }
        return  $result;
    }

    /**
     * @param null|mixed $options
     */
    public function status_interkassa($options = null)
    {
        // import operation
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // provider interkassa
        $payment_api = &$this->payment_api;
        $provider_name = 'interkassa';
        $provider_class = $payment_api->provider_class([
            'provider_name' => $provider_name,
        ]);
        if (empty($provider_class)) {
            $result = [
                'status_message' => 'Провайдер Интеркасса не доступен',
            ];
            return  $result;
        }
        // result
        $result = [
            'operation_id' => &$_operation_id,
            'status' => &$status_name,
            'status_message' => &$status_message,
        ];
        // state
        list($status_name, $status_message) = $provider_class->_state(
            $_state,
            $provider_class->_payout_status,
            $provider_class->_payout_status_message
        );
        $status_message = @$status_message ?: @$data['stateName'];
        // transaction compile
        if (@$result['status'] == 'success' || @$result['status'] == 'refused') {
            // save response
            $sql_datetime = $payment_api->sql_datetime();
            $operation_options = [
                'response' => [[
                    'datetime' => $sql_datetime,
                    'provider_name' => $provider_name,
                    'state' => $_state,
                    'status_name' => $status_name,
                    'status_message' => $status_message,
                    'data' => $_data,
                ]],
            ];
            $operation_update_data = [
                'operation_id' => $_operation_id,
                'datetime_update' => $sql_datetime,
                'options' => $operation_options,
            ];
            $payment_api->operation_update($operation_update_data);
            // update status
            return  $this->_status($result);
        }
        return  $result;
    }

    /**
     * @param null|mixed $options
     */
    public function _check_interkassa($options = null)
    {
        // check operation
        $operation = $this->_operation($options);
        // import options
        is_array($operation) && extract($operation, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if (empty($_is_valid)) {
            return  $this->_user_message($operation);
        }
        if ( ! $_is_processing_interkassa) {
            $result = [
                'status_message' => 'Данная операция не обрабатывается Интеркассой',
            ];
            return  $result;
        }
        // var
        $payment_api = &$this->payment_api;
        // response
        $response = @end($_response);
        if (empty($response)) {
            $result = [
                'status_message' => 'Транзакция не найдена',
            ];
            return  $result;
        }
        $id = &$response['data']['id'];
        if ( ! @$id || $id < 1) {
            $result = [
                'status_message' => 'Номер транзакции не найдена',
            ];
            return  $result;
        }
        // provider interkassa
        $provider_class = $payment_api->provider_class([
            'provider_name' => 'interkassa',
        ]);
        if (empty($provider_class)) {
            $result = [
                'status_message' => 'Провайдер Интеркасса не доступен',
            ];
            return  $result;
        }
        // check transaction
        $request_option = [
            'method_id' => 'withdraw-id',
            'id' => $id,
        ];
        list($status, $result) = $provider_class->api_request($request_option);
        if (empty($status)) {
            $result = [
                'status_message' => 'Невозможно выполнить проверку транзакции',
            ];
            return  $result;
        }
        // check status
        $data = @$result['data'];
        // check status
        $state = (int) $data['state'];
        $result = $this->status_interkassa([
            'operation_id' => $_operation_id,
            'state' => $state,
            'data' => $data,
        ]);
        return  $result;
    }


    public function check_interkassa()
    {
        $result = $this->_check_interkassa();
        return  $this->_user_message($result);
    }

    /**
     * @param null|mixed $options
     */
    public function _status($options = null)
    {
        // check operation
        $operation = $this->_operation($options);
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '_');
        is_array($operation) && extract($operation, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if (empty($_is_valid)) {
            return  $this->_user_message($operation);
        }
        $status = $_GET['status'] ?: $__status;
        switch ($status) {
            case 'success':
                $result = $_provider_class->_payout_success([
                    'operation_id' => $_operation_id,
                ]);
                $mail_tpl = 'payout_success';
                break;
            case 'refused':
                $result = $_provider_class->_payout_refused([
                    'operation_id' => $_operation_id,
                ]);
                $mail_tpl = 'payout_refused';
                break;
            case 'processing':
                $result = $_provider_class->_payout_processing([
                    'operation_id' => $_operation_id,
                ]);
                break;
        }
        if (empty($result['status'])) {
            $result['operation_id'] = $_operation_id;
            return  $result;
        }
        // mail
        $payment_api = &$this->payment_api;
        @$mail_tpl && $payment_api->mail([
            'tpl' => $mail_tpl,
            'user_id' => $_user_id,
            'admin' => true,
            'data' => [
                'operation_id' => $_operation_id,
                'amount' => $_operation['amount'],
            ],
        ]);
        $url_view = $this->_url('view', ['%operation_id' => $_operation_id]);
        if ( ! empty($__status_message)) {
            return  $options;
        }
        return  js_redirect($url_view, false);
    }

    /**
     * @param null|mixed $options
     */
    public function status($options = null)
    {
        $result = $this->_status($options);
        return  $this->_user_message($result);
    }

    /**
     * @param mixed $delim
     */
    public function _array2csv(array &$array, $delim = ';')
    {
        if (count((array) $array) == 0) {
            return null;
        }
        ob_start();
        $df = fopen('php://output', 'w');
        fputcsv($df, array_keys(reset($array)));
        foreach ($array as $row) {
            fputcsv($df, $row, $delim);
        }
        fclose($df);
        return ob_get_clean();
    }

    /**
     * https://cliff.ecommpay.com/download/%D0%98%D0%BD%D1%81%D1%82%D1%80%D1%83%D0%BA%D1%86%D0%B8%D1%8F%20%D0%BF%D0%BE%20%D0%B2%D1%8B%D0%BF%D0%BB%D0%B0%D1%82%D0%B0%D0%BC%20%D1%87%D0%B5%D1%80%D0%B5%D0%B7%20%D1%84%D0%B0%D0%B9%D0%BB.pdf.
     */
    public function csv()
    {
        // class
        $payment_api = &$this->payment_api;
        $provider_class = $payment_api->provider_class([
            'provider_name' => 'ecommpay',
        ]);
        // var
        $operation_id = (int) ($_GET['operation_id']);
        $info = db()->from('payment_operation')->where('operation_id', $operation_id)->get();
        if ( ! $info) {
            return _404();
        }
        $info['options'] = json_decode($info['options'], true);
        $options = $info['options']['request'][0]['options'];
        $opt_data = $info['options']['request'][0]['data'];
        $data = [];
        $data['payment_group_id'] = 1; // Bank cards
        $data['site_id'] = $provider_class->key(); // EcommPay site id
        $data['external_id'] = $operation_id;
        $data['comment'] = 'Payments out request. Date: ' . date('Y-m-d_H-i-s') . ' OID: ' . $operation_id;
        $data['phone'] = preg_replace('~[^0-9]~ims', '', $options['sender_phone']);
        $data['customer_purse'] = $options['card'];
        //		$data['transaction_id'] = ''; // [обязательный, если customer_purse не используется; пустой, если используется customer_purse]
        // Номер транзакции в Клиентском интерфейсе, по которой ранее был осуществлен прием средств.
        // Обычно используется для выплат на банковские карты при отсутствии сертификата PCI DSS.
        // Валюта, в которой была указана сумма платежа. Если валюта запроса не соответствует валюте счета, с которого будет осуществлен платеж,
        // то система автоматически осуществит пересчет суммы по курсу ЦБ РФ.
        //		$data['amount']				= intval($opt_data['amount'] * 100);
        //		$data['currency']			= $opt_data['currency_id'];
        $data['amount'] = (int) ($options['amount'] * 100);
        $data['currency'] = 'USD';
        $data = [$data];
        $csv = $this->_array2csv($data);
        // Ecommpay wants ";" everywhere
        $csv = explode(PHP_EOL, $csv);
        $csv[0] = str_replace(',', ';', $csv[0]);
        $csv = trim(implode(PHP_EOL, $csv));
        no_graphics(true);
        if (DEBUG_MODE) {
            echo '<pre>';
            print_r($csv);
            print_r($opt);
            print_r($info);
            print_r($data);
        } else {
            header('Content-disposition: attachment; filename=Ecommpay_out_' . (int) $operation_id . '_' . date('Ymd_His') . '.csv');
            header('Content-type: text/csv');
            echo $csv;
        }
        exit;
    }


    public function _check_all_interkassa()
    {
        // var
        $html = _class('html');
        $payment_api = &$this->payment_api;
        // update status only processing
        $object = $payment_api->get_status(['name' => 'processing']);
        list($status_id, $status) = $object;
        if (empty($status_id)) {
            return  $object;
        }
        // provider
        $providers_user = $payment_api->provider();
        $providers_id = implode(',', array_keys($providers_user));
        // fetch operations
        $db = db()->table('payment_operation')
            ->where('provider_id', 'in', $providers_id)
            ->where('status_id', '=', $status_id)
            ->where('direction', '=', 'out');
        // DEBUG
        // var_dump( $db->sql() ); exit;
        $operations = $db->get_all();
        // DEBUG
        // var_dump( $operations ); exit;
        if (empty($operations)) {
            return  $operations;
        }
        // check operations
        $result = [];
        foreach ($operations as $item) {
            $operation_id = $item['operation_id'];
            $r = $this->_check_interkassa([
                'operation_id' => $operation_id,
            ]);
            $result[$operation_id] = $r;
            sleep(5);
        }
        return  $result;
    }

    /**
     * @param null|mixed $options
     */
    public function check_all_interkassa($options = null)
    {
        // command line interface
        $is_cli = (php_sapi_name() == 'cli');
        $is_cli && $this->_check_all_interkassa_cli();
        // web
        $replace = [
            'is_confirm' => false,
        ];
        $html_result = '';
        $result = form($replace)
            ->on_post(function ($data, $extra, $rules) use (&$html_result) {
                $is_confirm = ! empty($_POST['is_confirm']);
                if ($is_confirm) {
                    $result = $this->_check_all_interkassa();
                    if (empty($result)) {
                        $level = 'warning';
                        $message = 'Нет операций для обработки';
                    } else {
                        $level = 'success';
                        $message = 'Выполнено, обновление статусов операций Интеркассы';
                        // prepare result html
                        $content = [];
                        $html = _class('html');
                        foreach ($result as $operation_id => $item) {
                            $link = $html->a([
                                'href' => $this->_url('view', ['%operation_id' => $operation_id]),
                                'class_add' => 'btn-xs btn-primary',
                                'target' => '_blank',
                                'icon' => 'fa fa-sign-out',
                                'title' => 'Вывод средств №' . $operation_id,
                                'text' => 'Вывод средств №' . $operation_id,
                            ]);
                            $content[$link] = $item['status_message'];
                        }
                        $html_result = $html->simple_table($content, ['no_total' => true]);
                    }
                    common()->add_message($message, $level);
                } else {
                    common()->message_info('Требуется подтверждение, для выполнения операции');
                }
            })
            ->check_box('is_confirm', ['desc' => 'Подтверждение', 'no_label' => true])
            ->row_start()
                ->submit('operation', 'update', ['desc' => 'Обновить статусы операций Интеркассы', 'icon' => 'fa fa-refresh'])
                ->link('Назад', $this->_url('list'), ['class' => 'btn btn-xs btn-default', 'icon' => 'fa fa-chevron-left'])
            ->row_end();
        return  $result . $html_result;
    }

    /**
     * @param null|mixed $options
     */
    public function _check_all_interkassa_cli($options = null)
    {
        $result = $this->_check_all_interkassa();
        if (empty($result)) {
            $status = -1;
            $message = 'no operations';
        } else {
            $status = 0;
            $content = [];
            foreach ($result as $operation_id => $item) {
                $content[] = $operation_id . ' - ' . (@$item['status'] ?: 'fail');

                $message = implode("\n", $content);
            }
        }
        echo  $message . PHP_EOL;
        exit($status);
    }


    public function _confirmation_update_expired()
    {
        // var
        $payment_api = _class('payment_api');
        // update status only in_progress
        $object = $payment_api->get_status(['name' => 'confirmation']);
        list($status_id, $status) = $object;
        if (empty($status_id)) {
            return  $object;
        }
        $object = $payment_api->get_status(['name' => 'expired']);
        list($new_status_id, $new_status) = $object;
        if (empty($new_status_id)) {
            return  $object;
        }
        // time expired
        $ts = strtotime($payment_api->CONFIRMATION_TIME);
        $sql_datetime_over = $payment_api->sql_datetime($ts);
        $sql_datetime = $payment_api->sql_datetime();
        $db = db()->table('payment_operation')->select('operation_id')
            ->where('status_id', '=', $status_id)
            ->where('direction', '=', 'out')
            ->where('datetime_update', '<', $sql_datetime_over)
            ->where_null('datetime_finish');
        // get items
        $items = @$db->get_all();
        $db_error = $db->db->_last_query_error;
        if ($items === false && is_array($db_error)) {
            return  null;
        }
        if ( ! $items) {
            return  true;
        }
        // processing
        $result = true;
        foreach ($items as $idx => $item) {
            $operation_id = (int) $item['operation_id'];
            $r = $payment_api->expired([
                'operation_id' => $operation_id,
            ]);
            $result &= @$r['status'];
        }
        return  $result;
    }


    public function confirmation_update_expired()
    {
        $url = &$this->url;
        // command line interface
        $is_cli = (php_sapi_name() == 'cli');
        $is_cli && $this->_confirmation_update_expired_cli();
        // web
        $replace = [
            'is_confirm' => false,
        ];
        $result = form($replace)
            ->on_post(function ($data, $extra, $rules) {
                $is_confirm = ! empty($_POST['is_confirm']);
                if ($is_confirm) {
                    $result = $this->_confirmation_update_expired();
                    if (empty($result)) {
                        $level = 'error';
                        $message = 'Ошибка при обновлении';
                    } else {
                        $level = 'success';
                        $message = 'Выполнено обновление';
                    }
                    common()->add_message($message, $level);
                } else {
                    common()->message_info('Требуется подтверждение, для выполнения операции');
                }
            })
            ->info('header', ['value' => 'Ввод средств: обновление статуса просроченных операций', 'no_label' => true, 'class' => 'text-warning'])
            ->check_box('is_confirm', ['desc' => 'Подтверждение', 'no_label' => true])
            ->row_start()
                ->submit('operation', 'update', ['desc' => 'Обновить', 'icon' => 'fa fa-refresh'])
                ->link('Назад', $url['list'], ['class' => 'btn btn-xs btn-default', 'icon' => 'fa fa-chevron-left'])
            ->row_end();
        return  $result;
    }


    public function _confirmation_update_expired_cli()
    {
        $result = $this->_confirmation_update_expired();
        if (empty($result)) {
            $status = 1;
            $message = 'Update is fail';
        } else {
            $status = 0;
            $message = 'Update is success';
        }
        echo  $message . PHP_EOL;
        exit($status);
    }

    /**
     * @param null|mixed $options
     */
    public function _get_operation_id($options = null)
    {
        // import operation
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // var
        $result = @$_operation_id ?: @$_GET['operation_id'] ?: @$_POST['operation_id'];
        if ( ! @$result) {
            $result = [
                'status_message' => 'Неверная операция',
            ];
            return  $this->_user_message($result);
        }
        return  $result;
    }

    /**
     * @param null|mixed $options
     */
    public function cancel($options = null)
    {
        $operation_id = (int) $this->_get_operation_id($options);
        if ($operation_id < 1) {
            $result = [
                'status_message' => 'Неверная операция',
            ];
            return  $this->_user_message($result);
        }
        // processing
        $payment_api = &$this->payment_api;
        $result = $payment_api->cancel([
            'operation_id' => $operation_id,
        ]);
        return  $this->_user_message($result);
    }

    /**
     * @param null|mixed $options
     */
    public function expired($options = null)
    {
        $operation_id = (int) $this->_get_operation_id($options);
        if ($operation_id < 1) {
            $result = [
                'status_message' => 'Неверная операция',
            ];
            return  $this->_user_message($result);
        }
        // processing
        $payment_api = &$this->payment_api;
        $result = $payment_api->expired([
            'operation_id' => $operation_id,
        ]);
        return  $this->_user_message($result);
    }

    // mass actions

    /**
     * @param null|mixed $options
     */
    public function _mass_result($options = null)
    {
        // import operation
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // prepare
        $result = [
            'status_header' => &$_status_header,
            'status_message' => &$_status_message,
            'status' => &$_status,
            'operation_id' => -1,
        ];
        return  $this->_user_message($result);
    }

    /**
     * @param null|mixed $options
     */
    public function mass_payout($options = null)
    {
        $operation_id = $this->_get_operation_id($options);
        is_numeric($operation_id) && $operation_id = [$operation_id => 1];
        if ( ! is_array($operation_id)) {
            return  $this->_mass_result([
                'status_message' => 'Требуется массив операций',
            ]);
        }
        // processing
        $payment_api = &$this->payment_api;
        $html = _class('html');
        $title = 'Вывод средств №';
        $content = [];
        foreach ($operation_id as $id => $selected) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            $header = $title . $id;
            $result = $this->_request([
                'operation_id' => $id,
            ]);
            $text = $result['status_message'];
            $link = $html->a([
                'href' => $this->_url('view', ['%operation_id' => $id]),
                'class_add' => 'btn-xs ' . (@$result['status'] ? 'btn-success' : 'btn-warning'),
                'target' => '_blank',
                'icon' => 'fa fa-sign-out',
                'title' => $text,
                'text' => $text,
            ]);
            $content[$header] = $link;
        }
        // table
        $table = $html->simple_table($content, ['no_total' => true]);
        return  $this->_mass_result([
            'status' => true,
            'status_message' => $table,
        ]);
    }

    /**
     * @param null|mixed $options
     */
    public function mass_cancel($options = null)
    {
        $operation_id = $this->_get_operation_id($options);
        is_numeric($operation_id) && $operation_id = [$operation_id => 1];
        if ( ! is_array($operation_id)) {
            return  $this->_mass_result([
                'status_header' => 'Отмена операций',
                'status_message' => 'Требуется массив операций',
            ]);
        }
        // processing
        $payment_api = &$this->payment_api;
        $html = _class('html');
        $title = 'Отменено №';
        $content = [];
        foreach ($operation_id as $id => $selected) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            $header = $title . $id;
            $result = $payment_api->cancel([
                'operation_id' => $id,
            ]);
            $text = $result['status_message'];
            $link = $html->a([
                'href' => $this->_url('view', ['%operation_id' => $id]),
                'class_add' => 'btn-xs ' . (@$result['status'] ? 'btn-success' : 'btn-warning'),
                'target' => '_blank',
                'icon' => 'fa fa-sign-out',
                'title' => $text,
                'text' => $text,
            ]);
            $content[$header] = $link;
        }
        // table
        $table = $html->simple_table($content, ['no_total' => true]);
        return  $this->_mass_result([
            'status' => true,
            'status_message' => $table,
            'status_header' => 'Отмена операций',
        ]);
    }

    /**
     * @param null|mixed $options
     */
    public function mass_remove($options = null)
    {
        // start
        $operation_id = $this->_get_operation_id($options);
        is_numeric($operation_id) && $operation_id = [$operation_id => 1];
        if ( ! is_array($operation_id)) {
            return  $this->_mass_result([
                'status_header' => 'Удаление операций',
                'status_message' => 'Требуется массив операций',
            ]);
        }
        // processing
        $ids = array_keys($operation_id);
        $r = db()->table('payment_operation')
            ->where('operation_id', 'in', _es($ids))
            ->delete();
        return  $this->_mass_result([
            'status' => $r,
            'status_message' => 'Список: ' . implode(', ', $ids),
            'status_header' => 'Удаление операций',
        ]);
    }


    public function stats()
    {
        $payment_api = &$this->payment_api;
        // payment providers
        $providers = $payment_api->provider();
        $payment_api->provider_options($providers, [
            'method_allow',
        ]);
        // payment status
        $payment_status = $payment_api->get_status();
        $name = 'in_progress';
        $item = $payment_api->get_status(['name' => $name]);
        list($payment_status_in_progress_id, $payment_status_in_progress) = $item;
        // prepare sql
        $sql = db()->select(
            'o.operation_id',
            'o.account_id',
            'o.provider_id',
            'o.options',
            'a.user_id',
            'u.name as user_name',
            'o.amount',
            'o.balance',
            'p.title as provider_title',
            'o.status_id as status_id',
            'o.datetime_start'
            )
            ->table('payment_operation as o')
                ->left_join('payment_provider as p', 'p.provider_id = o.provider_id')
                ->left_join('payment_account  as a', 'a.account_id  = o.account_id')
                ->left_join('user as u', 'u.id = a.user_id')
            ->where('p.system', 'in', 0)
            ->where('p.active', '>=', 1)
            ->where('o.direction', 'out')
            ->where('type_id', 2) // payout
            ->where('status_id', 1) // in_progress
;
        $data = $sql->all();
        $by_method = [];
        $totals = [];
        foreach ($data as $k => $v) {
            $data[$k]['options'] = json_decode($v['options'], true);
            $data[$k]['request'] = $data[$k]['options']['request'][0];
            unset($data[$k]['options']);
            $options = $data[$k]['request']['options'];
            $payout_method = $options['method_id'];
            if ($payout_method == 'pay_card' && substr($options['sender_phone'], 0, 2) == '38') {
                $payout_method = 'pay_card_ukr';
            }
            $by_method[$payout_method]['method'] = $payout_method;
            $by_method[$payout_method]['sum'] += $data[$k]['amount'];
            @$by_method[$payout_method]['count']++;
        }
        ksort($by_method);
        foreach ($by_method as $k => $v) {
            $totals['method'] = '_TOTAL_';
            $totals['sum'] += $v['sum'];
            $totals['count'] += $v['count'];
        }
        $by_method[] = $totals;
        $by_method = array_filter($by_method);
        return table($by_method)
            ->text('method')
            ->text('sum')
            ->text('count');
    }

    /**
     * @param null|mixed $options
     */
    protected function _recreate($options = null)
    {
        // import operation
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // var
        $payment_api = &$this->payment_api;
        $status = false;
        $status_message = '';
        $result = [
            'status' => &$status,
            'status_message' => &$status_message,
        ];
        // start transaction
        $r = $payment_api->transaction_start(['operation_id' => $_operation_id]);
        if ( ! $r) {
            $payment_api->transaction_rollback();
            $status_message = 'Ошибка установки уровня изоляции транзакции';
            return  $result;
        }
        // operation
        $operation = $payment_api->operation(['operation_id' => @$_operation_id]);
        $operation_id = (int) @$operation['operation_id'];
        if ($operation_id != $_operation_id) {
            $payment_api->transaction_rollback();
            $status_message = 'Операция не найдена';
            return  $result;
        }
        // amount
        $amount = (float) $operation['amount'];
        $amounts = @preg_replace('/[^\d\.\,]/', '|', $_amounts);
        $amounts = @explode('|', $amounts);
        if ($amount != array_sum($amounts)) {
            $payment_api->transaction_rollback();
            $status_message = 'Сумма не совпадает: ' . $amount . ' != ' . implode(' + ', $amounts);
            return  $result;
        }
        // balance
        $account_id = (int) $operation['account_id'];
        list($balance, $account_result) = $payment_api->get_balance(['account_id' => $account_id]);
        if ( ! $account_result) {
            $payment_api->transaction_rollback();
            $status_message = 'Счет не найден: ' . $account_id;
            return  $result;
        }
        if ($balance < $amount) {
            $payment_api->transaction_rollback();
            $status_message = 'Баланс '
                . $payment_api->money_text($balance)
                . '; требуется ' . $payment_api->money_text($amount)
                . '; не хватает  ' . $payment_api->money_text($amount - $balance);
            return  $result;
        }
        list($account_id, $account) = $account_result;
        // prepare operation
        $sql_datetime = $payment_api->sql_datetime();
        // status
        $object = $payment_api->get_status(['name' => 'in_progress']);
        list($status_id, $status) = $object;
        if (empty($status_id)) {
            $payment_api->transaction_rollback();
            return  $object;
        }
        $operation_new = $operation;
        // reset fields
        unset($operation_new['operation_id']);
        foreach ($operation_new as $key => $value) {
            if (strpos($key, '_') === 0) {
                unset($operation_new[$key]);
            }
        }
        $operation_new['status_id'] = $status['status_id'];
        $operation_new['datetime_start'] = $sql_datetime;
        $operation_new['datetime_update'] = $sql_datetime;
        $operation_new['datetime_finish'] = null;
        $operation_new['balance'] = null;
        // reset options fields
        $operation_new__options = $operation_new['options'];
        $object = [];
        $object['request'] = $operation_new__options['request'];
        $object['recreate'] = [
            'admin' => main()->ADMIN_ID,
            'datetime' => $sql_datetime,
        ];
        $operation_new['options'] = $object;
        unset(
              $operation_new['options']['request'][0]['data']['fee']
            , $operation_new['options']['request'][0]['data']['amount_currency']
            , $operation_new['options']['request'][0]['data']['amount_currency_total']
        );
        // reset amount
        $operation_new_items = [];
        $table = 'payment_operation';
        foreach ($amounts as $value) {
            $operation_new['amount'] = $value;
            $operation_new['options']['request'][0]['options']['amount'] = $value;
            $operation_new['options']['request'][0]['data']['amount'] = $value;
            $operation_new['options']['request'][0]['datetime'] = $sql_datetime;
            // insert new operation
            $sql_data = $operation_new;
            $sql_data['options'] = json_encode($sql_data['options']);
            $sql_status = db()->table($table)->insert($sql_data, ['escape' => true]);
            if ( ! $sql_status) {
                $payment_api->transaction_rollback();
                $status_message = 'Ошибка ДБ при создании новой операции';
                return  $result;
            }
            $operation_new_items[] = $sql_status;
        }
        // update balance
        $sql_amount = $payment_api->_number_mysql($amount);
        $sql_data = [
            'account_id' => $account_id,
            'datetime_update' => db()->escape_val($sql_datetime),
            'balance' => "( balance - $sql_amount )",
        ];
        $r = $payment_api->balance_update($sql_data, ['is_escape' => false]);
        if ( ! $r['status']) {
            $payment_api->transaction_rollback();
            $status_message = 'Ошибка при обновлении счета';
            return  $result;
        }
        $payment_api->transaction_commit();
        $status_message = 'Создано: ' . implode(', ', $operation_new_items);
        $result['operation_id'] = $operation_id;
        $result['items'] = $operation_new_items;
        return  $result;
    }

    /**
     * @param null|mixed $options
     */
    protected function _user_message($options = null)
    {
        $url = &$this->url;
        // import operation
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if (empty($_status_message)) {
            return  null;
        }
        switch (true) {
            case @$_status === 'in_progress':
                $_css_panel_status = 'warning';
                empty($_status_header) && $_status_header = 'В процессе';
                break;
            case @$_status === 'processing':
                $_css_panel_status = 'warning';
                empty($_status_header) && $_status_header = 'Обработка';
                break;
            case @$_status === 'success' || @$_status === true:
                $_css_panel_status = 'success';
                empty($_status_header) && $_status_header = 'Выполнено';
                break;
            case @$_status === 'refused':
                $_css_panel_status = 'danger';
                empty($_status_header) && $_status_header = 'Отказано';
                break;
            default:
                $_css_panel_status = 'danger';
                empty($_status_header) && $_status_header = 'Ошибка';
                break;
        }
        // body
        $content = empty($_is_html_message) ? $_status_message : htmlentities($_status_message, ENT_HTML5, 'UTF-8', $double_encode = false);
        $panel_body = '<div class="panel-body">' . $content . '</div>';
        // header
        $content = 'Вывод средств';
        if ( ! empty($_status_header)) {
            $content .= ': ' . $_status_header;
        }
        $content = htmlentities($content, ENT_HTML5, 'UTF-8', $double_encode = false);
        $panel_header = '<div class="panel-heading">' . $content . '</div>';
        // footer
        if ( ! empty($_status_footer)) {
            $content = $_status_footer;
        } else {
            $content = '';
            $operation_id = empty($_operation_id) ? (int) $_GET['operation_id'] : $_operation_id;
            if ($operation_id > 0) {
                $url_view = $this->_url('view', ['%operation_id' => $operation_id]);
                $content .= '<a href="' . $url_view . '" class="btn btn-xs btn-info">Назад к операции</a>';
            }
            $url_list = $this->_url('list');
            $content .= '<a href="' . $url_list . '" class="btn btn-xs btn-primary">Список операции</a>';
        }
        isset($content) && $panel_footer = '<div class="panel-footer">' . $content . '</div>';
        // panel
        $result = <<<"EOS"
<div class="panel panel-{$_css_panel_status}">
	$panel_header
	$panel_body
	$panel_footer
</div>
EOS;
        return  $result;
    }
}
