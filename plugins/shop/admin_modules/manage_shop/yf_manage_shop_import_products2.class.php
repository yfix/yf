<?php

/*
	# addon select: supplier_id

	# velika_kishenya
	# olis
	# start trade
	# fortuna
	# epicentr
	# talisman
	# zakaz_ua
	# oblik
	# dobra_hata
	# promozp
	# electrolux
	# ekanevidal
	# zoobonus
	# svitcom

	supplier_id, articul
		new by articul
			name, url( by name ),
			articul,
			price, price_raw,
			description,
			origin_url,
			cat_id( by cat_name), supplier_id( by sup_name ), manufacturer_id( by man_name ),
			status=1, active=1
			add_date( by time )
		update
			price, price_raw, cat_id, update_date( by time )

	format_data($name,$articul,$price,$supplier_id,$price_raw = 0)

	# insert, if exists

	# update, if exists id
	id, name, price, etc
		by id
		by supplier_id, articul
			update any fields

	# rules
		price > 0 or price_raw > 0
		articul IS NOT NULL
		cat_name IS NOT NULL
		supplier_id MUST EXISTS IN DB
		manufacturer_id MUST EXISTS IN DB
			or add manufacturer( url by name )
*/

class yf_manage_shop_import_products2 {

	private $_filter        = false;
	private $_filter_params = false;

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

