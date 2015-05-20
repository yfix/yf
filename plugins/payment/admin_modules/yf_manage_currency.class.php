<?php

class yf_manage_currency {

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
			'list' => url_admin( array(
				'object'           => $object,
			)),
			'update' => url_admin( array(
				'object' => $object,
				'action' => 'update',
			)),
			'edit' => url_admin( array(
				'object' => $object,
				'action' => 'edit',
				'id'     => '%currency_rate_id',
			)),
		);
	}

	function _filter_form_show( $filter, $replace ) {
		$order_fields = array(
			'currency_rate_id' => 'id',
			'datetime'         => 'дата обновления',
			'from'             => 'валюта продажи',
			'to'               => 'валюта покупки',
		);
		$payment_api = _class( 'payment_api' );
		$data  = $payment_api->currencies;
		$currenc_ids = array_keys( $data );
		$currencies = array_combine( $currenc_ids, $currenc_ids );
		// form
		// $min_date = db()->select( 'MIN( `datetime` )' )->from( 'payment_currency_rate' )->get_one();
		$result = form( $replace, array(
				'selected' => $filter,
			))
			->text( 'currency_rate_id', 'ID' )
			->date( 'datetime'      , 'Дата от' )
			->date( 'datetime__and' , 'Дата до' )
			// ->daterange( 'datetime', array(
				// 'format'       => 'DD.MM.YYYY',
				// 'min_date'     => date('d.m.Y', $min_date ?: (time() - 86400 * 30)),
				// 'max_date'     => date('d.m.Y', time() + 86400),
				// 'autocomplete' => 'off',
				// 'desc' => 'Дата обновления',
			// ))
			->select_box( 'from', $currencies, array( 'show_text' => 1, 'desc' => 'Валюта продажи' ) )
			->select_box( 'to',   $currencies, array( 'show_text' => 1, 'desc' => 'Валюта покупки' ) )
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
			case 'manage_currency__show':
				$url_redirect_url = url_admin( array(
					'object'     => $object,
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
		// var
		$html        = _class( 'html' );
		$payment_api = _class( 'payment_api' );
		// current current rates
		list( $currency_id, $currency ) = $payment_api->get_currency__by_id();
		// buy
		$data = $payment_api->currency_rate__buy();
		$content = array();
		foreach( $data as $id => $item ) { $content[ $id ] = sprintf( '%.3f', $item[ 'rate' ] / $item[ 'value' ] ); }
		$html_buy = $html->simple_table( $content, array( 'no_total' => true, 'rotate_table' => true ) );
		// sell
		$data = $payment_api->currency_rate__sell();
		$content = array();
		foreach( $data as $id => $item ) { $content[ $id ] = sprintf( '%.3f', $item[ 'rate' ] / $item[ 'value' ] ); }
		$html_sell = $html->simple_table( $content, array( 'no_total' => true, 'rotate_table' => true ) );
		// compile
		$html = <<<EOS
<div class="panel panel-default pull-left">
	<div class="panel-heading">Текущий курс валюты: $currency[name] ($currency_id, $currency[sign])</div>
	<div class="panel-body">
		<div class="pull-left">
			<b>Продажа</b>
$html_buy
		</div>
		<div class="pull-left">
			<b>Покупка</b>
$html_sell
		</div>
	</div>
</div>
EOS;
		// current rates
		$sql = db()->table( 'payment_currency_rate' )->sql();
		return $html.
			table( $sql, array(
				'filter' => $filter,
				'filter_params' => array(
					'currency_rate_id' => array( 'cond' => 'in', 'field' => 'currency_rate_id' ),
					'datetime' => 'daterange_between',
					'__default_order'  => 'ORDER BY datetime DESC',
				),
				'hide_empty' => true,
			))
			->text( 'currency_rate_id', 'ID'  )
			->date( 'datetime', 'дата обновления', array( 'format' => 'full', 'nowrap' => 1 ) )
			->text( 'from', 'валюта продажи' )
			->text( 'to'  , 'валюта покупки' )
			->text( 'from_value', 'величина продажи' )
			->text( 'to_value'  , 'величина покупки' )
			->btn( 'Правка' , $url[ 'edit' ], array( 'icon' => 'fa fa-edit' ) )
			->footer_link( 'Обновить курс валют', $url[ 'update' ], array( 'class' => 'btn btn-primary', 'icon' => 'fa fa-refresh' ) )
		;
	}

	function edit() {
		$object      = &$this->object;
		$action      = &$this->action;
		$filter_name = &$this->filter_name;
		$filter      = &$this->filter;
		$url         = &$this->url;
		// var
		$id = (int)$_GET[ 'id' ];
		if( empty( $_POST ) && $id < 1 ) {
			return js_redirect( $url[ 'list' ], false, 'currency rate list' );
		}
		$replace = db()->table( 'payment_currency_rate' )
			->where( 'currency_rate_id', $id )
			->get();
		;
		isset( $_POST ) && $replace = $_POST + $replace;
		$result = form( $replace )
			->validate( array(
				'__before__' => 'trim',
				'from_value' => 'required',
				'to_value'   => 'required',
			))
			->on_post( function( $data, $extra, $rules ) {
				$payment_api = _class( 'payment_api' );
				$decimals = 6;
				$value = &$_POST[ 'from_value' ];
					$value = $payment_api->_number_mysql( $value, $decimals );
				$value = &$_POST[ 'to_value' ];
					$value = $payment_api->_number_mysql( $value, $decimals );
			})
			->db_update_if_ok( 'payment_currency_rate', array(
				'from_value',
				'to_value',
			), 'currency_rate_id='. $id )
			->info( 'currency_rate_id' , 'ID' )
			->info_date( 'datetime', 'дата обновления' )
			->info( 'from', 'валюта продажи' )
			->info( 'to'  , 'валюта покупки' )
			->text( 'from_value', 'величина продажи' )
			->text( 'to_value'  , 'величина покупки' )
			->row_start()
				->save_and_back()
				->link( 'Назад' , $url[ 'list' ], array( 'class' => 'btn btn-default', 'icon' => 'fa fa-chevron-left' ) )
			->row_end()
		;
		return( $result );
	}

	function _update() {
		$currency__api = _class( 'payment_api__currency' );
		$data   = $currency__api->load_from_NBU();
		if( empty( $data ) ) { return( null ); }
		$data   = $currency__api->reverse( array( 'currency_rate'    => $data, ));
		$data   = $currency__api->prepare( array( 'currency_rate'    => $data, ));
		$data   = $currency__api->correction( array( 'currency_rate' => $data, ));
		$count = count( $data );
		$result = $currency__api->update( array( 'currency_rate'     => $data, ));
		return( array( $result, $count ) );
	}

	function update() {
		$url = &$this->url;
		// command line interface
		$is_cli = ( php_sapi_name() == 'cli' );
		$is_cli && $this->_update_cli();
		// web
		$replace = array(
			'is_confirm' => false,
		);
		$result = form( $replace )
			->on_post( function( $data, $extra, $rules ) {
				$is_confirm = !empty( $_POST[ 'is_confirm' ] );
				if( $is_confirm ) {
					list( $result, $count ) = $this->_update();
					if( empty( $result ) || $result < 4 ) {
						$level = 'error';
						$message = 'Ошибка: обновление курса валют: '. $result;
					} else {
						$level = 'success';
						$message = 'Выполнено: обновление курса валют: '. $count;
					}
					common()->add_message( $message, $level );
				} else {
					common()->message_info( 'Требуется подтверждение, для выполнения операции' );
				}
			})
			->check_box( 'is_confirm', array( 'desc' => 'Подтверждение', 'no_label' => true ) )
			->row_start()
				->submit( 'operation', 'update', array( 'desc' => 'Обновить курс валют', 'icon' => 'fa fa-refresh' ) )
				->link( 'Назад' , $url[ 'list' ], array( 'class' => 'btn btn-default', 'icon' => 'fa fa-chevron-left' ) )
			->row_end()
		;
		return( $result );
	}

	function _update_cli() {
		list( $result, $count ) = $this->_update();
		if( empty( $result ) || $result < 4 ) {
			$status = 1;
			$message = 'Currency rate update is fail: '. $result;
		} else {
			$status = 0;
			$message = 'Currency rate update is success: '. $count;
			;
		}
		echo( $message . PHP_EOL );
		exit( $status );
	}

}
