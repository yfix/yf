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
	public $import_rules = array(
		'insert' => array(
			'key' => array(
				array( 'name', 'cat_id',        ),
				array( 'name', 'category_name', ),
			),
			'exclude' => array(
				'id',
			),
			'include' => array(
				'name',
				'cat_id',
				'manufacturer_id',
				'manufacturer_name',
				'supplier_id',
				'supplier_name',
				'articul',
				'external_url',
				'origin_url',
				'source',
			),
		),
		'update' => array(
			'key' => array(
				array( 'id', ),
				array( 'supplier_id',   'articul', ),
				array( 'supplier_name', 'articul', ),
				array( 'name', ),
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
		// categories
		$this->set_cat_id = _class( 'cats' )->_get_cat_id_by_name( $this->set_cat_name );
		// cache fetch
		$this->_cache_fetch();
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

	function _cache_fetch() {
		$cache = &$this->cache;
		// category
		$items = db()->from( 'sys_category_items' )->where( 'cat_id', $this->set_cat_id )->order_by( 'name' )->all();
		if( !empty( $items ) ) {
			foreach( $items as $id => $item ) {
				$name = $item[ 'name' ];
				$cache[ 'category' ][ 'id'   ][ $id ] = &$items[ $id ];
				$cache[ 'category' ][ 'name' ][ $name ][ $id ] = &$items[ $id ];
			}
		}
		// supplier
		$items = db()->from( 'shop_suppliers' )->all();
		if( !empty( $items ) ) {
			foreach( $items as $id => $item ) {
				$name = $item[ 'name' ];
				$cache[ 'supplier' ][ 'id'   ][ $id   ] = &$items[ $id ];
				$cache[ 'supplier' ][ 'name' ][ $name ][ $id ] = &$items[ $id ];
			}
		}
		// supplier
		$items = db()->from( 'shop_manufacturers' )->all();
		if( !empty( $items ) ) {
			foreach( $items as $id => $item ) {
				$name = $item[ 'name' ];
				$cache[ 'manufacturer' ][ 'id'   ][ $id   ] = &$items[ $id ];
				$cache[ 'manufacturer' ][ 'name' ][ $name ][ $id ] = &$items[ $id ];
			}
		}
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
				if( $result[ 'status' ] ) {
					$test = $this->_upload_item__import( $result[ 'data' ][ 'options' ] );
					$result[ 'data' ][ 'test' ] = $test;
				}
				break;
			case 'remove':
				$id = $post[ 'id' ];
				$result = $this->_upload_item__remove( $id );
				break;
			case 'import':
				$result = $this->_upload_item__import( $post[ 'options' ] );
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

	protected function _option_default( &$options, $key, $cols, $default ) {
		if( empty( $options ) || empty( $key ) || empty( $cols ) ) { return( null ); }
		if( empty( $options[ $key ] ) ) {
			$value = array();
			for( $i = 0; $i < $cols; $i++ ) { $value[ $i ] = $default; }
			$options[ $key ] = $value;
			return( true );
		}
		return( false );
	}

	protected function _upload_item__get( $id ) {
		$result = array(
			'status' => false,
		);
		$upload_list = $this->upload_list;
		// get data, options
		$items   = $this->_file_get( $id );
		$options = $this->_options_get( $id );
		if( is_array( $items ) ) {
			$rows  = count( $items );
			$cols  = count( $items[ 0 ] );
			// default
			if( empty( $options ) ) {
				$options = array(
					'id'     => $id,
					'action' => $this->import_action_default,
				);
			}
			$this->_option_default( $options, 'fields', $cols, 0 );
			$this->_option_default( $options, 'keys',   $cols, 0 );
			$result = array(
				'data'   => array(
					'id'      => $id,
					'file'    => $upload_list[ $id ][ 'file_name' ],
					'rows'    => $rows,
					'cols'    => $cols,
					'items'   => $items,
					'options' => $options,
				),
				'status' => true,
			);
		} else {
			$upload_list = $this->upload_list;
			$file        = $upload_list[ $id ][ 'file_name' ];
			$this->_upload_item__remove( $id );
			if( $items === false ) {
				$status_message = 'файл не распознан';
			} else {
				$status_message = 'файл не найден';
			}
			$result = array(
				'status'         => false,
				'file'           => $file,
				'status_message' => $status_message,
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

	protected function _upload_item__import( $options ) {
		$_ = $options;
		$id          = $_[ 'id' ];
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
		$result      = $this->_save_json( $file_name, $_ );
		if( FALSE === $result ) {
			$result = array(
				'status_message' => 'импорт - невозможно сохранить параметры',
				'status'         => false,
			);
			return( $result );
		}
		// test import items
		$status_message = '';
		list( $status, $status_message ) = $this->_upload_item__import_action_test( $_ );
		$import_test = array();
		$status && $import_test = $this->_upload_item__import_test( $_ );
		// update db
		$confirm = $_[ 'confirm' ];
		if( $status && !empty( $confirm ) ) {
			list( $status, $status_message ) = $this->_db_import( $_, $import_test );
		}
		$result = array(
			'data'   => array(
				'id'           => $id,
				'_import_test' => $import_test,
			),
			'status'         => $status,
			'status_message' => $status_message,
		);
		return( $result );
	}

	protected function _upload_item__import_action_test( $options ) {
		$_ = $options;
		$fields      = $_[ 'fields'      ];
		$keys        = $_[ 'keys'        ];
		$supplier_id = $_[ 'supplier_id' ];
		$category_id = $_[ 'category_id' ];
		$action      = $_[ 'action'      ];
			$action  = !empty( $action ) ? $action :  $this->import_action_default;
			$rules   = &$this->import_rules[ $action ];
		$status         = false;
		$status_message = '';
		// test action, fields
		if( empty( $action ) || empty( $fields ) ) {
			$fields = array();
			foreach( $rules[ 'key' ] as $ks ) {
				$f = array();
				foreach( $ks as $k ) {
					$f[] = $k;
				}
				$fields[] = implode( ', ', $f );
			}
			$fields = implode( ' или ', $fields );
			$message = 'Установите поля: ';
			$status_message = $message . $fields;
			return( array( $status, $status_message ) );
		}
		// test rules
		if( empty( $rules ) ) {
			$action_message = $this->import_action[ $action ];
			$status_message = 'Отсутствуют правила для операции "'. $action_message;
			return( array( $status, html_entity_decode( $status_message, ENT_QUOTES, 'UTF-8' ) ) );
		}
		// test fields by name
		$fields_by_name = array_flip( $fields );
		unset( $fields_by_name[ 0 ] );
		if( $action == 'update' ) {
			foreach( $fields_by_name as $name => $key ) {
				if( !$keys[ $key ] ) { unset( $fields_by_name[ $name ] ); }
			}
		}
		// override supplier_id
		if( !empty( $supplier_id ) ) {
			$fields_by_name[ 'supplier_id' ] = 0;
		}
		// override category_id
		if( !empty( $category_id ) ) {
			$fields_by_name[ 'cat_id' ] = 0;
		}
		// test key: include, exclude
		foreach( $rules[ 'key' ] as $ks ) {
			$is_key = true;
			foreach( $ks as $k ) {
				if( !isset( $fields_by_name[ $k ] ) ) {
					$is_key = false;
					break;
				}
			}
			if( $is_key ) { break; }
		}
		$status = $is_key ? true : false;
		if( !$status ) {
			$fields = array();
			foreach( $rules[ 'key' ] as $ks ) {
				$f = array();
				foreach( $ks as $k ) {
					$f[] = $k;
				}
				$fields[] = implode( ', ', $f );
			}
			$fields = implode( ' или ', $fields );
			$action_message = $this->import_action[ $action ];
			$status_message = 'Требуются поля для операции "'. $action_message .'": ' . $fields;
			return( array( $status, html_entity_decode( $status_message, ENT_QUOTES, 'UTF-8' ) ) );
		}
		if( $status && $action == 'insert' ) {
			$is_key = false;
			foreach( $rules[ 'exclude' ] as $k ) {
				if( isset( $fields_by_name[ $k ] ) ) {
					$is_key = true;
					break;
				}
			}
			$status = !$is_key;
			if( !$status ) {
				$fields = implode( ', ', $rules[ 'exclude' ] );
				$action_message = $this->import_action[ $action ];
				$status_message = 'Поля для операции "'. $action_message .'" запрещены: ' . $fields; ;
				return( array( $status, html_entity_decode( $status_message, ENT_QUOTES, 'UTF-8' ) ) );
			}
		}
		return( array( $status, $status_message ) );
	}

	protected function _upload_item__import_test( $options ) {
		// load import data
		$_      = $options;
		$id          = $_[ 'id'          ];
		$fields      = $_[ 'fields'      ];
		$keys        = $_[ 'keys'        ];
		$supplier_id = $_[ 'supplier_id' ];
		$category_id = $_[ 'category_id' ];
		$action      = $_[ 'action'      ];
			$action  = !empty( $action ) ? $action :  $this->import_action_default;
			$rules   = &$this->import_rules[ $action ];
		// var_dump( $_ );
		// exit;
		// fields by name
		$fields_by_name = array_flip( $fields );
		unset( $fields_by_name[ 0 ] );
		$fields_keys = $fields_by_name;
		if( $action == 'update' ) {
			foreach( $fields_keys as $name => $key ) {
				if( !$keys[ $key ] ) { unset( $fields_keys[ $name ] ); }
			}
		} elseif( $action == 'insert' ) {
			$keys = array();
			if( !empty( $rules ) ) {
				foreach( $rules[ 'include' ] as $key ) {
					if( isset( $fields_keys[ $key ] ) ) { $keys[ $key ] = $fields_keys[ $key ]; }
				}
			}
			$fields_keys = $keys;
		}
		$_import_field = $this->import_field;
		$result = array(
			'items'         => null,
			'count'         => null,
			'count_valid'   => null,
			'count_invalid' => null,
		);
		$items = $this->_file_get( $id );
		foreach( $items as $index => $item ) {
			$valid          = true;
			$status         = true;
			$exists         = null;
			$status_message = array();
			$exists_message = array();
			// test row on exists
			$where = array();
			foreach( $fields_keys as $key => $key_index ) {
				$where[ $key ] = $item[ $key_index ];
			}
			// override supplier_id
			if( !empty( $supplier_id ) ) {
				unset( $where[ 'supplier_id' ], $where[ 'supplier_name' ] );
				$where[ 'supplier_id' ] = (int)$supplier_id;
			}
			// override category_id
			if( !empty( $category_id ) ) {
				unset( $where[ 'cat_id' ], $where[ 'category_name' ] );
				$where[ 'cat_id' ] = (int)$category_id;
			}
			list( $exists, $many, $found ) = $this->_db_exists( 'shop_products',
				array( 'where' => $where ) );
			if( $action == 'update' ) {
				!( $exists && $many == 1 ) && $valid = false;
					!$exists  && $status_message[] = 'не существует';
					$many > 1 && $status_message[] = 'обнаружено множество элементов';
			} elseif( $action == 'insert' ) {
				$exists
					&& ( $status_message[] = 'уже существует' )
					&& ( $valid = false );
			}
			$status = $valid;
			$result[ 'items' ][ $index ] = array(
				'fields'         => array(),
				'valid'          => $valid,
				'exists'         => $exists,
				'exists_message' => '',
				'status'         => $status,
				'status_message' => $status_message,
				'found'          => $found,
			);
			// var_dump( $where, $result[ 'items' ][ $index ] );
			// test fields
			if( $status ) {
				foreach( $fields_by_name as $field => $field_index ) {
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
							. ( $test[ 'exists' ] !== false ? 'существует': 'не существует' );
					}
				}
				$result[ 'items' ][ $index ][ 'status' ] = $status;
				$result[ 'items' ][ $index ][ 'exists' ] = $exists;
				!empty( $exists_message ) && ( $result[ 'items' ][ $index ][ 'exists_message' ]
					= implode( '; ', $exists_message ) );
				!empty( $status_message ) && ( $result[ 'items' ][ $index ][ 'status_message' ]
					= implode( '; ', $status_message ) );
			}
			if( $status ) {
				++$result[ 'count_valid' ];
			} else {
				++$result[ 'count_invalid' ];
			}
			++$result[ 'count' ];
		}
		// exit;
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
		$exists = null;
		$status = $valid;
		$status_message = $status ? 'товар уже существует' : 'товар не существует';
		$result = array(
			'valid'          => $valid,
			'exists'         => $exists,
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
		$exists = null;
		$status = $valid;
		$status_message = $status ? null : $status_message;
		$result = array(
			'valid'          => $valid,
			'exists'         => $exists,
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

	protected function _field_test__articul_skip( $value, $action ) {
		$value  = trim( $value );
		$valid  = !empty( $value );
		if( !$valid ) {
			$status_message = 'артикул пустой';
		}
		$exists = null;
		$status = $valid;
		$status_message = $status ? null : $status_message;
		$result = array(
			'valid'          => $valid,
			'exists'         => $exists,
			'status'         => $status,
			'status_message' => $status_message,
		);
		return( $result );
	}

	protected function _field_test__cat_id( $value, $action ) {
		$value  = (int)$value;
		$valid  = $value > 0;
			!$valid && $status_message = 'категория пустая';
		$exists = null;
		if( $valid ) {
			$cache = &$this->cache;
			$exists = is_array( $cache[ 'category' ][ 'id' ][ $value ] );
				!$exists  && $status_message = 'категория не существует';
		}
		$status = $valid && $exists;
			$status_message = $status ? null : $status_message;
		$result = array(
			'valid'          => $valid,
			'exists'         => $exists,
			'status'         => $status,
			'status_message' => $status_message,
		);
		return( $result );
	}

	protected function _field_test__category_name( $value, $action ) {
		$value  = trim( $value );
		$valid  = !empty( $value );
			!$valid && $status_message = 'категория пустая';
		$exists = null;
		if( $valid ) {
			$cache = &$this->cache;
			$exists = is_array( $cache[ 'category' ][ 'name' ][ $value ] );
			$many   = count( $cache[ 'category' ][ 'name' ][ $value ] );
				!$exists  && $status_message = 'категория не существует';
				$many > 1 && $status_message = 'множество категорий с этим именем: ' . $many;
		}
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

	function _db_exists( $name, $options = array() ) {
		$cache  = &$this->cache;
		$_     = $options;
		$key   = $_[ 'key'   ];
		$value = $_[ 'value' ];
		if( empty( $name ) ) { return( null ); }
		$cache = &$this->cache[ $name ];
		$is_cache = is_array( $cache[ $key ][ $value ] );
		if( $is_cache ) {
			$items = $cache[ $key ][ $value ];
		} else {
			$_get_options = $options + array(
				'name'  => $name,
			);
			if( !empty( $key ) && !empty( $value ) ) {
				$_get_options[ 'where' ][] = array( $key => $value );
			}
			$items = $this->_db_get( $_get_options );
			// cache
			if( !empty( $items ) && !empty( $key ) && !empty( $value ) ) {
				$cache[ $key ][ $value ] = array();
				foreach( (array)$items as $index => $_item ) {
					if( isset( $_item[ $key ] ) ) {
						if( isset( $_item[ 'id' ] ) ) {
							$id = (int)$_item[ 'id' ];
							$cache[ 'id' ][ $id ] = &$items[ $index ];
						} else {
							$id = $index;
						}
						$cache[ $key ][ $value ][ $id ] = &$items[ $index ];
					}
				}
			}
		}
		$exists = !is_null( $items );
		$many   = count( $items );
		return( array( $exists, $many, $items ) );
	}

	function _db_get( $options ) {
		$cache  = &$this->cache;
		$_      = $options;
		$_name  = $_[ 'name' ];
			$sql_name = _es( $_name );
		if( empty( $_name ) ) { return( null ); }
		$_where = $_[ 'where' ];
		$_limit = $_[ 'limit' ];
		// prepare where
		$where  = array();
		foreach( $_where as $field => $value ) {
			if( isset( $value ) ) {
				if( $field == 'category_name' ) {
				// convert category: name to id
					if( is_array( $cache[ 'category' ][ 'name' ][ $value ] ) ) {
						$value = current( $cache[ 'category' ][ 'name' ][ $value ] )[ 'id' ];
						$field = 'cat_id';
					} else { return( null ); }
				} elseif( $field == 'supplier_name' ) {
				// convert supplier: name to id
					if( is_array( $cache[ 'supplier' ][ 'name' ][ $value ] ) ) {
						$value = current( $cache[ 'supplier' ][ 'name' ][ $value ] )[ 'id' ];
						$field = 'supplier_id';
					} else { return( null ); }
				} elseif( $field == 'manufacturer_name' ) {
				// convert manufacturer: name to id
					if( is_array( $cache[ 'manufacturer' ][ 'name' ][ $value ] ) ) {
						$value = current( $cache[ 'manufacturer' ][ 'name' ][ $value ] )[ 'id' ];
						$field = 'manufacturer_id';
					} else { return( null ); }
				}
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
		if( $_limit > 0 && ( is_string( $_limit ) || is_int( $_limit ) ) ) {
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
		// var_dump( $sql, $result );
		return( $result );
	}

	protected function _field_to_sql( $field, $value ) {
		$_class  = $this;
		$_method = '_field_to_sql__' . $field;
		$_status = method_exists( $_class, $_method );
		if( !$_status ) { return( _es( $value ) ); }
		return( $_class->$_method( $value ) );
	}

	protected function _field_to_sql__price( $value ) {
		$result = number_format( $value, 2, '.', '' );
		return( $result );
	}

	protected function _db_import( $options, $test ) {
		$_ = $options;
		$id     = $_[ 'id'     ];
		$fields = $_[ 'fields' ];
		$keys   = $_[ 'keys'   ];
		$action = $_[ 'action' ];
		$supplier_id = $_[ 'supplier_id' ];
		$category_id = $_[ 'category_id' ];
		// get data
		$items = $this->_file_get( $id );
		// prepare sql data
		$data = array();
		$test_items = &$test[ 'items' ];
		// get fields, keys
		$fields_by_name = array_flip( $fields );
		unset( $fields_by_name[ 0 ] );
		$fields_keys   = $fields_by_name;
		$fields_values = $fields_by_name;
		if( $action == 'update' ) {
			foreach( $fields_keys as $name => $key ) {
				if( !$keys[ $key ] ) { unset( $fields_keys[ $name ] ); }
				else { unset( $fields_values[ $name ] ); }
			}
			$sql_item_default = array();
		} elseif( $action == 'insert' ) {
			$sql_item_default = array();
			// add supplier_id
			if( !empty( $supplier_id ) ) {
				$sql_item_default[ 'supplier_id' ] = (int)$supplier_id;
			}
			// add category_id
			if( !empty( $category_id ) ) {
				$sql_item_default[ 'cat_id' ] = (int)$category_id;
			}
		}
		foreach( $items as $index => $item ) {
			$sql_item = $sql_item_default;
			$status = $test_items[ $index ][ 'status' ];
			if( !$status ) { continue; }
			if( $action == 'update' ) {
				// get id
				$id = (int)$test_items[ $index ][ 'found' ][ 0 ][ 'id' ];
				if( $id < 1 ) { continue; }
				$sql_item[ 'id' ] = $id;
				// prepare fields
				foreach( $fields_values as $k => $i ) {
					$sql_item[ $k ] = $this->_field_to_sql( $k, $item[ $i ] );
				}
				// update record time
				$sql_item[ 'update_date' ] = time();
			} elseif( $action == 'insert' ) {
				// prepare fields
				foreach( $fields_values as $k => $i ) {
					$sql_item[ $k ] = $this->_field_to_sql( $k, $item[ $i ] );
				}
				// update record time
				$sql_item[ 'add1_date' ] = time();
			}
			$data[] = $sql_item;
		}
		// var_dump( $data ); exit;
		// update db
		$table  = 'shop_products';
		// debug sql
		$result = db()->insert_on_duplicate_key_update( $table, $data, $sql ); var_dump( $result ); exit;
		// end debug sql
		db()->begin();
		$result = db()->insert_on_duplicate_key_update( $table, $data );
		// *** yf db bug: after table repair - dropped error, affected_rows, etc
		// $error = db()->error();
		// $count = db()->affected_rows();
		// then usage: last_error, etc
		// $error = db()->last_error();
		if( $result ) {
			db()->commit();
			$count  = count( $data );
			$status = true;
			$status_message = 'Импортировано: ' . $count;
		}
		else {
			db()->rollback();
			$status = false;
			$status_message = 'Ошибка при работе с БД!';
		}
		return( array( $status, $status_message ) );
	}

	protected function _file_parse__get_cache( $file_name ) {
		$result = $this->_load_csv( $file_name . '.cache' );
		return( $result );
	}

	protected function _file_parse__set_cache( $file_name, $data ) {
		$result = $this->_save_csv( $file_name . '.cache', $data );
		return( $result );
	}

	protected function _file_parse( $file_name, $force = false ) {
		// cache
		if( !$force ) {
			$result = $this->_file_parse__get_cache( $file_name );
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
		$this->_file_parse__set_cache( $file_name, $result );
		return( $result );
	}

	protected function _file_get( $id ) {
		$result      = null;
		$upload_list = $this->upload_list;
		$upload_path = $this->upload_path;
		$file        = $upload_path . $id;
		if( !empty( $upload_list[ $id ] ) && file_exists( $file ) ) {
			$result = $this->_file_parse( $file );
		}
		return( $result );
	}

	protected function _options_get( $id ) {
		$result      = null;
		$upload_list = $this->upload_list;
		$upload_path = $this->upload_path;
		$file        = $upload_path . $id . '.import';
		if( !empty( $upload_list[ $id ] ) && file_exists( $file ) ) {
			$result = $this->_load_json( $file );
		}
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
		$cache = &$this->cache;
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
		// supplier
		$_supplier = $cache[ 'supplier' ][ 'id' ];
		$_supplier_array = array();
		$_supplier_array[] = array(
			'id'    => null,
			'title' => 'поставщик',
		);
		foreach( $_supplier as $id => $item ) {
			$title = $item[ 'name' ];
			$_supplier_array[] = array(
				'id'    => $id,
				'title' => $title . ' ('. $id .')',
			);
		}
		// category
		$_category = $cache[ 'category' ][ 'id' ];
		$_category_array = array();
		$_category_array[] = array(
			'id'    => null,
			'title' => 'категория',
		);
		foreach( $_category as $id => $item ) {
			$title = $item[ 'name' ];
			$_category_array[] = array(
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
			'_category'            => $_category,
			'_category_array'      => $_category_array,
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
		$_form_tpl  = tpl()->parse( 'manage_shop/import2__form', $data );
		$_form_ctrl = tpl()->parse( 'manage_shop/import2__ctrl', $data );
		// create form
		$_form = $_form_ctrl . $_form_tpl;
		return( $_form );
	}

}
