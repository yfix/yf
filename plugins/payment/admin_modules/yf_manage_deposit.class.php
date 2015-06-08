<?php

class yf_manage_deposit {

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
			'request' => url_admin( array(
				'object'       => $object,
				'action'       => 'request',
				'operation_id' => '%operation_id',
			)),
			'status_success' => url_admin( array(
				'object'       => $object,
				'action'       => 'status',
				'status'       => 'success',
				'operation_id' => '%operation_id',
			)),
			'status_refused' => url_admin( array(
				'object'       => $object,
				'action'       => 'status',
				'status'       => 'refused',
				'operation_id' => '%operation_id',
			)),
			'csv' => url_admin( array(
				'object'       => $object,
				'action'       => 'csv',
				'operation_id' => '%operation_id',
			)),
			'list' => url_admin( array(
				'object'       => $object,
			)),
			'update_expired' => url_admin( array(
				'object' => $object,
				'action' => 'update_expired',
			)),
			'view' => url_admin( array(
				'object'       => $object,
				'action'       => 'view',
				'operation_id' => '%operation_id',
			)),
			'balance' => url_admin( array(
				'object'     => 'manage_payment',
				'action'     => 'balance',
				'user_id'    => '%user_id',
				'account_id' => '%account_id',
			)),
			'user' => url_admin( array(
				'object' => 'members',
				'action' => 'edit',
				'id'     => '%user_id',
			)),
		);
	}

	function _url( $name, $replace = null ) {
		$url = &$this->url;
		$result = null;
		if( empty( $url[ $name ] ) ) { return( $result ); }
		if( !is_array( $replace ) ) { return( $url[ $name ] ); }
		$result = str_replace( array_keys( $replace ), array_values( $replace ), $url[ $name ] );
		return( $result );
	}

	function _filter_form_show( $filter, $replace ) {
		// order
		$order_fields = array(
			'o.operation_id'    => 'номер операций',
			'o.amount'          => 'сумма',
			'a.balance'         => 'баланс',
			'o.datetime_start'  => 'дата создания',
			'o.datetime_update' => 'дата обновления',
		);
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
			->text( 'operation_id', 'Номер операции'     )
			->text( 'user_id'     , 'Номер пользователя' )
			->text( 'name'        , 'Имя'                )
			->text( 'amount'      , 'Сумма от'           )
			->text( 'amount__and' , 'Сумма до'           )
			->text( 'balance'     , 'Баланс от'          )
			->text( 'balance__and', 'Баланс до'          )
			->select_box( 'status_id'  , $payment_status__select_box, array( 'show_text' => 'статус'    , 'desc' => 'Статус'     ) )
			->select_box( 'provider_id', $providers__select_box     , array( 'show_text' => 'провайдер' , 'desc' => 'Провайдер'  ) )
			->select_box( 'order_by'   , $order_fields              , array( 'show_text' => 'сортировка', 'desc' => 'Сортировка' ) )
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
		if( !in_array( $action, array( 'show' ) ) ) { return( false ); }
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
		}
		return( $result );
	}

	function filter_save() {
		$object = &$this->object;
		$id     = &$this->id;
		switch( $id ) {
			case 'manage_deposit__show':
				$url_redirect_url = url_admin( array(
					'object' => $object,
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
		// payment status
		$payment_api = _class( 'payment_api' );
		$payment_status = $payment_api->get_status();
		$name = 'in_progress';
		$item = $payment_api->get_status( array( 'name' => $name) );
		list( $payment_status_in_progress_id, $payment_success_in_progress ) = $item;
		if( empty( $payment_status_in_progress_id ) ) {
			$result = array(
				'status_message' => 'Статус платежей не найден: ' . $object_name,
			);
			return( $this->_user_message( $result ) );
		}
		// prepare sql
		$db = db()->select(
			'o.operation_id',
			'o.account_id',
			'o.provider_id',
			'o.options',
			'a.user_id',
			'u.name as user_name',
			'o.amount',
			'a.balance',
			'p.title as provider_title',
			'o.status_id as status_id',
			'o.datetime_start'
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
		return( table( $sql, array(
				'filter' => $filter,
				'filter_params' => array(
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
					'provider_id'  => array( 'cond' => 'eq'     , 'field' => 'o.provider_id'   ),
					'operation_id' => array( 'cond' => 'in'     , 'field' => 'o.operation_id'  ),
					'user_id'      => array( 'cond' => 'in'     , 'field' => 'a.user_id'       ),
					'name'         => array( 'cond' => 'like'   , 'field' => 'u.name'          ),
					'balance'      => array( 'cond' => 'between', 'field' => 'a.balance'       ),
					'amount'       => array( 'cond' => 'between', 'field' => 'o.amount'        ),
					'__default_order'  => 'ORDER BY o.datetime_start DESC',
				),
			))
			->text( 'operation_id'  , 'операция' )
			->text( 'provider_title', 'провайдер' )
			->text( 'amount'        , 'сумма' )
			->text( 'balance'       , 'баланс' )
			->func( 'user_name', function( $value, $extra, $row_info ) {
				$result = a('/members/edit/'.$row_info[ 'user_id' ], $value . ' (id: ' . $row_info[ 'user_id' ] . ')');
				return( $result );
			}, array( 'desc' => 'пользователь' ) )
			->func( 'status_id', function( $value, $extra, $row ) use ( $payment_status ){
				$status_id = $payment_status[ $value ][ 'name' ];
				$title     = $payment_status[ $value ][ 'title' ];
				switch( $status_id ) {
					case 'processing':
					case 'in_progress': $css = 'text-warning'; break;
					case 'success':     $css = 'text-success'; break;
					case 'expired':     $css = 'text-danger';  break;
					case 'refused':     $css = 'text-danger';  break;
				}
				$result = sprintf( '<span class="%s">%s</span>', $css, $title );
				return( $result );
			}, array( 'desc' => 'статус' ) )
			->text( 'datetime_start', 'дата создания' )
			->btn( 'Ввод средств',  $url[ 'view'    ], array( 'icon' => 'fa fa-sign-in', 'class_add' => 'btn-primary' ) )
			// ->btn( 'Пользователь' , $url[ 'user'    ], array( 'icon' => 'fa fa-user'   , 'class_add' => 'btn-info'   ) )
			// ->btn( 'Счет'         , $url[ 'balance' ], array( 'icon' => 'fa fa-money'  , 'class_add' => 'btn-info'   ) )
			->footer_link( 'Обновить просроченные операции', $url[ 'update_expired' ], array( 'class' => 'btn btn-primary', 'icon' => 'fa fa-refresh' ) )
		);
	}

	function _operation( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$payment_api = _class( 'payment_api' );
		// check operation
		$operation_id = isset( $_operation_id ) ? $_operation_id : (int)$_GET[ 'operation_id' ];
		$operation = $payment_api->operation( array(
			'operation_id' => $operation_id,
		));
		if( empty( $operation ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Ошибка: операция не найдена',
			);
			return( $this->_user_message( $result ) );
		}
		// import operation
		is_array( $operation ) && extract( $operation, EXTR_PREFIX_ALL | EXTR_REFS, 'o' );
		// check account
		$account_result = $payment_api->get_account( array( 'account_id' => $o_account_id ) );
		if( empty( $account_result ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Счет пользователя не найден',
			);
			return( $this->_user_message( $result ) );
		}
		list( $account_id, $account ) = $account_result;
		// check user
		$user_id = $account[ 'user_id' ];
		$user    = user( $user_id );
		if( empty( $user ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Пользователь не найден: ' . $user_id,
			);
			return( $this->_user_message( $result ) );
		}
		$online_users = _class( 'online_users', null, null, true );
		$user_is_online = $online_users->_is_online( $user_id );
		// check provider
		$providers_user = $payment_api->provider();
		if( empty( $providers_user[ $o_provider_id ] ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неизвестный провайдер',
			);
			return( $this->_user_message( $result ) );
		}
		$provider = &$providers_user[ $o_provider_id ];
		$provider_class = $payment_api->provider_class( array(
			'provider_name' => $provider[ 'name' ],
		));
		if( empty( $provider_class ) ) {
			$result = array(
				'status_message' => 'Провайдер недоступный: ' . $provider[ 'title' ],
			);
			return( $this->_user_message( $result ) );
		}
		// check request
		$request = array();
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
		$method = array();
		// check operation status
		$statuses = $payment_api->get_status();
		if( empty( $statuses[ $o_status_id ] ) ) {
			$result = array(
				'status_message' => 'Неизвестный статус операции: '. $o_status_id,
			);
			return( $this->_user_message( $result ) );
		}
		$o_status = $statuses[ $o_status_id ];
		// status css
		$status_name  = $o_status[ 'name' ];
		$status_title = $o_status[ 'title' ];
		$css = '';
		switch( $status_name ) {
			case 'processing':
			case 'in_progress': $css = 'text-warning'; break;
			case 'success':     $css = 'text-success'; break;
			case 'expired':     $css = 'text-danger';  break;
			case 'refused':     $css = 'text-danger';  break;
		}
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
		$result = array(
			'is_valid'             => true,
			'operation_id'         => &$operation_id,
			'operation'            => &$operation,
			'statuses'             => &$statuses,
			'status'               => &$o_status,
			'status_id'            => &$o_status_id,
			'status_name'          => &$status_name,
			'status_title'         => &$status_title,
			'html_status_title'    => &$html_status_title,
			'account_id'           => &$account_id,
			'account'              => &$account,
			'user_id'              => &$user_id,
			'user'                 => &$user,
			'user_is_online'       => &$user_is_online,
			'provider_id'          => &$o_provider_id,
			'provider'             => &$provider,
			'provider_class'       => &$provider_class,
			'providers_user'       => &$providers_user,
			'request'              => &$request,
			'method_id'            => &$method_id,
			'method'               => &$method,
			'response'             => &$response,
			'html_amount'          => &$html_amount,
			'html_datetime_start'  => &$html_datetime_start,
			'html_datetime_update' => &$html_datetime_update,
			'html_datetime_finish' => &$html_datetime_finish,
		);
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
			$content = array();
			foreach( $_method[ 'option' ] as $key => $title ) {
				if( !empty( $_request[ 'options' ][ $key ] ) ) {
					$content[ $title ] = $_request[ 'options' ][ $key ];
				}
			}
			$html_request_options = $html->simple_table( $content, array( 'no_total' => true ) );
		}
		// prepare view: response options
		$content = null;
		if( !empty( $_response ) ) {
			$response = array_reverse( $_response );
			$content = table( $response, array( 'no_total' => true ) )
				->text( 'datetime', 'дата' )
				->func( 'date', function( $value, $extra, $row_info ) {
					$value = $row_info[ 'data' ];
					$message = trim( $value[ 'message' ] );
					$message = trim( $value[ 'message' ], '.' );
					$result = t( $message ) . ' (' . $value[ 'state' ] . ')';
					return( $result );
				}, array( 'desc' => 'сообщение' ) )
			;
		}
		$html_response = $content;
		// prepare view: operation options
		$user_link = $html->a( array(
			'href'  => $this->_url( 'user', array( '%user_id' => $_user_id ) ),
			'icon'  => 'fa fa-user',
			'title' => 'Профиль',
			'text'  => $_user[ 'name' ],
		));
		$balance_link = $html->a( array(
			'href'  => $this->_url( 'balance', array( '%user_id' => $_user_id, '%account_id' => $_account_id ) ),
			'title' => 'Баланс',
			'text'  => $payment_api->money_text( $_account[ 'balance' ] ),
		));
		$content = array(
			'Пользователь'    => $user_link . $balance_link,
			'Сумма'           => $_html_amount,
			'Провайдер'       => $_provider[ 'title' ],
			'Статус'          => $_html_status_title,
			'Дата создания'   => $_html_datetime_start,
			'Дата обновления' => $_html_datetime_update,
			'Дата завершения' => $_html_datetime_finish,
		);
		$html_operation_options = $html->simple_table( $content, array( 'no_total' => true ) );
		$url_view = $this->_url( 'view', array( '%operation_id' => $_operation_id ) );
		// render
		$is_test = $_provider_class->is_test();
		$is_progressed = $_status[ 'name' ] == 'in_progress';
		$replace = $operation + array(
			'is_test'       => $is_test,
			'is_progressed' => $is_progressed,
			'header_data'   => $html_operation_options,
			'request_data'  => $html_request_options,
			'response_data' => $html_response,
			'url' => array(
				'list'           => $this->_url( 'list' ),
				'view'           => $this->_url( 'view',           array( '%operation_id' => $_operation_id ) ),
				'request'        => $this->_url( 'request',        array( '%operation_id' => $_operation_id ) ),
				'status_success' => $this->_url( 'status_success', array( '%operation_id' => $_operation_id ) ),
				'status_refused' => $this->_url( 'status_refused', array( '%operation_id' => $_operation_id ) ),
				'csv'            => $this->_url( 'csv',            array( '%operation_id' => $_operation_id ) ),
				'provider_operation_detail' => $url_operation_detail,
			)
		);
		$result = tpl()->parse( 'manage_deposit/view', $replace );
		return( $result );
	}

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
				$url_view = $this->_url( 'view', array( '%operation_id' => $operation_id ) );
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

	function status() {
		// check operation
		$operation = $this->_operation();
		// import options
		is_array( $operation ) && extract( $operation, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_is_valid ) ) { return( $result ); }
		$status = $_GET[ 'status' ];
		// check status
		if( !in_array( $status, array( 'success', 'refused' ) ) ) {
			$result = array(
				'status_message' => 'Неизвестный статус операции: '. $status,
			);
			return( $this->_user_message( $result ) );
		}
		// update status
		$result = $_provider_class->_api_deposition( array(
			'provider_name' => $_provider[ 'name' ],
			'response'      => array(
				'operation_id' => $_operation_id,
				'title'        => $_operation[ 'title' ],
				'comment'      => 'updated by admin: ' . main()->ADMIN_ID,
			),
			'status_name'    => $status,
			'status_message' => $_operation[ 'title' ],
		));
		if( empty( $result[ 'status' ] ) ) {
			$result[ 'operation_id' ] = $_operation_id;
			return( $this->_user_message( $result ) );
		}
		$url_view = $this->_url( 'view', array( '%operation_id' => $_operation_id ) );
		return( js_redirect( $url_view, false ) );
	}

	function _update_expired() {
		// var
		$payment_api = _class( 'payment_api' );
		// update status only in_progress
		$object = $payment_api->get_status( array( 'name' => 'in_progress' ) );
		list( $status_id, $status ) = $object;
		if( empty( $status_id ) ) { return( $object ); }
		$object = $payment_api->get_status( array( 'name' => 'expired' ) );
		list( $new_status_id, $new_status ) = $object;
		if( empty( $new_status_id ) ) { return( $object ); }
		// date: over 3 days
		$sql_datetime_over = date( 'Y-m-d', strtotime('-3 day') );
		$sql_datetime = $payment_api->sql_datetime();
		$db = db()->table( 'payment_operation' )
			->where( 'status_id', '=', $status_id )
			->where( 'direction', '=', 'in' )
			->where( 'datetime_update', '<', $sql_datetime_over )
			->where_null( 'datetime_finish' )
		;
		db()->begin();
		$result = $db->update( array(
			'status_id'       => $new_status_id,
			'datetime_finish' => $sql_datetime,
/* DEBUG
), array( 'sql' => true ) );
db()->rollback();
var_dump( $result );
exit; //*/
		));
		if( empty( $result ) ) { db()->rollback(); return( null ); }
		db()->commit();
		return( true );
	}

	function update_expired() {
		$url = &$this->url;
		// command line interface
		$is_cli = ( php_sapi_name() == 'cli' );
		$is_cli && $this->_update_expired_cli();
		// web
		$replace = array(
			'is_confirm' => false,
		);
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
			->info( 'header', array( 'value' => 'Ввод средств: обновление статуса просроченных операций', 'no_label' => true, 'class' => 'text-warning' ) )
			->check_box( 'is_confirm', array( 'desc' => 'Подтверждение', 'no_label' => true ) )
			->row_start()
				->submit( 'operation', 'update', array( 'desc' => 'Обновить', 'icon' => 'fa fa-refresh' ) )
				->link( 'Назад' , $url[ 'list' ], array( 'class' => 'btn btn-default', 'icon' => 'fa fa-chevron-left' ) )
			->row_end()
		;
		return( $result );
	}

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
