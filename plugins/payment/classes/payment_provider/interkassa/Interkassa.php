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
		'ik_sign',     // Signature, /^.{0,128}$/ oVAOevI3mWrcvrjB4j/ySg== Цифровая подпись. См. формирования цифровой подписи. Обязательный параметр, если в настройках кассы установлен параметр "Требуется ли цифровая подпись от кассы" (Sign Co Required).
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
	);
	protected $_signature_allow_x = array(
		'ik_x_', // ik_x_[name] - X Prefix, ik_x_field1 = somedata; ik_x_baggage1 = code123; Префикс дополнительных полей. Позволяет передавать дополнительные поля на SCI, после чего эти параметры включаются в данные уведомления о совершенном платеже на страницу взаимодействия.  Для создания вы можете воспользоваться нашим генератором платежной формы
	);

	private $_hash_method_allow = array(
		'md5',
		'sha256',
	);

	private $_public_key       = null;
	private $_private_key      = null;
	private $_private_key_test = null;
	private $_hash_method      = null;
	private $_test_mode        = null;

	public function __construct( $public_key, $private_key, $private_key_test, $hash_method = 'md5', $test_mode ) {
		if( empty( $public_key ) ) {
			throw new InvalidArgumentException( 'public_key (merchant) is empty' );
		}
		if( empty( $private_key ) ) {
			throw new InvalidArgumentException( 'private_key is empty' );
		}
		if( empty( $private_key_test ) ) {
			throw new InvalidArgumentException( 'private_key_test is empty' );
		}
		if( !$this->hash_method( $hash_method ) ) {
			throw new InvalidArgumentException( 'hash method allow is not allow' );
		}
		$this->_public_key       = $public_key;
		$this->_private_key      = $private_key;
		$this->_private_key_test = $private_key_test;
		$this->_test_mode        = $test_mode;
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

	public function private_key() {
		$result = $this->_test_mode ? $this->_private_key_test : $this->_private_key;
		return( $result );
	}

	public function signature( $options ) {
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
var_dump( $request );
		// compile string
		$key = $this->private_key();
var_dump( $key );
		$str = implode( ':', $request ) . ':' . $key;
var_dump( $str );
		// create signature
		$result = $this->str_to_sign( $str );
		return( $result );
	}

	public function str_to_sign( $str ) {
		$hash_method = $this->hash_method();
var_dump( $hash_method );
		$result = base64_encode( hash( $hash_method, $str, true ) );
		return( $result );
	}

}
