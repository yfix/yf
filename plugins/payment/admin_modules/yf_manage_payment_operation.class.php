<?php

class yf_manage_payment_operation {

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
			'list' => url_admin( [
				'object'       => $object,
			]),
			'payin' => url_admin( [
				'object'       => 'manage_deposit',
				'action'       => 'view',
				'operation_id' => '%operation_id',
			]),
			'payout' => url_admin( [
				'object'       => 'manage_payout',
				'action'       => 'view',
				'operation_id' => '%operation_id',
			]),
			'update_expired' => url_admin( [
				'object' => 'manage_deposit',
				'action' => 'update_expired',
			]),
			'check_all_interkassa' => url_admin( [
				'object' => 'manage_payout',
				'action' => 'check_all_interkassa',
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
			'o.datetime_update' => 'дата обновления',
			'o.datetime_start'  => 'дата создания',
			'o.datetime_finish' => 'дата окончания',
		];
		// provider
		$payment_api = &$this->payment_api;
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
			->radio_box('system', ['' => 'все', '1' => 'системный', '0' => 'внешний'], ['no_label' => 1, 'title' => 'Тип провайдера'])
			->radio_box('direction', ['' => 'все', 'in' => 'приход', 'out' => 'расход'], ['no_label' => 1, 'title' => 'Направление платежа'])
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
			case 'clear':
				$url_redirect_url = url_admin( [
					'object' => $object,
					'action' => 'show',
				]);
				$id = 'manage_payment_operation__show';
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
			->group_by('status_id')->get_2d();
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
		$html        = _class( 'html' );
		// payment providers
		$providers = $payment_api->provider();
		$payment_api->provider_options( $providers, [
			'method_allow',
		]);
		// payment status
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
			'o.direction',
			'o.title',
			'o.options',
			'a.user_id',
			'o.amount',
			// 'a.balance',
			'o.balance',
			'p.title as provider_title',
			'p.system as provider_system',
			'o.status_id as status_id',
			'o.datetime_update',
			'o.datetime_start',
			'o.datetime_finish',
			'u.name as user_name',
			'u.login as user_login',
			'u.nick as user_nick',
			'u.email as user_email'
			)
			->table( 'payment_operation as o' )
				->left_join( 'payment_provider as p', 'p.provider_id = o.provider_id' )
				->left_join( 'payment_account  as a', 'a.account_id  = o.account_id'   )
				->left_join( 'user as u'            , 'u.id = a.user_id'              )
			// ->where( 'p.system', 'in', 0 )
			->where( 'p.active', '>=', 1 )
			// ->where( 'o.direction', 'out' )
		;
		$sql = $db->sql();
		$_this = $this;
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
					'provider_id' => ['cond' => 'eq', 'field' => 'o.provider_id'],
					'balance' => ['cond' => 'between', 'field' => 'a.balance'],
					'amount' => ['cond' => 'between', 'field' => 'o.amount'],
					'datetime_start' => 'daterange_dt_between',
					'datetime_update' => 'daterange_dt_between',
					'datetime_finish' => 'daterange_dt_between',
					'__default_order' => 'ORDER BY o.datetime_update DESC',
				],
			])
			->text( 'operation_id',   'операция'  )
			->func( 'user_name', function( $value, $extra, $row ) {
				$name = $row['user_name'] ?: $row['user_login'] ?: $row['user_nick'] ?: $row['user_email'];
				$result = a('/members/edit/'.$row[ 'user_id' ], $name . ' (id: ' . $row[ 'user_id' ] . ')');
				return( $result );
			}, [ 'desc' => 'пользователь' ] )
			->text( 'title',          'название'  )
			->text( 'provider_title', 'провайдер' )
			->func( 'options', function( $value, $extra, $row ) use( $providers ) {
				$result = '-';
				if( empty( $row[ 'options' ] ) ) { return( $result ); }
				// options
				$options = @json_decode( $row[ 'options' ], true );
				if( empty( $options ) ) { return( $result ); }
				// request
				is_array( $options[ 'request' ] ) && $request = reset( $options[ 'request' ] );
				if( empty( $request ) ) { return( $result ); }
				// method
				$provider_id = @$request[ 'options' ][ 'provider_id' ];
				if( empty( $provider_id ) ) { return( $result ); }
				$method_id   = @$request[ 'options' ][ 'method_id' ];
				if( empty( $method_id ) ) { return( $result ); }
				$method = @$providers[ $provider_id ][ '_method_allow' ][ 'payout' ][ $method_id ];
				if( empty( $method ) ) { return( $result ); }
				$method_title = @$method[ 'title' ];
				$method_title && $result = $method_title;
				return( $result );
			}, [ 'desc' => 'метод' ] )
			->text( 'amount'        , 'сумма' )
			->text( 'balance'       , 'баланс' )
			->func( 'status_id', function( $value, $extra, $row ) use( $manage_lib, $payment_status ) {
				$status_name = $payment_status[ $value ][ 'name' ];
				$title       = $payment_status[ $value ][ 'title' ];
				$css = $manage_lib->css_by_status( [
					'status_name' => $status_name,
				]);
				$result = sprintf( '<span class="%s">%s</span>', $css, $title );
				return( $result );
			}, [ 'desc' => 'статус' ] )
			->text( 'datetime_update', 'дата обновления' )
			->text( 'datetime_start',  'дата создания'   )
			->text( 'datetime_finish', 'дата окончания'  )
			->func( 'operation_id', function( $value, $extra, $row ) use( $_this, $html ) {
				$result = '-';
				$is_system = (bool)$row[ 'provider_system' ];
				$is_in     = $row[ 'direction' ] == 'in';
				$is_out    = $row[ 'direction' ] == 'out';
				$action = [];
				if( !$is_system ) {
					$is_in && $action[] = $html->a( [
						'href'   => $_this->_url( 'payin', [ '%operation_id' => $value ] ),
						'class_add' => 'btn-xs btn-primary',
						'icon'   => 'fa fa-sign-in',
						'text'   => 'Ввод средств',
						'target' => '_blank',
					]);
					$is_out && $action[] = $html->a( [
						'href'   => $_this->_url( 'payout', [ '%operation_id' => $value ] ),
						'class_add' => 'btn-xs btn-primary',
						'icon'   => 'fa fa-sign-out',
						'text'   => 'Вывод средств',
						'target' => '_blank',
					]);
				}
				$action && $result = implode( '', $action );
				return( $result );
			}, [ 'desc' => 'действия' ] )
			->footer_link( 'Обновить просроченные операции', $url[ 'update_expired' ], [ 'title' => 'Обновить просроченные операции (только для ввода средств)', 'class' => 'btn btn-xs btn-primary', 'icon' => 'fa fa-refresh' ] )
			->footer_link( 'Обновить статусы операций Интеркассы', $url[ 'check_all_interkassa' ], [ 'title' => 'Обновить просроченные операции (только для ввода средств)', 'class' => 'btn btn-xs btn-primary', 'icon' => 'fa fa-refresh' ] )
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
}
