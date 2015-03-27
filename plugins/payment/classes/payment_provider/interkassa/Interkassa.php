<?php

// https://www.interkassa.com/

class Interkassa {

	protected $_currency_allow = array( 'USD', 'EUR', 'UAH', 'RUB', );

	protected $_signature_allow = array(
		'ik_co_id',    // * Checkout ID, /^[\w\-]{1,36}$/D 4f269503a1da92c807000002 Идентификатор кассы. Обязательный параметр. См.настройки кассы.
		'ik_pm_no',    // Payment No., /^[\w\-]{1,32}$/D 14533;ID_4233 Номер платежа. Сохраняется в биллинге Интеркассы.Позволяет идентифицировать платеж в системе, а также связать с заказами в вашем биллинге. Проверяетсяуникальность, если в настройках кассы установленаданная опция. Опциональный параметр.
		'ik_cur',      // Currency, /^.{3}$/ USD; EUR; UAH Валюта платежа. Обязательный параметр, если к кассе подключено больше чем одна валюта. См. настройки кассы.
		'ik_am',       // * Amount, /^(?=(0|\.|,)*[1-9])\d{0,8}(([\.|,])|(?:[\.|,]\d{1,2}))?$/ 1.43; 43 Сумма платежа. Обязательный параметр.
		'ik_am_ed',    // Amount Edit, /0|1/ 0; 1 Редактирование суммы платежа. Если включено, позволяет клиенту самостоятельно указать сумму платежа. Опциональный параметр. По умолчанию используется свойство кассы "Редактирование суммы платежа" (Payment Amount Edit).
		'ik_am_t',     // Amount Type, /invoice|payway/ invoice; payway Тип суммы платежа. Позволяет указать стратегию расчета суммы платежа кассы и платежной системы. В зависимости от нее расчет идет по той или иной сумме.  Если указан тип суммы "invoice", то сумма платежа в платежной системе рассчитывается от суммы платежа кассы. Если же тип суммы "payway" - то наоборот. По умолчанию - "invoice".
		'ik_desc',     // Description, /^.{0,255}$/ Payment Description; Cool stuff Описание платежа. Опциональный параметр.
		'ik_exp',      // Expired, /^.{0,30}$/ 2011-05-01; 2011-10-01 20:50:33 Срок истечения платежа. Не позволяет клиенту оплатить платеж позже указанного срока. Если же он совершил оплату, то средства зачисляются ему на лицевой счет в системе Интеркасса. Параметр используется если платеж привязан к заказу, который быстро теряет свою актуальность с истечением времени. Например: онлайн бронирование.  Опциональный параметр.
		'ik_ltm',      // Lifetime, /^[\d]{1,10}$/ 3600; 86400 Время жизни платежа. Указывает в секундах срок истечения платежа после его создания. Не используется, если установлен срок истечения платежа (ik_exp). Опциональный параметр. По умолчанию используется свойство кассы "Время жизни платежа" (Payment Lifetime).
		'ik_pw_on',    // Payway On, /^[\w;,\.]{0,512}$/ webmoney; w1_merchant_ usd Включенные способы оплаты. Позволяет указывать доступные способы оплаты для клиента.  Опциональный параметр.
		'ik_pw_off',   // Payway Off, /^[\w;,\.]{0,512}$/ webmoney_me rchant Отключенные способы оплаты. Позволяет указывать недоступные способы оплаты для клиента.  Опциональный параметр.
		'ik_pw_via',   // Payway Via, /^[\w]{0,62}$/ visa_liqpay_mer chant_usd Выбранный способ оплаты. Позволяет указать точный способ оплаты для клиента. Параметр работает только с параметром действия (ik_act) установленного в "process" или "payway". см. действие (ik_act).  Опциональный параметр.
		// 'ik_sign',     // Signature, /^.{0,128}$/ oVAOevI3mWrcvrjB4j/ySg== Цифровая подпись. См. формирования цифровой подписи. Обязательный параметр, если в настройках кассы установлен параметр "Требуется ли цифровая подпись от кассы" (Sign Co Required).
		'ik_loc',      // Locale, /^.{5}$/' ru; de_us Локаль. Позволяет явно указать язык и регион установленные для клиента. Формируется по шаблону: [language[_territory]. По умолчанию определяется автоматически.
		'ik_enc',      // Encoding, /^.{0,16}$/ utf-8; ISO-8859- 1; cp1251 Кодировка. По умолчанию используется кодировка UTF- 8.
		'ik_cli',      // User, /^.{0,64}$/ usermail@gmail .com; +380501234567 Контактная информация клиента. Принимает значение как email или номер мобильного телефона.  Опциональный параметр.
		'ik_ia_u',     // Interaction Url
		'ik_ia_m',     // Interaction Method, /get|post/i
		'ik_suc_u',    // Success Url
		'ik_suc_m',    // Success Method, /get|post/i
		'ik_pnd_u',    // Pending Url
		'ik_pnd_m',    // Pending Method, /get|post/i
		'ik_fal_u',    // Fail Url
		'ik_fal_m',    // Fail Method, /get|post/i
		'ik_act',      // process — обработать; payways — способы оплаты; payways_calc — расчет способов оплаты; payway — платежное направление.
		'ik_int',      // Interface, /web|json/ web; json Интерфейс. Позволяет указать формат интерфейса SCI как "web" или "json". По умолчанию "web".
		// ********************** response
		'ik_inv_id',   // Invoice Id, 12345; 5632156; Идентификатор платежа.
		'ik_co_prs_id', // Checkout Purse Id, 307447812424; Идентификатор кошелька кассы.
		'ik_trn_id',   // Transaction Id, 14533; ID_4233; Идентификатор транзакции.
		'ik_inv_crt',  // Invoice Created, 2013-03-17 17:30:33; Время создания платежа.
		'ik_inv_prc',  // Invoice Processed, 2013-03-20 15:46:58; Время проведения платежа.
		'ik_inv_st',   // Invoice State, success; fail Состояние платежа. Возможные значения: Дополнительные new — новый, waitAccept —ожидает оплаты, process —обрабатывается, success —успешно проведен, canceled—отменен, fail—не проведен.
		'ik_ps_price', // Paysystem Price, 25.32; Сумма платежа в платежной системе.
		'ik_co_rfn',   // Checkout Refund, 24.94; Сумма зачисления на счет кассы.
	);
	protected $_signature_allow_x = array(
		'_ik_x_', // not in manual, but allow %)
		'ik_x_', // ik_x_[name] - X Prefix, ik_x_field1 = somedata; ik_x_baggage1 = code123; Префикс дополнительных полей. Позволяет передавать дополнительные поля на SCI, после чего эти параметры включаются в данные уведомления о совершенном платеже на страницу взаимодействия.  Для создания вы можете воспользоваться нашим генератором платежной формы
	);

