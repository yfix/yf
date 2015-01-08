<?php

/*
	default currency:
	exchange:
		1 unit = 1 cent
	options:
		id          : UNT
		name        : балл(ы|ов)
		sign        : б.
		number      : 0
		minor_units : 0 { Number of digits after the decimal separator }

	db:
		payment_account
			account_id
			account_type_id
			user_id
			currency_id
			balance
			title
			description
			datetime_create
			datetime_update

		payment_account_type
				{ main, deposit, credit, etc }
			account_type_id
			name
			title
			options
				{ icon, image }

		payment_operation
			operation_id
			account_id
			provider_id
				{ system, administration, webmoney, privat24 }
			provider_operation_id
			direction
				{ in (приход), out (расход) }
			type_id
			status_id
			amount
			title
			description
			datetime_start
			datetime_finish
			datetime_update

		payment_provider
				{ system, administration, webmoney, privat24 }
			provider_id
			name
			title
			options
				{ icon, image }

		payment_status
				{ in_progress, success, refused }
			status_id
			name
			title
			options
				{ icon, image }

		payment_type
				{
					deposition : пополнение счета (приход)
					payment    : платежи со счета (расход)
					exchange   : обмен валют
					transfers  : переводы p2p
				}
			type_id
			name
			title
			options
				{ icon, image }

*/

class yf_payment_api {

	public $user_id_default = null;
	public $user_id         = null;

	public $account_id = null;
	public $account    = null;

	public $account_type_id_default = 'main';
	public $account_type_id         = null;
	public $account_type            = null;

	public $currency_id_default = 'UNT';
	public $currency_id         = null;
	public $currency            = null;
	public $currency_rate       = null;
	public $currencies          = array(
		'UNT' => array(
			'currency_id' => 'UNT',
			'name'        => 'балл',
			'short'       => 'балл.',
			'sign'        => 'б.',
			'number'      => 0,
			'minor_units' => 0,      // Number of digits after the decimal separator
		),
		'USD' => array(
			'currency_id' => 'USD',
			'name'        => 'Доллар США',
			'short'       => '$',
			'sign'        => '$',
			'number'      => 840,
			'minor_units' => 2,
		),
		'EUR' => array(
			'currency_id' => 'EUR',
			'name'        => 'Евро',
			'short'       => 'евро',
			'sign'        => '€',
			'number'      => 978,
			'minor_units' => 2,
		),
		'UAH' => array(
			'currency_id' => 'UAH',
			'name'        => 'Украинская гривна',
			'short'       => 'грн',
			'sign'        => '₴',
			'number'      => 980,
			'minor_units' => 2,
		),
		'RUB' => array(
			'currency_id' => 'RUB',
			'name'        => 'Российский рубль',
			'short'       => 'руб',
			'sign'        => 'р.',
			'number'      => 643,
			'minor_units' => 2,
		),
	);

	public $provider_id    = null;
	public $provider       = null;
	public $provider_index = null;

	public $type_id    = null;
	public $type_name  = null;
	public $type       = null;
	public $type_index = null;

	public $OPERATION_LIMIT = 10;

	public function _init() {
		$this->user_id_default = (int)main()->USER_ID;
		$this->user_id( $this->user_id_default );
	}

	public function user_id( $value = -1 ) {
		$object = &$this->user_id;
		if( $value !== -1 ) { $object = $value; }
		return( $object );
	}

	public function account_type( $value = -1 ) {
		$id     = &$this->account_type_id;
		$object = &$this->account_type;
		if( $value !== -1 ) {
			list( $id, $object ) = $value;
		}
		return( array( $id, $object ) );
	}

	public function currency( $value = -1 ) {
		$id     = &$this->currency_id;
		$object = &$this->currency;
		if( $value !== -1 ) {
			list( $id, $object ) = $value;
		}
		return( array( $id, $object ) );
	}

	public function get_account_type__by_name( $options = null ) {
		$_ = &$options;
		$name = $_[ 'name' ] ?: $this->account_type_id_default;
		$result = db()->table( 'payment_account_type' )
			->where( 'name', '=', _es( $name ) )
			->get_deep_array( 1 );
		if( empty( $result ) ) {
			$account_type    = null;
			$account_type_id = null;
		} else {
			$account_type    = current( $result );
			$account_type_id = $account_type[ 'account_type_id' ];
		}
		return( array( $account_type_id, $account_type ) );
	}

