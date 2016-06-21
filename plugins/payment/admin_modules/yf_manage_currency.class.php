<?php

class yf_manage_currency {

	public $load_provider = [
		'nbu' => true,
		'cbr' => true,
	];

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
		$url = [
			'list' => url_admin( [
				'object'           => $object,
			]),
			'update' => url_admin( [
				'object' => $object,
				'action' => 'update',
			]),
			'edit' => url_admin( [
				'object' => $object,
				'action' => 'edit',
				'id'     => '%currency_rate_id',
			]),
		];
	}

	function _filter_form_show( $filter, $replace ) {
		$order_fields = [
			'provider_id'      => 'provider_id',
			'currency_rate_id' => 'id',
			'datetime'         => 'дата обновления',
			'from'             => 'валюта продажи',
			'to'               => 'валюта покупки',
		];
		$payment_api = _class( 'payment_api' );
		$data  = $payment_api->currencies;
		$currency_ids = array_keys( $data );
		$currencies = array_combine( $currency_ids, $currency_ids );
		// currency_ids
		$currency__api = _class( 'payment_api__currency' );
		$provider       = &$currency__api->provider;
		$provider_index = &$currency__api->index[ 'provider' ][ 'id' ];
		$providers = [];
		foreach( $provider as $key => $item ) {
			$provider_id = $item[ 'id' ];
			$title       = $item[ 'short' ];
			$providers[ $provider_id ] = $title;
		}
		// form
		// $min_date = db()->select( 'MIN( `datetime` )' )->from( 'payment_currency_rate' )->get_one();
		$result = form( $replace, [
				'selected' => $filter,
			])
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
			->select_box( 'provider_id', $providers, [ 'show_text' => 1, 'desc' => 'Провайдер' ] )
			->select_box( 'from', $currencies, [ 'show_text' => 1, 'desc' => 'Валюта продажи' ] )
			->select_box( 'to',   $currencies, [ 'show_text' => 1, 'desc' => 'Валюта покупки' ] )
			->select_box( 'order_by', $order_fields, [ 'show_text' => 1, 'desc' => 'Сортировка' ] )
			->radio_box( 'order_direction', [ 'asc' => 'прямой', 'desc' => 'обратный' ], [ 'desc' => 'Направление сортировки' ] )
			->save_and_clear()
		;
		return( $result );
	}

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

	function filter_save() {
		$object = &$this->object;
		$id     = &$this->id;
		switch( $id ) {
			case 'manage_currency__show':
				$url_redirect_url = url_admin( [
					'object'     => $object,
				]);
			break;
		}
		$options = [
			'filter_name'  => $id,
			'redirect_url' => $url_redirect_url,
		];
		return( _class( 'admin_methods' )->filter_save( $options ) );
	}

	function show() {
		$object      = &$this->object;
		$action      = &$this->action;
		$filter_name = &$this->filter_name;
		$filter      = &$this->filter;
		$url         = &$this->url;
		// var
		$html          = _class( 'html' );
		$payment_api   = _class( 'payment_api' );
		$currency__api = _class( 'payment_api__currency' );
		// current current rates
		list( $currency_id, $currency ) = $payment_api->get_currency__by_id();
		$provider       = &$currency__api->provider;
		$provider_index = &$currency__api->index[ 'provider' ][ 'id' ];
		// buy
		$data = $payment_api->currency_rates__buy();
		$content = [];
		foreach( $data as $id => $item ) { $content[ $id ] = sprintf( '%.3f', $item[ 'rate' ] / $item[ 'value' ] ); }
		$html_buy = $html->simple_table( $content, [ 'no_total' => true, 'rotate_table' => true ] );
		// sell
		$data = $payment_api->currency_rates__sell();
		$content = [];
		foreach( $data as $id => $item ) { $content[ $id ] = sprintf( '%.3f', $item[ 'rate' ] / $item[ 'value' ] ); }
		$html_sell = $html->simple_table( $content, [ 'no_total' => true, 'rotate_table' => true ] );
		// provider
		$currency_rate__provider = $payment_api->currency_rates__provider();
		// compile
		$html = <<<EOS
<div class="panel panel-default pull-left">
	<div class="panel-heading">$currency_rate__provider[short], курс валюты: $currency[name] ($currency_id, $currency[sign])</div>
	<div class="panel-body">
		<div class="pull-left">
			<b>Покупка</b>
$html_buy
		</div>
		<div class="pull-left">
			<b>Продажа</b>
$html_sell
		</div>
	</div>
</div>
EOS;
		// current rates
		$sql = db()->table( 'payment_currency_rate' )->sql();
		return $html.
			table( $sql, [
				'filter' => $filter,
				'filter_params' => [
					'currency_rate_id' => [ 'cond' => 'in', 'field' => 'currency_rate_id' ],
					'datetime' => 'daterange_between',
					'__default_order'  => 'ORDER BY datetime DESC',
				],
				'hide_empty' => true,
				'no_total' => true,
			])
			->text( 'currency_rate_id', 'ID'  )
			->date( 'datetime', 'дата обновления', [ 'format' => 'full', 'nowrap' => 1 ] )
			->func( 'provider_id', function( $value, $extra, $row_info ) use( $provider_index ){
				$id     = (int)$value;
				$title  = $provider_index[ $id ][ 'short' ];
				$result = $title;
				return( $result );
			}, [ 'desc' => 'провайдер' ] )
			->text( 'from', 'валюта продажи' )
			->text( 'to'  , 'валюта покупки' )
			->text( 'from_value', 'величина продажи' )
			->text( 'to_value'  , 'величина покупки' )
			->btn( 'Правка' , $url[ 'edit' ], [ 'icon' => 'fa fa-edit' ] )
			->footer_link( 'Обновить курс валют', $url[ 'update' ], [ 'class' => 'btn btn-primary', 'icon' => 'fa fa-refresh' ] )
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
		// currency__api
		$currency__api = _class( 'payment_api__currency' );
		$provider       = &$currency__api->provider;
		$provider_index = &$currency__api->index[ 'provider' ][ 'id' ];
			$id     = (int)$replace[ 'provider_id' ];
			$title  = $provider_index[ $id ][ 'short' ];
			$replace[ 'provider' ] = $title;
		// post
		isset( $_POST ) && $replace = $_POST + $replace;
		$result = form( $replace )
			->validate( [
				'__before__' => 'trim',
				'from_value' => 'required',
				'to_value'   => 'required',
			])
			->on_post( function( $data, $extra, $rules ) {
				$payment_api = _class( 'payment_api' );
				$decimals = 6;
				$value = &$_POST[ 'from_value' ];
					$value = $payment_api->_number_mysql( $value, $decimals );
				$value = &$_POST[ 'to_value' ];
					$value = $payment_api->_number_mysql( $value, $decimals );
			})
			->db_update_if_ok( 'payment_currency_rate', [
				'from_value',
				'to_value',
			], 'currency_rate_id='. $id )
			->info( 'currency_rate_id' , 'ID' )
			->info_date( 'datetime', 'дата обновления' )
			->info( 'provider', 'провайдер' )
			->info( 'from', 'валюта продажи' )
			->info( 'to'  , 'валюта покупки' )
			->text( 'from_value', 'величина продажи' )
			->text( 'to_value'  , 'величина покупки' )
			->row_start()
				->save_and_back()
				->link( 'Назад' , $url[ 'list' ], [ 'class' => 'btn btn-default', 'icon' => 'fa fa-chevron-left' ] )
			->row_end()
		;
		return( $result );
	}

	function _update() {
		$currency__api = _class( 'payment_api__currency' );
		$result = true;
		$total  = 0;
		$index  = 0;
		$max    = 0;
		// error_reporting(-1);
		foreach( $this->load_provider as $item => $active ) {
			if( !$active ) { continue; }
			$data = $currency__api->load([ 'provider' => $item ]);
			if( !$data ) { $result = false; continue; }
			// count
			$count = count( $data ); $max = max( $max, $count );
			$total += $count;
			$index++;
			// processing
			$data    = $currency__api->reverse( [ 'provider' => $item, 'currency_rate'    => $data, ]);
			$data    = $currency__api->prepare( [ 'provider' => $item, 'currency_rate'    => $data, ]);
			$data    = $currency__api->correction( [ 'provider' => $item, 'currency_rate' => $data, ]);
			$result &= $currency__api->update( [ 'provider' => $item, 'currency_rate'     => $data, ]);
		}
		if( ( $total / $index ) < $max ) {
			$result = false;
		}
		return( $result );
	}

	function update() {
		$url = &$this->url;
		// command line interface
		$is_cli = ( php_sapi_name() == 'cli' );
		$is_cli && $this->_update_cli();
		// web
		$replace = [
			'is_confirm' => false,
		];
		$result = form( $replace )
			->on_post( function( $data, $extra, $rules ) {
				$is_confirm = !empty( $_POST[ 'is_confirm' ] );
				if( $is_confirm ) {
					$result = $this->_update();
					if( !@$result ) {
						$level = 'error';
						$message = 'Ошибка: обновление курса валют';
					} else {
						$level = 'success';
						$message = 'Выполнено: обновление курса валют';
					}
					common()->add_message( $message, $level );
				} else {
					common()->message_info( 'Требуется подтверждение, для выполнения операции' );
				}
			})
			->check_box( 'is_confirm', [ 'desc' => 'Подтверждение', 'no_label' => true ] )
			->row_start()
				->submit( 'operation', 'update', [ 'desc' => 'Обновить курс валют', 'icon' => 'fa fa-refresh' ] )
				->link( 'Назад' , $url[ 'list' ], [ 'class' => 'btn btn-default', 'icon' => 'fa fa-chevron-left' ] )
			->row_end()
		;
		return( $result );
	}

	function _update_cli() {
		$result = $this->_update();
		if( !@$result ) {
			$status = 1;
			$message = 'Currency rate update is fail';
		} else {
			$status = 0;
			$message = 'Currency rate update is success';
			;
		}
		echo( $message . PHP_EOL );
		exit( $status );
	}

}
