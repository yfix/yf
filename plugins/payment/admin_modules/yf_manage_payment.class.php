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
		$url = array(
			'balance' => url_admin( array(
				'object'     => $object,
				'action'     => 'balance',
				'user_id'    => '%user_id',
				'account_id' => '%account_id',
			)),
			'operation_remove' => url_admin( array(
				'object'       => $object,
				'action'       => 'mass_remove',
				'operation_id' => '%operation_id',
			)),
		);
	}

	function _url( $name, $replace = null, $url = null ) {
		$url = @$url ?: $this->url;
		$result = null;
		if( empty( $url[ $name ] ) ) { return( $result ); }
		if( !is_array( $replace ) ) { return( $url[ $name ] ); }
		$result = str_replace( array_keys( $replace ), array_values( $replace ), $url[ $name ] );
		return( $result );
	}

	function _filter_form_show( $filter, $replace ) {
		$order_fields = array();
		foreach( explode( '|', 'name|email|add_date|last_login|num_logins|active|balance' ) as $f ) {
			$order_fields[ $f ] = $f;
		}
		$result = form( $replace, array(
				'selected' => $filter,
			))
			->text( 'user_id'     , 'Номер(а) пользователя' )
			->text( 'name'        , 'Имя'                   )
			->text( 'email'       , 'Почта'                 )
			->text( 'balance'     , 'Баланс от'             )
			->text( 'balance__and', 'Баланс до'             )
			->select_box( 'group', main()->get_data( 'user_groups' ), array( 'show_text' => 1 ) )
			->select_box( 'order_by', $order_fields, array( 'show_text' => 1, 'desc' => 'Сортировка' ) )
			->radio_box( 'order_direction', array( 'asc' => 'прямой', 'desc' => 'обратный' ), array( 'desc' => 'Направление сортировки' ) )
			->save_and_clear()
		;
		return( $result );
	}

	function _filter_form_balance( $filter, $replace ) {
		$order_fields = array();
		foreach( explode( '|', 'operation_id|datetime_update|datetime_start|datetime_finish|title|amount|balance' ) as $f ) {
			$order_fields[ $f ] = $f;
		}
		// provider
		$payment_api = _class( 'payment_api' );
		$providers = $payment_api->provider();
		$providers__select_box = array();
		foreach( $providers as $id => $item ) {
			$providers__select_box[ $id ] = $item[ 'title' ];
		}
		// status
		$payment_status = $payment_api->get_status();
		$payment_status__select_box = array();
		$payment_status__select_box[ -1 ] = 'ВСЕ СТАТУСЫ';
		foreach( $payment_status as $id => $item ) {
			$payment_status__select_box[ $id ] = $item[ 'title' ];
		}
		// render
		$result = form( $replace, array(
				'selected' => $filter,
			))
			->hidden( 'user_id'    )
			->hidden( 'account_id' )
			->text( 'operation_id', 'Номер операции' )
			->text( 'title'       , 'Название'       )
			->select_box( 'status_id'  , $payment_status__select_box, array( 'show_text' => 'статус'    , 'desc' => 'Статус'     ) )
			->select_box( 'provider_id', $providers__select_box     , array( 'show_text' => 'провайдер' , 'desc' => 'Провайдер'  ) )
			->radio_box( 'direction', array( '' => 'все', 'in' => 'приход', 'out' => 'расход' ), array( 'desc' => 'Направление' ) )
			->datetime_select( 'datetime_update',      'Дата с',  array( 'with_time' => 1 ) )
			->datetime_select( 'datetime_update__and', 'Дата до', array( 'with_time' => 1 ) )
			->select_box( 'order_by', $order_fields, array( 'show_text' => 1, 'desc' => 'Сортировка' ) )
			->radio_box( 'order_direction', array( 'asc' => 'прямой', 'desc' => 'обратный' ), array( 'desc' => 'Направление сортировки' ) )
			->save_and_clear()
		;
		return( $result );
	}

	function _show_filter() {
		$object      = &$this->object;
		$action      = &$this->action;
		$filter_name = &$this->filter_name;
		$filter      = &$this->filter;
		if( !in_array( $action, array( 'show', 'balance' ) ) ) { return( false ); }
		// url
		$url_base = array(
			'object' => $object,
			'action' => 'filter_save',
			'id'     => $filter_name,
		);
		$result = '';
		switch( $action ) {
			case 'show':
				$url_filter       = url_admin( $url_base );
				$url_filter_clear = url_admin( $url_base + array(
					'page'   => 'clear',
				));
				$replace = array(
					'form_action' => $url_filter,
					'clear_url'   => $url_filter_clear,
				);
				$result = $this->_filter_form_show( $filter, $replace );
			break;
			case 'balance':
				$user_id    = (int)$_GET[ 'user_id' ];
				$account_id = (int)$_GET[ 'account_id' ];
				$url_filter = url_admin( $url_base + array(
					'user_id'    => $user_id,
					'account_id' => $account_id,
				));
				$url_filter_clear = url_admin( $url_base + array(
					'user_id'    => $user_id,
					'account_id' => $account_id,
					'page'       => 'clear',
				));
				$replace = array(
					'form_action' => $url_filter,
					'clear_url'   => $url_filter_clear,
				);
				$result = $this->_filter_form_balance( $filter, $replace );
			break;
		}
		return( $result );
	}

	function filter_save() {
		$object = &$this->object;
		$id     = &$this->id;
		switch( $id ) {
			case 'manage_payment__show':
				$url_redirect_url = url_admin( array(
					'object'     => $object,
				));
			break;
			case 'manage_payment__balance':
				$user_id    = (int)$_GET[ 'user_id' ];
				$account_id = (int)$_GET[ 'account_id' ];
				$url_redirect_url = url_admin( array(
					'object'     => $object,
					'action'     => 'balance',
					'user_id'    => $user_id,
					'account_id' => $account_id,
				));
			break;
		}
		$options = array(
			'filter_name'  => $id,
			'redirect_url' => $url_redirect_url,
		);
		return( _class( 'admin_methods' )->filter_save( $options ) );
	}

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
		$sql = db()->select( 'u.id as id', 'u.id as user_id', 'u.name as name', 'u.email as email', 'pa.balance as balance', 'pa.account_id as account_id' )
			->table( 'user as u' )
			->left_join( 'payment_account as pa', 'pa.user_id = u.id' )
			->sql();
		$_this = $this;
		return $sum_chart. $num_chart.
			table( $sql, array(
				'filter' => $filter,
				'filter_params' => array(
					'user_id' => array( 'cond' => 'in', 'field' => 'u.id' ),
					'name'    => 'like',
					'balance' => 'between',
					'email'   => 'like',
				),
				'hide_empty' => true,
			))
			->on_before_render(function($p, $data, $table) use ($_this) {
			})
			->text( 'id', 'Номер'  )
			->text( 'name', 'Имя', array('link' => url('/members/edit/%id')) )
			->text( 'email'  , 'Почта'  )
			->text( 'balance', 'Баланс' )
			->func('id', function($in, $e, $a, $p, $table) use ($_this) {
				if (!isset($table->_data_daily_sum)) {
					$table->_data_daily_sum = $_this->_get_users_daily_payments($table->_ids, 'sum');
				}
				$daily = _class('charts')->jquery_sparklines($table->_data_daily_sum[$a['id']]);
				return $daily ? '<span title="'.t('График изменения баланса').'">'.$daily.'</span>' : false;
			}, array('desc' => 'Изменение баланса'))
			->func('id', function($in, $e, $a, $p, $table) use ($_this) {
				if (!isset($table->_data_daily_num)) {
					$table->_data_daily_num = $_this->_get_users_daily_payments($table->_ids, 'num');
				}
				$daily = _class('charts')->jquery_sparklines($table->_data_daily_num[$a['id']]);
				return $daily ? '<span title="'.t('Транзакции').'">'.$daily.'</span>' : false;
			}, array('desc' => 'Транзакции'))
			->btn( 'Баланс' , $url[ 'balance' ], array( 'icon' => 'fa fa-money', 'class_add' => 'btn-primary', 'target' => '_blank' ) )
		;
	}

	function _operation_sql( $options = null ) {
		$payment_api = _class( 'payment_api' );
		list( $sql ) = $payment_api->operation( $options );
		$result = $sql;
		return( $result );
	}

	function _operation_table( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// class
		$manage_lib  = &$this->manage_payment_lib;
		$html       = _class( 'html' );
		// status
		$payment_status = &$_payment_status;
		$result = table( $_sql, array(
				'filter' => $_filter,
				'filter_params' => array(
					'operation_id'    => 'in',
					'title'           => 'like',
					'datetime_update' => array( 'datetime_between', 'datetime_update' ),
				),
			))
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
			}, array( 'desc' => 'Провайдер' ) )
			->text( 'amount'         , 'Сумма'           )
			->text( 'balance'        , 'Баланс'          )
			->func( 'status_id', function( $value, $extra, $row ) use( $manage_lib, $payment_status ) {
				$status_name = $payment_status[ $value ][ 'name' ];
				$title       = $payment_status[ $value ][ 'title' ];
				$css = $manage_lib->css_by_status( array(
					'status_name' => $status_name,
				));
				$result = sprintf( '<span class="%s">%s</span>', $css, $title );
				return( $result );
			}, array( 'desc' => 'статус' ) )
			->date( 'datetime_update', 'Дата'           , array( 'format' => 'full', 'nowrap' => 1 ) )
			->date( 'datetime_start' , 'Дата начала'    , array( 'format' => 'full', 'nowrap' => 1 ) )
			->date( 'datetime_finish', 'Дата завершения', array( 'format' => 'full', 'nowrap' => 1 ) )
			->btn_func( 'Отмена', function( $row, $params, $instance_params, $table ) use( $html ) {
				$operation_id = (int)$row[ 'operation_id' ];
				$url = url_admin( array(
					'object'       => 'manage_payment',
					'action'       => 'mass_cancel',
					'operation_id' => $operation_id,
				));
				$link = $html->a( array(
					'href'  => $url,
					'class' => 'btn btn-warning',
					'icon'  => 'fa fa-ban',
					'title' => 'Отмена',
					'text'  => 'Отмена',
				));
				return( $link );
			})
			->btn_func( 'Удаление', function( $row, $params, $instance_params, $table ) use( $html ) {
				$operation_id = (int)$row[ 'operation_id' ];
				$url = url_admin( array(
					'object'       => 'manage_payment',
					'action'       => 'mass_remove',
					'operation_id' => $operation_id,
				));
				$link = $html->a( array(
					'href'  => $url,
					'class' => 'btn btn-danger',
					'icon'  => 'fa fa-remove',
					'title' => 'Удаление',
					'text'  => 'Удаление',
				));
				return( $link );
			})
		;
		return( $result );
	}

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
				list( $account_id, $account ) = $payment_api->get_account__by_id( array(
					'account_id' => $account_id,
				));
			} else {
				list( $account_id, $account ) = $payment_api->account( array(
					'user_id' => $user_id,
				));
			}
		} else {
			common()->message_error( 'Не определен пользователь', array( 'translate' => false ) );
			$form = form()->link( 'Назад', $url_back, array( 'class' => 'btn', 'icon' => 'fa fa-chevron-left' ) );
			return( $form );
		}
		// prepare url
		$url_form_action = url_admin( array(
			'object'     => $object,
			'action'     => $action,
			'user_id'    => $user_id,
			'account_id' => $account_id,
		));
		$url_operation = url_admin( array(
			'object'     => $object,
			'action'     => 'operation',
			'user_id'    => $user_id,
			'account_id' => $account_id,
		));
		// prepare provider
		$providers = $payment_api->provider( array(
			'all' => true,
		));
		$items = array();
		foreach( $providers as $i => $item ) {
			$items[ $item[ 'name' ] ] = $item[ 'title' ];
		}
		$providers_form = $items;
		// prepare form
		$replace = array(
			'amount'        => null,
			'user_id'       => $user_id,
			'provider_name' => 'administration',
			'account_id'    => $account_id,
				'form_action'   => $url_form_action,
				'redirect_link' => $url_operation,
				'back_link'     => $url_back,
		);
		// $replace += $_POST + $data;
		$replace += $_POST;
		$form = form( $replace, array( 'autocomplete' => 'off' ) )
			->validate(array(
				'amount'        => 'trim|required|numeric|greater_than[0]',
				'provider_name' => 'trim|required',
			))
			->on_validate_ok( function( $data, $extra, $rules ) use( &$user_id, &$account_id, &$account ) {
				$payment_api = _class( 'payment_api' );
				$provider_name = $_POST[ 'provider_name' ];
				$provider_name = empty( $provider_name ) ? 'administration' : $provider_name;
				$operation = $_POST[ 'operation' ];
				if( $operation == 'payment' ) {
					$provider_name = 'administration';
				}
				$options = array(
					'user_id'         => $user_id,
					'account_id'      => $account_id,
					'amount'          => $_POST[ 'amount' ],
					'operation_title' => $_POST[ 'title' ],
					'operation'       => $operation,
					'provider_name'   => $provider_name,
					'is_balance_limit_lower' => false,
				);
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
				common()->$message( $result[ 'status_message' ], array( 'translate' => false ) );
				if( !$is_account ) {
					$url = url_admin( array(
						'object'     => $_GET[ 'object' ],
						'action'     => $_GET[ 'action' ],
						'user_id'    => $user_id,
						'account_id' => $account_id,
					));
					return( js_redirect( $url ) );
				}
			})
			->float( 'amount', 'Сумма' )
			->text( 'title', 'Название' )
			->select_box( 'provider_name', $providers_form, array( 'show_text' => 1, 'desc' => 'Провайдер', 'tip' => 'Выбрать провайдера возможно только для пополнения. Списание возможно только от Администратора.' ) )
			->row_start( array(
				'desc' => 'Операция',
			))
				->submit( 'operation', 'deposition', array( 'desc' => 'Пополнить' ) )
				->submit( 'operation', 'payment',    array( 'desc' => 'Списать', 'tip' => 'Списание возможно только от Администратора.' ) )
			->row_end()
		;
		if( $account_id > 0 ) {
			// fetch operations
			$operation_options = array(
				'user_id'     => $user_id,
				'account_id'  => $account_id,
				'no_limit'    => true,
				'no_order_by' => true,
				'sql'         => true,
			);
			$sql = $this->_operation_sql( $operation_options );
			if( empty( $filter ) ) {
				$filter = array(
					'order_by'        => 'datetime_update',
					'order_direction' => 'desc',
				);
			}
			// operations table
			$table = $this->_operation_table( array(
				'filter'         => $filter,
				'sql'            => $sql,
				'user_id'        => $user_id,
				'payment_status' => $payment_status,
				'providers'      => $providers,
			));
		} else {
			if( !$_POST[ 'amount' ] ) {
				common()->message_warning( 'Счет не определен', array( 'translate' => false ) );
			}
		}
		$balance      = '';
		$currency_str = '';
		$back = form_item()->link( 'Назад', $url_back, array( 'class' => 'btn', 'icon' => 'fa fa-chevron-left' ) );
		if( $account ) {
			list( $currency_id, $currency ) = $payment_api->get_currency__by_id( $account );
			$currency && $currency_str = ' ' . $currency[ 'short' ];
			$balance = $account[ 'balance' ];
		}
		$user = user( $user_id );
		$url_user = a( '/members/edit/'.$user_id, $user[ 'name' ] );
		$replace += array(
			'user'     => $user,
			'url_user' => $url_user,
			'balance'  => array(
				'amount'   => $balance,
				'currency' => $currency_str,
			),
		);
		$header = tpl()->parse( 'manage_payment/balance_header', $replace );
		$result = $header . $form . '<hr>' . $table;
		return( $result );
	}

	/**
	*/
	function _get_users_daily_payments($user_ids = array(), $type = 'sum') {
		if (!$user_ids) {
			return false;
		}
		if (!is_array($user_ids)) {
			$user_ids = array($user_ids);
		}
		$time = time();
		$days = 60;
		$min_time = $time - $days * 86400;
		$data = array();
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
		$dates = array();
		foreach (range($days, 0) as $days_ago) {
			$date = date('Y-m-d', $time - $days_ago * 86400);
			$dates[$date] = $days_ago;
		}
		$result = array();
		foreach ((array)$data as $user_id => $user_dates) {
			$result[$user_id] = array();
			$_data = null;
			foreach ($dates as $date => $days_ago) {
				$in = $user_dates[$date][$type]['in'];
				$out = $user_dates[$date][$type]['out'];
				if ($type == 'num') {
					$_data = array($in, $out);
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
		$data = array();
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
		$dates = array();
		foreach (range($days, 0) as $days_ago) {
			$date = date('Y-m-d', $time - $days_ago * 86400);
			$dates[$date] = $days_ago;
		}
		$result = array();
		$_data = null;
		foreach ($dates as $date => $days_ago) {
			$in = $data[$date]['in'];
			$out = $data[$date]['out'];
			if ($type == 'num') {
				$_data = array($in, $out);
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

	protected function _user_message( $options = null ) {
		$url = &$this->url;
		// import operation
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_status_message ) ) { return( null ); }
		switch( true ) {
			case @$_status === 'in_progress':
				$_css_panel_status = 'warning';
				empty( $_status_header ) && $_status_header = 'В процессе';
				break;
			case @$_status === 'processing':
				$_css_panel_status = 'warning';
				empty( $_status_header ) && $_status_header = 'Обработка';
				break;
			case @$_status === 'success' || @$_status === true:
				$_css_panel_status = 'success';
				empty( $_status_header ) && $_status_header = 'Выполнено';
				break;
			case @$_status === 'refused':
				$_css_panel_status = 'danger';
				empty( $_status_header ) && $_status_header = 'Отказано';
				break;
			default:
				$_css_panel_status = 'danger';
				empty( $_status_header ) && $_status_header = 'Ошибка';
				break;
		}
		// body
		$content = empty( $_is_html_message ) ? $_status_message : htmlentities( $_status_message, ENT_HTML5, 'UTF-8', $double_encode = false );
		$panel_body = '<div class="panel-body">'. $content .'</div>';
		// header
		$content = 'Операция';
		if( !empty( $_status_header ) ) { $content .= ': ' . $_status_header; }
		$content = htmlentities( $content, ENT_HTML5, 'UTF-8', $double_encode = false );
		$panel_header = '<div class="panel-heading">'. $content .'</div>';
		// footer
		if( !empty( $_status_footer ) ) {
			$content = $_status_footer;
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

	function _get_operation_id( $options = null ) {
		// import operation
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$result = @$_operation_id ?: @$_GET[ 'operation_id' ] ?: @$_POST[ 'operation_id' ];
		if( ! @$result ) {
			$result = array(
				'status_message' => 'Неверная операция',
			);
			return( $this->_user_message( $result ) );
		}
		return( $result );
	}

	function cancel( $options = null ) {
		$operation_id = (int)$this->_get_operation_id( $options );
		if( $operation_id < 1 ) {
			$result = array(
				'status_message' => 'Неверная операция',
			);
			return( $this->_user_message( $result ) );
		}
		// processing
		$payment_api = &$this->payment_api;
		$result = $payment_api->cancel( array(
			'operation_id' => $operation_id,
		));
		return( $this->_user_message( $result ) );
	}

	function expired( $options = null ) {
		$operation_id = (int)$this->_get_operation_id( $options );
		if( $operation_id < 1 ) {
			$result = array(
				'status_message' => 'Неверная операция',
			);
			return( $this->_user_message( $result ) );
		}
		// processing
		$payment_api = &$this->payment_api;
		$result = $payment_api->expired( array(
			'operation_id' => $operation_id,
		));
		return( $this->_user_message( $result ) );
	}

	// mass actions

	function mass_cancel( $options = null ) {
		$operation_id = $this->_get_operation_id( $options );
		is_numeric( $operation_id ) && $operation_id = array( $operation_id => 1 );
		if( ! is_array( $operation_id ) ) {
			$result = array(
				'status_message' => 'Требуется массив операций',
			);
			return( $this->_user_message( $result ) );
		}
		// processing
		$payment_api = &$this->payment_api;
		$html = _class( 'html' );
		$title = 'Отменено №';
		$content = array();
		foreach( $operation_id as $id => $selected ) {
			$id = (int)$id;
			if( $id < 1 ) { continue; }
			$header = $title . $id;
			$result = $payment_api->cancel( array(
				'operation_id' => $id,
			));
			$text = $result[ 'status_message' ];
			$link = $html->a( array(
				'href'      => $this->_url( 'view', array( '%operation_id' => $id ) ),
				'class_add' => @$result[ 'status' ] ? 'btn-success' : 'btn-warning',
				'target'    => '_blank',
				'icon'      => 'fa fa-sign-out',
				'title'     => $text,
				'text'      => $text,
			));
			$content[ $header ] = $link;
		}
		// table
		$result = $html->simple_table( $content, array( 'no_total' => true ) );
		return( $result );
	}

	function mass_remove( $options = null ) {
		$operation_id = $this->_get_operation_id( $options );
		is_numeric( $operation_id ) && $operation_id = array( $operation_id => 1 );
		if( ! is_array( $operation_id ) ) {
			$result = array(
				'status_message' => 'Требуется массив операций',
			);
			return( $this->_user_message( $result ) );
		}
		// processing
		$ids = array_keys( $operation_id );
		$r = db()->table( 'payment_operation' )
			->where( 'operation_id', 'in', _es( $ids ) )
			->delete()
		;
		$result = array(
			'operation_id'    -1,
			'status'         => $r,
			'status_message' => 'Удаление операций: '. implode( ', ', $ids ),
		);
		return( $this->_user_message( $result ) );
	}

}
