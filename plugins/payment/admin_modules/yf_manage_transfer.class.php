<?php

class yf_manage_transfer {

	public $IS_PAYOUT_INTERKASSA = null;

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
			'create' => url_admin( array(
				'object'       => $object,
				'action'       => 'create',
			)),
			'list' => url_admin( array(
				'object'       => $object,
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

			'request' => url_admin( array(
				'object'       => $object,
				'action'       => 'request',
				'operation_id' => '%operation_id',
			)),
			'request_interkassa' => url_admin( array(
				'object'       => $object,
				'action'       => 'request_interkassa',
				'operation_id' => '%operation_id',
			)),
			'check_interkassa' => url_admin( array(
				'object'       => $object,
				'action'       => 'check_interkassa',
				'operation_id' => '%operation_id',
			)),
			'check_all_interkassa' => url_admin( array(
				'object'       => $object,
				'action'       => 'check_all_interkassa',
			)),
			'status_processing' => url_admin( array(
				'object'       => $object,
				'action'       => 'status',
				'status'       => 'processing',
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
			'csv_request' => url_admin( array(
				'object'       => $object,
				'action'       => 'csv_request',
				'operation_id' => '%operation_id',
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
		$payment_api = &$this->payment_api;
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
			case 'manage_payout__show':
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
		// class
		$payment_api = &$this->payment_api;
		$manage_lib  = &$this->manage_payment_lib;
		// payment providers
		$providers = $payment_api->provider();
		$payment_api->provider_options( $providers, array(
			'method_allow',
		));
		// payment status
		$payment_status = $payment_api->get_status();
		$name = 'in_progress';
		$item = $payment_api->get_status( array( 'name' => $name) );
		list( $payment_status_in_progress_id, $payment_status_in_progress ) = $item;
		if( empty( $payment_status_in_progress_id ) ) {
			$result = array(
				'status_message' => 'Статус платежей не найден: ' . $name,
			);
			return( $this->_user_message( $result ) );
		}
		// payment type
		$payment_type = $payment_api->get_type();
		$name = 'transfer';
		$item = $payment_api->get_type( array( 'name' => $name) );
		list( $payment_type_transfer_id, $payment_type_transfer ) = $item;
		if( empty( $payment_type_transfer_id ) ) {
			$result = array(
				'status_message' => 'Тип платежей не найден: ' . $name,
			);
			return( $this->_user_message( $result ) );
		}
		// prepare sql
		$db = db()->select(
			'o.operation_id',
			'o.account_id',
			'o.provider_id',
			'o.direction',
			'o.options',
			'a.user_id',
			'u.name as user_name',
			'o.amount',
			// 'a.balance',
			'o.balance',
			'p.title as provider_title',
			'o.status_id as status_id',
			'o.datetime_start'
			)
			->table( 'payment_operation as o' )
				->left_join( 'payment_provider as p', 'p.provider_id = o.provider_id' )
				->left_join( 'payment_account  as a', 'a.account_id  = o.account_id'   )
				->left_join( 'user as u'            , 'u.id = a.user_id'              )
			->where( 'o.type_id', $payment_type_transfer_id )
			->where( 'p.active', '>=', 1 )
			// ->where( 'p.system', 'in', 0 )
			// ->where( 'o.direction', 'out' )
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
					'provider_id'  => array( 'cond' => 'eq',      'field' => 'o.provider_id'  ),
					'operation_id' => array( 'cond' => 'in',      'field' => 'o.operation_id' ),
					'user_id'      => array( 'cond' => 'in',      'field' => 'a.user_id'      ),
					'name'         => array( 'cond' => 'like',    'field' => 'u.name'         ),
					'balance'      => array( 'cond' => 'between', 'field' => 'a.balance'      ),
					'amount'       => array( 'cond' => 'between', 'field' => 'o.amount'       ),
					'__default_order'  => 'ORDER BY o.datetime_update DESC',
				),
			))
			->text( 'operation_id'  , 'операция' )
			->text( 'provider_title', 'провайдер' )
			->text( 'amount'        , 'сумма' )
			->func( 'direction', function( $value, $extra, $row ) {
				switch( $value ) {
					case 'in':
						$css = 'fa fa-long-arrow-right text-success';
						break;
					case 'out':
						$css = 'fa fa-long-arrow-left text-danger';
						break;
				}
				$result = sprintf( '<div class="text-center"><i class="%s"></i></div>', $css );
				return( $result );
			}, array( 'desc' => 'направление', 'align' => 'center' ) )
			->text( 'balance'       , 'баланс' )
			->func( 'user_name', function( $value, $extra, $row ) {
				$result = a('/members/edit/'.$row[ 'user_id' ], $value . ' (id: ' . $row[ 'user_id' ] . ')');
				return( $result );
			}, array( 'desc' => 'пользователь' ) )
			->func( 'status_id', function( $value, $extra, $row ) use( $manage_lib, $payment_status ) {
				$status_name = $payment_status[ $value ][ 'name' ];
				$title       = $payment_status[ $value ][ 'title' ];
				$css = $manage_lib->css_by_status( array(
					'status_name' => $status_name,
				));
				$result = sprintf( '<span class="%s">%s</span>', $css, $title );
				return( $result );
			}, array( 'desc' => 'статус' ) )
			->text( 'datetime_start', 'дата создания' )
			->btn( 'Перевод', $url[ 'view' ], array( 'icon' => 'fa fa-mail-forward', 'class_add' => 'btn-primary', 'target' => '_blank' ) )
			->header_link( 'Перевод', $url[ 'create' ], array( 'class' => 'btn btn-primary', 'icon' => 'fa fa-mail-forward' ) )
			->footer_link( 'Перевод', $url[ 'create' ], array( 'class' => 'btn btn-primary', 'icon' => 'fa fa-mail-forward' ) )
		);
	}

	function create() {
		@$replace = array() + $_POST;
		$_this = $this;
		$result = form( $replace, array( 'autocomplete' => 'off' ) )
			->validate(array(
				'from[user_id]' => 'trim|required|numeric|greater_than[0]|exists[user.id]',
				'to[user_id]'   => 'trim|required|numeric|greater_than[0]|exists[user.id]',
				'amount'        => 'trim|required|numeric|greater_than[0]',
			))
			->on_validate_ok( function( $data, $extra, $rules ) use( &$_this ) {
				$is_error    = false;
				$is_continue = $_POST[ 'operation' ] === 'transfer_and_continue';
				// handler
				$result = $this->_transfer( $_POST );
				// message
				$message = @$result[ 'status_message' ];
				if( @$result[ 'status' ] ) {
					$message_type = 'message_success';
					$message = $message ?: 'Операция выполнена';
				} else {
					$message_type = 'message_error';
					$message = $message ?: 'Операция не выполнена';
					$is_error = true;
				}
				common()->$message_type( $message, array( 'translate' => false ) );
				if( $is_error ) { return( $result ); }
				// redirect
				$url = $_this->_url( 'list' );
				$is_continue && $url = $_this->_url( 'create' );
				return( js_redirect( $url, false, $_operation ) );
			})
			->text( 'from[user_id]'  , 'От пользователя' )
			->text( 'to[user_id]'    , 'К пользователю'  )
			->text( 'amount'         , 'Сумма'           )
			->text( 'operation_title', 'Название'        )
			->row_start(array( 'desc' => 'Операция' ))
				->submit( 'operation', 'transfer', array( 'desc' => 'Перечислить' ) )
				->submit( 'operation', 'transfer_and_continue', array( 'desc' => 'Перечислить и продолжить' ) )
			->row_end()
		;
		return( $result );
	}

	function _transfer( $options = null ) {
		$payment_api = _class( 'payment_api' );
		$options[ 'provider_name' ] = 'administration';
		$result = $payment_api->transfer( $options );
		return( $result );
	}

	function _operation( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$payment_api = &$this->payment_api;
		$manage_lib  = &$this->manage_payment_lib;
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
		$payment_api->provider_options( $providers_user, array(
			'method_allow',
		));
		if( empty( $providers_user[ $o_provider_id ] ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неизвестный провайдер',
			);
			return( $this->_user_message( $result ) );
		}
		$provider = &$providers_user[ $o_provider_id ];
		// providers by name
		$providers_user__by_name = array();
		foreach( $providers_user as &$item ) {
			$provider_name = $item[ 'name' ];
			$providers_user__by_name[ $provider_name ] = &$item;
		}
		$provider_name = &$provider[ 'name' ];
		$provider_class = $payment_api->provider_class( array(
			'provider_name' => $provider_name,
		));
		if( empty( $provider_class ) ) {
			$result = array(
				'status_message' => 'Провайдер недоступный: ' . $provider[ 'title' ],
			);
			return( $this->_user_message( $result ) );
		}
		// check request
		if(
			empty( $o_options[ 'request' ] )
			|| !is_array( $o_options[ 'request' ] )
		) {
			$result = array(
				'status_message' => 'Параметры запроса отсутствует',
			);
			return( $this->_user_message( $result ) );
		}
		$request = reset( $o_options[ 'request' ] );
		// check method
		if( empty( $request[ 'options' ][ 'method_id' ] ) ) {
			$result = array(
				'status_message' => 'Метод вывода средств отсутствует',
			);
			return( $this->_user_message( $result ) );
		}
		$method_id = $request[ 'options' ][ 'method_id' ];
		$method    = $provider_class->api_method( array(
			'type'      => 'payout',
			'method_id' => $method_id,
		));
		// detect card
		$card = @$request[ 'options' ][ 'card' ];
		$result = $this->interkassa_detect_card( array(
			'card' => $card,
		));
		@list( $card_method_id, $card_method ) = $result;
		$html_card_title = null;
		if( $card_method_id ) {
			$html_card_title = $card_method[ 'title' ];
		}
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
		$css = $manage_lib->css_by_status( array(
			'status_name' => $status_name,
		));
		$html_status_title = $status_title;
		// is
		$is_progressed = $o_status[ 'name' ] == 'in_progress';
		$is_processing = $o_status[ 'name' ] == 'processing';
		$is_finish     = !( $is_progressed || $is_processing );
		$is_payout_interkassa = (bool)$this->IS_PAYOUT_INTERKASSA && $card_method_id;
		// processing
		$processing = array();
		$is_processing_self = false;
		if( is_array( $o_options[ 'processing' ] ) ) {
			$processing_log = array_reverse( $o_options[ 'processing' ] );
			$processing     = reset( $processing_log );
			if( @$processing[ 'provider_name' ] && $processing[ 'provider_name' ] != $provider_name ) {
				@list( $processing_provider_id, $processing_provider ) = $payment_api->get_provider( array(
					'name' => $processing[ 'provider_name' ],
				));
				if( $is_processing && $processing_provider ) {
					$html_status_title = $status_title . ' ('. $processing_provider[ 'title' ] .')';
				}
			} else {
				$is_processing_self = $is_processing;
			}
		}
		$html_status_title = sprintf( '<span class="%s">%s</span>', $css, $html_status_title );
		$is_processing_interkassa     = $is_processing && $processing[ 'provider_name' ] == 'interkassa';
		$is_processing_administration = $is_processing && $processing[ 'provider_name' ] == 'administration';
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
			'is_valid'                     => true,
			'operation_id'                 => &$operation_id,
			'operation'                    => &$operation,
			'processing_log'               => &$processing_log,
			'processing'                   => &$processing,
			'statuses'                     => &$statuses,
			'status'                       => &$o_status,
			'status_id'                    => &$o_status_id,
			'status_name'                  => &$status_name,
			'status_title'                 => &$status_title,
			'html_status_title'            => &$html_status_title,
			'account_id'                   => &$account_id,
			'account'                      => &$account,
			'user_id'                      => &$user_id,
			'user'                         => &$user,
			'user_is_online'               => &$user_is_online,
			'provider_id'                  => &$o_provider_id,
			'provider'                     => &$provider,
			'provider_name'                => &$provider_name,
			'provider_class'               => &$provider_class,
			'providers_user'               => &$providers_user,
			'providers_user__by_name'      => &$providers_user__by_name,
			'request'                      => &$request,
			'method_id'                    => &$method_id,
			'method'                       => &$method,
			'card_method_id'               => &$card_method_id,
			'card_method'                  => &$card_method,
			'html_card_title'              => &$html_card_title,
			'response'                     => &$response,
			'is_progressed'                => &$is_progressed,
			'is_processing'                => &$is_processing,
			'is_processing_self'           => &$is_processing_self,
			'is_processing_administration' => &$is_processing_administration,
			'is_processing_interkassa'     => &$is_processing_interkassa,
			'is_payout_interkassa'         => &$is_payout_interkassa,
			'is_finish'                    => &$is_finish,
			'html_amount'                  => &$html_amount,
			'html_datetime_start'          => &$html_datetime_start,
			'html_datetime_update'         => &$html_datetime_update,
			'html_datetime_finish'         => &$html_datetime_finish,
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
		$url         = &$this->url;
		$html        = _class( 'html' );
		$payment_api = &$this->payment_api;
		$manage_lib  = &$this->manage_payment_lib;
		// prepare view: request options
		$content = array();
		foreach( $_method[ 'option' ] as $key => $title ) {
			if( !empty( $_request[ 'options' ][ $key ] ) ) {
				$content[ $title ] = $_request[ 'options' ][ $key ];
			}
		}
		$html_request_options = $html->simple_table( $content, array( 'no_total' => true ) );
		$html_request_options_csv = $html->a( array(
			'href'   => $this->_url( 'csv_request', array( '%operation_id' => $_operation_id ) ),
			'icon'   => 'fa fa-file-excel-o',
			'title'  => 'Опции запроса: экспорт в CSV файл',
			'text'   => 'CSV файл',
			'target' => '_blank',
		));
		// prepare view: response options
		$content = null;
		if( !empty( $_response ) ) {
			$response = array_reverse( $_response );
			$content = table( $response, array( 'no_total' => true ) )
				->text( 'datetime', 'дата' )
				->func( 'data', function( $value, $extra, $row ) use( $_provider_name, $_providers_user__by_name  ) {
					// message
					$message = @$row[ 'status_message' ] ?: @$row[ 'data' ][ 'message' ];
					$result = t( trim( trim( $message ), '.,:' ) );
					// provider
					$provider_name = @$row[ 'provider_name' ];
					if( $provider_name && $provider_name != $_provider_name ) {
						$provider_title = @$_providers_user__by_name[ $provider_name ][ 'title' ];
						$result .= ' ('. $provider_title .')';
					}
					return( $result );
				}, array( 'desc' => 'сообщение' ) )
				->func( 'data', function( $value, $extra, $row ) {
					$result = @$row[ 'state' ] ?: @$row[ 'data' ][ 'state' ] ?: null;
					return( $result );
				}, array( 'desc' => 'статус' ) )
			;
			$response_last = reset( $response );
			$response_last = $response_last[ 'data' ];
		}
		$html_response = $content;
		// prepare view: operations by method
		list( $data, $count ) = $payment_api->operation( array(
			'where' =>
				'account_id = '. $_account_id
				.' AND provider_id = '. $_provider_id
				.' AND operation_id != '. $_operation_id
				.' AND direction = "out"'
			,
			'limit' => 50,
		));
		$html_operations_by_method = null;
		if( @count( $data ) > 0 ) {
			$content = array();
			foreach( $data as $item ) {
				$request = &$item[ 'options' ][ 'request' ][ 0 ];
				// match method
				if( @$request[ 'options' ][ 'method_id' ] == $_method_id ) {
					$request_options = &$request[ 'options' ];
					$account_number = @$request_options[ 'account_number' ] ?:
							@$request_options[ 'account' ] ?:
							@$request_options[ 'card' ] ?:
							'-'
					;
					$content[ $item[ 'operation_id' ] ] = array(
						'operation_id'   => $item[ 'operation_id' ],
						'account_number' => $account_number,
						'amount'         => $item[ 'amount' ],
						'status_id'      => $item[ 'status_id' ],
						'date'           => $item[ 'datetime_update' ],
					);
				}
			}
			$content && $html_operations_by_method = table( $content, array( 'no_total' => true ) )
				->text( 'operation_id'  , 'операция' )
				->text( 'account_number', 'счет, номер карты, кошелек' )
				->func( 'amount', function( $value, $extra, $row ) use( $payment_api ) {
					$result = $payment_api->money_html( $value );
					return( $result );
				}, array( 'desc' => 'сумма' ) )
				->func( 'status_id', function( $value, $extra, $row ) use( $manage_lib, $_statuses ) {
					$status_name = $_statuses[ $value ][ 'name' ];
					$title       = $_statuses[ $value ][ 'title' ];
					$css = $manage_lib->css_by_status( array(
						'status_name' => $status_name,
					));
					$result = sprintf( '<span class="%s">%s</span>', $css, $title );
					return( $result );
				}, array( 'desc' => 'статус' ) )
				->text( 'date' , 'дата' )
				->btn( 'Вывод средств', $url[ 'view'    ], array( 'icon' => 'fa fa-sign-out', 'class_add' => 'btn-primary', 'target' => '_blank' ) )
			;
		}
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
		// compile
		$content = array(
			'Операция'        => $_operation_id,
			'Пользователь'    => $user_link . $balance_link,
			'Провайдер'       => $_provider[ 'title' ],
			'Метод'           => $_method[ 'title' ],
			'Тип карты'       => $_html_card_title,
			'Сумма'           => $_html_amount,
			'Статус'          => $_html_status_title,
			'Дата создания'   => $_html_datetime_start,
			'Дата обновления' => $_html_datetime_update,
			'Дата завершения' => $_html_datetime_finish,
		);
		if( ! @$_html_card_title ) { unset( $content[ 'Тип карты' ] ); };
		$html_operation_options = $html->simple_table( $content, array( 'no_total' => true ) );
		$url_view = $this->_url( 'view', array( '%operation_id' => $_operation_id ) );
		// manual mode
		$is_manual = false;
		$is_test = $_provider_class->is_test();
		switch( $_provider_name ) {
			case 'ecommpay':
				$is_manual = true;
				$url_base = 'https://cliff.ecommpay.com/';
				$url_provider_operations = $url_base . 'operations/searchPayout';
				$url_provider_payouts    = $url_base . 'payouts';
				if( $is_test ) {
					$url_base = 'https://cliff-sandbox.ecommpay.com/';
					$url_provider_operations = $url_base . 'operations';
					$url_provider_payouts    = $url_provider_operations;
				}
				$url_provider_operation_detail = empty( $response_last[ 'transaction_id' ] ) ? null : $url_base . 'operations/detail/' . $response_last[ 'transaction_id' ];
				break;
		}
		// render
		$replace = $operation + array(
			'is_manual'            => $is_manual,
			'header_data'          => $html_operation_options,
			'request_data'         => $html_request_options,
			'request_data_csv'     => $html_request_options_csv,
			'response_data'        => $html_response,
			'operations_by_method' => $html_operations_by_method,
			'url' => array(
				'list'               => $this->_url( 'list' ),
				'view'               => $this->_url( 'view',               array( '%operation_id' => $_operation_id ) ),
				'request'            => $this->_url( 'request',            array( '%operation_id' => $_operation_id ) ),
				'request_interkassa' => $this->_url( 'request_interkassa', array( '%operation_id' => $_operation_id ) ),
				'check_interkassa'   => $this->_url( 'check_interkassa',   array( '%operation_id' => $_operation_id ) ),
				'status_processing'  => $this->_url( 'status_processing',  array( '%operation_id' => $_operation_id ) ),
				'status_success'     => $this->_url( 'status_success',     array( '%operation_id' => $_operation_id ) ),
				'status_refused'     => $this->_url( 'status_refused',     array( '%operation_id' => $_operation_id ) ),
				'csv'                => $this->_url( 'csv',                array( '%operation_id' => $_operation_id ) ),
				'provider_operation_detail' => @$url_provider_operation_detail,
				'provider_operations'       => @$url_provider_operations,
				'provider_payouts'          => @$url_provider_payouts,
			)
		);
		$result = tpl()->parse( 'manage_payout/view', $replace );
		return( $result );
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

}
