<?php

class yf_manage_shop_region {

	private $_table = array(
		'table' => 'shop_regions',
		'fields' => array(
			'active',
			'value',
		),
		'no_escape' => true,
	);

	private $_instance = false;
	private $_class_region        = false;
	private $_class_admin_methods = false;

	function _init() {
		$this->_class_region        = _class( '_shop_region', 'modules/shop/' );
		$this->_class_admin_methods = _class( 'admin_methods' );
		// actions urls
		$_object = main()->_get( 'object' );
		$_module = 'region';
		$_uri_object = "./?object=$_object";
		$_uri_action = "$_uri_object&action=$_module";

		$this->_uri = array(
			'show'   => $_uri_action,
			'add'    => $_uri_action . '_add',
			'edit'   => $_uri_action . '_edit'   . '&id=%d',
			'delete' => $_uri_action . '_delete' . '&id=%d',
			'active' => $_uri_action . '_active' . '&id=%d',
		);
		$this->_table[ 'back_link' ] = $this->_uri[ 'show' ];
		$_instance = $this; $this->_instance = $_instance;
		$this->_table[ 'on_after_update' ] = function( &$fields ) use( $_instance ) {
			return( $_instance->_on_after_update( $fields ) );
		};
	}

	function region_add() {
		$replace = _class( 'admin_methods' )->add( $this->_table );
		return( $this->_form( $replace ) );
	}

	function region_edit() {
		$replace = _class( 'admin_methods' )->edit( $this->_table );
		return( $this->_form( $replace ) );
	}

	function region_delete() {
		return( _class( 'admin_methods' )->delete( $this->_table ) );
	}

	function region_active() {
		return( _class( 'admin_methods' )->active( $this->_table ) );
	}

	function _on_after_update( &$fields ) {
		$_class_region = $this->_class_region;
		$_class_region->_cache_refresh();
	}

	function region() {
		$session_key = $_GET[ 'object' ] . '__' . $_GET[ 'action' ];
		$filter = array(
			'filter'        => $_SESSION[ $session_key ],
			'filter_params' => array(
				'id'    => 'in',
				'value' => 'like'
			),
			// // 'hide_empty'    => 1,
		);

		$table = table( 'SELECT * FROM ' . db( 'shop_regions' ), $filter )
			->text( 'id',    'Номер'    )
			->text( 'value', 'Название' )
			->btn_edit(   '', $this->_uri[ 'edit'   ], array( 'no_ajax' => 1 ) )
			->btn_delete( '', $this->_uri[ 'delete' ] )
			->btn_active( '', $this->_uri[ 'active' ] )
			->footer_add( '', $this->_uri[ 'add'    ], array( 'no_ajax' => 1 ) );
		;
		return( $table );
	}

	function _form( $replace ) {
		$replace = (array)$replace;
		$_form = form( $replace )
			->info( 'id', 'Номер' )
			->text( 'value', ' Название' )
			->active_box('active')
			->save_and_back();
		return( $_form );
	}

}