	private $_hash_method_allow = array(
		'md5',
		'sha256',
	);

	private $_key_public       = null;
	private $_key_private      = null;
	private $_key_private_test = null;
	private $_hash_method      = null;
	private $_test_mode        = null;

	public function __construct( $key_public, $key_private, $key_private_test, $hash_method = 'md5', $test_mode ) {
		if( empty( $key_public ) ) {
			throw new InvalidArgumentException( 'key_public (ik_co_id) is empty' );
		}
		if( empty( $key_private ) ) {
			throw new InvalidArgumentException( 'key_private is empty' );
		}
		if( empty( $key_private_test ) ) {
			throw new InvalidArgumentException( 'key_private_test is empty' );
		}
		if( !$this->hash_method( $hash_method ) ) {
			throw new InvalidArgumentException( 'hash method allow is not allow' );
		}
		$this->_key_public       = $key_public;
		$this->_key_private      = $key_private;
		$this->_key_private_test = $key_private_test;
		$this->_test_mode        = $test_mode;
	}

	public function key( $name = 'public', $value = null ) {
		if( !in_array( $name, array( 'public', 'private', 'private_test' ) ) ) {
			return( null );
		}
		$_name  = '_key_' . $name;
		$_value = &$this->{ $_name };
		// set
		if( !empty( $value ) && is_string( $value ) ) { $_value = $value; }
		// get
		return( $_value );
	}

	public function hash_method_allow( $value ) {
		$result = is_string( $value ) && in_array( $value, $this->_hash_method_allow );
		return( $result );
	}

	public function hash_method( $value = null ) {
		$result = null;
		if( !empty( $value ) ) {
			if( $this->hash_method_allow( $value ) ) {
				$this->_hash_method = $value;
			} else {
				return( $result );
			}
		}
		$result = $this->_hash_method;
		return( $result );
	}

	public function key_private( $is_request = true ) {
		$result = !(bool)$is_request && $this->_test_mode ? $this->_key_private_test : $this->_key_private;
		// $result = $this->_test_mode ? $this->_key_private_test : $this->_key_private;
		return( $result );
	}

	public function signature( $options, $is_request = true ) {
		$_ = &$options;
		$request = array();
		// add allow fields
		foreach( (array)$this->_signature_allow as $key  ) {
			isset( $_[ $key ] ) && $request[ $key ] = &$_[ $key ];
		}
		// add allow x-fields
		foreach( (array)$_ as $key => $value ) {
			foreach( (array)$this->_signature_allow_x as $_x  ) {
				if( strpos( $key, $_x ) === 0 ) {
					$request[ $key ] = &$_[ $key ];
				}
			}
		}
		// sort by key
		ksort( $request, SORT_STRING );
// var_dump( $request );
		// compile string
		$key = $this->key_private( $is_request );
// $key = $this->_key_private;
// var_dump( $key  );
		$str = implode( ':', $request ) . ':' . $key;
		// create signature
		$result = $this->str_to_sign( $str );
		return( $result );
	}

	public function str_to_sign( $str ) {
		$hash_method = $this->hash_method();
		$result = base64_encode( hash( $hash_method, $str, true ) );
// var_dump( $hash_method, $str, $result  );
		return( $result );
	}

}