	public function get_currency__by_id( $options = null ) {
		$_ = &$options;
		$to_set      = $_[ 'to_set'      ];
		$currency_id = $_[ 'currency_id' ]
			?: $this->currency_id
			?: $this->currency_id_default
		;
		$result = $this->currencies[ $currency_id ];
		if( empty( $result ) ) {
			$currency    = null;
			$currency_id = null;
		} else {
			$currency    = $result;
		}
		if( $to_set ) {
			$this->currency    = $currency;
			$this->currency_id = $currency_id;
		}
		return( array( $currency_id, $currency ) );
	}

	public function get_account__by_id( $options = null ) {
		$account    = &$this->account;
		$account_id = $this->account_id;
		// cache
		if( empty( $options[ 'force' ] ) && !empty( $account[ $account_id ] ) ) {
			return( array( $account_id, $account ) );
		}
		// get from db
		$_ = &$options;
		$account_id = (int)$_[ 'account_id' ];
		if( empty( $account_id ) ) { return( null ); }
		$result = db()->table( 'payment_account' )
			->where( 'account_id', '=', $account_id )
			->order_by( 'account_id' )
			->get_deep_array( 1 );
		if( empty( $result ) ) {
			$account    = null;
			$account_id = null;
		} else {
			$account = &$result[ $account_id ];
		}
		$this->account    = $account;
		$this->account_id = $account_id;
		// get currency
		$account_id && $this->get_currency__by_id( array(
			'to_set'      => true,
			'currency_id' => $account[ 'currency_id' ],
		));
		return( array( $account_id, $account ) );
	}

	public function currency_rate__buy( $options = null ) {
		$_ = &$options; $_ = (array)$_;
		$_[ 'currency_rate_type' ] = 'buy';
		$result = $this->currency_rate( $_ );
		return( $result );
	}

	public function currency_rate__sell( $options = null ) {
		$_ = &$options; $_ = (array)$_;
		$_[ 'currency_rate_type' ] = 'sell';
		$result = $this->currency_rate( $_ );
		return( $result );
	}

	public function currency_rate( $options = null ) {
		$_ = &$options;
		// default 'buy'
		$type = $_[ 'currency_rate_type' ] == 'sell' ? 'sell' : 'buy';
		$currency_rate = &$this->currency_rate[ $type ];
		// cache
		if( $_[ 'force' ] || !$currency_rate ) {
			list( $currency_id, $currency ) = $this->get_currency__by_id();
			if( $type == 'buy' ) {
				$target = 'to';
				$source = 'from';
			} else {
				$target = 'from';
				$source = 'to';
			}
			$key_value = $target . '_value';
			$key_rate  = $source . '_value';
			$result = db()->table( 'payment_currency_rate' )
				->where( $target, '=', $currency_id )
				->group_by( $source )
				->order_by( 'datetime', 'DESC' )
				->get_deep_array( 1 );
			if( empty( $result ) ) {
				$currency_id_default = &$this->currency_id_default;
				foreach( $this->currencies as $key => $item ) {
					if( $currency_id_default == $key ) { continue; }
					$currency_rate[ $key ] = array(
						'value' => 1.0,
						'rate'  => 1.0,
					);
				}
			} else {
				foreach( $result as $id => $item ) {
					$key   = $item[ $source    ];
					$value = $item[ $key_value ];
					$rate  = $item[ $key_rate  ];
					$currency_rate[ $key ] = array(
						'value' => (float)$value,
						'rate'  => (float)$rate,
					);
				}
			}
		}
		// get from db
		return( $currency_rate );
	}

	public function currency_conversion( $options = null ) {
		$_ = &$options;
		$conversion_type = $_[ 'conversion_type' ] == 'sell' ? 'sell' : 'buy';
		$currency_id     = $_[ 'currency_id'     ];
		$amount          = $_[ 'amount'          ];
		if( empty( $currency_id ) || empty( $amount ) ) { return( null ); }
		// rate
		$currency_rate = $this->currency_rate( array(
			'currency_rate_type' => $conversion_type,
		));
		if( empty( $currency_rate[ $currency_id ] ) ) { return( null ); }
		// calc
		$rate  = $currency_rate[ $currency_id ][ 'rate'  ];
		$value = $currency_rate[ $currency_id ][ 'value' ];
		$result = $amount * $rate / $value;
		// round
		list( $currency_id, $currency ) = $this->get_currency__by_id( array(
			'currency_id' => $currency_id,
		));
		if( empty( $currency_id ) ) { return( null ); }
		$result = $this->_number_float( $result, $currency[ 'minor_units' ] );
		return( $result );
	}

