<?php

class yf_manage_shop_region_update {

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
		// $this->_class_price          = _class( '_shop_price',          'modules/shop/'              );
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

	function products_region_update() {
		$data   = $this->_data();
		$form   = $this->_form( $data );
		return( $form );
	}

	function _data() {
		$_region = _class( '_shop_region', 'modules/shop/' )->_get_list();
		// array_unshift( $region, '- регион не выбран -' );
			$region = array_values( (array)$_POST[ 'region' ] );
			$region = array_combine( $region, $region );
				$is_region = true;
				foreach( $region as $id ) {
					if( $id === '0' || !isset( $_region[ $id ] ) ) {
						$is_region = false;
						break;
					}
				}
		// -----
		$_sub_action = array(
			'0'  => '- не выбрано -',
			'1'  => 'добавить',
			'-1' => 'удалить',
		);
			$sub_action = $_POST[ 'sub_action' ];
				$is_sub_action = $sub_action !== '0' && isset( $_sub_action[ $sub_action ] );
		// init sql
		$sql_table   = db( 'shop_products' );
		$sql_table_action = db( 'shop_product_to_region' );
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
		if( !empty( $order  ) ) { $sql_order  = implode( ', ', $order  ); }
		// compile sql
		$sql_filter = sprintf( 'SELECT p.id FROM %s as p %s %s'
			, $sql_table
			, $sql_where
			, $sql_order
		);
		$sql_count = sprintf( 'SELECT COUNT(*) FROM %s as p %s %s'
			, $sql_table
			, $sql_where
			, $sql_order
		);
		$total = (int)db()->get_one( $sql_count );
		// update
		$apply   = $_POST[ 'apply'   ];
		$confirm = $_POST[ 'confirm' ];
		$is_update = $this->is_post && $is_sub_action && $is_region && isset( $apply ) && isset( $confirm ) ? true : false;
		if( $is_update ) {
			// prepare data
			$data = array();
			$ids  = db()->get_2d( $sql_filter );
			foreach( $ids as $id ) {
				foreach( $region as $r_id ) {
					$data[] = array( 'product_id' => $id, 'region_id' => $r_id );
				}
			}
			db_query( 'START TRANSACTION' );
				// db()->insert_on_duplicate_key_update( $sql_table_action, $data );
			db_query( 'COMMIT' );
			$sql = db()->delete( $sql_table_action, array( array( 'product_id', 'in', $ids ) ), true );
			var_dump( $sql );
			exit;
		}
		// -----
		$result = array(
			'total'       => $total,
			'_region'     => $_region,
				'region'     => $region,
			'_sub_action' => $_sub_action,
				'sub_action' => $sub_action,
		);
		return( $result );
	}

	function _form( $data ) {
		// create form
		$link_back = './?object=manage_shop&action=products';
		$_form = form( $data )
			->row_start( array( 'desc' => 'Всего выбрано' ) )
				->info( 'total' )
				->link( 'Back', $link_back , array( 'title' => 'Вернуться в к фильтру продуктов', 'icon' => 'fa fa-arrow-circle-left' ))
			->row_end()
			->select2_box( array(
				'desc'     => 'Действие',
				'name'     => 'sub_action',
				'values'   => $data[ '_sub_action' ],
			))
			->select2_box( array(
				'desc'     => 'Регион',
				'name'     => 'region',
				'multiple' => true,
				'values'   => $data[ '_region' ],
			))
			->row_start( array( 'desc' => '' ) )
				->submit( 'apply', 'Выполнить' )
				->check_box( 'confirm', false, array( 'desc' => 'подтверждение', 'no_label' => true ) )
			->row_end()
		;
		return( $_form );
	}


// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
	function __init() {
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


	function __data() {
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
		if( !empty( $order  ) ) { $sql_order  = implode( ', ', $order  ); }
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
		$_percent = $_class_price->_number_float( $_POST[ 'percent' ] );
		$percent = 1 + $_percent / 100;
		$percent = $_class_price->_number_mysql( $percent, 4 );
		// prepare add
		$_add = $_class_price->_number_float( $_POST[ 'add' ] );
		$add  = $_class_price->_number_mysql( $_add   );
		// prepare to field
		$_fields  = $this->_fields_update;
		$to_field = $_POST[ 'to_field' ];
		// to field error
		if( !empty( $to_field ) && !isset( $_fields[ $to_field ] ) ) { return( js_redirect( './', true, 'error to field' ) ); }
		$apply   = $_POST[ 'apply'   ];
		$confirm = $_POST[ 'confirm' ];
		$is_update = isset( $_fields[ $to_field ] ) && isset( $apply ) && isset( $confirm ) ? true : false;
		$to_field = $to_field ?: $_fields[ 'price_raw' ];
		$sql_price_update = "$to_field = ( IF( price_raw > 0, price_raw, price ) * $percent + $add )";
		$css_field[ $to_field ] = 'text-success';
		$limit = 5;
		// preview
		// db_query( "DROP TABLE IF EXISTS $sql_table_t" );
		db_query( "CREATE TEMPORARY TABLE $sql_table_t LIKE $sql_table" );
		db_query( "INSERT INTO $sql_table_t $sql_filter LIMIT $limit" );
		db_query( "UPDATE $sql_table_t SET $sql_price_update LIMIT $limit" );
		$result = db_get_all( "SELECT $sql_fields FROM $sql_table_t as p $sql_order LIMIT $limit" );
		$result_t = table( $result, array( 'no_total' => true ) )
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
			$info = '';
			if( !empty( $this->_filter ) ) {
				foreach( $this->_filter as $key => $value ) {
					if( strlen( $value ) < 1 ) { continue; }
					$info .= "$key: $value; ";
				}
				$info = " ( $info )";
			}
			common()->admin_wall_add( array( "shop price update: percent = $_percent%; add = $_add" . $_class_price->CURRENCY . $info ) );
		}
		return( $result );
	}


}