	public $import_action = array(
		'update' => 'обновление',
		'insert' => 'вставка',
	);
	public $import_action_default = 'update';
	public $import_rulues = array(
		'update' => array(
			'key' => array(
				array( 'id', ),
				array( 'supplier_id',   'articul', ),
				array( 'supplier_name', 'articul', ),
			),
		),
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
	public $supplier = null;
	// categories
	public $set_cat_name = 'shop_cats';
	public $set_cat_id   = null;

	// cache
	public $cache = array();

	// class
	private $_instance = false;

	private $_class_price          = false;
	private $_class_admin_products = false;

	function _init() {
		$this->_class_price          = _class( '_shop_price',          'modules/shop/'              );
		$this->_class_admin_products = _class( 'manage_shop_products', 'admin_modules/manage_shop/' );
		$this->is_post = input()->is_post();
		$this->is_init = (bool)input()->get( 'init' );
		$this->upload_path = PROJECT_PATH . 'uploads/price/';
		$this->upload_list__file_name = $this->upload_path . 'list.csv';
		$this->_load_upload_list();
		$supplier = db()->select( 'id', 'name' )->from( 'shop_suppliers' )->get_2d();
		$this->supplier = $supplier;
		// categories
		$this->set_cat_id = _class( 'cats' )->_get_cat_id_by_name( $this->set_cat_name );
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
				$result = $this->_upload_item__get( $id );
					$test = $this->_upload_item__import( $id, $result[ 'data' ][ 'fields' ] );
					$result[ 'data' ][ 'test' ] = $test;
				$result = array(
					'data' => $this->_data_ng(),
					'action' => array(
						'data'           => $result[ 'data' ],
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
				$test = $this->_upload_item__import( $result[ 'data' ][ 'option' ] );
				$result[ 'data' ][ 'test' ] = $test;
				break;
			case 'remove':
				$id = $post[ 'id' ];
				$result = $this->_upload_item__remove( $id );
				break;
			case 'import':
				$result = $this->_upload_item__import( $post[ 'option' ] );
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

	protected function _upload_list_item__remove( $id ) {
		$result      = false;
		$upload_list = &$this->upload_list;
		$upload_path = $this->upload_path;
		$file        = $upload_path . $id;
		$file_cache  = $file . '.cache';
		$file_import = $file . '.import';
			file_exists( $file_cache  ) && @unlink( $file_cache  );
			file_exists( $file_import ) && @unlink( $file_import );
		if( file_exists( $file ) && false === @unlink( $file ) ) {
			$result = false;
		} else {
			$result = true;
			unset( $upload_list[ $id ] );
		}
		return( $result );
	}

	protected function _upload_list__remove_all() {
		$upload_list = &$this->upload_list;
		foreach( $upload_list as $id => $item ) {
			if( !$this->_upload_list_item__remove( $id ) ) {
				$result = array(
					'status'         => false,
					'status_message' => 'ошибка при удалении файла: ' . $item[ 'file_name' ],
				);
				return( $result );
			}
		}
		$this->_save_upload_list();
		$result = array(
			'status'         => true,
			'status_message' => 'все загруженные файлы удалены',
		);
		return( $result );
	}

	protected function _option_default( &$option, $key, $cols, $default ) {
		if( empty( $option ) || empty( $key ) || empty( $cols ) ) { return( null ); }
		if( empty( $option[ $key ] ) ) {
			$value = array();
			for( $i = 0; $i < $cols; $i++ ) { $value[ $i ] = $default; }
			$option[ $key ] = $value;
			return( true );
		}
		return( false );
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
			$option    = $this->_load_json( $file_name );
			// default
			if( FALSE === $option ) {
				$option = array(
					'id'     => $id,
					'action' => $this->import_action_default,
				);
			}
			$this->_option_default( $option, 'fields', $cols, 0 );
			$this->_option_default( $option, 'keys',   $cols, 0 );
			$result = array(
				'data'   => array(
					'id'     => $id,
					'file'   => $upload_list[ $id ][ 'file_name' ],
					'rows'   => $rows,
					'cols'   => $cols,
					'items'  => $items,
					'option' => $option,
				),
				'status' => true,
			);
		}
		return( $result );
	}

	protected function _upload_item__remove( $id ) {
		$result = array(
			'status' => false,
		);
		if( $this->_upload_list_item__remove( $id ) ) {
			$result[ 'status' ] = true;
			$this->_save_upload_list();
		}
		return( $result );
	}

	protected function _upload_item__import( $option ) {
		$id          = $option[ 'id' ];
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
		$upload_path = $this->upload_path;
		$file        = $upload_path . $id;
		$file_name   = $file . '.import';
		$result      = $this->_save_json( $file_name, $option );
		if( FALSE === $result ) {
			$result = array(
				'status_message' => 'импорт - невозможно сохранить параметры',
				'status'         => false,
			);
			return( $result );
		}
		// test import items
		$_upload_item__import_test = $this->_upload_item__import_test( $option );
		$status = $this->_upload_item__import_action_test( $option, $import_test );
		$result = array(
			'data'   => array(
				'id'           => $id,
				'_import_test' => $_upload_item__import_test,
			),
			'status' => $status,
		);
		return( $result );
	}

	protected function _upload_item__import_action_test( $option, $import_test ) {
		$action = $option[ 'action' ];
		$fields = $option[ 'fields' ];
		$keys   = $option[ 'keys'   ];
		$result = false;
		// test action, fields
		if( empty( $action ) || empty( $fields ) ) { return( $result ); }
		// test rules
		$import_rulues = $this->import_rulues;
		$rulues = $import_rulues[ $action ];
		if( empty( $rulues ) ) { return( $result ); }
		// test fields
		$keys = array_values( $fields );
		$fields_test = array_combine( $keys, $keys ); unset( $fields_test[ 0 ] );
		foreach( $rulues[ 'key' ] as $keys ) {
			$test = true;
			foreach( $keys as $key ) {
				if( !isset( $fields_test[ $key ] ) ) {
					$test = false;
					break;
				}
			}
			if( $test ) { break; }
		}
		$result = $test ? true : false;
		return( $result );
	}

	protected function _upload_item__import_test( $options ) {
		$upload_list = $this->upload_list;
		// load import data
		$id            = $options[ 'id'     ];
		$import_fields = $options[ 'fields' ];
		$action        = $options[ 'action' ];
		$upload_path   = $this->upload_path;
		$file          = $upload_path . $id;
		$items = $this->_file_parse( $file, $upload_list[ $id ] );
		$import_fields_test = array();
		// get import fields
		foreach( $import_fields as $index => $field ) {
			if( empty( $field ) ) { continue; }
			$import_fields_test[ $index ] = $field;
		}
		$_import_field = $this->import_field;
		$result = array(
			'items'         => null,
			'count'         => null,
			'count_valid'   => null,
			'count_invalid' => null,
		);
		foreach( $items as $index => $item ) {
			$valid          = true;
			$status         = true;
			$exists         = null;
			$status_message = array();
			$exists_message = array();
			$result[ 'items' ][ $index ] = array(
				'fields'         => array(),
				'valid'          => $valid,
				'exists'         => $exists,
				'exists_message' => '',
				'status'         => $status,
				'status_message' => 'правильный формат',
			);
			foreach( $import_fields_test as $field_index => $field ) {
				$value = $item[ $field_index ];
				$test = $this->_field__test( $field, $value, $options );
				if( $test === FALSE ) { continue; }
				$result[ 'items' ][ $index ][ 'fields' ][ $field_index ] = $test;
				$status = $status && $test[ 'status' ];
				if( $test[ 'status' ] === FALSE ) {
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
			$result[ 'items' ][ $index ][ 'status' ] = $status;
			$result[ 'items' ][ $index ][ 'exists' ] = $exists;
			!empty( $exists_message ) && ( $result[ 'items' ][ $index ][ 'exists_message' ]
				= implode( '; ', $exists_message ) );
			!empty( $status_message ) && ( $result[ 'items' ][ $index ][ 'status_message' ]
				= implode( '; ', $status_message ) );
			if( $status ) {
				++$result[ 'count_valid' ];
			} else {
				++$result[ 'count_invalid' ];
			}
			++$result[ 'count' ];
		}
		return( $result );
	}

	protected function _field__test( $field, $value, $action ) {
		$_class  = $this;
		$_method = '_field_test__' . $field;
		$_status = method_exists( $_class, $_method );
		if( !$_status ) { return( false ); }
		return( $_class->$_method( $value, $action ) );
	}

	protected function _field_test__id( $value, $action ) {
		$value  = (int)$value;
		$valid  = $value > 0;
			$valid && list( $exists, $many ) = $this->_db_exists( 'shop_products', 'id', $value );
		$status = $valid && $exists;
		$status_message = $status ? 'товар уже существует' : 'товар не существует';
		$result = array(
			'valid'          => $valid,
			'exists'         => $exists,
			'many'           => $many,
			'status'         => $status,
			'status_message' => $status_message,
		);
		return( $result );
	}

	protected function _field_test__name( $value, $action ) {
		$value  = trim( $value );
		$valid  = !empty( $value );
		if( !$valid ) {
			$status_message = 'название товара пустое';
		} else {
			$length = 3;
			$valid = mb_strlen( $value, 'UTF-8' ) >= $length;
			if( !$valid ) {
				$status_message = 'название товара менее '. $length .' символов';
			}
		}
		$valid && list( $exists, $many ) = $this->_db_exists( 'shop_products', 'name', $value );
		$status = $valid;
		$status_message = $status ? null : $status_message;
		$result = array(
			'valid'          => $valid,
			'exists'         => $exists,
			'many'           => $many,
			'status'         => $status,
			'status_message' => $status_message,
		);
		return( $result );
	}

	protected function _field_test__price( $value, $action ) {
		$_class_price = $this->_class_price;
		$value  = $_class_price->_number_float( $value );
		$valid  = $value > 0;
		$exists = null;
		$status = $valid;
		$status_message = $status ? null : 'цена должна быть больше нуля';
		$result = array(
			'valid'          => $valid,
			'exists'         => $exists,
			'status'         => $status,
			'status_message' => $status_message,
		);
		return( $result );
	}

	protected function _field_test__price_raw( $value, $action ) {
		$_class_price = $this->_class_price;
		$value  = $_class_price->_number_float( $value );
		$valid  = $value > 0;
		$exists = null;
		$status = $valid;
		$status_message = $status ? null : 'себестоимость должна быть больше нуля';
		$result = array(
			'valid'          => $valid,
			'exists'         => $exists,
			'status'         => $status,
			'status_message' => $status_message,
		);
		return( $result );
	}

	protected function _field_test__articul( $value, $action ) {
		$value  = trim( $value );
		$valid  = !empty( $value );
		if( !$valid ) {
			$status_message = 'артикул пустой';
		}
		$valid && list( $exists, $many ) = $this->_db_exists( 'shop_products', 'articul', $value );
		$status = $valid;
		$status_message = $status ? null : $status_message;
		$result = array(
			'valid'          => $valid,
			'exists'         => $exists,
			'many'           => $many,
			'status'         => $status,
			'status_message' => $status_message,
		);
		return( $result );
	}

	protected function _field_test__cat_id( $value, $action ) {
		$value  = (int)$value;
		$valid  = $value > 0;
			!$valid && $status_message = 'категория пустая';
		$valid && ( list( $exists, $many ) = $this->_db_exists( 'sys_category_items', 'id', $value
			, array( 'where' => array( 'cat_id' => $this->set_cat_id ) ) ) );
				!$exists  && $status_message = 'категория не существует';
				$many > 1 && $status_message = 'множество категорий с этим id: ' . $many;
		$status = $valid && $exists && $many == 1;
			$status_message = $status ? null : $status_message;
		$result = array(
			'valid'          => $valid,
			'exists'         => $exists,
			'many'           => $many,
			'status'         => $status,
			'status_message' => $status_message,
		);
		return( $result );
	}

	protected function _field_test__category_name( $value, $action ) {
		$value  = trim( $value );
		$valid  = !empty( $value );
			!$valid && $status_message = 'категория пустая';
		$valid && ( list( $exists, $many ) = $this->_db_exists( 'sys_category_items', 'name', $value
			, array( 'where' => array( 'cat_id' => $this->set_cat_id ) ) ) );
				!$exists  && $status_message = 'категория не существует';
				$many > 1 && $status_message = 'множество категорий с этим именем: ' . $many;
		$status = $valid && $exists && $many == 1;
			$status_message = $status ? null : $status_message;
		$result = array(
			'valid'          => $valid,
			'exists'         => $exists,
			'many'           => $many,
			'status'         => $status,
			'status_message' => $status_message,
		);
		return( $result );
	}

	function _db_exists( $name, $key, $value, $options = array() ) {
		if( empty( $name ) || empty( $key ) ) { return( null ); }
		$_      = $options;
		$_where = (array)$_[ 'where' ];
		$items = &$this->cache[ $name ];
		if( !isset( $items[ $key ][ $value ] ) ) {
			$_get_options = array(
				'name'  => $name,
				'key'   => $key,
				'where' => $_where + array(
					$key => $value
				),
			);
			$item = $this->_db_get( $_get_options );
			// cache
			if( empty( $item ) ) {
				$items[ $key ][ $value ] = null;
			} else {
				$items[ $key ][ $value ] = array();
				foreach( (array)$item as $index => $_item ) {
					if( isset( $_item[ $key ] ) ) {
						if( isset( $_item[ 'id' ] ) ) {
							$id = (int)$_item[ 'id' ];
							$items[ 'id' ][ $id ] = &$item[ $index ];
						} else {
							$id = $index;
						}
						$items[ $key ][ $value ][ $id ] = &$item[ $index ];
					}
				}
			}
		}
		$exists = !is_null( $items[ $key ][ $value ] );
		$many   = count( $items[ $key ][ $value ] );
		return( array( $exists, $many ) );
	}

	function _db_get( $options ) {
		$_      = $options;
		$_name  = $_[ 'name' ];
			$sql_name = _es( $_name );
		if( empty( $_name ) ) { return( null ); }
		$_where = $_[ 'where' ];
		$_limit = $_[ 'limit' ];
		$_key   = isset( $_[ 'key' ] ) ? $_[ 'key' ] : 'id';
		// prepare where
		$where  = array();
		foreach( $_where as $field => $value ) {
			if( isset( $value ) ) {
				$values = _es( (array)$value );
				foreach( $values as $i => $v ) {
					if( !is_int( $v ) && !is_null( $v ) ) {
						$values[ $i ] = "'$v'";
					}
				}
				$where[] = $field . ' IN( ' . implode( ', ', $values ) . ' )';
			}
		}
		$sql_where = '';
		if( !empty( $where ) ) {
			$sql_where = 'WHERE ' . implode( ' AND ', $where );
		}
		// prepare limit
		$sql_limit = '';
		if( is_string( $_limit ) || is_int( $_limit ) ) {
			$sql_limit = 'LIMIT ' . $_limit;
		}
		// prepare sql
		$sql = sprintf(
			'SELECT * FROM %s %s %s'
			, db( $sql_name )
			, $sql_where
			, $sql_limit
		);
		$result = db_get_all( $sql, '-1' );
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
			$result = $excel->getActiveSheet()->toArray( null, true, true, false );
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
		$_import_action = $this->import_action;
		$_import_action_array = array();
		foreach( $_import_action as $key => $value ) {
			$_import_action_array[] = array(
				'key'   => $key,
				'value' => $value,
			);
		}
		$_supplier = $this->supplier;
		$_supplier_array = array();
		$_supplier_array[] = array(
			'id'    => null,
			'title' => 'поставщик',
		);
		foreach( $_supplier as $id => $title ) {
			$_supplier_array[] = array(
				'id'    => $id,
				'title' => $title . ' ('. $id .')',
			);
		}
		$result = array(
			'_upload_status'       => $_upload_status,
			'_upload_list'         => $_upload_list,
			'_import_field'        => $_import_field,
			'_import_action'       => $_import_action,
			'_import_action_array' => $_import_action_array,
			'_supplier'            => $_supplier,
			'_supplier_array'      => $_supplier_array,
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