	public function sql_datatime( $timestamp = null ) {
		$tpl = 'Y-m-d H:i:s';
		if( is_int( $timestamp ) ) {
			$result = date( $tpl, $timestamp );
		} else {
			$result = date( $tpl );
		}
		return( $result );
	}

	public function account_create( $options = null ) {
		// options
		$_ = &$options;
		$data = array();
		// user_id
		$value = (int)$_[ 'user_id' ] ?: $this->user_id;
		if( empty( $value ) ) { return( null ); }
		$data[ 'user_id' ] = $value;
		// account_type_id
		$value = (int)$_[ 'account_type_id' ] ?: $this->account_type_id;
		empty( $value ) && ( list( $value ) = $this->get_account_type__by_name() );
		if( empty( $value ) ) { return( null ); }
		$data[ 'account_type_id' ] = $value;
		// currency_id
		$value = (int)$_[ 'currency_id' ] ?: $this->currency_id;
		empty( $value ) && list( $value ) = $this->get_currency__by_id();
		if( empty( $value ) ) { return( null ); }
		$data[ 'currency_id' ] = $value;
		// balance
		$data[ 'balance' ] = 0;
		// date
		$value = $this->sql_datatime();
		$data[ 'datetime_create' ] = $value;
		$data[ 'datetime_update' ] = $value;
		// create
		$id = db()->table( 'payment_account' )->insert( _es( $data ) );
		if( $id < 1 ) { return( null ); }
		$result = $this->get_account__by_id( array( 'account_id' => $id ) );
		return( $result );
	}

	public function account( $options = null ) {
		// by account_id
		$result = $this->get_account__by_id( $options );
		if( !empty( $result ) ) { return( $result ); }
		// options
		$_ = &$options;
		$db = db()->table( 'payment_account' )->order_by( 'account_id' );
		// by user_id
		$value = (int)$_[ 'user_id' ] ?: $this->user_id;
		if( empty( $value ) ) { return( null ); }
		$this->user_id( $value );
		$db->where( 'user_id', '=', _es( $value ) );
		$options[ 'user_id' ] = $value;
		// by account_type_id
		$value = (int)$_[ 'account_type_id' ] ?: $this->account_type_id;
		empty( $value ) && ( list( $value ) = $this->get_account_type__by_name() );
		if( empty( $value ) ) { return( null ); }
		$db->where( 'account_type_id', '=', _es( $value ) );
		$options[ 'account_type_id' ] = $value;
		// by currency_id
		$value = (int)$_[ 'currency_id' ] ?: $this->currency_id;
		empty( $value ) && list( $value ) = $this->get_currency__by_id();
		if( empty( $value ) ) { return( null ); }
		$db->where( 'currency_id', '=', _es( $value ) );
		$options[ 'currency_id' ] = $value;
		// get from db
		$result = $db->get_deep_array( 1 );
		if( empty( $result ) ) {
			// account not exists
			list( $account_id, $account ) = $this->account_create( $options );
		} else {
			$account    = current( $result );
			$account_id = $account[ 'account_id' ];
		}
		$this->account    = $account;
		$this->account_id = (int)$account_id;
		// get currency
		$account_id && $result = $this->get_currency__by_id( array(
			'currency_id' => $account[ 'currency_id' ],
		));
		return( array( $account_id, $account ) );
	}

