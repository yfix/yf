<?php

class yf_manage_payout {

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
				->left_join( 'payment_account  as a', 'a.account_id  = o.account_id'   )
				->left_join( 'user as u'            , 'u.id = a.user_id'              )
			->where( 'p.system', 'in', 0 )
			->where( 'p.active', '>=', 1 )
			->where( 'o.direction', 'out' )
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
			->func( 'options', function( $value, $extra, $row ) use( $providers ) {
				if( empty( $row[ 'options' ] ) ) { return( null ); }
				// options
				$options = @json_decode( $row[ 'options' ], true );
				if( empty( $options ) ) { return( null ); }
				// request
				$request = @reset( $options[ 'request' ] );
				if( empty( $request ) ) { return( null ); }
				// method
				$provider_id = @$request[ 'options' ][ 'provider_id' ];
				if( empty( $provider_id ) ) { return( null ); }
				$method_id   = @$request[ 'options' ][ 'method_id' ];
				if( empty( $method_id ) ) { return( null ); }
				$method = @$providers[ $provider_id ][ '_method_allow' ][ 'payout' ][ $method_id ];
				if( empty( $method ) ) { return( null ); }
				$method_title = @$method[ 'title' ];
				if( empty( $method_title ) ) { return( null ); }
				$result = $method_title;
				return( $result );
			}, array( 'desc' => 'метод' ) )
			->text( 'amount'        , 'сумма' )
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
			->btn( 'Вывод средств', $url[ 'view'    ], array( 'icon' => 'fa fa-sign-out', 'class_add' => 'btn-primary', 'target' => '_blank' ) )
			// ->btn( 'Пользователь' , $url[ 'user'    ], array( 'icon' => 'fa fa-user'    , 'class_add' => 'btn-info'   ) )
			// ->btn( 'Счет'         , $url[ 'balance' ], array( 'icon' => 'fa fa-money'   , 'class_add' => 'btn-info'   ) )
			->footer_link( 'Обновить статусы операций Интеркассы', $url[ 'check_all_interkassa' ], array( 'class' => 'btn btn-primary', 'icon' => 'fa fa-refresh' ) )
		);
	}

	function csv_request( $options = null ) {
		// check operation
		$operation = $this->_operation();
		// import options
		is_array( $operation ) && extract( $operation, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_is_valid ) ) { return( $operation ); }
		// var
		$payment_api = &$this->payment_api;
		// prepare view: request options
		$content = array();
		foreach( $_method[ 'option' ] as $key => $title ) {
			if( !empty( $_request[ 'options' ][ $key ] ) ) {
				$content[ $title ] = $_request[ 'options' ][ $key ];
			}
		}
		// prepare data
		$data = array();
		$data[] = array_keys( $content );
		$data[] = array_values( $content );
		$file_name = 'operation_'. $_operation_id .'__'. date( 'Y-m-d_H-i-s' ) .'.csv';
		// output
		$result = $this->_http_csv( array(
			'file_name' => $file_name,
			'data'      => $data,
			// 'debug'     => true,
		));
		$result[ 'operation_id' ] = $_operation_id;
		return( $this->_user_message( $result ) );
	}

	function _save_csv( $file_name, $data = null ) {
		// setlocale( LC_ALL, 'ru_RU.utf8' )
			// || setlocale( LC_ALL, 'ru_UA.utf8' )
			// || setlocale( LC_ALL, 'en_US.utf8' );
		if( is_array( $data ) && ( $file = fopen( $file_name, 'w' ) ) !== FALSE ) {
			foreach( $data as $id => $item ) {
				if( !is_array( $item ) ) {
					fclose( $file );
					$result = array(
						'status'         => false,
						'status_header'  => 'Экспорт в CSV',
						'status_message' => 'Требуется массив, неверные данные: '. $item,
					);
					return( $result );
				}
				$_data = array_values( $item );
				$result = fputcsv( $file, $_data, ';', '"' );
				if( false === $result ) {
					fclose( $file );
					$result = array(
						'status'         => false,
						'status_header'  => 'Экспорт в CSV',
						'status_message' => 'Ошибка при конвертацию данных: '. $_data,
					);
					return( $result );
				}
			}
			fclose( $file );
			$result = array(
				'status' => true,
			);
		} else {
			$result = array(
				'status'         => false,
				'status_header'  => 'Экспорт в CSV',
				'status_message' => 'Ошибка при открытие потока данных',
			);
		}
		return( $result );
	}

	function _http_csv( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( !is_array( $_data ) || empty( $_file_name ) ) {
			$result = array(
				'status'         => false,
				'status_header'  => 'Экспорт в CSV',
				'status_message' => 'Нет данных',
			);
			return( $result );
		}
		// start
		ob_start();
		$result = $this->_save_csv( 'php://output', $_data );
		$csv = ob_get_clean();
		if( empty( $result[ 'status' ] ) ) { return( $result ); }
		if( !empty( $_debug ) ) { return( $csv ); }
		header( 'Content-type: text/csv' );
		header( 'Content-disposition: attachment; filename='. $_file_name );
		echo $csv;
		exit;
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
		$is_payout_interkassa = (bool)$this->IS_PAYOUT_INTERKASSA;
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
			case @$_status == 'in_progress':
				$_css_panel_status = 'warning';
				empty( $_status_header ) && $_status_header = 'В процессе';
				break;
			case @$_status == 'processing':
				$_css_panel_status = 'warning';
				empty( $_status_header ) && $_status_header = 'Обработка';
				break;
			case @$_status == 'success' || @$_status === true:
				$_css_panel_status = 'success';
				empty( $_status_header ) && $_status_header = 'Выполнено';
				break;
			case @$_status == 'refused':
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

	function request() {
		// check operation
		$operation = $this->_operation();
		// import options
		is_array( $operation ) && extract( $operation, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_is_valid ) ) { return( $operation ); }
		// is_processing
		if( @$_is_processing ) {
			$result = array(
				'status_message' => 'Операция уже обрабатывается',
			);
			return( $this->_user_message( $result ) );
		}
		// var
		$payment_api = &$this->payment_api;
		$data = $_request[ 'options' ] + array(
			'operation_id' => $_operation_id,
		);
		$result = $_provider_class->api_payout( $data );
		$result[ 'operation_id' ] = $_operation_id;
		if( @$result[ 'status' ] == 'success' ) {
			// processing
			$_provider_class->_update_status( array(
				'operation_id' => $_operation_id,
				'name'         => 'processing',
			));
		}
		return( $this->_user_message( $result ) );
	}

	function request_interkassa() {
		// check operation
		$operation = $this->_operation();
		// import options
		is_array( $operation ) && extract( $operation, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_is_valid ) ) { return( $operation ); }
		// is_processing
		if( @$_is_processing ) {
			$result = array(
				'status_message' => 'Операция уже обрабатывается',
			);
			return( $this->_user_message( $result ) );
		}
		// var
		$payment_api = &$this->payment_api;
		$data = $_request[ 'options' ] + array(
			'operation_id' => $_operation_id,
		);
		// provider
		$provider_class = $payment_api->provider_class( array(
			'provider_name' => 'interkassa',
		));
		if( empty( $provider_class ) ) {
			$result = array(
				'status_message' => 'Провайдер Интеркасса не доступен',
			);
			return( $this->_user_message( $result ) );
		}
		// detect card type
		$card = @$data[ 'card' ];
		$result = $this->interkassa_detect_card( array(
			'card' => $card,
		));
		@list( $method_id, $method ) = $result;
		if( empty( $method_id ) ) { return( $this->_user_message( $result ) ); }
		$data[ 'method_id'      ] = $method_id;
		$data[ 'provider_force' ] = true;
		// result
		$result = $provider_class->api_payout( $data );
		return( $this->_user_message( $result ) );
	}

	function interkassa_detect_card( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_card ) ) {
			$result = array(
				'status_message' => 'Не задан номер карты',
			);
			return( $result );
		}
		// var
		$payment_api = &$this->payment_api;
		$provider_class = $payment_api->provider_class( array(
			'provider_name' => 'interkassa',
		));
		if( empty( $provider_class ) ) {
			$result = array(
				'status_message' => 'Провайдер Интеркасса не доступен',
			);
			return( $result );
		}
		// find by card
		$validate = _class( 'validate' );
		$methods = &$provider_class->method_allow[ 'payout' ];
		$is_method_id = null;
		foreach( $methods as $method_id => $method ) {
			$rules = &$method[ 'option_validation' ][ 'card' ];
			if( empty( $rules ) ) { continue; }
			$result = $validate->_input_is_valid( $_card, $rules );
			if( $result ) { $is_method_id = $method_id; break; }
		}
		if( empty( $is_method_id ) ) {
			$result = array(
				'status_message' => 'Карта не опознана: '. $_card,
			);
		} else {
			$result = array( $method_id, $method );
		}
		return( $result );
	}

	function status_interkassa( $options = null ) {
		// import operation
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// provider interkassa
		$payment_api = &$this->payment_api;
		$provider_name = 'interkassa';
		$provider_class = $payment_api->provider_class( array(
			'provider_name' => $provider_name,
		));
		if( empty( $provider_class ) ) {
			$result = array(
				'status_message' => 'Провайдер Интеркасса не доступен',
			);
			return( $result );
		}
		// result
		$result = array(
			'operation_id'   => &$_operation_id,
			'status'         => &$status_name,
			'status_message' => &$status_message,
		);
		// state
		list( $status_name, $status_message ) = $provider_class->_state( $_state
			, $provider_class->_payout_status
			, $provider_class->_payout_status_message
		);
		$status_message = @$status_message ?: @$data[ 'stateName' ];
		// transaction compile
		if( @$result[ 'status' ] == 'success' || @$result[ 'status' ] == 'refused' ) {
			// save response
			$sql_datetime = $payment_api->sql_datetime();
			$operation_options = array(
				'response' => array( array(
					'datetime'       => $sql_datetime,
					'provider_name'  => $provider_name,
					'state'          => $_state,
					'status_name'    => $status_name,
					'status_message' => $status_message,
					'data'           => $_data,
				)),
			);
			$operation_update_data = array(
				'operation_id'    => $_operation_id,
				'datetime_update' => $sql_datetime,
				'options'         => $operation_options,
			);
			$payment_api->operation_update( $operation_update_data );
			// update status
			return( $this->_status( $result ) );
		}
		return( $result );
	}

	function _check_interkassa( $options = null ) {
		// check operation
		$operation = $this->_operation( $options );
		// import options
		is_array( $operation ) && extract( $operation, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! @$_is_valid ) { return( $operation ); }
		if( !$_is_processing_interkassa ) {
			$result = array(
				'status_message' => 'Данная операция не обрабатывается Интеркассой',
			);
			return( $result );
		}
		// var
		$payment_api = &$this->payment_api;
		// response
		$response = @end( $_response );
		if( empty( $response ) ) {
			$result = array(
				'status_message' => 'Транзакция не найдена',
			);
			return( $result );
		}
		$id = &$response[ 'data' ][ 'id' ];
		if( !@$id || $id < 1 ) {
			$result = array(
				'status_message' => 'Номер транзакции не найдена',
			);
			return( $result );
		}
		// provider interkassa
		$provider_class = $payment_api->provider_class( array(
			'provider_name' => 'interkassa',
		));
		if( empty( $provider_class ) ) {
			$result = array(
				'status_message' => 'Провайдер Интеркасса не доступен',
			);
			return( $result );
		}
		// check transaction
		$request_option = array(
			'method_id' => 'withdraw-id',
			'id'        => $id,
		);
		list( $status, $result ) = $provider_class->api_request( $request_option );
		if( empty( $status ) ) {
			$result = array(
				'status_message' => 'Невозможно выполнить проверку транзакции',
			);
			return( $result );
		}
		// check status
		$data = @$result[ 'data' ];
		// check status
		$state = (int)$data[ 'state' ];
		$result = $this->status_interkassa( array(
			'operation_id' => $_operation_id,
			'state'        => $state,
			'data'         => $data
		));
		return( $result );
	}

	function check_interkassa() {
		$result = $this->_check_interkassa();
		return( $this->_user_message( $result ) );
	}

	function _status( $options = null ) {
		// check operation
		$operation = $this->_operation( $options );
		// import options
		is_array( $options   ) && extract( $options,   EXTR_PREFIX_ALL | EXTR_REFS, '_' );
		is_array( $operation ) && extract( $operation, EXTR_PREFIX_ALL | EXTR_REFS, ''  );
		if( empty( $_is_valid ) ) { return( $operation ); }
		$status = $_GET[ 'status' ] ?: $__status;
		switch( $status ) {
			case 'success':
				$result = $_provider_class->_payout_success( array(
					'operation_id' => $_operation_id,
				));
				$mail_tpl = 'payout_success';
				break;
			case 'refused':
				$result = $_provider_class->_payout_refused( array(
					'operation_id' => $_operation_id,
				));
				$mail_tpl = 'payout_refused';
				break;
			case 'processing':
				$result = $_provider_class->_payout_processing( array(
					'operation_id' => $_operation_id,
				));
				break;
		}
		if( empty( $result[ 'status' ] ) ) {
			$result[ 'operation_id' ] = $_operation_id;
			return( $result );
		}
		// mail
		$payment_api = &$this->payment_api;
		@$mail_tpl && $payment_api->mail( array(
			'tpl'     => $mail_tpl,
			'user_id' => $_user_id,
			'admin'   => true,
			'data'    => array(
				'operation_id' => $_operation_id,
				'amount'       => $_operation[ 'amount' ],
			),
		));
		$url_view = $this->_url( 'view', array( '%operation_id' => $_operation_id ) );
		if( !empty( $__status_message ) ) { return( $options ); }
		return( js_redirect( $url_view, false ) );
	}

	function status( $options = null ) {
		$result = $this->_status( $options );
		return( $this->_user_message( $result ) );
	}

	function _array2csv(array &$array, $delim = ';') {
		if (count($array) == 0) {
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
	* https://cliff.ecommpay.com/download/%D0%98%D0%BD%D1%81%D1%82%D1%80%D1%83%D0%BA%D1%86%D0%B8%D1%8F%20%D0%BF%D0%BE%20%D0%B2%D1%8B%D0%BF%D0%BB%D0%B0%D1%82%D0%B0%D0%BC%20%D1%87%D0%B5%D1%80%D0%B5%D0%B7%20%D1%84%D0%B0%D0%B9%D0%BB.pdf
	*/
	function csv() {
		$operation_id = intval($_GET['operation_id']);
		$info = db()->from('payment_operation')->where('operation_id', $operation_id)->get();
		if (!$info) {
			return _404();
		}
		$info['options'] = json_decode($info['options'], true);
		$options = $info['options']['request'][0]['options'];
		$opt_data = $info['options']['request'][0]['data'];
		$data = array();
		$data['payment_group_id']	= 1; // Bank cards
		$data['site_id']			= '2415'; // Betonmoney.com
		$data['external_id']		= $operation_id;
		$data['comment']			= 'Payments out request. Date: '.date('Y-m-d_H-i-s').' OID: '.$operation_id;
		$data['phone']				= preg_replace('~[^0-9]~ims', '', $options['sender_phone']);
		$data['customer_purse']		= $options['card'];
#		$data['transaction_id'] = ''; // [обязательный, если customer_purse не используется; пустой, если используется customer_purse]
			// Номер транзакции в Клиентском интерфейсе, по которой ранее был осуществлен прием средств.
			// Обычно используется для выплат на банковские карты при отсутствии сертификата PCI DSS.
		// Валюта, в которой была указана сумма платежа. Если валюта запроса не соответствует валюте счета, с которого будет осуществлен платеж,
		// то система автоматически осуществит пересчет суммы по курсу ЦБ РФ.
#		$data['amount']				= intval($opt_data['amount'] * 100);
#		$data['currency']			= $opt_data['currency_id'];
		$data['amount']				= intval($options['amount'] * 100);
		$data['currency']			= 'USD';
		$data = array($data);
		$csv = $this->_array2csv($data);
		// Ecommpay wants ";" everywhere
		$csv = explode(PHP_EOL, $csv);
		$csv[0] = str_replace(',', ';', $csv[0]);
		$csv = trim(implode(PHP_EOL, $csv));
		no_graphics(true);
		if (DEBUG_MODE) {
			echo '<pre>'; print_r($csv); print_r($opt); print_r($info); print_r($data);
		} else {
			header('Content-disposition: attachment; filename=Ecommpay_out_'.intval($operation_id).'_'.date('Ymd_His').'.csv');
			header('Content-type: text/csv');
			echo $csv;
		}
		exit;
	}

	function _check_all_interkassa() {
		// var
		$html        = _class( 'html' );
		$payment_api = &$this->payment_api;
		// update status only processing
		$object = $payment_api->get_status( array( 'name' => 'processing' ) );
		list( $status_id, $status ) = $object;
		if( empty( $status_id ) ) { return( $object ); }
		// provider
		$providers_user = $payment_api->provider();
		$providers_id = implode( ',', array_keys( $providers_user ) );
		// fetch operations
		$db = db()->table( 'payment_operation' )
			->where( 'provider_id', 'in', $providers_id )
			->where( 'status_id', '=', $status_id )
			->where( 'direction', '=', 'out' )
		;
// DEBUG
// var_dump( $db->sql() ); exit;
		$operations = $db->get_all();
// DEBUG
// var_dump( $operations ); exit;
		if( empty( $operations ) ) { return( $operations ); }
		// check operations
		$result = array();
		foreach( $operations as $item ) {
			$operation_id = $item[ 'operation_id' ];
			$r = $this->_check_interkassa( array(
				'operation_id' => $operation_id,
			));
			$result[ $operation_id ] = $r;
			sleep( 5 );
		}
		return( $result );
	}

	function check_all_interkassa( $options = null ) {
		// command line interface
		$is_cli = ( php_sapi_name() == 'cli' );
		$is_cli && $this->_check_all_interkassa_cli();
		// web
		$replace = array(
			'is_confirm' => false,
		);
		$html_result = '';
		$result = form( $replace )
			->on_post( function( $data, $extra, $rules ) use( &$html_result ) {
				$is_confirm = !empty( $_POST[ 'is_confirm' ] );
				if( $is_confirm ) {
					$result = $this->_check_all_interkassa();
					if( empty( $result ) ) {
						$level = 'warning';
						$message = 'Нет операций для обработки';
					} else {
						$level = 'success';
						$message = 'Выполнено, обновление статусов операций Интеркассы';
						// prepare result html
						$content = array();
						$html = _class( 'html' );
						foreach( $result as $operation_id => $item ) {
							$link = $html->a( array(
								'href'      => $this->_url( 'view', array( '%operation_id' => $operation_id ) ),
								'class_add' => 'btn-primary',
								'target'    => '_blank',
								'icon'      => 'fa fa-sign-out',
								'title'     => 'Вывод средств №'. $operation_id,
								'text'      => 'Вывод средств №'. $operation_id,
							));
							$content[ $link ] = $item[ 'status_message' ];
						}
						$html_result = $html->simple_table( $content, array( 'no_total' => true ) );
					}
					common()->add_message( $message, $level );
				} else {
					common()->message_info( 'Требуется подтверждение, для выполнения операции' );
				}
			})
			->check_box( 'is_confirm', array( 'desc' => 'Подтверждение', 'no_label' => true ) )
			->row_start()
				->submit( 'operation', 'update', array( 'desc' => 'Обновить статусы операций Интеркассы', 'icon' => 'fa fa-refresh' ) )
				->link( 'Назад' , $this->_url( 'list' ), array( 'class' => 'btn btn-default', 'icon' => 'fa fa-chevron-left' ) )
			->row_end()
		;
		return( $result . $html_result );
	}

	function _check_all_interkassa_cli( $options = null ) {
		$result = $this->_check_all_interkassa();
		if( empty( $result ) ) {
			$status = -1;
			$message = 'no operations';
		} else {
			$status = 0;
			$content = array();
			foreach( $result as $operation_id => $item ) {
				$content[] = $operation_id .' - '. ( @$item[ 'status' ] ?: 'fail' );
			;
			$message = implode( "\n", $content );
			}
		}
		echo( $message . PHP_EOL );
		exit( $status );
	}

}
