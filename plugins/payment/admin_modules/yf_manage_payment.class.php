<?php

class yf_manage_payment {

	protected $object      = null;
	protected $action      = null;
	protected $id          = null;
	protected $filter_name = null;
	protected $filter      = null;
	protected $url         = null;

	function _init() {
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
		);
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
		$result = form( $replace, array(
				'selected' => $filter,
			))
			->hidden( 'user_id'    )
			->hidden( 'account_id' )
			->text( 'operation_id', 'Номер операции' )
			->text( 'title'       , 'Название'       )
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
		$sql = db()->select( 'u.id as user_id', 'u.name as name', 'u.email as email', 'pa.balance as balance', 'pa.account_id as account_id' )
			->table( 'user as u' )
			->left_join( 'payment_account as pa', 'pa.user_id = u.id' )
			->sql();
		return( table( $sql, array(
				'filter' => $filter,
				'filter_params' => array(
					'user_id' => array( 'cond' => 'in', 'field' => 'u.id' ),
					'name'    => 'like',
					'balance' => 'between',
					'email'   => 'like',
				),
			))
			->text( 'user_id', 'Номер'  )
			->text( 'name', 'Имя', array('link' => url('/members/edit/%user_id')) )
			->text( 'email'  , 'Почта'  )
			->text( 'balance', 'Баланс' )
			->btn( 'Баланс' , $url[ 'balance' ], array( 'link_params' => 'user_id, account_id', 'icon' => 'fa fa-money' ) )
		);
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
		// check id: user, operation
		if( $user_id > 0 ) {
			$user_info = user( $user_id );
			list( $account_id, $account ) = $payment_api->get_account__by_id( array(
				'account_id' => $account_id,
			));
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
		// prepare form
		$replace = array(
			'amount'        => null,
			'user_id'       => $user_id,
			'account_id'    => $account_id,
				'form_action'   => $url_form_action,
				'redirect_link' => $url_operation,
				'back_link'     => $url_back,
		);
		// $replace += $_POST + $data;
		$replace += $_POST;
		$form = form( $replace, array( 'autocomplete' => 'off' ) )
			->validate(array(
				'amount' => 'trim|required|numeric|greater_than_equal_to[1]',
			))
			->on_validate_ok( function( $data, $extra, $rules ) use( &$user_id, &$account_id, &$account ) {
				$payment_api = _class( 'payment_api' );
				$options = array(
					'user_id'         => $user_id,
					'account_id'      => $account_id,
					'amount'          => $_POST[ 'amount' ],
					'operation_title' => $_POST[ 'title' ],
					'operation'       => $_POST[ 'operation' ],
					'provider_name'   => 'administration',
				);
				$result = $payment_api->transaction( $options );
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
			->number( 'amount', 'Сумма' )
			->text( 'title', 'Название' )
			->row_start( array(
				'desc' => 'Операция',
			))
				->submit( 'operation', 'deposition', array( 'desc' => 'Пополнить' ) )
				->submit( 'operation', 'payment',    array( 'desc' => 'Списать'   ) )
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
			list( $sql, $sql_count ) = $payment_api->operation( $operation_options );
			if( empty( $filter ) ) {
				$filter = array(
					'order_by'        => 'datetime_update',
					'order_direction' => 'desc',
				);
			}
			$table = table( $sql, array(
					'filter' => $filter,
					'filter_params' => array(
						'operation_id'    => 'in',
						'title'           => 'like',
						'datetime_update' => array( 'datetime_between', 'datetime_update' ),
					),
				))
				->text( 'operation_id'   , 'Номер'           )
				->date( 'datetime_update', 'Дата', array( 'format' => 'full', 'nowrap' => 1 ) )
				->text( 'amount'         , 'Сумма'           )
				->text( 'balance'        , 'Баланс'          )
				->text( 'title'          , 'Название'        )
				->date( 'datetime_start' , 'Дата начала'     )
				->date( 'datetime_finish', 'Дата завершения' )
			;
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
		$replace += array(
			'user' => user( $user_id ),
			'balance' => array(
				'amount'   => $balance,
				'currency' => $currency_str,
			),
		);
		$header = tpl()->parse( 'manage_payment/balance_header', $replace );
		$result = $header . $form . '<hr>' . $table;
		return( $result );
	}

}
