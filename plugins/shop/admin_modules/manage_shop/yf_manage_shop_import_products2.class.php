<?php

class yf_manage_shop_import_products2 {

	private $_filter        = false;
	private $_filter_params = false;

	private $_instance             = false;
	private $_class_admin_products = false;

	public $import_field = array(
		0                   => 'не использовать (0)',
		'id'                => 'идентификатор (id)',
		'name'              => 'название (name)',
		'price'             => 'цена (price)',
		'price_raw'         => 'себестоимость (price_raw)',
		'articul'           => 'артикул (articul)',
		'cat_id'            => 'категория: идентификатор (cat_id)',
		'category_name'     => 'категория: название (category_name)',
		'manufacturer_id'   => 'производитель: идентификатор (manufacturer_id)',
		'manufacturer_name' => 'производитель: название (manufacturer_name)',
		'supplier_id'       => 'поставщик: идентификатор (supplier_id)',
		'supplier_name'     => 'поставщик: название (supplier_name)',
	);
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
	// cache
	public $cache_products = array();

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
		$data = $this->_load_csv( $upload_list__file_name );
		$result = array();
		foreach( $data as $item ) {
			$id = $item[ 0 ];
			if( empty( $id ) ) { continue; }
			$_data = array();
			foreach( $upload_list__field as $idx => $field ) {
				$_data[ $field ] = $item[ $idx ];
			}
			$result[ $id ] = $_data;
		}
		$upload_list = $result;
		return( $result );
	}

	protected function _load_csv( $file_name ) {
		if( is_readable( $file_name ) && ( $file = fopen( $file_name, 'r' ) ) !== FALSE ) {
			$result = array();
			while( ( $item = fgetcsv( $file, 1000, ';' ) ) !== FALSE ) {
				$result[] = $item;
			}
			fclose( $file );
		} else {
			$result = false;
		}
		return( $result );
	}

	protected function _save_upload_list( $data = null ) {
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
		$result = $this->_save_csv( $upload_list__file_name, $upload_list );
		return( $result );
	}

	protected function _save_csv( $file_name, $data = null ) {
		if( is_array( $data ) && ( $file = fopen( $file_name, 'w' ) ) !== FALSE ) {
			foreach( $data as $id => $item ) {
				$data = array_values( $item );
				$result = fputcsv( $file, $data, ';' );
				if( false === $result ) { return( $result ); }
			}
			fclose( $file );
			$result = true;
		} else {
			$result = false;
		}
		return( $result );
	}

	protected function _load_json( $file_name ) {
		$result = FALSE;
		if( file_exists( $file_name ) && ( $file = @fopen( $file_name, 'r' ) ) !== FALSE ) {
			if( ( $data = @fread( $file, filesize( $file_name ) ) ) !== FALSE ) {
				$result = @json_decode( $data, true );
			}
			fclose( $file );
		}
		return( $result );
	}

	protected function _save_json( $file_name, $data = null ) {
		if( is_array( $data ) && ( $file = @fopen( $file_name, 'w' ) ) !== FALSE ) {
			$result = @fwrite( $file, @json_encode( $data, JSON_NUMERIC_CHECK ) );
			fclose( $file );
		} else {
			$result = false;
		}
		return( $result );
	}

	protected function _api_upload() {
		$file = $_FILES[ 'file' ];
		if( empty( $file ) || $file[ 'error' ] != UPLOAD_ERR_OK ) {
			$this->_reject( 'PHP: Entity Too Large', '500 Internal Server Error', 500 );
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
					$this->_reject( 'save upload file data error' );
				}
				$result = array(
					'data' => $this->_data_ng(),
					'action' => array(
						'status'         => true,
						'status_message' => 'файл загружен',
					),
				);
			} else {
				$this->_reject( 'save upload file error' );
			}
		}
		return( $result );
	}

	protected function _api_upload_list() {
		$sub_action = $_GET[ 'sub_action' ];
		switch( $sub_action ) {
			case 'remove_all':
				$result = $this->_upload_list__remove_all();
				break;
		}
		$result = array(
			'response'   => array(
				'data'   => $this->_data_ng(),
				'action' => $result,
			),
		);
		return( $result );
	}

	protected function _api_upload_item() {
		$sub_action = $_GET[ 'sub_action' ];
		if( $this->is_post ) {
			$post = json_decode( file_get_contents( 'php://input' ), true );
		}
		switch( $sub_action ) {
			case 'get':
				$id = $_REQUEST[ 'id' ];
				$result = $this->_upload_item__get( $id );
				$test = $this->_upload_item__import( $id, $result[ 'data' ][ 'fields' ] );
				$result[ 'data' ][ 'test' ] = $test;
				break;
			case 'import':
				$id = $post[ 'id' ];
				$import_fields = $post[ 'data' ];
				$result = $this->_upload_item__import( $id, $import_fields );
				break;
		}
		$result = array(
			'response'   => array(
				'data'   => $this->_data_ng(),
				'action' => $result,
			),
		);
		return( $result );
	}

	protected function _upload_list__remove_all() {
		$upload_path = $this->upload_path;
		$upload_list = &$this->upload_list;
		foreach( $upload_list as $id => $item ) {
			$file       = $upload_path . $id;
			$file_cache = $file . '.cache';
				file_exists( $file_cache ) && @unlink( $file_cache );
			if( file_exists( $file ) && false === @unlink( $file ) ) {
				$result = array(
					'status'         => false,
					'status_message' => 'ошибка при удалении файла: ' . $item[ 'file_name' ],
				);
				return( $result );
			}
			unset( $upload_list[ $id ] );
		}
		$this->_save_upload_list();
		$result = array(
			'status'         => true,
			'status_message' => 'все загруженные файлы удалены',
		);
		return( $result );
	}

	protected function _upload_item__get( $id ) {
		$result = array(
			'status' => false,
		);
		$upload_path = $this->upload_path;
		$upload_list = $this->upload_list;
		$file = $upload_path . $id;
		if( !empty( $upload_list[ $id ] ) && file_exists( $file ) ) {
			$items = $this->_file_parse( $file, $upload_list[ $id ] );
			$rows  = count( $items );
			$cols  = count( $items[ 0 ] );
			// load import options
			$upload_path = $this->upload_path;
			$file      = $upload_path . $id;
			$file_name = $file . '.import';
			$result    = $this->_load_json( $file_name );
			if( FALSE === $result ) {
				$fields = array();
				for( $i = 0; $i < $cols; $i++ ) {
					$fields[ $i ] = 0;
				}
			} else {
				$fields = $result;
			}
			$result = array(
				'data'   => array(
					'id'     => $id,
					'file'   => $upload_list[ $id ][ 'file_name' ],
					'rows'   => $rows,
					'cols'   => $cols,
					'items'  => $items,
					'fields' => $fields,
				),
				'status' => true,
			);
		}
		return( $result );
	}

	protected function _upload_item__import( $id, $import_fields ) {
		$upload_list = $this->upload_list;
		// item exists
		if( !isset( $upload_list[ $id ] ) ) {
			$result = array(
				'status_message' => 'импорт - данная операция невозможна, данный файл отсутствует',
				'status'         => false,
			);
			return( $result );
		}
		// save import options
		$upload_path   = $this->upload_path;
		$file          = $upload_path . $id;
		$file_name     = $file . '.import';
		$result    = $this->_save_json( $file_name, $import_fields );
		if( FALSE === $result ) {
			$result = array(
				'status_message' => 'импорт - невозможно сохранить параметры',
				'status'         => false,
			);
			return( $result );
		}
		// test import items
		$_upload_item__import_test = $this->_upload_item__import_test( $id, $import_fields );
		$result = array(
			'data'   => array(
				'id'           => $id,
				'_import_test' => $_upload_item__import_test,
			),
			'status' => false,
		);
		return( $result );
	}

	protected function _upload_item__import_test( $id, $import_fields ) {
		$upload_list = $this->upload_list;
		// load import data
		$upload_path = $this->upload_path;
		$file        = $upload_path . $id;
		$items = $this->_file_parse( $file, $upload_list[ $id ] );
		$import_fields_test = array();
		// get import fields
		foreach( $import_fields as $index => $field ) {
			if( empty( $field ) ) { continue; }
			$import_fields_test[ $index ] = $field;
		}
		$_import_field = $this->import_field;
		$result = array();
		foreach( $items as $index => $item ) {
			$valid          = true;
			$status         = true;
			$exists         = null;
			$status_message = array();
			$exists_message = array();
			$result[ $index ] = array(
				'fields'         => array(),
				'valid'          => $valid,
				'exists'         => $exists,
				'exists_message' => '',
				'status'         => $status,
				'status_message' => 'правильный формат',

			);
			foreach( $import_fields_test as $field_index => $field ) {
				$value = $item[ $field_index ];
				$test = $this->_field__test( $field, $value );
				if( $test === FALSE ) { continue; }
				$result[ $index ][ 'fields' ][ $field_index ] = $test;
				$status = $status && $test[ 'status' ];
				if( $status === FALSE ) {
					$status_message[] = $test[ 'status_message' ];
				}
				if( !is_null( $test[ 'exists' ] ) ) {
					if( is_null( $exists ) ) {
						$exists = $test[ 'exists' ];
					} elseif( $exists != $test[ 'exists' ] ) {
						// collision
						$exists = -1;
					}
					$exists_message[] =
						$_import_field[ $field ] . ' = ' . $value . ' - '
						. ( $test[ 'exists' ] ? 'существует': 'не существует' );
				}
			}
			$result[ $index ][ 'status' ] = $status;
			$result[ $index ][ 'exists' ] = $exists;
			!empty( $exists_message ) && ( $result[ $index ][ 'exists_message' ]
				= implode( '; ', $exists_message ) );
			!empty( $status_message ) && ( $result[ $index ][ 'status_message' ]
				= implode( '; ', $status_message ) );
		}
		return( $result );
	}

	protected function _field__test( $field, $value ) {
		$_class  = $this;
		$_method = '_field_test__' . $field;
		$_status = method_exists( $_class, $_method );
		if( !$_status ) { return( false ); }
		return( $_class->$_method( $value ) );
	}

	protected function _field_test__id( $value ) {
		$value  = (int)$value;
		$valid  = $value > 0;
		$exists = $this->_product_exists( $value );
		$status = $valid && $exists;
		$status_message = $status ? 'товар уже существует' : 'товар не существует';
		$result = array(
			'valid'          => $valid,
			'exists'         => $exists,
			'status'         => $status,
			'status_message' => $status_message,
		);
		return( $result );
	}

	protected function _field_test__price( $value ) {
		$value  = (float)$value;
		$valid  = $value > 0;
		$exists = null;
		$status = $valid;
		$status_message = $status ? 'цена больше нуля' : 'цена должна быть больше нуля';
		$result = array(
			'valid'          => $valid,
			'exists'         => $exists,
			'status'         => $status,
			'status_message' => $status_message,
		);
		return( $result );
	}

	function _product_exists( $id ) {
		$products = &$this->cache_products;
		if( !isset( $products[ $id ] ) ) {
			$product = $this->_get_products( array(
				'where' => array(
					'id' => $id
				),
			));
			// cache
			$products[ $id ] = null;
			if( isset( $product[ $id ] ) ) {
				$products[ $id ] = $product[ $id ];
			}
		}
		$result = !is_null( $products[ $id ] );
		return( $result );
	}

	function _get_products( $options ) {
		$_      = $options;
		$_where = $_[ 'where' ];
		$_key   = isset( $_[ 'key' ] ) ? $_[ 'key' ] : 'id';
		// prepare where
		$where  = array();
		foreach( $_where as $field => $value ) {
			if( isset( $value ) ) {
				$value = (array)$value;
				$where[] = $field . ' IN( ' . implode( ', ', _es( $value ) ) . ' )';
			}
		}
		$sql_where = '';
		if( !empty( $where ) ) {
			$sql_where = 'WHERE ' . implode( ' AND ', $where );
		}
		$sql = sprintf(
			'SELECT * FROM %s %s'
			, db( 'shop_products' )
			, $sql_where
		);
		$result = db_get_all( $sql, $_key );
		return( $result );
	}

	protected function _file_parse__get_cache( $file_name, $item ) {
		$result = $this->_load_csv( $file_name . '.cache' );
		return( $result );
	}

	protected function _file_parse__set_cache( $file_name, $item, $data ) {
		$result = $this->_save_csv( $file_name . '.cache', $data );
		return( $result );
	}

	protected function _file_parse( $file_name, $item, $force = false ) {
		// cache
		if( !$force ) {
			$result = $this->_file_parse__get_cache( $file_name, $item );
			if( false !== $result ) { return( $result ); }
		}
		// parse
		ini_set( 'memory_limit', '1024M' );
		$type           = pathinfo( $file_name, PATHINFO_EXTENSION );
		$format_default = ( $type == 'xls' ? 'Excel5' : 'Excel2007' );
		// init Excel reader
		if( file_exists( YF_PATH.'libs/phpexcel/PHPExcel.php' ) ) {
			require_once( YF_PATH.'libs/phpexcel/PHPExcel.php' );
		} else {
			require_once( INCLUDE_PATH.'libs/phpexcel/PHPExcel.php' );
		}
		// parse file
		$format = PHPExcel_IOFactory::identify( $file_name );
		$format = $format ?: $format_default;
		$reader = PHPExcel_IOFactory::createReader( $format );
		$reader->setReadDataOnly( true );
		// $reader->setLoadAllSheets();
		// for csv
		$format == 'CSV' && $reader->setDelimiter( ';' );
			// setEnclosure() | default is "
			// setLineEnding() | default is PHP_EOL
			// setInputEncoding() | default is UTF-8
		try {
			$excel = $reader->load( $file_name );
			// $nullValue = null, $calculateFormulas = true, $formatData = true, $returnCellRef = false
			$result = $excel->getActiveSheet()->toArray( null, false, false, false );
		} catch ( Exception $e ) {
			$result = false;
		}
		// free memory
		unset( $excel, $reader );
		// cache
		$this->_file_parse__set_cache( $file_name, $item, $result );
		return( $result );
	}

	// api call
	protected function _reject( $message = 'Service Unavailable', $header = 'Status: 503 Service Unavailable', $code = 503 ) {
		http_response_code( $code );
		header( $header );
		die( $message );
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
		$_import_field  = $this->import_field;
		$result = array(
			'_upload_status' => $_upload_status,
			'_upload_list'   => $_upload_list,
			'_import_field'  => $_import_field,
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
		$_ng_controller       = 'ctrl.import2';
		$_api_url_upload      = url_admin( '//manage_shop/import2/&api=upload' );
		$_api_url_upload_list = url_admin( '//manage_shop/import2/&api=upload_list' );
		$_api_url_upload_item = url_admin( '//manage_shop/import2/&api=upload_item' );
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
			'_api_url_upload'      => $_api_url_upload,
			'_api_url_upload_list' => $_api_url_upload_list,
			'_api_url_upload_item' => $_api_url_upload_item,
			'_ng_controller'       => $_ng_controller,
			'_ng_data'             => $_ng_data,
			'_sub_action'          => $_sub_action,
			'_supplier'            => $_supplier,
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
		$_form = $_form_tpl;
		// $_form = form( $data, array( 'ng-controller' => $data[ '_ng_controller' ] ) )
			// ->fieldset_start()
				// ->container( $_form_tpl, 'Загрузка' )
			// ->fieldset_end()
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
