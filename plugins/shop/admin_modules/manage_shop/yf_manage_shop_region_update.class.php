<?php

class yf_manage_shop_region_update {

	private $_filter        = false;
	private $_filter_params = false;

	private $_instance             = false;
	private $_class_admin_products = false;

	function _init() {
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
		$_sub_action = array(
			'0'      => '- не выбрано -',
			'add'    => 'добавить',
			'delete' => 'удалить',
			'clean'  => 'очистить',
		);
			$sub_action = $_POST[ 'sub_action' ];
				$is_sub_action = $sub_action !== '0' && isset( $_sub_action[ $sub_action ] );
		// -----
		$_region = _class( '_shop_region', 'modules/shop/' )->_get_list();
		// array_unshift( $region, '- регион не выбран -' );
			if( $sub_action == 'clean' ) {
				$is_region = true;
			} else {
				$region = array_values( (array)$_POST[ 'region' ] );
				$is_region = false;
				if( !empty( $region ) ) {
					$region = array_combine( $region, $region );
						$count = 0;
						foreach( $region as $id ) {
							if( $id === '0' || !isset( $_region[ $id ] ) ) { break; }
							$count++;
						}
						$count == count( $region ) && $is_region = true;
				}
			}
		// init sql
		$sql_table = db( 'shop_products' );
		$sql_table_action = 'shop_product_to_region';
		$where  = array(); $sql_where = '';
		$order  = array(); $sql_order = '';
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
		$is_action  = $this->is_post && $is_sub_action && $is_region && isset( $apply ) ? true : false;
		$is_update  = $is_action &&  isset( $confirm ) ? true : false;
		$no_confirm = $is_action && !isset( $confirm ) ? true : false;
		if( $is_update ) {
			// prepare data
			$data             = array();
			$sub_action_count = null;
			$ids  = db()->get_2d( $sql_filter );
			// ----- add regions to products
			if( $sub_action == 'add' ) {
				foreach( $ids as $id ) {
					foreach( $region as $r_id ) {
						$data[] = array( 'product_id' => $id, 'region_id' => $r_id );
					}
				}
				db_query( 'START TRANSACTION' );
					db()->insert_on_duplicate_key_update( $sql_table_action, $data );
					$sub_action_count = db()->affected_rows();
				db_query( 'COMMIT' );
			// ----- delete regions to products
			} elseif( $sub_action == 'delete' ||  $sub_action == 'clean' ) {
				$sql_product_ids = array( 'product_id', 'in', $ids    );
				if( $sub_action == 'clean' ) {
					$data = array( '__args__' => array(
						$sql_product_ids,
					));
				} else {
					$sql_region_ids  = array( 'region_id',  'in', $region );
					$data = array( '__args__' => array(
						$sql_product_ids,
						'and',
						$sql_region_ids,
					));
				}
				db_query( 'START TRANSACTION' );
					db()->delete( $sql_table_action, $data );
					$sub_action_count = db()->affected_rows();
				db_query( 'COMMIT' );
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
			'total'       => $total,
			'_sub_action' => $_sub_action,
				'sub_action' => $sub_action,
			'_region'     => $_region,
				'region'     => $region,
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

}
