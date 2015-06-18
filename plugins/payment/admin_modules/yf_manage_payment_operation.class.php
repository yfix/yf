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
			'list' => url_admin( array(
				'object'       => $object,
			)),
			'payin' => url_admin( array(
				'object'       => 'manage_deposit',
				'action'       => 'view',
				'operation_id' => '%operation_id',
			)),
			'payout' => url_admin( array(
				'object'       => 'manage_payout',
				'action'       => 'view',
				'operation_id' => '%operation_id',
			)),
			'update_expired' => url_admin( array(
				'object' => 'manage_deposit',
				'action' => 'update_expired',
			)),
			'check_all_interkassa' => url_admin( array(
				'object' => 'manage_payout',
				'action' => 'check_all_interkassa',
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
			'o.datetime_update' => 'дата обновления',
			'o.datetime_start'  => 'дата создания',
			'o.datetime_finish' => 'дата окончания',
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
			->text( 'name'        , 'Имя пользователя'   )
			->text( 'title'       , 'Название'           )
			->text( 'amount'      , 'Сумма от'           )
			->text( 'amount__and' , 'Сумма до'           )
			->text( 'balance'     , 'Баланс от'          )
			->text( 'balance__and', 'Баланс до'          )
			->select_box( 'status_id'  , $payment_status__select_box, array( 'show_text' => 'статус'    , 'desc' => 'Статус'     ) )
			->select_box( 'provider_id', $providers__select_box     , array( 'show_text' => 'провайдер' , 'desc' => 'Провайдер'  ) )
			->radio_box( 'system', array( '' => 'все', '1' => 'системный', '0' => 'внешний' ), array( 'desc' => 'Тип провайдера' ) )
			->radio_box( 'direction', array( '' => 'все', 'in' => 'приход', 'out' => 'расход' ), array( 'desc' => 'Направление платежа' ) )
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
		$options = array(
			'filter_name'  => $id,
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
		$html        = _class( 'html' );
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
			'o.direction',
			'o.title',
			'o.options',
			'a.user_id',
			'u.name as user_name',
			'o.amount',
			'a.balance',
			'p.title as provider_title',
			'p.system as provider_system',
			'o.status_id as status_id',
			'o.datetime_update',
			'o.datetime_start',
			'o.datetime_finish'
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
					'title'        => array( 'cond' => 'like',    'field' => 'o.title'        ),
					'balance'      => array( 'cond' => 'between', 'field' => 'a.balance'      ),
					'amount'       => array( 'cond' => 'between', 'field' => 'o.amount'       ),
					'__default_order'  => 'ORDER BY o.datetime_update DESC',
				),
			))
			->text( 'operation_id',   'операция'  )
			->func( 'user_name', function( $value, $extra, $row ) {
				$result = a('/members/edit/'.$row[ 'user_id' ], $value . ' (id: ' . $row[ 'user_id' ] . ')');
				return( $result );
			}, array( 'desc' => 'пользователь' ) )
			->text( 'title',          'название'  )
			->text( 'provider_title', 'провайдер' )
			->func( 'options', function( $value, $extra, $row ) use( $providers ) {
				$result = '-';
				if( empty( $row[ 'options' ] ) ) { return( $result ); }
				// options
				$options = @json_decode( $row[ 'options' ], true );
				if( empty( $options ) ) { return( $result ); }
				// request
				$request = @reset( $options[ 'request' ] );
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
			}, array( 'desc' => 'метод' ) )
			->text( 'amount'        , 'сумма' )
			->text( 'balance'       , 'баланс' )
			->func( 'status_id', function( $value, $extra, $row ) use( $manage_lib, $payment_status ) {
				$status_name = $payment_status[ $value ][ 'name' ];
				$title       = $payment_status[ $value ][ 'title' ];
				$css = $manage_lib->css_by_status( array(
					'status_name' => $status_name,
				));
				$result = sprintf( '<span class="%s">%s</span>', $css, $title );
				return( $result );
			}, array( 'desc' => 'статус' ) )
			->text( 'datetime_update', 'дата обновления' )
			->text( 'datetime_start',  'дата создания'   )
			->text( 'datetime_finish', 'дата окончания'  )
			->func( 'operation_id', function( $value, $extra, $row ) use( $_this, $html ) {
				$result = '-';
				$is_system = (bool)$row[ 'provider_system' ];
				$is_in     = $row[ 'direction' ] == 'in';
				$is_out    = $row[ 'direction' ] == 'out';
				$action = array();
				if( !$is_system ) {
					$is_in && $action[] = $html->a( array(
						'href'   => $_this->_url( 'payin', array( '%operation_id' => $value ) ),
						'class_add' => 'btn-primary',
						'icon'   => 'fa fa-sign-in',
						'text'   => 'Ввод средств',
						'target' => '_blank',
					));
					$is_out && $action[] = $html->a( array(
						'href'   => $_this->_url( 'payout', array( '%operation_id' => $value ) ),
						'class_add' => 'btn-primary',
						'icon'   => 'fa fa-sign-out',
						'text'   => 'Вывод средств',
						'target' => '_blank',
					));
				}
				$action && $result = implode( '', $action );
				return( $result );
			}, array( 'desc' => 'действия' ) )
			->footer_link( 'Обновить просроченные операции', $url[ 'update_expired' ], array( 'title' => 'Обновить просроченные операции (только для ввода средств)', 'class' => 'btn btn-primary', 'icon' => 'fa fa-refresh' ) )
			->footer_link( 'Обновить статусы операций Интеркассы', $url[ 'check_all_interkassa' ], array( 'title' => 'Обновить просроченные операции (только для ввода средств)', 'class' => 'btn btn-primary', 'icon' => 'fa fa-refresh' ) )
		);
	}

}