	public function get_account( $options = null ) {
		$result = $this->account( $options );
		list( $account_id, $account ) = $result;
		if( $account_id <= 0 ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Счет не существует',
			);
		}
		return( $result );
	}

	public function get_balance( $options = null ) {
		$result = $this->get_account( $options );
		list( $account_id, $account ) = $result;
		// need user authentication
		if( $account_id <= 0 ) { return( $result ); }
		$value = $account[ 'balance' ];
		// prepare balance
		$decimals = $this->currency[ 'minor_units' ];
		$balance  = $this->_number_float( $value, $decimals );
		return( array( $balance, $result ) );
	}

	public function type( $options = null ) {
		// get type
		$type       = $this->type;
		$type_index = $this->type_index;
		if( empty( $type ) ) {
			$type = db()->table( 'payment_type' )->where( 'active', 1 )->get_deep_array( 1 );
			foreach ((array)$type as $index => $item ) {
				$id   = (int)$item[ 'type_id' ];
				$name = $item[ 'name' ];
				$type_index[ 'name' ][ $name ][ $id ] = &$type[ $id ];
			}
		}
		// options
		$_       = &$options;
		$exists  = $_[ 'exists'  ];
		$type_id = $_[ 'type_id' ];
		$name    = $_[ 'name'    ];
		// test: exists by type_id
		if( !empty( $exists ) ) {
			$result = !empty( $type[ $exists ] );
		}
		// by type_id
		elseif( !empty( $type_id ) ) {
			$result = array( $type_id => $type[ $type_id ] );
		}
		// by name
		elseif( !empty( $name ) ) {
			$result = $type_index[ 'name' ][ $name ];
		}
		// by default: all
		else {
			$result = $type;
		}
		return( $result );
	}

	public function status( $options = null ) {
		// get status
		$status       = $this->status;
		$status_index = $this->status_index;
		if( empty( $status ) ) {
			$status = db()->table( 'payment_status' )->get_deep_array( 1 );
			if( empty( $status ) ) {
				$status       = null;
				$status_index = null;
				return( $status );
			}
			foreach ((array)$status as $index => $item ) {
				$id   = (int)$item[ 'status_id' ];
				$name = $item[ 'name' ];
				$status_index[ 'name' ][ $name ][ $id ] = &$status[ $id ];
			}
		}
		// options
		$_         = &$options;
		$exists    = $_[ 'exists'  ];
		$status_id = $_[ 'status_id' ];
		$name      = $_[ 'name'    ];
		// test: exists by status_id
		if( !empty( $exists ) ) {
			$result = !empty( $status[ $exists ] );
		}
		// by status_id
		elseif( !empty( $status_id ) ) {
			$result = array( $status_id => $status[ $status_id ] );
		}
		// by name
		elseif( !empty( $name ) ) {
			$result = $status_index[ 'name' ][ $name ];
		}
		// by default: all
		else {
			$result = $status;
		}
		return( $result );
	}

	public function get_status( $options = null ) {
		$_ = &$options;
		$payment_status = $this->status( $_ );
		if( empty( $payment_status ) ) {
			$name = $_[ 'exists' ] ?: $_[ 'status_id' ] ?: $_[ 'name' ];
			$result = array(
				'status'         => false,
				'status_message' => 'Статус не существует: "' . $name . '"',
			);
			return( $result );
		}
		if( count( $payment_status ) == 1 ) {
			$payment_status    = current( $payment_status );
			$payment_status_id = (int)$payment_status[ 'status_id' ];
		}
		return( array( $payment_status_id, $payment_status ) );
	}

	public function provider( $options = null ) {
		// get providers
		$provider       = $this->provider;
		$provider_index = $this->provider_index;
		if( empty( $provider ) ) {
			$provider = db()->table( 'payment_provider' )->where( 'active', 1 )->get_deep_array( 1 );
			// $provider = db()->table( 'payment_provider' )->get_deep_array( 1 );
			if( empty( $provider ) ) {
				$provider       = null;
				$provider_index = null;
				return( $provider );
			}
			foreach ((array)$provider as $index => $item ) {
				$id     = (int)$item[ 'provider_id' ];
				$system = (int)$item[ 'system' ];
				$name   = $item[ 'name' ];
				$provider_index[ 'system' ][ $system ][ $id ] = &$provider[ $id ];
				$provider_index[ 'name'   ][ $name   ][ $id ] = &$provider[ $id ];
			}
		}
		// options
		$_             = &$options;
		$exists      = $_[ 'exists'      ];
		$provider_id = $_[ 'provider_id' ];
		$name        = $_[ 'name'        ];
		$system      = $_[ 'system'      ];
		// test: exists by provider_id
		if( !empty( $exists ) ) {
			$result = !empty( $provider[ $exists ] );
		}
		// by provider_id
		elseif( !empty( $provider_id ) ) {
			$result = array( $provider_id => $provider[ $provider_id ] );
		}
		// by name
		elseif( !empty( $name ) ) {
			$result = $provider_index[ 'name' ][ $name ];
		}
		// by system
		elseif( isset( $system ) ) {
			$result = $provider_index[ 'system' ][ (int)$system ];
		}
		// by default: all, not system
		else {
			$result = $provider_index[ 'system' ][ 0 ];
		}
		return( $result );
	}

	public function provider_currency( $options = null ) {
		// get options '_'
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$result = null;
		if( is_array( $_provider ) ) {
			foreach( $_provider as $id => $item ) {
				$name = $item[ 'name' ];
				$class = 'provider_' . $name;
				$provider = $this->_class( $class );
				$provider && $_provider[ $id ][ '_currency_allow' ] = &$provider->currency_allow;
			}
		}
		return( $result );
	}

	/*
		account_id by user_id
		provider_id
		provider_operation_id { NULL - inner methods }
		direction             { in (приход) }
		type_id               { deposition : пополнение счета (приход) }
		status_id             { in_progress, success, refused }

		example:
			$payment_api = _class( 'payment_api' );
			$options = array(
				'user_id'         => $user_id,
				'amount'          => '10',
				'operation_title' => 'Пополнение счета',
				'operation'       => 'deposition', // or 'payment'
				'provider_name'   => 'system', // or 'administration', etc
			);
			$result = $payment_api->transaction( $options );
	 */
	public function transaction( $options = null ) {
		$_ = &$options;
		$operation = $_[ 'operation' ];
		if( !in_array( $operation, array( 'payment', 'deposition', ) ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неизвестная операция: ' . $operation,
			);
			return( $result );
		}
		$result = $this->$operation( $options );
		return( $result );
	}

	/*
		example:
			$payment_api = _class( 'payment_api' );
			$options = array(
				'user_id'         => $user_id,
				'amount'          => '10',
				'operation_title' => 'Пополнение счета',
				'operation'       => 'deposition', // or 'payment'
			);
			$result = $payment_api->transaction_system( $options );
	 */
	public function transaction_system( $options = null ) {
		$_ = &$options;
		$operation = $_[ 'operation' ] . '_system';
		if( !in_array( $operation, array( 'payment_system', 'deposition_system', ) ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неизвестная операция: ' . $operation,
			);
			return( $result );
		}
		$result = $this->$operation( $options );
		return( $result );
	}

	/*
		example:
			$payment_api = _class( 'payment_api' );
			$options = array(
				'user_id'         => $user_id,
				'amount'          => '10',
				'operation_title' => 'Пополнение счета',
			);
			$result = $payment_api->deposition_system( $options );
	 */
	public function deposition_system( $options = null ) {
		$options[ 'provider_name' ] = 'system';
		$result = $this->deposition( $options );
		return( $result );
	}
	public function deposition( $options = null ) {
		$_ = &$options;
		$_[ 'type_name' ] = __FUNCTION__;
		$_[ 'operation_title' ] = $_[ 'operation_title' ] ?: 'Пополнение счета';
		$result = $this->_operation_check( $_ );
		list( $status, $data, $operation_data ) = $result;
		if( empty( $status ) ) { return( $result ); }
		// update payment operation
		$provider_title = $operation_data[ 'provider' ][ 'title' ];
		$title = $_[ 'operation_title' ] . ' (' . $provider_title . ')';
		$data += array(
			'direction' => 'in',
			'title'     => $title,
		);
		// create operation
		$status = db()->table( 'payment_operation' )->insert( $data );
		if( empty( $status ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Ошибка при создании операции',
			);
			return( $result );
		}
		$operation_id = (int)$status;
		$data[ 'operation_id' ] = $operation_id;
		// try provider operation
		$provider_class = 'provider_' . $operation_data[ 'provider' ][ 'name' ];
		$result = $this->_class( $provider_class, __FUNCTION__, array(
			'provider'       => $operation_data[ 'provider' ],
			'data'           => $data,
			'options'        => $options,
			'operation_data' => $operation_data,
		));
		return( $result );
	}

	/*
		example:
			$payment_api = _class( 'payment_api' );
			$options = array(
				'user_id'         => $user_id,
				'amount'          => '10',
				'operation_title' => 'Пополнение счета',
			);
			$result = $payment_api->payment_system( $options );
	 */
	public function payment_system( $options = null ) {
		$options[ 'provider_name' ] = 'system';
		$result = $this->payment( $options );
		return( $result );
	}
	public function payment( $options = null ) {
		$_ = &$options;
		$_[ 'type_name' ] = __FUNCTION__;
		$_[ 'operation_title' ] = $_[ 'operation_title' ] ?: 'Оплата';
		$result = $this->_operation_check( $_ );
		list( $status, $data, $operation_data ) = $result;
		if( empty( $status ) ) { return( $result ); }
		// update payment operation
		$provider_title = $operation_data[ 'provider' ][ 'title' ];
		$title = $_[ 'operation_title' ] . ' (' . $provider_title . ')';
		$data += array(
			'direction' => 'out',
			'title'     => $title,
		);
		// create operation
		$status = db()->table( 'payment_operation' )->insert( $data );
		if( empty( $status ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Ошибка при создании операции',
			);
			return( $result );
		}
		$operation_id = (int)$status;
		$data[ 'operation_id' ] = $operation_id;
		// try provider operation
		$provider_class = 'provider_' . $operation_data[ 'provider' ][ 'name' ];
		$result = $this->_class( $provider_class, __FUNCTION__, array(
			'provider'       => $operation_data[ 'provider' ],
			'data'           => $data,
			'options'        => $options,
			'operation_data' => $operation_data,
		));
		return( $result );
	}

	public function _operation_check( $options = null ) {
		$result = array();
		$data   = array();
		// options
		$_ = &$options;
		// check user_id
		$user_id = (int)$_[ 'user_id' ] ?: $this->user_id ?: $this->user_id_default;
		if( $user_id <= 0 ) {
			$result = array(
				'status'         => -1,
				'status_message' => 'Требуется авторизация',
			);
			return( $result );
		}
		$data[ 'user_id' ] = $user_id;
		// check type
		$object = array();
		if( !empty( $_[ 'type_name' ] ) ) {
			$object[ 'name' ] = $_[ 'type_name' ];
		}
		elseif( !empty( $_[ 'type_id' ] ) ) {
			$object[ 'type_id' ] = (int)$_[ 'type_id' ];
		}
		$object = $this->type( $object );
		if( empty( $object ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Данная операция недоступна',
			);
			return( $result );
		}
		$type    = current( $object );
		$type_id = (int)$type[ 'type_id' ];
		$data[ 'type' ] = $type;
		// check status
		$status = $this->status( array( 'name' => 'in_progress' ) );
		if( empty( $status ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Статус не существует: "in_progress"',
			);
			return( $result );
		}
		$status    = current( $status );
		$status_id = (int)$status[ 'status_id' ];
		$data[ 'status' ] = $status;
		// check account
		$account_result = $this->get_account( $options );
		if( empty( $account_result ) ) { return( $account_result ); }
		list( $account_id, $account ) = $account_result;
		// check amount
		$decimals = $this->currency[ 'minor_units' ];
		$amount   = $this->_number_float( $_[ 'amount' ], $decimals );
		if( $amount <= 0 ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Сумма должна быть больше нуля',
			);
			return( $result );
		}
		$sql_amount = $this->_number_mysql( $amount );
		$data[ 'sql_amount' ] = $sql_amount;
		// prepare provider
		$object = array();
		if( !empty( $_[ 'provider_name' ] ) ) {
			$object[ 'name' ] = $_[ 'provider_name' ];
		}
		elseif( !empty( $_[ 'provider_id' ] ) ) {
			$object[ 'provider_id' ] = (int)$_[ 'provider_id' ];
		}
		$object = $this->provider( $object );
		if( empty( $object ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неизвестный провайдер',
			);
			return( $result );
		}
		$provider    = current( $object );
		$provider_id = (int)$provider[ 'provider_id' ];
		$data[ 'provider' ] = $provider;
		// prepare result
		$sql_datetime = $this->sql_datatime();
		$data[ 'sql_datetime' ] = $sql_datetime;
		$result = array(
			'account_id'            => $account_id,
			'provider_id'           => $provider_id,
			'provider_operation_id' => null,
			'status_id'             => $status_id, // in_progress
			'type_id'               => $type_id,   // deposition, payment, etc
			'amount'                => $sql_amount,
			'datetime_start'        => $sql_datetime,
			'datetime_update'       => $sql_datetime,
		);
		return( array( true, $result, $data ) );
	}

	public function operation( $options = null ) {
		$_ = &$options;
		$is_sql         = $_[ 'sql'          ];
		$is_no_limit    = $_[ 'no_limit'     ];
		$is_no_order_by = $_[ 'no_order_by'  ];
		// by operation_id
		$operation_id   = (int)$_[ 'operation_id' ];
		$db = db()->table( 'payment_operation' );
		if( $operation_id > 0 ) {
			$db->where( 'operation_id', $operation_id );
			// sql only or fetch
			if( $is_sql ) {
				$result = $db->sql();
			} else {
				$result = $db->get();
			}
			return( $result );
		}
		// by account
		$account_result = $this->get_account( $options );
		if( empty( $account_result ) ) { return( $account_result ); }
		list( $account_id, $account ) = $account_result;
		$db->where( 'account_id', $account_id );
		if( !$is_no_order_by ) {
			$db->order_by( 'datetime_update', 'DESC' );
		}
		// limit
		if( !$is_no_limit ) {
			$limit = (int)$_[ 'limit' ] ?: $this->OPERATION_LIMIT;
			$limit_from = $_[ 'limit_from' ];
			if( empty( $limit_from ) ) {
				$page = (int)$_[ 'page' ];
				$page = $page < 1 ? 1 : $page;
				$limit_from = ( $page - 1 ) * $limit;
			}
			$db->limit( $limit, $limit_from );
		}
		// sql only or fetch
		if( $is_sql ) {
			$result = $db->sql();
		} else {
			$result = $db->all();
		}
		return( $result );
	}

	// simple route: class__sub_class->method
	public function _class( $class, $method = null, $options = null ) {
		$_path  = $this->_class_path;
		$_class_name = __CLASS__ . '__' . $class;
		$_class = _class_safe( $_class_name, $_path );
		$status = $_class instanceof $_class_name;
		if( !$status ) { return( null ); }
		$status = method_exists( $_class, $method );
		if( !$status ) { return( $_class ); }
		$result = $_class->{ $method }( $options );
		return( $result );
	}

	// helpers
	function _number_round( $float = 0, $precision = null , $mode = null ) {
		$precision = $precision ?: $this->DECIMALS;
		$mode      = $mode      ?: $this->ROUND_MODE;
		$result = round( $float, $precision, $mode );
		$result = $result == 0 ? 0.0 : $result; // fix php round bug: -0.00
		return( $result );
	}

	function _number_float( $float = 0, $decimals = null ) {
		return( (float)$this->_number_format( $float, $decimals, '.', '' ) );
	}

	function _number_mysql( $float = 0, $decimals = null ) {
		return( $this->_number_format( $float, $decimals, '.', '' ) );
	}

	function _number_from_mysql( $float = 0 ) {
		return( $this->_number_format( $float ) );
	}

	function _number_format( $float = 0, $decimals = null, $decimal_point = null, $thousands_separator = null ) {
		$locale_info = localeconv(); $_decimal_point = $locale_info[ 'decimal_point' ];
		$float = (float)str_replace( $_decimal_point, '.', $float );
		!isset( $decimals            ) && $decimals            = $this->DECIMALS            ?: 2;
		!isset( $decimal_point       ) && $decimal_point       = $this->DECIMAL_POINT       ?: ',';
		!isset( $thousands_separator ) && $thousands_separator = $this->THOUSANDS_SEPARATOR ?: '&nbsp;';
		if( empty( $this->FORCE_DECIMALS ) && (int)$float == $float  ) { $decimals = 0; }
		$float = number_format( $float, $decimals, $decimal_point, '`' );
		$float = str_replace( '`', $thousands_separator, $float );
		return( $float );
	}

	public function dump( $options = null ) {
		static $is_first = true;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$ts = microtime( true );
		$file = $_file ?: sprintf( '/tmp/payment_api_dump-%s.txt', date( 'Y-m-d_H-i-s', $ts ) );
		ini_set( 'html_errors', 0 );
		$result = '';
		if( $is_first ) {
			$result .= 'SERVER:' . PHP_EOL . var_export( $_SERVER, true ) . PHP_EOL . PHP_EOL;
			$result .= 'GET:'    . PHP_EOL . var_export( $_GET,    true ) . PHP_EOL . PHP_EOL;
			$result .= 'POST:'   . PHP_EOL . var_export( $_POST,   true ) . PHP_EOL . PHP_EOL;
		}
		$_var && $result .= 'VAR:' . PHP_EOL . var_export( $_var, true ) . PHP_EOL . PHP_EOL;
		!empty( $result ) && file_put_contents( $file, $result, FILE_APPEND );
		$is_first = false;
	}

}
