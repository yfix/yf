<?php

class yf_manage_payout {

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
			->text( 'user_id'     , 'Номер(а) пользователя' )
			->text( 'name'        , 'Имя'                   )
			->text( 'amount'      , 'Сумма от'              )
			->text( 'amount__and' , 'Сумма до'              )
			->text( 'balance'     , 'Баланс от'             )
			->text( 'balance__and', 'Баланс до'             )
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
					'provider_id' => array( 'cond' => 'eq',      'field' => 'o.provider_id' ),
					'user_id'     => array( 'cond' => 'in',      'field' => 'a.user_id'     ),
					'name'        => array( 'cond' => 'like',    'field' => 'u.name'        ),
					'balance'     => array( 'cond' => 'between', 'field' => 'a.balance'     ),
					'amount'      => array( 'cond' => 'between', 'field' => 'o.amount'      ),
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
			->func( 'status_id', function( $value, $extra, $row_info ) use ( $payment_status ){
				$result = $payment_status[ $value ][ 'title' ];
				return( $result );
			}, array( 'desc' => 'статус' ) )
			->text( 'datetime_start', 'дата создания' )
			->btn( 'Вывод средств', $url[ 'view'    ], array( 'icon' => 'fa fa-sign-out', 'class_add' => 'btn-danger' ) )
			->btn( 'Пользователь' , $url[ 'user'    ], array( 'icon' => 'fa fa-user'    , 'class_add' => 'btn-info'   ) )
			->btn( 'Счет'         , $url[ 'balance' ], array( 'icon' => 'fa fa-money'   , 'class_add' => 'btn-info'   ) )
		);
	}

	function csv_request( $options = null ) {
		// check operation
		$operation = $this->_operation();
		// import options
		is_array( $operation ) && extract( $operation, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_is_valid ) ) { return( $operation ); }
		// var
		$html        = _class( 'html' );
		$payment_api = _class( 'payment_api' );
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
		$html        = _class( 'html' );
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
		$method    = $provider_class->api_method_payout( $method_id );
		// check operation status
		$statuses = $payment_api->get_status();
		if( empty( $statuses[ $o_status_id ] ) ) {
			$result = array(
				'status_message' => 'Неизвестный статус операции: '. $o_status_id,
			);
			return( $this->_user_message( $result ) );
		}
		$o_status = $statuses[ $o_status_id ];
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
			$content = table( $_response, array( 'no_total' => true ) )
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
			'Провайдер'       => $_provider[ 'title' ],
			'Метод'           => $_method[ 'title' ],
			'Сумма'           => $_html_amount,
			'Статус'          => $_status[ 'title' ],
			'Дата создания'   => $_html_datetime_start,
			'Дата обновления' => $_html_datetime_update,
			'Дата завершения' => $_html_datetime_finish,
		);
		$html_operation_options = $html->simple_table( $content, array( 'no_total' => true ) );
		$url_view = $this->_url( 'view', array( '%operation_id' => $_operation_id ) );
		// url EcommPay
		$is_test = $_provider_class->is_test();
		$url_base = 'https://cliff.ecommpay.com/';
		$is_test && $url_base = 'https://cliff-sandbox.ecommpay.com/';
		$url_operation_detail = empty( $_transaction_id ) ? $url_view .'#/' : $url_base . 'operations/detail/' . $_transaction_id;
		$url_payouts          = $url_base . 'payouts/index';
		// render
		$is_progressed = $_status[ 'name' ] != 'in_progress';
		$replace = $operation + array(
			'is_progressed'    => $is_progressed,
			'header_data'      => $html_operation_options,
			'request_data'     => $html_request_options,
			'request_data_csv' => $html_request_options_csv,
			'response_data'    => $html_response,
			'url' => array(
				'list'           => $this->_url( 'list' ),
				'view'           => $this->_url( 'view',           array( '%operation_id' => $_operation_id ) ),
				'request'        => $this->_url( 'request',        array( '%operation_id' => $_operation_id ) ),
				'status_success' => $this->_url( 'status_success', array( '%operation_id' => $_operation_id ) ),
				'status_refused' => $this->_url( 'status_refused', array( '%operation_id' => $_operation_id ) ),
				'csv'            => $this->_url( 'csv',            array( '%operation_id' => $_operation_id ) ),
				'provider_operation_detail' => $url_operation_detail,
				'provider_payouts'          => $url_payouts,
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

	function request() {
		// check operation
		$operation = $this->_operation();
		// import options
		is_array( $operation ) && extract( $operation, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_is_valid ) ) { return( $result ); }
		// var
		$html        = _class( 'html' );
		$payment_api = _class( 'payment_api' );
		$data = $_request[ 'options' ] + array(
			'operation_id' => $_operation_id,
		);
		$result = $_provider_class->api_request( $data );
		// message
		$message = array();
		$message[] = $result[ 'status_message' ];
		// if( empty( $result[ 'status' ] ) ) {
			// $r = $_provider_class->_payout_refused( array(
				// 'operation_id' => $_operation_id,
			// ));
		// } else {
			// $r = $_provider_class->_payout_success( array(
				// 'operation_id' => $_operation_id,
			// ));
		// }
		if( empty( $r[ 'status' ] ) ) {
			$message[] = $r[ 'status_message' ];
			$result = array(
				'status_message'  => implode( '<br>', $message ),
				'is_html_message' => true,
			) + $result;
		}
		$result[ 'operation_id' ] = $_operation_id;
		return( $this->_user_message( $result ) );
	}

	function status() {
		// check operation
		$operation = $this->_operation();
		// import options
		is_array( $operation ) && extract( $operation, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_is_valid ) ) { return( $result ); }
		$status = $_GET[ 'status' ];
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
		}
		if( empty( $result[ 'status' ] ) ) {
			$result[ 'operation_id' ] = $_operation_id;
			return( $this->_user_message( $result ) );
		}
		// mail
		$payment_api = _class( 'payment_api' );
		$payment_api->mail( array(
			'tpl'     => $mail_tpl,
			'user_id' => $_user_id,
			'data'    => array(
				'operation_id' => $_operation_id,
				'amount'       => $_operation[ 'amount' ],
			),
		));
		$url_view = $this->_url( 'view', array( '%operation_id' => $_operation_id ) );
		return( js_redirect( $url_view, false ) );
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

}
