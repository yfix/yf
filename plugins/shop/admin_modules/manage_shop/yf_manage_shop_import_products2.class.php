<?php

class yf_manage_shop_import_products2 {

	private $_filter        = false;
	private $_filter_params = false;

	private $_instance             = false;
	private $_class_admin_products = false;

	public $upload_path            = null;
	public $upload_list            = null;
	public $upload_list__file_name = null;
	public $upload_list__field     = array(
		'id',
		'file_name',
		'file_size',
		'time',
		'status',
	);
	public $upload_status          = array(
		'upload' => 'загружен',
		'import' => 'импортирован',
	);

	function _init() {
		$this->_class_admin_products = _class( 'manage_shop_products', 'admin_modules/manage_shop/' );
		$this->is_post = input()->is_post();
		$this->is_init = (bool)input()->get( 'init' );
		$this->upload_path = PROJECT_PATH . 'uploads/price/';
		$this->upload_list__file_name = $this->upload_path . 'list.csv';
		$this->_load_upload_list();
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

	protected function _load_upload_list() {
		$upload_list__file_name = $this->upload_list__file_name;
		$upload_list__field     = $this->upload_list__field;
		$upload_list            = &$this->upload_list;
		$data = array();
		if( is_readable( $upload_list__file_name ) && ( $file = fopen( $upload_list__file_name, 'r' ) ) !== FALSE ) {
			while( ( $item = fgetcsv( $file, 1000, ';' ) ) !== FALSE ) {
				$id = $item[ 0 ];
				if( empty( $id ) ) { continue; }
				$_data = array();
				foreach( $upload_list__field as $idx => $field ) {
					$_data[ $field ] = $item[ $idx ];
				}
				$data[ $id ] = $_data;
			}
			fclose( $file );
			/* uasort( $data, function( $a, $b ) {
				$_a = (int)$a[ 'time' ];
				$_b = (int)$b[ 'time' ];
				if( $_a == $_b )     { $result =  0; }
				elseif( $_a >  $_b ) { $result =  1; }
				else                 { $result = -1; }
				return( $result );
			}); */
			$upload_list = $data;
		}
	}

	protected function _save_upload_list( $data ) {
		$upload_list__file_name = $this->upload_list__file_name;
		$upload_list__field     = $this->upload_list__field;
		$upload_list            = &$this->upload_list;
		// add item
		if( !empty( $data  ) ) {
			if( !empty( $data[ 0 ] ) && empty( $data[ 'id' ] ) ) {
				$id = $data[ 0 ];
				$item = array();
				foreach( $upload_list__field as $idx => $field ) {
					$item[ $field ] = $data[ $idx ];
				}
				$data = $item;
			}
			if( !empty( $data[ 'id' ] ) ) {
				$upload_list[ $data[ 'id' ] ] = $data;
			}
		}
		// save items
		if( ( $file = fopen( $upload_list__file_name, 'w' ) ) !== FALSE ) {
			foreach( $upload_list as $id => $item ) {
				$data = array();
				foreach( $upload_list__field as $idx => $field ) {
					$data[] = $item[ $field ];
				}
				fputcsv( $file, $data, ';' );
			}
			fclose( $file );
			$result = true;
		} else {
			$result = false;
		}
		return( $result );
	}

	protected function _api_upload() {
		$file = $_FILES[ 'file' ];
		if( empty( $file ) || $file[ 'error' ] != UPLOAD_ERR_OK ) {
			header( 'Status: 503 Service Unavailable' );
			die( 'Service Unavailable' );
		} else {
			$upload_path = $this->upload_path;
			$name = $file[ 'name' ];
			$time = time();
			$id   = md5( $name . $time );
			$to   = $upload_path . $id;
			$from = $file[ 'tmp_name' ];
			if( move_uploaded_file( $from, $to) ) {
				$data = array(
					$id,
					$file[ 'name' ],
					$file[ 'size' ],
					$time,
					'upload',
				);
				$result = $this->_save_upload_list( $data );
				if( !$result ) {
					header( 'Status: 503 Service Unavailable' );
					die( 'save upload file data error' );
				}
				$result = array(
					'data' => $this->_data_ng(),
					'action' => array(
						'upload' => array(
							'status'         => true,
							'status_message' => 'файл загружен',
						),
					),
				);
			} else {
				header( 'Status: 503 Service Unavailable' );
				die( 'save upload file error' );
			}
		}
		return( $result );
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
		$json = json_encode( $result, JSON_NUMERIC_CHECK );
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

	function _data_ng( $json = false ) {
		$_upload_list   = $this->upload_list;
		$_upload_status = $this->upload_status;
		$result = array(
			'_upload_status' => $_upload_status,
			'_upload_list'   => $_upload_list,
		);
		if( $json ) {
			$result = json_encode( $result, JSON_NUMERIC_CHECK );
		}
		return( $result );
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
		// prepare ng-app
		$_ng_controller  = 'ctrl.import2';
		$_api_url_upload = url_admin( '//manage_shop/import2/&api=upload' );
		// -----
		$_ng_data = $this->_data_ng( true );
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
			'_api_url_upload' => $_api_url_upload,
			'_ng_controller'  => $_ng_controller,
			'_ng_data'        => $_ng_data,
			'_sub_action'     => $_sub_action,
			'_supplier'       => $_supplier,
				'sub_action' => $sub_action,
				'supplier'   => $supplier,
		);
		return( $result );
	}

	function _form( $data ) {
		// prepare form
		$data = (array)$data;
		$_form_tpl = tpl()->parse( 'manage_shop/import2__form', $data );
		// create form
		$_form = form( $data, array( 'ng-controller' => $data[ '_ng_controller' ] ) )
			->fieldset_start()
				->container( $_form_tpl, 'Загрузка' )
			->fieldset_end()
			// ->select2_box( array(
				// 'desc'     => 'Действие',
				// 'name'     => 'sub_action',
				// 'values'   => $data[ '_sub_action' ],
			// ))
			// ->select2_box( array(
				// 'desc'     => 'Поставщик',
				// 'name'     => 'supplier',
				// 'values'   => $data[ '_supplier' ],
			// ))
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
