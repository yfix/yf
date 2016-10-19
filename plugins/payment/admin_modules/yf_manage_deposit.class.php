<?php

class yf_manage_deposit {

	protected $object      = null;
	protected $action      = null;
	protected $id          = null;
	protected $filter_name = null;
	protected $filter      = null;
	protected $url         = null;

	public $payment_api        = null;
	public $manage_payment_lib = null;

	/**
	*/
	function _init() {
		// class
		$this->payment_api        = _class( 'payment_api'        );
		$this->manage_payment_lib = module( 'manage_payment_lib' );
		// property
		$object      = &$this->object;
		$action      = &$this->action;
		$id          = &$this->id;
		$filter_name = &$this->filter_name;
		$filter      = &$this->filter;
		$url         = &$this->url;
		// setup property
		$object = $_GET[ 'object' ];
		$action = $_GET[ 'action' ];
		$id     = $_GET[ 'id'     ];
		$filter_name = $object . '__' . $action;
		$filter      = $_SESSION[ $filter_name ];
		// url
		$url = [
			'request' => url_admin( [
				'object'       => $object,
				'action'       => 'request',
				'operation_id' => '%operation_id',
			]),
			'status_success' => url_admin( [
				'object'       => $object,
				'action'       => 'status',
				'status'       => 'success',
				'operation_id' => '%operation_id',
			]),
			'status_refused' => url_admin( [
				'object'       => $object,
				'action'       => 'status',
				'status'       => 'refused',
				'operation_id' => '%operation_id',
			]),
			'csv' => url_admin( [
				'object'       => $object,
				'action'       => 'csv',
				'operation_id' => '%operation_id',
			]),
			'list' => url_admin( [
				'object'       => $object,
			]),
			'update_expired' => url_admin( [
				'object' => $object,
				'action' => 'update_expired',
			]),
			'view' => url_admin( [
				'object'       => $object,
				'action'       => 'view',
				'operation_id' => '%operation_id',
			]),
			'balance' => url_admin( [
				'object'     => 'manage_payment',
				'action'     => 'balance',
				'user_id'    => '%user_id',
				'account_id' => '%account_id',
			]),
			'user' => url_admin( [
				'object' => 'members',
				'action' => 'edit',
				'id'     => '%user_id',
			]),
		];
	}

	/**
	*/
	function _url( $name, $replace = null ) {
		$url = &$this->url;
		$result = null;
		if( empty( $url[ $name ] ) ) { return( $result ); }
		if( !is_array( $replace ) ) { return( $url[ $name ] ); }
		$result = str_replace( array_keys( $replace ), array_values( $replace ), $url[ $name ] );
		return( $result );
	}

