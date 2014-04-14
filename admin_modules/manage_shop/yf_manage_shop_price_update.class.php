<?php

class yf_manage_shop_price_update {

	private $_filter      = false;

	private $_instance    = false;
	private $_class_price = false;

	function _init() {
		$this->_class_price = _class( '_shop_price', 'modules/shop/' );
		$this->is_post = main()->is_post();
		$this->is_init = (int)main()->_get( 'init' );
		// get filter
		$_object             = main()->_get( 'object' );
		$_action             = main()->_get( 'action' );
		$_action_parent      = main()->_get( 'filter' );
		$_session_parent_key = $_object . '__' . $_action_parent;
		$_session_key        = $_object . '__' . $_action;
		if( $this->is_init ) { $_SESSION[ $_session_key ] = $_SESSION[ $_session_parent_key ]; }
		$this->filter = $_SESSION[ $_session_key ];
	}

	function products_price_update() {
		var_dump( $this->filter );
		exit;
		// init sql
		$table = db( 'shop_products' );
		$fields = array(
			'price_raw',
			'price',
			'old_price',
		);
		$sql_fields = array();
		$sql_where  = array();
		// prepare supplier
		$supplier = (int)module( 'manage_shop' )->SUPPLIER_ID;
		if( $supplier > 0 ) {
			$sql_where[] = 'supplier_id = ' . $supplier;
		}
		// prepare sql fields
		$sql_fields[] = 'name';
		$sql_fields[] = $fields;
		// compile sql chunk
		if( !empty( $sql_fields ) ) { $sql_fields = implode( ', ', $sql_fields ); }
		if( !empty( $sql_where  ) ) { $sql_where  = implode( ', ', $sql_where  ); }
		// compile sql
		$sql = sprintf( '
			SELECT %s FROM %s WHERE %s
			'
			, $sql_fields
			, $sql_where
		);
		var_dump( $filters );
		exit;
		list($filter_sql,$order_sql) = _class('table2_filter', 'classes/table2/')->_filter_sql_prepare($filter_arr, $this->_filter_params, $sql);
		if ($filter_sql || $order_sql) {
			$sql .= ' WHERE 1 '.$filter_sql;
			if ($order_sql) {
				$sql .= ' '.$order_sql;
			}
		}
var_dump( $sql );
exit;
	}

}
