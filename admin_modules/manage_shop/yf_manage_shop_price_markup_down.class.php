<?php

class yf_manage_shop_price_markup_down {

	private $_table = array(
		'table' => 'shop_price_markup_down',
		'fields' => array(
			'active',
			'type',
			'value',
			'description',
			'time_from',
			'time_to',
			'conditions',
		),
	);

	private $_instance    = false;
	private $_class_price = false;

	function _init() {
		$this->_class_price = _class( '_shop_price', 'modules/shop/' );
		$_object = main()->_get( 'object' );
		$_module = 'price_markup_down';
		$_uri_object = "./?object=$_object";
		$_uri_action = "$_uri_object&action=";

		$this->_uri = array(
			'show'   => $_uri_action . 'price_markup_down',
			'add'    => $_uri_action . 'price_markup_down_add',
			'edit'   => $_uri_action . 'price_markup_down_edit'   . '&id=%d',
			'delete' => $_uri_action . 'price_markup_down_delete' . '&id=%d',
			'active' => $_uri_action . 'price_markup_down_active' . '&id=%d',
		);
		$this->_table[ 'back_link' ] = $this->_uri[ 'show' ];
		$_instance = $this; $this->_instance = $_instance;
		$this->_table[ 'on_before_show' ] = function( &$fields ) use( $_instance ) {
			return( $_instance->_on_before_show( $fields ) );
		};
		$this->_table[ 'on_before_update' ] = function( &$fields ) use( $_instance ) {
			return( $_instance->_on_before_update( $fields ) );
		};
	}

	function price_markup_down() {
		$filter = array(
			// 'filter'        => $_SESSION[ $_GET[ 'object' ]. $_GET[ 'action' ] ],
			// 'filter_params' => array( 'description' => 'like' ),
			// // 'hide_empty'    => 1,
		);

		$table = table( 'SELECT * FROM ' . db( 'shop_price_markup_down' ), $filter )
			->text( 'description' )
			// ->text( 'value', 'Процент, +/-' )
			->btn_active( '', $this->_uri[ 'active' ] )
			->btn_edit(   '', $this->_uri[ 'edit'   ], array( 'no_ajax' => 1 ) )
			->btn_delete( '', $this->_uri[ 'delete'   ] )
			->footer_add( '', $this->_uri[ 'add'    ], array( 'no_ajax' => 1 ) );
		;
		return( $table );
	}

	function price_markup_down_active() {
		return _class( 'admin_methods' )->active( $this->_table );
	}

	function _select_box__type() {
		$_class_price = $this->_class_price;
		$types = $_class_price->types;
		$result = array();
		foreach( $types as $id => $item ) {
			$result[] = array(
				'id'    => (int)$id,
				'title' => $item[ 'description' ],
			);
		}
		return( $result );
	}

	function _form( $replace ) {
		$replace = (array)$replace;
		// prepare ng-app
		$_ng_controller = 'ctrl.price_markup_down.conditions';
		$replace[ '_ng_controller'      ] = $_ng_controller;
		$replace[ '_api_url_products'   ] = ADMIN_WEB_PATH.'?object=manage_shop&action=product_search_autocomplete';
		$replace[ '_api_url_categories' ] = ADMIN_WEB_PATH.'?object=manage_shop&action=category_search_autocomplete';
		$conditions = json_decode( $replace[ 'conditions' ], true );
		$replace[ '_categories' ] = $this->get_categories( $conditions[ 'category_id' ] ) ?: '[]';
		$replace[ '_products'   ] = $this->get_products( $conditions[ 'product_id' ] ) ?: '[]';
		$replace[ '_types'   ] = json_encode( $this->_select_box__type() ) ?: '[]';
;
		// prepare form
		$_form_tpl = tpl()->parse( 'manage_shop/price_markup_down__form', $replace );
		// create form
		$_form = form( $replace, array( 'ng-controller' => $_ng_controller ) )
			->number( 'value', 'Процент, +/-' )
			->text( 'description' )
			->datetime_select( 'time_from', 'Дата от', array( 'with_time' => 1 ) )
			->datetime_select( 'time_to',   'Дата до', array( 'with_time' => 1 ) )
			->fieldset_start()
				->container( $_form_tpl, 'Условие' )
			->fieldset_end()
			->active_box('active')
			->save_and_back();
		// form controller
		$_form_ctrl = tpl()->parse( 'manage_shop/price_markup_down__ctrl', $replace );
		return( $_form_ctrl . $_form );
	}

	function _on_before_show( &$fields ) {
		$_class_price = $this->_class_price;
		$conditions_json = $fields[ 'conditions' ];
		$fields[ 'conditions' ] = $conditions_json ?: '{}';
		$fields[ 'value'     ] = $_class_price->_number_from_mysql( $fields[ 'value' ] );
	}

	function _on_before_update( &$fields ) {
		$_class_price = $this->_class_price;
		$fields[ 'value'     ] = $_class_price->_number_mysql( $fields[ 'value' ] );
		$fields[ 'time_from' ] = $fields[ 'time_from' ] ? date( 'Y-m-d H:i', strtotime( $fields[ 'time_from' ] ) ) : null;
		$fields[ 'time_to'   ] = $fields[ 'time_to'   ] ? date( 'Y-m-d H:i', strtotime( $fields[ 'time_to'   ] ) ) : null;
	}

	function get_categories( $ids ) {
		if( empty( $ids ) ) { return( null ); }
		$sql_table  = db( 'sys_category_items' );
		$sql_cat_id = _class( 'cats' )->_get_cat_id_by_name( 'shop_cats' );
		$sql_ids   = implode( ',', (array)$ids );
		$sql = sprintf( 'SELECT id, name FROM %s WHERE cat_id = %u AND id IN( %s )'
			, $sql_table
			, $sql_cat_id
			, $sql_ids
		);
		$items = db()->get_all( $sql );
		if( empty( $items ) ) { return false; }
		$result = array();
		foreach( $items as $i ){
			$id = (int)$i[ 'id' ];
			$result[] = array(
				'id'   => $id,
				'text' => "[$id] ${i[name]}",
			);
		}
		return( json_encode( $result ) );
	}

	function get_products( $ids ) {
		if( empty( $ids ) ) { return( null ); }
		$sql_table  = db( 'shop_products' );
		$sql_ids   = implode( ',', (array)$ids );
		$sql = sprintf( 'SELECT id, name FROM %s WHERE id IN( %s )'
			, $sql_table
			, $sql_ids
		);
		$items = db()->get_all( $sql );
		if( empty( $items ) ) { return false; }
		$result = array();
		foreach( $items as $i ){
			$id = (int)$i[ 'id' ];
			$result[] = array(
				'id'   => $id,
				'text' => "[$id] ${i[name]}",
			);
		}
		return( json_encode( $result ) );
	}

	function price_markup_down_add() {
		$replace = _class( 'admin_methods' )->add( $this->_table );
		return( $this->_form( $replace ) );
	}

	function price_markup_down_edit() {
		$replace = _class( 'admin_methods' )->edit( $this->_table );
		return( $this->_form( $replace ) );
	}

	function price_markup_down_delete() {
		$replace = _class( 'admin_methods' )->delete( $this->_table );
		return( $this->_form( $replace ) );
	}

}
