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
			$result[ $id ] = $item[ 'description' ];
		}
		return( $result );
	}

	function _form( $replace ) {
		// prepare ng-app
		$_ng_controller = 'ctrl.price_markup_down.conditions';
		$replace[ '_ng_controller' ] = $_ng_controller;
		$replace[ '_api_url_products' ] = ADMIN_WEB_PATH.'?object=manage_shop&action=product_search_autocomplete';
		$replace[ '_api_url_categories' ] = ADMIN_WEB_PATH.'?object=manage_shop&action=category_search_autocomplete';
		// create form
		$_form = form( $replace, array( 'ng-controller' => $_ng_controller ) )
			->select_box( 'type', $this->_select_box__type() )
			->number( 'value', 'Процент, +/-' )
			->text( 'description' )
			->datetime_select( 'time_from', 'Дата от', array( 'with_time' => 1 ) )
			->datetime_select( 'time_to',   'Дата до', array( 'with_time' => 1 ) )
			->fieldset_start( 'Дополнительные условие' )
				->hidden( 'conditions', array(
					'ng-model' => 'conditions'
				))
				->input( '_product_ids', 'список продуктов', array(
					'ng-model' => 'conditions.product_id',
					'ng-list'  => 'true',
				))
				->button( '_add_product_id', 'добавить', array(
					'ng-click' => 'add_product()',
				))
				->input( '_categories_ids', 'список категорий', array(
					'ng-model' => 'conditions.category_id',
					'ng-list'  => 'true',
				))
				->button( '_add_category_id', 'добавить', array(
					'ng-click' => 'add_category()',
				))
				->input( '_search', 'добавить', array(
					'data-bs-typeahead'   => 'true',
					'ng-model'            => 'term',
					'ng-options'          => 'data.id as data.text for data in search( $viewValue )',
					'placeholder'         => 'введите фразу',
				))
			->fieldset_end()
			->active_box('active')
			->save_and_back();
		$_form_ctrl = tpl()->parse( 'manage_shop/price_markup_down', $replace );
		return( $_form . $_form_ctrl );
	}

	function _on_before_show( &$fields ) {
		$fields[ 'conditions' ] = $fields[ 'conditions' ] ?: '{}';
	}

	function _on_before_update( &$fields ) {
		$fields[ 'time_from' ] = $fields[ 'time_from' ] ? date( 'Y-m-d H:i', strtotime( $fields[ 'time_from' ] ) ) : null;
		$fields[ 'time_to'   ] = $fields[ 'time_to'   ] ? date( 'Y-m-d H:i', strtotime( $fields[ 'time_to'   ] ) ) : null;
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
