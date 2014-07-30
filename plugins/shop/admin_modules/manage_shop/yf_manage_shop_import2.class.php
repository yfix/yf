<?php

class yf_manage_shop_import2 {

	private $_filter        = false;
	private $_filter_params = false;

	private $_instance             = false;
	private $_class_admin_products = false;

	function __init() {
		$this->_class_admin_products = _class( 'manage_shop_products', 'admin_modules/manage_shop/' );
		$this->is_post = input()->is_post();
		$this->is_init = (bool)input()->get( 'init' );
		// get filter
		$_object             = input()->get( 'object' );
		$_action             = input()->get( 'action' );
		$_action_parent      = input()->get( 'filter' );
		$_session_parent_key = $_object . '__' . $_action_parent;
		$_session_key        = $_object . '__' . $_action;
		if( $this->is_init ) { $_SESSION[ $_session_key ] = $_SESSION[ $_session_parent_key ]; }
		$this->_filter = $_SESSION[ $_session_key ];
		$this->_filter_params = $this->_class_admin_products->_filter_params;
	}

	protected function _api_upload() {
		if( empty( $_FILES[ 'file' ] ) ) {
			header( 'Status: 503 Service Unavailable' );
			die( 'Service Unavailable' );
		}
		var_dump( $_FILES, $_POST, $_GET );
		exit;
	}


	// api call
	protected function _reject() {
		header( 'Status: 503 Service Unavailable' );
		die( 'Service Unavailable' );
	}

	protected function _firewall( $class = null, $class_path = null, $method = null, $options = array() ) {
		if( $class && $class_path ) {
			$_class  = _class_safe( $class, $class_path );
		} else {
			$_class = $this;
		}
		empty( $method ) && $method = $_GET[ 'api' ];
		$_method = '_api_' . $method;
		$_status = method_exists( $_class, $_method );
		if( !$_status ) { $this->_reject(); }
		return( $_class->$_method( $options ) );
	}

	protected function _call( $class = null, $class_path = null, $method = null, $options = array() ) {
		main()->NO_GRAPHICS = true;
		$result = $this->_firewall( $class, $class_path, $method, $options );
		$json = json_encode( $result );
		$response = &$json;
		// check jsonp
		$type = 'json';
		if( isset( $_GET[ 'callback' ] ) ) {
			$jsonp_callback = $_GET[ 'callback' ];
			$response = '/**/ ' . $jsonp_callback . '(' . $json . ');';
			$type = 'javascript';
		}
		header( "Content-Type: application/$type; charset=UTF-8" );
		echo( $response );
		// without debug info
		exit( 0 );
	}

	function import2() {
		if( !empty( $_GET[ 'api' ] ) ) { return( $this->_call() ); }
		$data   = $this->_data();
		$form   = $this->_form( $data );
		return( $form );
	}

	function _data() {
		$_sub_action = array(
			'0'      => '- не выбрано -',
			'load'   => 'загрузить',
			'import' => 'импортировать',
			'delete' => 'удалить',
		);
			$sub_action = $_POST[ 'sub_action' ];
				$is_sub_action = $sub_action !== '0' && isset( $_sub_action[ $sub_action ] );
		// -----
		$_supplier = _class('manage_shop')->_suppliers_for_select;
			$supplier = (int)$_POST[ 'supplier' ];
				$is_supplier = $supplier != 0 && isset( $_supplier[ $supplier ] );
		// -----
		// update
		$apply   = $_POST[ 'apply'   ];
		$confirm = $_POST[ 'confirm' ];
		$is_action  = $this->is_post && $is_sub_action && $is_supplier && isset( $apply ) ? true : false;
		$is_update  = $is_action &&  isset( $confirm ) ? true : false;
		$no_confirm = $is_action && !isset( $confirm ) ? true : false;
		if( $is_update ) {
			if( $sub_action == 'add' ) {
			} elseif( $sub_action == 'delete' ) {
			}
			if( $sub_action_count ) {
				common()->message_success( 'Операция выполнена успешно.' );
				_class( '_shop_region', 'modules/shop/' )->_cache_refresh();
			} else {
				common()->message_warning( 'Данная операция выполнена ранее.' );
			}
			$sub_action = null;
			$region     = null;
		}
		if( $no_confirm ) {
			common()->message_warning( 'Требуется подтверждение.' );
		}
		// -----
		$result = array(
			'_ng_controller' => 'import2',
			'_sub_action'    => $_sub_action,
				'sub_action'     => $sub_action,
			'_supplier'      => $_supplier,
				'supplier'       => $supplier,
		);
		return( $result );
	}

	function _form( $data ) {
		// prepare form
		$data = (array)$data;
		// prepare ng-app
		$_ng_controller = 'ctrl.import2';
		$data[ '_ng_controller' ] = $_ng_controller;
		$data[ '_url_upload' ] = url_admin( '//manage_shop/import2/&api=upload' );
		$_form_tpl = tpl()->parse( 'manage_shop/import2__form', $data );
		// create form
		$_form = form( $data, array( 'ng-controller' => $_ng_controller ) )
			->select2_box( array(
				'desc'     => 'Действие',
				'name'     => 'sub_action',
				'values'   => $data[ '_sub_action' ],
			))
			->select2_box( array(
				'desc'     => 'Поставщик',
				'name'     => 'supplier',
				'values'   => $data[ '_supplier' ],
			))
			->fieldset_start()
				->container( $_form_tpl, 'Данные' )
			->fieldset_end()
		// $link_back = './?object=manage_shop&action=products';
		// $_form = form( $data )
			// ->row_start( array( 'desc' => 'Всего выбрано' ) )
				// ->info( 'total' )
				// ->link( 'Back', $link_back , array( 'title' => 'Вернуться в к фильтру продуктов', 'icon' => 'fa fa-arrow-circle-left' ))
			// ->row_end()
			// ->select2_box( array(
				// 'desc'     => 'Действие',
				// 'name'     => 'sub_action',
				// 'values'   => $data[ '_sub_action' ],
			// ))
			// ->select2_box( array(
				// 'desc'     => 'Регион',
				// 'name'     => 'region',
				// 'multiple' => true,
				// 'values'   => $data[ '_region' ],
			// ))
			// ->row_start( array( 'desc' => '' ) )
				// ->submit( 'apply', 'Выполнить' )
				// ->check_box( 'confirm', false, array( 'desc' => 'подтверждение', 'no_label' => true ) )
			// ->row_end()
		;
		$_form_ctrl = tpl()->parse( 'manage_shop/import2__ctrl', $data );
		return( $_form_ctrl . $_form );
	}

}
