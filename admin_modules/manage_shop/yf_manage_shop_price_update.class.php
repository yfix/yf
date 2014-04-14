<?php

class yf_manage_shop_price_update {

	private $_fields_show = array(
		'name',
		'price_raw',
		'price',
		'old_price',
	);
	private $_fields_update = array(
		'price_raw' => 'price_raw',
		'price'     => 'price',
		'old_price' => 'old_price',
	);
	private $_filter        = false;
	private $_filter_params = false;

	private $_instance             = false;
	private $_class_price          = false;
	private $_class_admin_products = false;

	function _init() {
		$this->_class_price          = _class( '_shop_price',          'modules/shop/'              );
		$this->_class_admin_products = _class( 'manage_shop_products', 'admin_modules/manage_shop/' );
		$this->is_post = main()->is_post();
		$this->is_init = (int)main()->_get( 'init' );
		// get filter
		$_object             = main()->_get( 'object' );
		$_action             = main()->_get( 'action' );
		$_action_parent      = main()->_get( 'filter' );
		$_session_parent_key = $_object . '__' . $_action_parent;
		$_session_key        = $_object . '__' . $_action;
		if( $this->is_init ) { $_SESSION[ $_session_key ] = $_SESSION[ $_session_parent_key ]; }
		$this->_filter = $_SESSION[ $_session_key ];
		$this->_filter_params = $this->_class_admin_products->_filter_params;
	}

	function products_price_update() {
		$data   = $this->_data();
		$form   = $this->_form( $data );
		return( $form );
	}

	function _form( $data ) {
		$_class_price = $this->_class_price;
		list( $total, $preview ) = $data;
		$replace = array(
			'_all'  => $total,
			'percent' => $_class_price->_number_format( $_POST[ 'percent' ] ),
		);
		$to_field = $this->_fields_update;
		// create form
		$link_back = './?object=manage_shop&action=products';
		$_form = form( $replace )
			->row_start( array( 'desc' => 'Всего выбрано' ) )
				->info( '_all' )
				->link( 'Back', $link_back , array( 'title' => 'Вернуться в к фильтру продуктов', 'icon' => 'fa fa-arrow-circle-left' ))
			->row_end()
			->number( 'percent', 'Процент, +/-' )
			->select_box( 'to_field', $to_field, array(
				'selected'  => $_POST[ 'to_field' ],
				'translate' => true,
				'desc'      => 'Приминть к полю',
				'tip'       => 'цена берется из поля "'. t( 'price_raw' ) . '" и применяется к данному полю' ,
			))
			->submit( 'preview', 'Предпросмотр' )
			->submit( 'apply', 'Выполнить' )
			->check_box( 'confirm', false, array( 'desc' => 'подтверждение', 'no_label' => true ) )
		;
		return( $_form . $preview );
	}

	function _data() {
		$_class_price = $this->_class_price;
		// init sql
		$sql_table   = db( 'shop_products' );
		$sql_table_t = $sql_table . '_tmp';
		$_fields = $this->_fields_show;
		$fields = array(); $sql_fields = '*';
		$where  = array(); $sql_where = '';
		$order  = array(); $sql_order = '';
		// prepare sql fields
		$fields[] = implode( ', ', $_fields );
		// prepare supplier
		$supplier = (int)module( 'manage_shop' )->SUPPLIER_ID;
		if( $supplier > 0 ) {
			$where[] = 'supplier_id = ' . $supplier;
		}
		// prepare filter
		list( $_where, $_order ) = _class('table2_filter', 'classes/table2/')->_filter_sql_prepare( $this->_filter, $this->_filter_params );
		if( !empty( $_where ) ) { $where[] = '1' . $_where; }
		if( !empty( $_order ) ) { $order[] = $_order; }
		// compile sql chunk
		if( !empty( $fields ) ) { $sql_fields = implode( ', ', $fields ); }
		if( !empty( $where  ) ) { $sql_where  = 'WHERE '    . implode( ', ', $where  ); }
		if( !empty( $order  ) ) { $sql_order  = 'ORDER BY ' . implode( ', ', $order  ); }
		// compile sql
		$sql = sprintf( 'SELECT %s FROM %s as p %s %s'
			, $sql_fields
			, $sql_table
			, $sql_where
			, $sql_order
		);
		$sql_filter = sprintf( 'SELECT * FROM %s as p %s %s'
			, $sql_table
			, $sql_where
			, $sql_order
		);
		$sql_count = sprintf( 'SELECT COUNT(*) FROM %s as p %s %s'
			, $sql_table
			, $sql_where
			, $sql_order
		);
		$count = db()->get_one( $sql_count );
		// build temp data
		// prepare percent
		$percent = $_class_price->_number_float( $_POST[ 'percent' ] );
		$percent = 1 + $percent / 100;
		$percent = $_class_price->_number_mysql( $percent, 4 );
		// prepare to field
		$_fields  = $this->_fields_update;
		$to_field = $_POST[ 'to_field' ];
		// to field error
		if( !empty( $to_field ) && !isset( $_fields[ $to_field ] ) ) { return( js_redirect( './', true, 'error to field' ) ); }
		$apply   = $_POST[ 'apply'   ];
		$confirm = $_POST[ 'confirm' ];
		$is_update = isset( $_fields[ $to_field ] ) && isset( $apply ) && isset( $confirm ) ? true : false;
		$to_field = $to_field ?: $_fields[ 'price_raw' ];
		$sql_price_update = "$to_field = ( IF( price_raw > 0, price_raw, price ) * $percent )";
		$css_field[ $to_field ] = 'text-success';
		$limit = 5;
		// preview
		// db_query( "DROP TABLE IF EXISTS $sql_table_t" );
		db_query( "CREATE TEMPORARY TABLE $sql_table_t LIKE $sql_table" );
		db_query( "INSERT INTO $sql_table_t $sql_filter LIMIT $limit" );
		db_query( "UPDATE $sql_table_t SET $sql_price_update LIMIT $limit" );
		$result = db_get_all( "SELECT $sql_fields FROM $sql_table_t LIMIT $limit" );
		$result_t = table( $result )
			->text( 'name' )
			->text( 'price_raw', array( 'class' => $css_field[ 'price_raw' ] ) )
			->text( 'price',     array( 'class' => $css_field[ 'price'     ] ) )
			->text( 'old_price', array( 'class' => $css_field[ 'old_price' ] ) )
		;
		$result_t = _class('html')->panel( array( 'title' => 'Предпросмотр', 'body' => $result_t ) );
		$result = array( $count, $result_t );
		// apply
		if( $is_update ) {
			db_query( "UPDATE $sql_table as p SET $sql_price_update $sql_where" );
		}
		return( $result );
	}


}
