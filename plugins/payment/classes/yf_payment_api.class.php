<?php

if( !function_exists( 'array_replace_recursive' ) ) {
	trigger_error( 'Not exists function "array_replace_recursive ( PHP 5 >= 5.3.0 )"', E_USER_ERROR );
}

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

		payment_currency_rate
			currency_rate_id
			datetime
			from
			to
			from_value
			to_value

		payment_operation
			operation_id
			account_id
			provider_id
				{ system, administration, webmoney, privat24 }
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
			options

		payment_provider
				{ system, administration, webmoney, privat24 }
			provider_id
			name
			title
			options
				{ icon, image }
			system
			active
			order

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
			active

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
	public $currencies          = null;
	public $currencies_default  = array(
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
			'short'       => 'доллар',
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

	public $CONFIG              = null;
	public $OPERATION_LIMIT     = 10;
	public $BALANCE_LIMIT_LOWER = 0;

	public $MAIL_COPY_TO = array(
		'all' => array(
			// 'all'   => 'larv.job+payment@gmail.com',
			// 'payin' => array(
				// 'larv.job+payin@gmail.com',
			// ),
			// 'payout' => array(
				// 'larv.job+payout@gmail.com',
			// ),
			// 'success' => array(
				// 'larv.job+payment.success@gmail.com',
			// ),
			'refused' => array(
				'larv.job+payment.refused@gmail.com',
			),
			'request' => array(
				'larv.job+payment.request@gmail.com',
			),
		),
		// 'payin' => array(
			// 'all' => array(
				// 'larv.job+payin@gmail.com',
			// ),
			// 'success' => array(
				// 'larv.job+payin.success@gmail.com',
			// ),
			// 'refused' => array(
				// 'larv.job+payin.refused@gmail.com',
			// ),
		// ),
		// 'payout' => array(
			// 'all' => array(
				// 'larv.job+payin@gmail.com',
			// ),
			// 'success' => array(
				// 'larv.job+payin.success@gmail.com',
			// ),
			// 'refused' => array(
				// 'larv.job+payin.refused@gmail.com',
			// ),
			// 'request' => array(
				// 'larv.job+payin.request@gmail.com',
			// ),
		// ),
	);

	public function _init() {
		$this->config();
		$this->user_id_default = (int)main()->USER_ID;
		$this->user_id( $this->user_id_default );
	}

	public function config( $options = null ) {
		!empty( (array)$options ) && $this->CONFIG = (array)$options;
		$config = &$this->CONFIG;
		if( is_array( $config[ 'currencies' ] ) ) {
			$this->currencies = array_replace_recursive( $this->currencies_default, $config[ 'currencies' ] );
		}
	}

	public function user_id( $value = -1 ) {
		$object = &$this->user_id;
		if( $this->check_user_id( $value ) && $value !== -1 ) { $object = $value; }
		return( $object );
	}

	public function check_user_id( $value = null ) {
		if( empty( $value ) || $value != (int)$value || $value < 0 ) { return( null ); }
		return( $value );
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
			$account_type    = reset( $result );
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
		$_ = (array)$options;
		$_[ 'currency_rate_type' ] = 'buy';
		$result = $this->currency_rate( $_ );
		return( $result );
	}

	public function currency_rate__sell( $options = null ) {
		$_ = (array)$options;
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
			$sql = db()->table( 'payment_currency_rate' )
				->where( $target, '=', $currency_id )
				// ->group_by( $source )
				->order_by( 'datetime', 'DESC' )
				->sql();
			$result = db()->query_fetch_all( 'SELECT * FROM ( '. $sql .' ) as cr GROUP BY '. db()->escape_key( $source ) );
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

	public function fee( $amount, $fee ) {
		$result = $amount + $amount * ( $fee / 100 );
		return( $result );
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

	public function sql_datetime( $timestamp = null ) {
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
		$value = $this->_default( array(
			$_[ 'user_id' ],
			$this->user_id,
		));
		$value = $this->check_user_id( $value );
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
		$value = $this->sql_datetime();
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
		// user_id
		$value = $this->_default( array(
			$_[ 'user_id' ],
			$this->user_id,
		));
		$value = $this->check_user_id( $value );
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
		$value = $_[ 'currency_id' ] ?: $this->currency_id;
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
			$account    = reset( $result );
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

	public function get_type( $options = null ) {
		$_ = &$options;
		$object = $this->type( $options );
		if( empty( $object ) ) {
			$name = $_[ 'exists' ] ?: $_[ 'type_id' ] ?: $_[ 'name' ];
			$result = array(
				'status'         => false,
				'status_message' => 'Тип платежей не существует: "' . $name . '"',
			);
			return( $result );
		}
		if( count( $object ) == 1 ) {
			$object    = reset( $object );
			$object_id = (int)$object[ 'type_id' ];
			$result    = array( $object_id, $object );
		} else {
			$result = $object;
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
		$payment_status = $this->status( $options );
		if( empty( $payment_status ) ) {
			$name = $_[ 'exists' ] ?: $_[ 'status_id' ] ?: $_[ 'name' ];
			$result = array(
				'status'         => false,
				'status_message' => 'Статус не существует: "' . $name . '"',
			);
			return( $result );
		}
		if( count( $payment_status ) == 1 ) {
			$payment_status    = reset( $payment_status );
			$payment_status_id = (int)$payment_status[ 'status_id' ];
			$result = array( $payment_status_id, $payment_status );
		} else {
			$result = $payment_status;
		}
		return( $result );
	}

	public function provider_class( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$result = null;
		if( !empty( $_provider_name ) ) {
			$class_name = 'provider_' . $_provider_name;
			$class = $this->_class( $class_name );
			if( !( $class && $provider_class->ENABLE ) ) { $result = $class; }
		}
		return( $result );
	}

	public function provider( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// get providers
		$provider       = $this->provider;
		$provider_index = $this->provider_index;
		if( empty( $provider ) ) {
			$is_admin = main()->ADMIN_ID > 0;
			$active = $is_admin || $_is_service ? 1 : 2;
			$provider = db()->table( 'payment_provider' )
				->where( 'active', '>=', $active )
				->order_by( 'order' )
				->get_deep_array( 1 );
			if( empty( $provider ) ) {
				$provider       = null;
				$provider_index = null;
				return( $provider );
			}
			foreach( (array)$provider as $index => $item ) {
				$name   = $item[ 'name' ];
				$id     = (int)$item[ 'provider_id' ];
				$provider_index[ 'all'    ][ $id ] = &$provider[ $id ];
				$class = 'provider_' . $name;
				$provider_class = $this->_class( $class );
				if( !( $provider_class && $provider_class->ENABLE ) ) {
					unset( $provider[ $index ] );
					continue;
				}
				$system = (int)$item[ 'system' ];
				$provider_index[ 'system' ][ $system ][ $id ] = &$provider[ $id ];
				$provider_index[ 'name'   ][ $name   ][ $id ] = &$provider[ $id ];
			}
		}
		/**
		 * options
		 * $_all
		 * $_exists
		 * $_provider_id
		 * $_name
		 * $_system
		 */
		// test: exists by provider_id
		if( !empty( $_exists ) ) {
			$result = !empty( $provider[ $exists ] );
		}
		// all
		elseif( !empty( $_all ) ) {
			$result = $provider_index[ 'all' ];
		}
		// by provider_id
		elseif( isset( $_provider_id ) ) {
			$provider[ $_provider_id ] && $result = array( $_provider_id => $provider[ $_provider_id ] );
		}
		// by name
		elseif( !empty( $_name ) ) {
			$result = $provider_index[ 'name' ][ $_name ];
		}
		// by system
		elseif( isset( $_system ) ) {
			$result = $provider_index[ 'system' ][ (int)$_system ];
		}
		// by default: all, not system
		else {
			$result = $provider_index[ 'system' ][ 0 ];
		}
		if( is_array( $result ) ) {
			foreach( $result as $index => $item ) {
				$_options = &$result[ $index ][ 'options' ];
				$_options && $_options = (array)json_decode( $_options, true );
			}
		}
		return( $result );
	}

	public function provider_options( &$provider, $options = null ) {
		if( !isset( $options ) || !is_array( $provider ) ) { return( false ); }
		foreach( $provider as $id => $item ) {
			$name = $item[ 'name' ];
			$class = 'provider_' . $name;
			$provider_class = $this->_class( $class );
			if( empty( $provider_class ) ) { continue; }
			foreach( $options as $item ) {
				$provider[ $id ][ '_' . $item ] = &$provider_class->{$item};
			}
		}
		return( true );
	}

	/*
		account_id by user_id
		provider_id
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
	public function deposition_user( $options = null ) {
		$options[ 'user_mode' ] = true;
		$result = $this->deposition( $options );
		return( $result );
	}
	public function deposition( $options = null ) {
		$_ = &$options;
		$_[ 'type_name' ] = __FUNCTION__;
		$_[ 'operation_title' ] = $_[ 'operation_title' ] ?: 'Пополнение счета';
		$result = $this->_operation_check( $options );
		list( $status, $data, $operation_data ) = $result;
		if( empty( $status ) ) { return( $result ); }
		// update payment operation
		// $provider_title = $operation_data[ 'provider' ][ 'title' ];
		// $title = $_[ 'operation_title' ] . ' (' . $provider_title . ')';
		$title = $_[ 'operation_title' ];
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
		$result[ 'operation_id' ] = $operation_id;
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
	public function payment_user( $options = null ) {
		$options[ 'user_mode' ] = true;
		$result = $this->payment( $options );
		return( $result );
	}
	public function payment( $options = null ) {
		$_ = &$options;
		$_[ 'type_name' ] = __FUNCTION__;
		$_[ 'operation_title' ] = $_[ 'operation_title' ] ?: 'Оплата';
		$result = $this->_operation_check( $options );
		list( $status, $data, $operation_data ) = $result;
		if( empty( $status ) ) { return( $result ); }
		// update payment operation
		// $provider_title = $operation_data[ 'provider' ][ 'title' ];
		// $title = $_[ 'operation_title' ] . ' (' . $provider_title . ')';
		$title = $_[ 'operation_title' ];
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
		$result[ 'operation_id' ] = $operation_id;
		return( $result );
	}

	public function _operation_check( $options = null ) {
		$result = array();
		$data   = array();
		// options
		$_ = &$options;
		// check user_id
		$value = $this->_default( array(
			$_[ 'user_id' ],
			$this->user_id,
		));
		$value = $this->check_user_id( $value );
		if( empty( $value ) ) {
			$result = array(
				'status'         => -1,
				'status_message' => 'Требуется авторизация',
			);
			return( $result );
		}
		$data[ 'user_id' ] = $value;
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
		$type    = reset( $object );
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
		$status    = reset( $status );
		$status_id = (int)$status[ 'status_id' ];
		$data[ 'status' ] = $status;
		// check account
		$_options = $options;
		unset( $_options[ 'currency_id' ] );
		list( $balance, $account_result ) = $this->get_balance( $_options );
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
		// check balance limit lower
		$balance_limit_lower = $this->_default( array(
			$_[ 'balance_limit_lower' ],
			$account[ 'options' ][ 'balance_limit_lower' ],
			$this->BALANCE_LIMIT_LOWER,
			0,
		));
		$balance_limit_lower = $this->_number_float( $balance_limit_lower );
		if( $type[ 'name' ] == 'payment' && ( $balance - $amount < $balance_limit_lower ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Недостаточно средств на счету',
			);
			return( $result );
		}
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
		$provider = reset( $object );
		if( $_[ 'user_mode' ] && (bool)$provider[ 'system' ] ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неизвестный провайдер',
			);
			return( $result );
		}
		$provider_id = (int)$provider[ 'provider_id' ];
		$data[ 'provider' ] = $provider;
		// provider class
		$object = $this->provider_class( array(
			'provider_name' => $provider[ 'name' ],
		));
		if( empty( $object ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Неизвестный класс провайдера',
			);
			return( $result );
		}
		// $data[ 'provider_class' ] = $object;
		// provider validate
		$result = $object->validate( $options );
		if( empty( $result[ 'status' ] ) ) { return( $result ); }
		// prepare result
		$sql_datetime = $this->sql_datetime();
		$data[ 'sql_datetime' ] = $sql_datetime;
		$result = array(
			'account_id'            => $account_id,
			'provider_id'           => $provider_id,
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
		$is_no_count    = $_[ 'no_count'     ];
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
				$_options = &$result[ 'options' ];
				isset( $_options ) && $_options = (array)json_decode( $_options, true );
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
		$count = null;
		if( !$is_no_count ) {
			$count = $db->order_by()->limit( null )->count( '*', $is_sql );
		}
		if( is_array( $result ) ) {
			$datetime_key = array( 'start', 'finish', 'update', );
			foreach( $result as $index => &$item ) {
				$_options = &$item[ 'options' ];
				$_options && $_options = (array)json_decode( $_options, true );
				foreach( $datetime_key as $key ) {
					$item[ '_ts_' . $key ] = strtotime( $item[ 'datetime_' . $key ] );
				}
			}
		}
		return( array( $result, $count ) );
	}

	public function balance_update( $data, $options = null ) {
		$result = $this->_object_update( 'account', $data, $options );
		return( $result );
	}

	public function operation_update( $data, $options = null ) {
		$result = $this->_object_update( 'operation', $data, $options );
		return( $result );
	}

	protected function _object_update( $name, $data, $options = null ) {
		if( empty( $name ) ) { return( null ); }
		// import options
		is_array( $data    ) && extract( $data,    EXTR_PREFIX_ALL | EXTR_REFS, ''  );
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '_' );
		// check object id
		$status         = false;
		$status_message = '';
		$result = array(
			'status'         => &$status,
			'status_message' => &$status_message,
		);
		$id_name = $name . '_id';
		$id      = (int)${ '_' . $id_name };
		if( $id < 1 ) {
			$status_message = 'Ошибка при обновлении "' . $name . '": ' . $id;
			return( $result );
		}
		$table = 'payment_' . $name;
		// extend options
		if( is_array( $_options ) ) {
			// get operation
			$operation = db()->table( $table )
				->where( $id_name, $id )
				->get();
			$operation_options = (array)json_decode( $operation[ 'options' ], true );
			$json_options = json_encode( array_merge_recursive(
				$operation_options,
				$_options
			));
			$json_options && $_options = $json_options;
		}
		// remove id by update
		unset( $data[ $id_name ] );
		// escape sql data
		$sql_data = $data;
		$is_escape = isset( $__is_escape ) ? (bool)$__is_escape : true;
		$is_escape && $sql_data = _es( $data );
		// query
		$sql_status = db()->table( $table )
			->where( $id_name, $id )
			->update( $sql_data, array( 'escape' => $__is_escape ) );
		// status
		if( empty( $sql_status ) ) {
			$status_message = 'Ошибка при обновлении "' . $name . '": ' . $id;
			return( $result );
		}
		$status         = true;
		$status_message = 'Выполнено обновление "' . t( $name ) . '"';
		return( $result );
	}

	public function mail( $options = null ) {
		$result = true;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// tpl by type, status
		if( empty( $_tpl ) && !( empty( $_type ) && empty( $_status ) ) ) {
			$_tpl = $_type .'_'. $_status;
		}
		if( empty( $_tpl ) ) { return( null ); }
		if( empty( $_type ) || empty( $_status ) ) {
			list( $type, $status ) = @explode( '_', $_tpl );
			if( !@$_type && @$type ) {
				$_type = $type;
				$options[ 'type' ] = $_type;
			}
			if( !@$_status && @$status ) {
				$_status = $status;
				$options[ 'status' ] = $_status;
			}
		}
		// var
		$payment_api = $this;
		$mail_class  = _class( 'email' );
		// check user
		if( !empty( $_user_id ) ) {
			$user = user( $_user_id );
			// check email, validate email
			if( !@$_force && (
					empty( $user )
					|| empty( $user[ 'email' ] )
					|| $user[ 'email' ] != $user[ 'email_validated' ]
				)
			) { return( null ); }
			$mail_to   = $user[ 'email' ];
			$mail_name = $user[ 'name'  ];
		}
		// check data
		$data = array();
		if( !empty( $_data ) ) {
			// import data
			is_array( $_data ) && extract( $_data, EXTR_PREFIX_ALL | EXTR_REFS, '_' );
			// amount
			if( !empty( $__amount ) ) {
				$__amount = $payment_api->money_text( $__amount );
			}
			$data = $_data;
		}
		// url
		$url = array(
			'user_payments' => url_user( '/payments' ),
		);
		// mail
		$mail_admin_to   = $mail_class->ADMIN_EMAIL;
		$mail_admin_name = $mail_class->ADMIN_NAME;
		$mail = array(
			'support_mail' => $mail_admin_to,
			'support_name' => $mail_admin_name,
		);
		// compile
		$data = array_replace_recursive( $data, array(
			'url'  => $url,
			'mail' => $mail,
		));
		$is_admin = !empty( $_is_admin );
		$admin    = !empty( $_admin    );
		// user
		if( !$is_admin ) {
			$result &= $mail_class->_send_email_safe( $mail_to, $mail_name, $_tpl, $data );
			// mail copy
			!$admin && $this->mail_copy( array( 'tpl' => $_tpl, 'type' => $_type, 'status' => $_status, 'subject' => @$_subject, 'data' => $data ) );
		}
		// admin
		if( $admin || $is_admin ) {
			$url = array(
				'user_manage' => $this->url_admin( array(
					'object' => 'members',
					'action' => 'edit',
					'id'     => $_user_id,
				)),
				'user_balance' => $this->url_admin( array(
					'object'  => 'manage_payment',
					'action'  => 'balance',
					'user_id' => $_user_id,
				)),
			);
			// compile
			$data = array_replace_recursive( $data, array(
				'url'        => $url,
				'user_title' => $user[ 'name' ] . ' (id: '. $_user_id .')'
			));
			$tpl = $_tpl . '_admin';
			$result &= $mail_class->_send_email_safe( $mail_admin_to, $mail_admin_name, $tpl, $data );
			// mail copy
			$this->mail_copy( array( 'tpl' => $_tpl, 'type' => $_type, 'status' => $_status, 'subject' => @$_subject, 'data' => $data ) );
		}
		return( $result );
	}

	public function mail_copy_find( &$mails, $options = null ) {
		if( empty( $this->MAIL_COPY_TO ) ) { return; }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$mail_copy = &$this->MAIL_COPY_TO;
		// add: all, type, status
		if( @$mail_copy[ 'all' ] ) {
			$mail_ref = &$mail_copy[ 'all' ];
			foreach( @array( 'all', $_type, $_status ) as $key ) {
				foreach( (array)@$mail_ref[ $key ] as $value ) {
					$mails[ $value ] = $value;
				}
			}
		}
		// add by type: all, status
		if( @$mail_copy[ $_type ] ) {
			$mail_ref = &$mail_copy[ $_type ];
			foreach( @array( 'all', $_status ) as $key ) {
				foreach( (array)@$mail_ref[ $key ] as $value ) {
					$mails[ $value ] = $value;
				}
			}
		}
	}

	public function mail_copy( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_tpl ) || empty( $_data ) ) { return( null ); }
		// prepare admin mail
		$mails = array();
		$this->mail_copy_find( $mails, $options );
		// processing
		$result = true;
		if( is_array( $mails ) ) {
			$mail_class = _class( 'email' );
			$override = array();
			$_subject && $override[ 'subject' ] = $_subject;
			$name = 'Payment admin';
			$instant_send = true;
			foreach( $mails as $mail ) {
				$result &= $mail_class->_send_email_safe( $mail, $name, $_tpl, $_data, $instant_send, $override );
			}
		}
		return( $result );
	}

	public function url_admin( $options = null ) {
		if( empty( $options ) ) { return( null ); }
		$result = url_admin( $options );
		if( substr( $result, 0, 2 ) == '//' ) {
			$result = str_replace( '//', 'http://', $result );
		}
		return( $result );
	}

	public function money_text( $options = null ) {
		!is_array( $options ) && $options = array(
			'value' => $options,
		);
		$options += array(
			'sign'   => true,
		);
		$result = $this->money_format( $options );
		return( $result );
	}

	public function money_html( $options = null ) {
		!is_array( $options ) && $options = array(
			'value' => $options,
		);
		$options += array(
			'format' => 'html',
			'sign'   => true,
		);
		$result = $this->money_format( $options );
		return( $result );
	}

	public function money_format( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// currency
		list( $currency_id, $currency ) = $this->get_currency__by_id( array( 'currency_id' => $_currency_id ) );
		$decimals = $currency[ 'minor_units' ];
		$sign     = $currency[ 'sign'        ];
		switch( $_format ) {
			case 'html':
				$thousands_separator = '&nbsp;';
			default:
				$thousands_separator = ' ';
				break;
		}
		// format number
		isset(     $_decimals            ) && $decimals            = $_decimals;
		is_string( $_decimal_point       ) && $decimal_point       = $_decimal_point;
		is_string( $_thousands_separator ) && $thousands_separator = $_thousands_separator;
		$value  = $this->_number_round( $_value, $decimals );
		$value = $this->_number_format( $value, $decimals, $decimal_point, $thousands_separator );
		// decoration
		$nbsp = '';
		switch( $_format ) {
			case 'html':
				$sign  = '<span class="currency">'. $sign  .'</span>';
				$value = '<span class="money">'.    $value .'</span>';
				$nbsp = '&nbsp;';
				$thousands_separator = '&nbsp;';
			default:
				$nbsp = ' ';
				$thousands_separator = ' ';
				break;
		}
		// render
		$result = $value;
		$_nbsp && $result .= $nbsp;
		$_sign && $result .= $sign;
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

	public function _default( $list = null ) {
		$result = null;
		foreach( $list as $index => $value ) {
			if( !is_null( $value ) ) {
				$result = &$list[ $index ];
				break;
			}
		}
		return( $result );
	}

	public function dump( $options = null ) {
		static $is_first = true;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$ts = microtime( true );
		$file = $_file ?: sprintf( '/tmp/payment_api_dump-%s.txt', date( 'Y-m-d_H-i-s', $ts ) );
		$html_errors = ini_get( 'html_errors' );
		ini_set( 'html_errors', 0 );
		$result = '';
		if( $is_first ) {
			$result .= 'SERVER:' . PHP_EOL . var_export( $_SERVER, true ) . PHP_EOL . PHP_EOL;
			$result .= 'GET:'    . PHP_EOL . var_export( $_GET,    true ) . PHP_EOL . PHP_EOL;
			$result .= 'POST:'   . PHP_EOL . var_export( $_POST,   true ) . PHP_EOL . PHP_EOL;
		}
		isset( $_var ) && $result .= 'VAR:' . PHP_EOL . var_export( $_var, true ) . PHP_EOL . PHP_EOL;
		!empty( $result ) && file_put_contents( $file, $result, FILE_APPEND );
		$is_first = false;
		ini_set( 'html_errors', $html_errors );
		return( $result );
	}

}
