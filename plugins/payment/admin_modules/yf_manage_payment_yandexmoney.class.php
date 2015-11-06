<?php

class yf_manage_payment_yandexmoney {

	protected $object      = null;
	protected $action      = null;
	protected $id          = null;
	protected $filter_name = null;
	protected $filter      = null;
	protected $url         = null;

	public $payment_api        = null;
	public $manage_payment_lib = null;

	public $provider_name  = 'yandexmoney';
	public $provider_class = null;

	function _init() {
		$payment_api = &$this->payment_api;
		$manage_lib  = &$this->manage_payment_lib;
		$provider_name  = &$this->provider_name;
		$provider_class = &$this->provider_class;
		// class
		$payment_api        = _class( 'payment_api'        );
		$manage_payment_lib = module( 'manage_payment_lib' );
		// provider
		$provider_class = $payment_api->provider_class(array( 'provider_name' => $provider_name ));
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
				'is_full_url'  => true,
				'object'       => $object,
				'action'       => 'show',
			)),
			'authorize' => url_admin( array(
				'is_full_url'  => true,
				'object'       => $object,
				'action'       => 'authorize',
			)),
			'request_interkassa' => url_admin( array(
				'object'       => $object,
				'action'       => 'request_interkassa',
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

	function show() {
		$url = $this->_url( 'authorize' );
		$result = js_redirect( $url, false );
		return( $result );
	}

	function _authorize() {
		// class
		$provider_class = &$this->provider_class;
		// request
		$url = $this->_url( 'authorize' );
		$result = $provider_class->authorize_request( array(
			'redirect_uri' => $url,
		));
		return( $result );
	}

	function authorize() {
		$url = &$this->url;
		// class
		$provider_class = &$this->provider_class;
		// is authorize
		$is_authorize = $provider_class->access_token;
		$authorize_icon = 'fa fa-chain';
		$authorize_class = 'btn text-success';
		if( !$is_authorize ) {
			$authorize_icon .= '-broken';
			$authorize_class = 'btn text-danger';
		}
		// web
		$replace = array(
			'is_confirm'   => false,
			'is_authorize' => $is_authorize ? 'выполнена' : 'не выполнена',
		);
		$result = form( $replace )
			->on_post( function( $data, $extra, $rules ) {
				$is_confirm = !empty( $_POST[ 'is_confirm' ] );
				if( $is_confirm ) {
					$result = $this->_authorize();
					if( empty( $result[ 'status' ] ) ) {
						$level = 'error';
						$message = 'Ошибка, авторизация YandexMoney: '. $result[ 'status_message' ];
					} else {
						$level = 'success';
						$message = 'Выполнено, авторизация YandexMoney.';
					}
					common()->add_message( $message, $level );
				} else {
					common()->message_info( 'Требуется подтверждение, для выполнения операции' );
				}
			})
			->info( 'is_authorize', array( 'desc' => 'Авторизация', 'icon' => $authorize_icon, 'class' => $authorize_class ) )
			->check_box( 'is_confirm', array( 'desc' => 'Подтверждение', 'no_label' => true ) )
			->row_start()
				->submit( 'operation', 'authorize', array( 'desc' => 'Авторизация YandexMoney', 'icon' => 'fa fa-key' ) )
				->link( 'Назад' , $url[ 'list' ], array( 'class' => 'btn btn-default', 'icon' => 'fa fa-chevron-left' ) )
			->row_end()
		;
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
		$content = empty( $_is_html_message ) ? $_status_message : htmlentities( $_status_message, ENT_HTML5, 'UTF-8', $double_encode = false );
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

	function status( $options = null ) {
		$result = $this->_status( $options );
		return( $this->_user_message( $result ) );
	}

}