	/**
	*/
	function _filter_form_show( $filter, $replace ) {
		$order_fields = [
			'o.operation_id'    => 'номер операций',
			'o.amount'          => 'сумма',
			'a.balance'         => 'баланс',
			'o.datetime_start'  => 'дата создания',
			'o.datetime_update' => 'дата обновления',
		];
		$payment_api = _class( 'payment_api' );
		$providers = $payment_api->provider();
		$providers__select_box = [];
		foreach( $providers as $id => $item ) {
			$providers__select_box[ $id ] = $item[ 'title' ];
		}
		$payment_status = $payment_api->get_status();
		$payment_status__select_box = [];
		$payment_status__select_box[ -1 ] = 'ВСЕ СТАТУСЫ';
		foreach( $payment_status as $id => $item ) {
			$payment_status__select_box[ $id ] = $item[ 'title' ];
		}
		$min_date = from('payment_operation')->one('UNIX_TIMESTAMP(MIN(datetime_start))');
		return form($replace, [
				'filter' => true,
				'selected' => $filter,
			])
			->daterange('datetime_start', [
				'format'		=> 'YYYY-MM-DD',
				'min_date'		=> date('Y-m-d', $min_date ?: (time() - 86400 * 30)),
				'max_date'		=> date('Y-m-d', time() + 86400),
				'autocomplete'	=> 'off',
				'desc'			=> 'Дата создания',
				'no_label'		=> 1,
			])
			->daterange('datetime_update', [
				'format'		=> 'YYYY-MM-DD',
				'min_date'		=> date('Y-m-d', $min_date ?: (time() - 86400 * 30)),
				'max_date'		=> date('Y-m-d', time() + 86400),
				'autocomplete'	=> 'off',
				'desc'			=> 'Дата обновления',
				'no_label'		=> 1,
			])
			->daterange('datetime_finish', [
				'format'		=> 'YYYY-MM-DD',
				'min_date'		=> date('Y-m-d', $min_date ?: (time() - 86400 * 30)),
				'max_date'		=> date('Y-m-d', time() + 86400),
				'autocomplete'	=> 'off',
				'desc'			=> 'Дата окончания',
				'no_label'		=> 1,
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
			->save_and_clear()
		;
	}

	/**
	*/
	function _show_filter() {
		$object      = &$this->object;
		$action      = &$this->action;
		$filter_name = &$this->filter_name;
		$filter      = &$this->filter;
		if( !in_array( $action, [ 'show' ] ) ) { return( false ); }
		// url
		$url_base = [
			'object' => $object,
			'action' => 'filter_save',
			'id'     => $filter_name,
		];
		$result = '';
		switch( $action ) {
			case 'show':
				$url_filter       = url_admin( $url_base );
				$url_filter_clear = url_admin( $url_base + [
					'page'   => 'clear',
				]);
				$replace = [
					'form_action' => $url_filter,
					'clear_url'   => $url_filter_clear,
				];
				$result = $this->_filter_form_show( $filter, $replace );
			break;
		}
		return( $result );
	}

	/**
	*/
	function filter_save() {
		$object = &$this->object;
		$id     = &$this->id;
		switch( $id ) {
			case 'manage_deposit__show':
				$url_redirect_url = url_admin( [
					'object' => $object,
				]);
			case 'clear':
				$url_redirect_url = url_admin( [
					'object' => $object,
					'action' => 'show',
				]);
				$id = 'manage_deposit__show';
			break;
		}
		$options = [
			'filter_name'  => $id,
			'redirect_url' => $url_redirect_url,
		];
		return( _class( 'admin_methods' )->filter_save( $options ) );
	}

	/**
	*/
	function _show_quick_filter () {
		$a = [];
		$status_names = from('payment_status')->get_2d('status_id, title');
		$count_by_status = select(['status_id', 'COUNT(*) AS num'])->from('payment_operation')
			->where('direction', '=', 'in')->group_by('status_id')->get_2d();
		$statuses_display = [
			1 => 'text-warning',
			2 => 'text-success',
			5 => 'text-warning',
			3 => 'text-danger',
			6 => 'text-danger',
			4 => 'text-muted',
			7 => 'text-info',
		];
		foreach ((array)$statuses_display as $status_id => $css_class) {
			if ($count_by_status[$status_id]) {
				$name = $status_names[$status_id];
				$a[] = a('/@object/filter_save/clear/?filter=status_id:'.$status_id, $name, 'fa fa-filter', $name, $css_class, '');
			}
		}
		$a[] = a('/@object/filter_save/clear/', 'Clear filter', 'fa fa-close', '', '', '');
		return $a ? '<div class="pull-right">'.implode(PHP_EOL, $a).'</div>' : '';
	}

	/**
	*/
	function _get_daily_data($days = null) {
		$time = time();
		$days = $days ?: 60;
		$min_time = $time - $days * 86400;
		$data = [];
		$sql = select('FROM_UNIXTIME(UNIX_TIMESTAMP(datetime_start), "%Y-%m-%d") AS day', 'COUNT(*) AS count')
			->from('payment_operation')->where('datetime_start', '>', $min_time)
			->group_by('FROM_UNIXTIME(UNIX_TIMESTAMP(datetime_start), "%Y-%m-%d")')
			->where('direction', '=', 'in')
		;
		foreach ((array)$sql->all() as $a) {
			$data[$a['day']] = $a['count'];
		}
		if (!$data) {
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
			if (!$out && !$_data) {
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

	/**
	*/
	function show() {
		$object      = &$this->object;
		$action      = &$this->action;
		$filter_name = &$this->filter_name;
		$filter      = &$this->filter;
		$url         = &$this->url;
		// class
		$payment_api = &$this->payment_api;
		$manage_lib  = &$this->manage_payment_lib;
		// status
		$payment_status = $payment_api->get_status();
		$name = 'in_progress';
		$item = $payment_api->get_status( [ 'name' => $name] );
		list( $payment_status_in_progress_id, $payment_success_in_progress ) = $item;
		if( empty( $payment_status_in_progress_id ) ) {
			$result = [
				'status_message' => 'Статус платежей не найден: ' . $object_name,
			];
			return( $this->_user_message( $result ) );
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
			->table( 'payment_operation as o' )
				->left_join( 'payment_provider as p', 'p.provider_id = o.provider_id' )
				->left_join( 'payment_account  as a', 'a.account_id  = o.account_id'  )
				->left_join( 'user as u'            , 'u.id = a.user_id'              )
			->where( 'p.system', 'in', 0 )
			->where( 'p.active', '>=', 1 )
			->where( 'o.direction', '=', 'in' )
		;
		$sql = $db->sql();
		$result = table( $sql, [
				'filter' => $filter,
				'filter_params' => [
					'status_id'   => function( $a ) use( $payment_status_in_progress_id ) {
						$result = null;
						$value  = $a[ 'value' ];
						// default status_id = in_progress
						if( empty( $value ) ) {
							$value = $payment_status_in_progress_id;
						} elseif( $value == -1 ) {
							$value = null;
						}
						isset( $value ) && $result = ' o.status_id = ' . $value;
						return( $result );
					},
					'provider_id'  => [ 'cond' => 'eq'     , 'field' => 'o.provider_id'   ],
					'balance'      => [ 'cond' => 'between', 'field' => 'a.balance'       ],
					'amount'       => [ 'cond' => 'between', 'field' => 'o.amount'        ],
					'name' => function($a) {
						$v = $a['value'];
						$like = 'LIKE "'._es($v).'%"';
						if (is_numeric($v)) {
							return 'u.id = '.(int)$v;
						} elseif (false !== strpos($v, ',')) {
							return 'u.id IN('._es($v).')';
						} else {
							return '(u.name '.$like.' OR u.nick '.$like.' OR u.login '.$like.' OR u.email '.$like.')';
						}
					},
					'title' => function($a) {
						$v = $a['value'];
						$like = 'LIKE "'._es($v).'%"';
						if (is_numeric($v)) {
							return 'o.operation_id = '.(int)$v;
						} elseif (false !== strpos($v, ',')) {
							return 'o.operation_id IN('._es($v).')';
						} else {
							return '(o.title '.$like.' OR o.options '.$like.')';
						}
					},
					'datetime_start' => 'daterange_dt_between',
					'datetime_update' => 'daterange_dt_between',
					'datetime_finish' => 'daterange_dt_between',
					'__default_order'  => 'ORDER BY o.datetime_update DESC',
				],
			])
			->text( 'operation_id'  , 'операция' )
			->text( 'provider_title', 'провайдер' )
			->text( 'amount'        , 'сумма' )
			->text( 'balance'       , 'баланс' )
			->func( 'user_name', function( $value, $extra, $row ) {
				$name = $row['user_name'] ?: $row['user_login'] ?: $row['user_nick'] ?: $row['user_email'];
				$result = a('/members/edit/'.$row[ 'user_id' ], $name . ' (id: ' . $row[ 'user_id' ] . ')');
				return( $result );
			}, [ 'desc' => 'пользователь' ] )
			->func( 'status_id', function( $value, $extra, $row ) use( $manage_lib, $payment_status ) {
				$status_name = $payment_status[ $value ][ 'name' ];
				$title       = $payment_status[ $value ][ 'title' ];
				$css = $manage_lib->css_by_status( [
					'status_name' => $status_name,
				]);
				$result = sprintf( '<span class="%s">%s</span>', $css, $title );
				return( $result );
			}, [ 'desc' => 'статус' ] )
			->text( 'datetime_start', 'дата создания' )
			->btn( 'Ввод средств',  $url[ 'view'    ], [ 'icon' => 'fa fa-sign-in', 'class_add' => 'btn-primary', 'target' => '_blank' ] )
			// ->btn( 'Пользователь' , $url[ 'user'    ], array( 'icon' => 'fa fa-user'   , 'class_add' => 'btn-info'   ) )
			// ->btn( 'Счет'         , $url[ 'balance' ], array( 'icon' => 'fa fa-money'  , 'class_add' => 'btn-info'   ) )
			->footer_link( 'Обновить просроченные операции', $url[ 'update_expired' ], [ 'class' => 'btn btn-primary', 'icon' => 'fa fa-refresh' ] )
		;

		$data_daily = $this->_get_daily_data($last_days = 180);
		$data_chart = _class('charts')->jquery_sparklines($data_daily);

		$quick_filter = $this->_show_quick_filter();

		return
			'<div class="col-md-12">' .
				( $data_chart ? '<div class="col-md-6" title="'.t('Транзакции по дням').'">'.$data_chart.'</div>' : '') .
				( $quick_filter ? '<div class="col-md-6 pull-right" title="'.t('Быстрый фильтр').'">'.$quick_filter.'</div>' : '') .
			'</div>' .
			$result
		;

	}

	/**
	*/
	function _operation( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// class
		$payment_api = &$this->payment_api;
		$manage_lib  = &$this->manage_payment_lib;
		// check operation
		$operation_id = isset( $_operation_id ) ? $_operation_id : (int)$_GET[ 'operation_id' ];
		$operation = $payment_api->operation( [
			'operation_id' => $operation_id,
		]);
		if( empty( $operation ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Ошибка: операция не найдена',
			];
			return( $this->_user_message( $result ) );
		}
		// import operation
		is_array( $operation ) && extract( $operation, EXTR_PREFIX_ALL | EXTR_REFS, 'o' );
		// check account
		$account_result = $payment_api->get_account( [ 'account_id' => $o_account_id ] );
		if( empty( $account_result ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Счет пользователя не найден',
			];
			return( $this->_user_message( $result ) );
		}
		list( $account_id, $account ) = $account_result;
		// check user
		$user_id = $account[ 'user_id' ];
		$user    = user( $user_id );
		if( empty( $user ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Пользователь не найден: ' . $user_id,
			];
			return( $this->_user_message( $result ) );
		}
		$online_users = _class( 'online_users', null, null, true );
		$user_is_online = $online_users->_is_online( $user_id );
		// check provider
		$providers_user = $payment_api->provider();
		if( empty( $providers_user[ $o_provider_id ] ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Неизвестный провайдер',
			];
			return( $this->_user_message( $result ) );
		}
		$provider = &$providers_user[ $o_provider_id ];
		// providers by name
		$providers_user__by_name = [];
		foreach( $providers_user as &$item ) {
			$provider_name = $item[ 'name' ];
			$providers_user__by_name[ $provider_name ] = &$item;
		}
		$provider_name = &$provider[ 'name' ];
		$provider_class = $payment_api->provider_class( [
			'provider_name' => $provider[ 'name' ],
		]);
		if( empty( $provider_class ) ) {
			$result = [
				'status_message' => 'Провайдер недоступный: ' . $provider[ 'title' ],
			];
			return( $this->_user_message( $result ) );
		}
		// check request
		$request = [];
		if(
			empty( $o_options[ 'request' ] )
			|| !is_array( $o_options[ 'request' ] )
		) {
			// $result = array(
				// 'status_message' => 'Параметры запроса отсутствует',
			// );
			// return( $this->_user_message( $result ) );
		} else {
			$request = reset( $o_options[ 'request' ] );
		}
		// check method
		$method_id = null;
		$method = [];
		// check operation status
		$statuses = $payment_api->get_status();
		if( empty( $statuses[ $o_status_id ] ) ) {
			$result = [
				'status_message' => 'Неизвестный статус операции: '. $o_status_id,
			];
			return( $this->_user_message( $result ) );
		}
		$o_status = $statuses[ $o_status_id ];
		// status css
		$status_name  = $o_status[ 'name' ];
		$status_title = $o_status[ 'title' ];
		$css = $manage_lib->css_by_status( [
			'status_name' => $status_name,
		]);
		$html_status_title = sprintf( '<span class="%s">%s</span>', $css, $status_title );
		// check response
		$response = null;
		if(
			!empty( $o_options[ 'response' ] )
			&& is_array( $o_options[ 'response' ] )
		) {
			$response = $o_options[ 'response' ];
		}
		// misc
		$html_amount          = $payment_api->money_html( $o_amount );
		$html_datetime_start  = $o_datetime_start;
		$html_datetime_update = $o_datetime_update;
		$html_datetime_finish = $o_datetime_finish;
		// result
		$result = [
			'is_valid'                => true,
			'operation_id'            => &$operation_id,
			'operation'               => &$operation,
			'statuses'                => &$statuses,
			'status'                  => &$o_status,
			'status_id'               => &$o_status_id,
			'status_name'             => &$status_name,
			'status_title'            => &$status_title,
			'html_status_title'       => &$html_status_title,
			'account_id'              => &$account_id,
			'account'                 => &$account,
			'user_id'                 => &$user_id,
			'user'                    => &$user,
			'user_is_online'          => &$user_is_online,
			'provider_id'             => &$o_provider_id,
			'provider'                => &$provider,
			'provider_name'           => &$provider_name,
			'provider_class'          => &$provider_class,
			'providers_user'          => &$providers_user,
			'providers_user__by_name' => &$providers_user__by_name,
			'request'                 => &$request,
			'method_id'               => &$method_id,
			'method'                  => &$method,
			'response'                => &$response,
			'html_amount'             => &$html_amount,
			'html_datetime_start'     => &$html_datetime_start,
			'html_datetime_update'    => &$html_datetime_update,
			'html_datetime_finish'    => &$html_datetime_finish,
		];
		return( $result );
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
	 *   etc: see _operation()
	 */
	function view() {
		// check operation
		$operation = $this->_operation();
		// import options
		is_array( $operation ) && extract( $operation, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_is_valid ) ) { return( $operation ); }
		// var
		$html        = _class( 'html' );
		$payment_api = _class( 'payment_api' );
		// prepare view: request options
		$html_request_options = null;
		if( !empty( $_method ) ) {
			$content = [];
			foreach( $_method[ 'option' ] as $key => $title ) {
				if( !empty( $_request[ 'options' ][ $key ] ) ) {
					$content[ $title ] = $_request[ 'options' ][ $key ];
				}
			}
			$html_request_options = $html->simple_table( $content, [ 'no_total' => true ] );
		}
		// prepare view: response options
		$content = null;
		if( !empty( $_response ) ) {
			$response = array_reverse( $_response );
			$content = table( $response, [ 'no_total' => true ] )
				->text( 'datetime', 'дата' )
				->func( 'data', function( $value, $extra, $row ) use( $_provider_name, $_providers_user__by_name  ) {
					// message
					$message = @$row[ 'status_message' ] ?: @$row[ 'data' ][ 'message' ];
					$result = t( trim( trim( $message ), '.,:' ) );
					// provider
					$provider_name = @$row[ 'provider_name' ];
					if( $_provider_name && $provider_name != $_provider_name ) {
						$provider_title = @$_providers_user__by_name[ $provider_name ][ 'title' ];
						$result .= ' ('. $provider_title .')';
					}
					return( $result );
				}, [ 'desc' => 'сообщение' ] )
				->func( 'data', function( $value, $extra, $row ) {
					$result = @$row[ 'state' ] ?: @$row[ 'data' ][ 'state' ] ?: null;
					return( $result );
				}, [ 'desc' => 'статус' ] )
			;
		}
		$html_response = $content;
		// prepare view: operation options
		$user_link = $html->a( [
			'href'  => $this->_url( 'user', [ '%user_id' => $_user_id ] ),
			'icon'  => 'fa fa-user',
			'title' => 'Профиль',
			'text'  => $_user[ 'name' ],
		]);
		$balance_link = $html->a( [
			'href'  => $this->_url( 'balance', [ '%user_id' => $_user_id, '%account_id' => $_account_id ] ),
			'title' => 'Баланс',
			'text'  => $payment_api->money_text( $_account[ 'balance' ] ),
		]);
		$content = [
			'Операция'        => $_operation_id,
			'Пользователь'    => $user_link . $balance_link,
			'Сумма'           => $_html_amount,
			'Провайдер'       => $_provider[ 'title' ],
			'Статус'          => $_html_status_title,
			'Дата создания'   => $_html_datetime_start,
			'Дата обновления' => $_html_datetime_update,
			'Дата завершения' => $_html_datetime_finish,
		];
		$html_operation_options = $html->simple_table( $content, [ 'no_total' => true ] );
		$url_view = $this->_url( 'view', [ '%operation_id' => $_operation_id ] );
		// render
		$is_test = $_provider_class->is_test();
		$is_progressed = $_status[ 'name' ] == 'in_progress';
		$replace = $operation + [
			'is_test'       => $is_test,
			'is_progressed' => $is_progressed,
			'header_data'   => $html_operation_options,
			'request_data'  => $html_request_options,
			'response_data' => $html_response,
			'url' => [
				'list'           => $this->_url( 'list' ),
				'view'           => $this->_url( 'view',           [ '%operation_id' => $_operation_id ] ),
				'request'        => $this->_url( 'request',        [ '%operation_id' => $_operation_id ] ),
				'status_success' => $this->_url( 'status_success', [ '%operation_id' => $_operation_id ] ),
				'status_refused' => $this->_url( 'status_refused', [ '%operation_id' => $_operation_id ] ),
				'csv'            => $this->_url( 'csv',            [ '%operation_id' => $_operation_id ] ),
				'provider_operation_detail' => $url_operation_detail,
			]
		];
		$result = tpl()->parse( 'manage_deposit/view', $replace );
		return( $result );
	}

	/**
	*/
	protected function _user_message( $options = null ) {
		$url = &$this->url;
		// import operation
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_status_message ) ) { return( null ); }
		switch( !empty( $_status ) ) {
			case true:
				$_css_panel_status = 'success';
				empty( $_status_header ) && $_status_header = 'Выполнено';
				break;
			case false:
			default:
				$_css_panel_status = 'danger';
				empty( $_status_header ) && $_status_header = 'Ошибка';
				break;
		}
		// body
		$content = empty( $is_html_message ) ? $_status_message : htmlentities( $_status_message, ENT_HTML5, 'UTF-8', $double_encode = false );
		$panel_body = '<div class="panel-body">'. $content .'</div>';
		// header
		$content = 'Вывод средств';
		if( !empty( $_status_header ) ) { $content .= ': ' . $_status_header; }
		$content = htmlentities( $content, ENT_HTML5, 'UTF-8', $double_encode = false );
		$panel_header = '<div class="panel-heading">'. $content .'</div>';
		// footer
		if( !empty( $_status_footer ) ) {
			$content = $_status_footer;
		} else {
			$content  = '';
			$operation_id = empty( $_operation_id ) ? (int)$_GET[ 'operation_id' ] :  $_operation_id;
			if( $operation_id > 0 ) {
				$url_view = $this->_url( 'view', [ '%operation_id' => $operation_id ] );
				$content .= '<a href="'. $url_view .'" class="btn btn-info">Назад к операции</a>';
			}
			$url_list = $this->_url( 'list' );
			$content .= '<a href="'. $url_list .'" class="btn btn-primary">Список операции</a>';
		}
		isset( $content ) && $panel_footer = '<div class="panel-footer">'. $content .'</div>';
		// panel
		$result =  <<<"EOS"
<div class="panel panel-{$_css_panel_status}">
	$panel_header
	$panel_body
	$panel_footer
</div>
EOS;
		return( $result );
	}

	/**
	*/
	function status() {
		// check operation
		$operation = $this->_operation();
		// import options
		is_array( $operation ) && extract( $operation, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_is_valid ) ) { return( $result ); }
		$status = $_GET[ 'status' ];
		// check status
		if( !in_array( $status, [ 'success', 'refused' ] ) ) {
			$result = [
				'status_message' => 'Неизвестный статус операции: '. $status,
			];
			return( $this->_user_message( $result ) );
		}
		// update status
		$result = $_provider_class->_api_deposition( [
			'provider_name' => $_provider[ 'name' ],
			'response'      => [
				'operation_id' => $_operation_id,
				'title'        => $_operation[ 'title' ],
				'comment'      => 'updated by admin: ' . main()->ADMIN_ID,
			],
			'status_name'    => $status,
			'status_message' => $_operation[ 'title' ],
		]);
		if( empty( $result[ 'status' ] ) ) {
			$result[ 'operation_id' ] = $_operation_id;
			return( $this->_user_message( $result ) );
		}
		$url_view = $this->_url( 'view', [ '%operation_id' => $_operation_id ] );
		return( js_redirect( $url_view, false ) );
	}

	/**
	*/
	function _update_expired() {
		// var
		$payment_api = _class( 'payment_api' );
		// update status only in_progress
		$object = $payment_api->get_status( [ 'name' => 'in_progress' ] );
		list( $status_id, $status ) = $object;
		if( empty( $status_id ) ) { return( $object ); }
		$object = $payment_api->get_status( [ 'name' => 'expired' ] );
		list( $new_status_id, $new_status ) = $object;
		if( empty( $new_status_id ) ) { return( $object ); }
		// date: over 3 days
		$ts = strtotime( $payment_api->DEPOSITION_TIME );
		$sql_datetime_over = $payment_api->sql_datetime( $ts );
		$sql_datetime = $payment_api->sql_datetime();
		$db = db()->table( 'payment_operation' )
			->where( 'status_id', '=', $status_id )
			->where( 'direction', '=', 'in' )
			->where( 'datetime_update', '<', $sql_datetime_over )
			->where_null( 'datetime_finish' )
		;
		db()->begin();
		$result = $db->update( [
			'status_id'       => $new_status_id,
			'datetime_finish' => $sql_datetime,
		]);
		if( empty( $result ) ) { db()->rollback(); return( null ); }
		db()->commit();
		return( true );
	}

	/**
	*/
	function update_expired() {
		$url = &$this->url;
		// command line interface
		$is_cli = ( php_sapi_name() == 'cli' );
		$is_cli && $this->_update_expired_cli();
		// web
		$replace = [
			'is_confirm' => false,
		];
		$result = form( $replace )
			->on_post( function( $data, $extra, $rules ) {
				$is_confirm = !empty( $_POST[ 'is_confirm' ] );
				if( $is_confirm ) {
					$result = $this->_update_expired();
					if( empty( $result ) ) {
						$level = 'error';
						$message = 'Ошибка при обновлении';
					} else {
						$level = 'success';
						$message = 'Выполнено обновление';
					}
					common()->add_message( $message, $level );
				} else {
					common()->message_info( 'Требуется подтверждение, для выполнения операции' );
				}
			})
			->info( 'header', [ 'value' => 'Ввод средств: обновление статуса просроченных операций', 'no_label' => true, 'class' => 'text-warning' ] )
			->check_box( 'is_confirm', [ 'desc' => 'Подтверждение', 'no_label' => true ] )
			->row_start()
				->submit( 'operation', 'update', [ 'desc' => 'Обновить', 'icon' => 'fa fa-refresh' ] )
				->link( 'Назад' , $url[ 'list' ], [ 'class' => 'btn btn-default', 'icon' => 'fa fa-chevron-left' ] )
			->row_end()
		;
		return( $result );
	}

	/**
	*/
	function _update_expired_cli() {
		$result = $this->_update_expired();
		if( empty( $result ) ) {
			$status = 1;
			$message = 'Update is fail';
		} else {
			$status = 0;
			$message = 'Update is success';
			;
		}
		echo( $message . PHP_EOL );
		exit( $status );
	}
}
