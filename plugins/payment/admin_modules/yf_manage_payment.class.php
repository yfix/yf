<?php

class yf_manage_payment {

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
			'balance' => url_admin( [
				'object'     => $object,
				'action'     => 'balance',
				'user_id'    => '%user_id',
				'account_id' => '%account_id',
			]),
			'operation_remove' => url_admin( [
				'object'       => $object,
				'action'       => 'mass_remove',
				'operation_id' => '%operation_id',
			]),
		];
	}

	/**
	*/
	function _url( $name, $replace = null, $url = null ) {
		$url = @$url ?: $this->url;
		$result = null;
		if( empty( $url[ $name ] ) ) { return( $result ); }
		if( !is_array( $replace ) ) { return( $url[ $name ] ); }
		$result = str_replace( array_keys( $replace ), array_values( $replace ), $url[ $name ] );
		return( $result );
	}

	/**
	*/
	function _filter_form_show( $filter, $replace ) {
		$order_fields = [];
		foreach( explode( '|', 'user_id|name|email|add_date|last_login|num_logins|active|balance|datetime_create|datetime_update' ) as $f ) {
			$order_fields[ $f ] = $f;
		}
		$min_date = from('payment_account')->one('UNIX_TIMESTAMP(MIN(datetime_create))');
		return form($replace, [
				'filter' => true,
				'selected' => $filter,
			])
			->daterange('datetime_create', [
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
			->text('user_id', 'Номер(а) пользователя', ['no_label' => 1])
			->text('name', 'Имя', ['no_label' => 1])
			->text('email', 'Почта', ['no_label' => 1])
			->row_start()
				->number('balance', 'Баланс от')
				->number('balance__and', 'Баланс до')
			->row_end()
			->select_box('group', main()->get_data('user_groups'), ['show_text' => 1, 'no_label' => 1])
			->row_start()
				->select_box('order_by', $order_fields, ['show_text' => 1, 'desc' => 'Сортировка'])
				->select_box('order_direction', ['asc' => '⇑', 'desc' => '⇓'])
			->row_end()
			->save_and_clear()
		;
	}

	/**
	*/
	function _filter_form_balance( $filter, $replace ) {
		$order_fields = [];
		foreach( explode( '|', 'operation_id|datetime_update|datetime_start|datetime_finish|title|amount|balance' ) as $f ) {
			$order_fields[ $f ] = $f;
		}
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
		$min_date = from('payment_account')->one('UNIX_TIMESTAMP(MIN(datetime_create))');
		return form($replace, [
				'filter' => true,
				'selected' => $filter,
			])
			->hidden( 'user_id'    )
			->hidden( 'account_id' )
			->text( 'operation_id', 'Номер операции' )
			->text( 'title'       , 'Название'       )
			->select_box( 'status_id'  , $payment_status__select_box, [ 'show_text' => 'статус'    , 'desc' => 'Статус'     ] )
			->select_box( 'provider_id', $providers__select_box     , [ 'show_text' => 'провайдер' , 'desc' => 'Провайдер'  ] )
			->radio_box( 'direction', [ '' => 'все', 'in' => 'приход', 'out' => 'расход' ], [ 'desc' => 'Направление' ] )
			->daterange('datetime_update', [
				'format'		=> 'YYYY-MM-DD',
				'min_date'		=> date('Y-m-d', $min_date ?: (time() - 86400 * 30)),
				'max_date'		=> date('Y-m-d', time() + 86400),
				'autocomplete'	=> 'off',
				'desc'			=> 'Дата обновления',
				'no_label'		=> 1,
			])
			->select_box( 'order_by', $order_fields, [ 'show_text' => 1, 'desc' => 'Сортировка' ] )
			->radio_box( 'order_direction', [ 'asc' => 'прямой', 'desc' => 'обратный' ], [ 'desc' => 'Направление сортировки' ] )
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
		if( !in_array( $action, [ 'show', 'balance' ] ) ) { return( false ); }
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
			case 'balance':
				$user_id    = (int)$_GET[ 'user_id' ];
				$account_id = (int)$_GET[ 'account_id' ];
				$url_filter = url_admin( $url_base + [
					'user_id'    => $user_id,
					'account_id' => $account_id,
				]);
				$url_filter_clear = url_admin( $url_base + [
					'user_id'    => $user_id,
					'account_id' => $account_id,
					'page'       => 'clear',
				]);
				$replace = [
					'form_action' => $url_filter,
					'clear_url'   => $url_filter_clear,
				];
				$result = $this->_filter_form_balance( $filter, $replace );
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
			case 'manage_payment__show':
				$url_redirect_url = url_admin( [
					'object'     => $object,
				]);
			break;
			case 'manage_payment__balance':
				$user_id    = (int)$_GET[ 'user_id' ];
				$account_id = (int)$_GET[ 'account_id' ];
				$url_redirect_url = url_admin( [
					'object'     => $object,
					'action'     => 'balance',
					'user_id'    => $user_id,
					'account_id' => $account_id,
				]);
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
	function show() {
		$object      = &$this->object;
		$action      = &$this->action;
		$filter_name = &$this->filter_name;
		$filter      = &$this->filter;
		$url         = &$this->url;
		$num_daily = $this->_get_daily_stats('num', $last_days = 180);
		$num_chart = _class('charts')->jquery_sparklines($num_daily);
		if ($num_chart) {
			$num_chart = '<div title="'.t('Транзакции в системе по дням').'" style="margin-bottom: 10px;">'.$num_chart.'</div>';
		}
		$sql = db()->select(
				'u.id as id',
				'u.id as user_id',
				'pa.balance as balance',
				'pa.account_id as account_id',
				'u.name as name',
				'u.login as login',
				'u.nick as nick',
				'u.email as email'
			)
			->table( 'user as u' )
			->left_join( 'payment_account as pa', 'pa.user_id = u.id' )
			->sql();
		$_this = $this;
		return $sum_chart. $num_chart.
			table( $sql, [
				'filter' => $filter,
				'filter_params' => [
					'user_id' => [ 'cond' => 'in', 'field' => 'u.id' ],
					'name'    => 'like',
					'balance' => 'between',
					'email'   => 'like',
					'datetime_create' => 'daterange_dt_between',
					'datetime_update' => 'daterange_dt_between',
				],
				'hide_empty' => true,
			])
			->on_before_render(function($p, $data, $table) use ($_this) {
			})
			->text( 'id', 'Номер'  )
			->func( 'user_name', function( $value, $extra, $row ) {
				$name = $row['name'] ?: $row['login'] ?: $row['nick'] ?: $row['email'];
				$result = a('/members/edit/'.$row[ 'user_id' ], $name . ' (id: ' . $row[ 'user_id' ] . ')');
				return( $result );
			}, [ 'desc' => 'пользователь' ] )
			->text( 'email'  , 'Почта'  )
			->text( 'balance', 'Баланс' )
			->func('id', function($in, $e, $a, $p, $table) use ($_this) {
				if (!isset($table->_data_daily_sum)) {
					$table->_data_daily_sum = $_this->_get_users_daily_payments($table->_ids, 'sum');
				}
				$daily = _class('charts')->jquery_sparklines($table->_data_daily_sum[$a['id']]);
				return $daily ? '<span title="'.t('График изменения баланса').'">'.$daily.'</span>' : false;
			}, ['desc' => 'Изменение баланса'])
			->func('id', function($in, $e, $a, $p, $table) use ($_this) {
				if (!isset($table->_data_daily_num)) {
					$table->_data_daily_num = $_this->_get_users_daily_payments($table->_ids, 'num');
				}
				$daily = _class('charts')->jquery_sparklines($table->_data_daily_num[$a['id']]);
				return $daily ? '<span title="'.t('Транзакции').'">'.$daily.'</span>' : false;
			}, ['desc' => 'Транзакции'])
			->btn( 'Баланс' , $url[ 'balance' ], [ 'icon' => 'fa fa-money', 'class_add' => 'btn-primary', 'target' => '_blank' ] )
		;
	}

	/**
	*/
	function _operation_sql( $options = null ) {
		$payment_api = _class( 'payment_api' );
		list( $sql ) = $payment_api->operation( $options );
		$result = $sql;
		return( $result );
	}

	/**
	*/
	function _operation_table( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// class
		$manage_lib  = &$this->manage_payment_lib;
		$html       = _class( 'html' );
		// status
		$payment_status = &$_payment_status;
		$result = table( $_sql, [
				'filter' => $_filter,
				'filter_params' => [
					'operation_id'    => 'in',
					'title'           => 'like',
					'datetime_create' => 'daterange_dt_between',
					'datetime_update' => 'daterange_dt_between',
				],
			])
			->text( 'operation_id'   , 'Номер'           )
			->text( 'title'          , 'Название'        )
			->func( 'provider_id', function($in, $e, $a, $p, $table) use( $_providers ) {
				$system    = &$_providers[ $in ][ 'system' ];
				$direction = &$a[ 'direction' ];
				$url = null;
				$title = $_providers[ $in ][ 'title' ];
				if( empty( $system ) ) {
					$uri = '';
					switch( $direction ) {
						case 'in':
							$uri   = '/manage_deposit';
							break;
						case 'out':
							$uri   = '/manage_payout';
							break;
					}
					$uri && $url = a( $uri . '/view?operation_id=' . $a[ 'operation_id' ], $title );
				}
				$result = $url ?: $title;
				return( $result );
			}, [ 'desc' => 'Провайдер' ] )
			->text( 'amount'         , 'Сумма'           )
			->text( 'balance'        , 'Баланс'          )
			->func( 'status_id', function( $value, $extra, $row ) use( $manage_lib, $payment_status ) {
				$status_name = $payment_status[ $value ][ 'name' ];
				$title       = $payment_status[ $value ][ 'title' ];
				$css = $manage_lib->css_by_status( [
					'status_name' => $status_name,
				]);
				$result = sprintf( '<span class="%s">%s</span>', $css, $title );
				return( $result );
			}, [ 'desc' => 'статус' ] )
			->date( 'datetime_update', 'Дата'           , [ 'format' => 'full', 'nowrap' => 1 ] )
			->date( 'datetime_start' , 'Дата начала'    , [ 'format' => 'full', 'nowrap' => 1 ] )
			->date( 'datetime_finish', 'Дата завершения', [ 'format' => 'full', 'nowrap' => 1 ] )
#			->btn_func('Отмена', function($row) {
#				return a('/manage_payment/cancel/'.(int)$row['operation_id'], 'Отмена', 'fa fa-ban', 'Отмена', 'btn btn-xs btn-warning', '', '');
#			})
#			->btn_func('Удаление', function($row) {
#				return a('/manage_payment/remove/'.(int)$row['operation_id'], 'Удаление', 'fa fa-remove', 'Удаление', 'btn btn-xs btn-warning', '', '');
#			})
		;
		return( $result );
	}

	/**
	*/
	function balance() {
		$object      = &$this->object;
		$action      = &$this->action;
		$filter_name = &$this->filter_name;
		$filter      = &$this->filter;
		$url         = &$this->url;
		$user_id     = (int)$_GET[ 'user_id' ];
		$account_id  = (int)$_GET[ 'account_id' ];
		$url_back    = url_admin( '/@object' );
		$payment_api = _class( 'payment_api' );
		$payment_status = $payment_api->get_status();
		// check id: user, operation
		if( $user_id > 0 ) {
			$user_info = user( $user_id );
			if( $account_id > 0 ) {
				list( $account_id, $account ) = $payment_api->get_account__by_id( [
					'account_id' => $account_id,
				]);
			} else {
				list( $account_id, $account ) = $payment_api->account( [
					'user_id' => $user_id,
				]);
			}
		} else {
			common()->message_error( 'Не определен пользователь', [ 'translate' => false ] );
			$form = form()->link( 'Назад', $url_back, [ 'class' => 'btn', 'icon' => 'fa fa-chevron-left' ] );
			return( $form );
		}
		$user = user( $user_id );
		$uname = $user['name'] ?: $user['login'] ?: $user['email'];
		// prepare url
		$url_form_action = url_admin( [
			'object'     => $object,
			'action'     => $action,
			'user_id'    => $user_id,
			'account_id' => $account_id,
		]);
		$url_operation = url_admin( [
			'object'     => $object,
			'action'     => 'operation',
			'user_id'    => $user_id,
			'account_id' => $account_id,
		]);
		// prepare provider
		$providers = $payment_api->provider( [
			'all' => true,
		]);
		$items = [];
		foreach( $providers as $i => $item ) {
			$items[ $item[ 'name' ] ] = $item[ 'title' ];
		}
		$providers_form = $items;
		// prepare form
		$replace = [
			'amount'        => null,
			'user_id'       => $user_id,
			'provider_name' => 'administration',
			'account_id'    => $account_id,
				'form_action'   => $url_form_action,
				'redirect_link' => $url_operation,
				'back_link'     => $url_back,
		];
		// $replace += $_POST + $data;
		$replace += $_POST;
		$form = form( $replace, [ 'autocomplete' => 'off' ] )
			->validate([
				'amount'        => 'trim|required|numeric|greater_than[0]',
				'provider_name' => 'trim|required',
			])
			->on_validate_ok( function( $data, $extra, $rules ) use( &$user_id, &$account_id, &$account ) {
				$payment_api = _class( 'payment_api' );
				$provider_name = $_POST[ 'provider_name' ];
				$provider_name = empty( $provider_name ) ? 'administration' : $provider_name;
				$operation = $_POST[ 'operation' ];
				if( $operation == 'payment' ) {
					$provider_name = 'administration';
				}
				$options = [
					'user_id'         => $user_id,
					'account_id'      => $account_id,
					'amount'          => $_POST[ 'amount' ],
					'operation_title' => $_POST[ 'title' ],
					'operation'       => $operation,
					'provider_name'   => $provider_name,
					'is_balance_limit_lower' => false,
				];
				$result = $payment_api->transaction( $options );
				if( !empty( $result[ 'form' ] ) ) {
					$form = $result[ 'form' ]
						. '<script>document.forms[0].submit();</script>'
					;
					echo $form;
					exit;
				}
				if( $result[ 'status' ] === true ) {
					$message = 'message_success';
					if( empty( $account_id ) ) {
						$new_account = true;
						list( $account_id, $account ) = $payment_api->get_account();
					}
				} else {
					$message = 'message_error';
				}
				common()->$message( $result[ 'status_message' ], [ 'translate' => false ] );
				if( !$is_account ) {
					$url = url_admin( [
						'object'     => $_GET[ 'object' ],
						'action'     => $_GET[ 'action' ],
						'user_id'    => $user_id,
						'account_id' => $account_id,
					]);
					return( js_redirect( $url ) );
				}
			})
			->row_start()
				->container('<div class="controls"><label>'.a('/members/edit/'.$user_id, t('Edit user'), 'fa fa-user', $uname.'&nbsp;['.$user_id.']', '').'<br><small>account_id: </small>'.(int)$account_id.' &nbsp;</label></div>')
				->float( 'amount', 'Сумма' )
				->text( 'title', 'Название' )
				->hidden( 'provider_name', 'administration')
				->submit( 'operation', 'deposition', [ 'desc' => 'Пополнить' ] )
				->submit( 'operation', 'payment',    [ 'desc' => 'Списать' ] )
			->row_end()
		;
		if( $account_id > 0 ) {
			// fetch operations
			$operation_options = [
				'user_id'     => $user_id,
				'account_id'  => $account_id,
				'no_limit'    => true,
				'no_order_by' => true,
				'sql'         => true,
			];
			$sql = $this->_operation_sql( $operation_options );
			if( empty( $filter ) ) {
				$filter = [
					'order_by'        => 'datetime_update',
					'order_direction' => 'desc',
				];
			}
			// operations table
			$table = $this->_operation_table( [
				'filter'         => $filter,
				'sql'            => $sql,
				'user_id'        => $user_id,
				'payment_status' => $payment_status,
				'providers'      => $providers,
			]);
		} else {
			if( !$_POST[ 'amount' ] ) {
				common()->message_warning( 'Счет не определен', [ 'translate' => false ] );
			}
		}
		$balance      = '';
		$currency_str = '';
		$back = form_item()->link( 'Назад', $url_back, [ 'class' => 'btn', 'icon' => 'fa fa-chevron-left' ] );
		if( $account ) {
			list( $currency_id, $currency ) = $payment_api->get_currency__by_id( $account );
			$currency && $currency_str = ' ' . $currency[ 'short' ];
			$balance = $account[ 'balance' ];
		}
		$url_user = a( '/members/edit/'.$user_id, $user[ 'name' ] );
		$replace += [
			'user'     => $user,
			'url_user' => $url_user,
			'balance'  => [
				'amount'   => $balance,
				'currency' => $currency_str,
			],
		];
		$header = tpl()->parse( 'manage_payment/balance_header', $replace );
		$result = $header . $form . $table;
		return( $result );
	}

	/**
	*/
	function _get_users_daily_payments($user_ids = [], $type = 'sum') {
		if (!$user_ids) {
			return false;
		}
		if (!is_array($user_ids)) {
			$user_ids = [$user_ids];
		}
		$time = time();
		$days = 60;
		$min_time = $time - $days * 86400;
		$data = [];
		$sql = '
			SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(o.datetime_start), "%Y-%m-%d") AS day, a.user_id, o.direction, COUNT(*) AS num, SUM(amount) AS sum
			FROM '.db("payment_account").' AS a
			INNER JOIN '.db("payment_operation").' AS o ON o.account_id = a.account_id
			WHERE o.status_id = 2
				AND a.user_id IN('.implode(',', $user_ids).')
				AND o.datetime_start >= "'.date('Y-m-d H:i:s', $min_time).'"
			GROUP BY FROM_UNIXTIME(UNIX_TIMESTAMP(o.datetime_start), "%Y-%m-%d"), a.user_id, o.direction
		';
		foreach ((array)db()->get_all($sql) as $a) {
			$data[$a['user_id']][$a['day']][$type][$a['direction']] = $a[$type];
		}
		if (!$data) {
			return false;
		}
		$dates = [];
		foreach (range($days, 0) as $days_ago) {
			$date = date('Y-m-d', $time - $days_ago * 86400);
			$dates[$date] = $days_ago;
		}
		$result = [];
		foreach ((array)$data as $user_id => $user_dates) {
			$result[$user_id] = [];
			$_data = null;
			foreach ($dates as $date => $days_ago) {
				$in = $user_dates[$date][$type]['in'];
				$out = $user_dates[$date][$type]['out'];
				if ($type == 'num') {
					$_data = [$in, $out];
				} else {
					$_data = $in - $out;
				}
				// Trim empty values from left side
				if (!$result[$user_id] && !$_data) {
					continue;
				}
				$result[$user_id][$date] = $_data ?: null;
			}
			// Trim values from the right side too
			foreach (array_reverse($result[$user_id], $preserve_keys = true) as $k => $v) {
				if (is_array($v)) {
					if (array_sum($v)) {
						break;
					}
				} elseif ($v) {
					break;
				}
				unset($result[$user_id][$k]);
			}
		}
		return $result;
	}

	/**
	*/
	function _get_daily_stats($type = 'sum', $days = null) {
		$time = time();
		$days = $days ?: 60;
		$min_time = $time - $days * 86400;
		$data = [];
		$sql = '
			SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(o.datetime_start), "%Y-%m-%d") AS day, o.direction, COUNT(*) AS num, SUM(amount) AS sum
			FROM '.db("payment_account").' AS a
			INNER JOIN '.db("payment_operation").' AS o ON o.account_id = a.account_id
			WHERE o.status_id = 2
				AND o.datetime_start >= "'.date('Y-m-d H:i:s', $min_time).'"
			GROUP BY FROM_UNIXTIME(UNIX_TIMESTAMP(o.datetime_start), "%Y-%m-%d"), o.direction
		';
		foreach ((array)db()->get_all($sql) as $a) {
			$data[$a['day']][$a['direction']] = $a[$type];
		}
		if (!$data) {
			return false;
		}
		$dates = [];
		foreach (range($days, 0) as $days_ago) {
			$date = date('Y-m-d', $time - $days_ago * 86400);
			$dates[$date] = $days_ago;
		}
		$result = [];
		$_data = null;
		foreach ($dates as $date => $days_ago) {
			$in = $data[$date]['in'];
			$out = $data[$date]['out'];
			if ($type == 'num') {
				$_data = [$in, $out];
			} else {
				$_data = $in - $out;
			}
			// Trim empty values from left side
			if (!$result && !$_data) {
				continue;
			}
			$result[$date] = $_data ?: null;
		}
		// Trim values from the right side too
		foreach (array_reverse($result, $preserve_keys = true) as $k => $v) {
			if (is_array($v)) {
				if (array_sum($v)) {
					break;
				}
			} elseif ($v) {
				break;
			}
			unset($result[$k]);
		}
		return $result;
	}
}
