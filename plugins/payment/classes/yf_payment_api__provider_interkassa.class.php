<?php

_class( 'payment_api__provider_remote' );

class yf_payment_api__provider_interkassa extends yf_payment_api__provider_remote {

	public $URL              = 'https://sci.interkassa.com/';
	public $KEY_PUBLIC       = null;     // Checkout ID, Идентификатор кассы
	public $KEY_PRIVATE      = null;     // secret key
	public $KEY_PRIVATE_TEST = null;     // secret key for test
	public $HASH_METHOD      = 'sha256'; // signature hash method: md5, sha256

	public $PURSE_ID         = null;     // purse_id by currency_id
/* example for project_conf.php:
	public $PURSE_ID         = array(    // purse_id by currency_id
		'UAH' => '...',
	);
*/

	public $URL_API          = 'https://api.interkassa.com/v1/%method';
	public $API_ACCOUNT      = null; // api header: Ik-Api-Account-Id

	public $method_allow = [
		'order' => [
			'payin' => [
				'interkassa',
			],
			'payout' => [
				'visa_p2p_privat_uah',
			],
		],
		'api' => [
			// Список используемых в системе валют и курсов
			'currency' => [
				'uri' => [
					'%method' => 'currency',
				],
				// 'option' => array(
					// 'active' => true,
				// ),
			],
			// Список платежных направлений на ввод, включенных в системе ИК
			'paysystem-input-payway' => [
				'uri' => [
					'%method' => 'paysystem-input-payway',
				],
			],
			// Список платежных направлений на вывод, включенных в системе ИК
			'paysystem-output-payway' => [
				'uri' => [
					'%method' => 'paysystem-output-payway',
				],
			],
			// Список аккаунтов, доступных пользователю
			'account' => [
				'is_authorization' => true,
				'uri' => [
					'%method' => 'account',
				],
			],
			// Список касс, привязанных к аккаунту
			'checkout' => [
				'is_authorization' => true,
				'is_api_account'   => true,
				'uri' => [
					'%method' => 'checkout',
				],
			],
			// Список бизнес касс, привязанных к аккаунту
			'checkout-b' => [
				'is_authorization' => true,
				'is_handler'       => 'checkout_b',
			],
			// Список кошельков, привязанных к аккаунту, с их параметрами
			'purse' => [
				'is_authorization' => true,
				'is_api_account'   => true,
				'uri' => [
					'%method' => 'purse',
				],
			],
			// Позволяет получить выгрузку платежей
			'co-invoice' => [
				'is_authorization' => true,
				'is_api_account'   => true,
				'uri' => [
					'%method' => 'co-invoice',
				],
			],
			'co-invoice-id' => [
				'is_authorization' => true,
				'is_api_account'   => true,
				'url' => 'https://api.interkassa.com/v1/%method/%id',
				'uri' => [
					'%method' => 'co-invoice',
					'%id'     => '$id',
				],
			],
			// GET
			// - список осуществленных выводов
			// - информацию по конкретному выводу
			// POST
			// - создать новый вывод в системе
			'withdraw' => [
				'is_authorization' => true,
				'is_api_account'   => true,
				'uri' => [
					'%method' => 'withdraw',
				],
			],
			'withdraw-id' => [
				'is_authorization' => true,
				'is_api_account'   => true,
				'url' => 'https://api.interkassa.com/v1/%method/%id',
				'uri' => [
					'%method' => 'withdraw',
					'%id'     => '$id',
				],
			],
			'withdraw-calc' => [
				'is_authorization' => true,
				'is_api_account'   => true,
				'uri' => [
					'%method' => 'withdraw',
				],
				'option' => [
					'action' => 'calc',
				],
			],
			'withdraw-process' => [
				'is_authorization' => true,
				'is_api_account'   => true,
				'uri' => [
					'%method' => 'withdraw',
				],
				'option' => [
					'action' => 'process',
					// 'action' => 'calc', // DEBUG
				],
			],
		],
		'payin' => [
			'interkassa' => [
				'title'       => 'Visa, MasterCard',
				'icon'        => 'interkassa',
				'amount_min'  => 100,
				'fee'         => 0, // 0.1%
				'currency' => [
					// 'USD' => array(
						// 'currency_id' => 'USD',
						// 'active'      => true,
					// ),
					// 'EUR' => array(
						// 'currency_id' => 'EUR',
						// 'active'      => true,
					// ),
					'UAH' => [
						'currency_id' => 'UAH',
						'active'      => true,
					],
					// 'RUB' => array(
						// 'currency_id' => 'RUB',
						// 'active'      => true,
					// ),
				],
			],
		],
		'payout' => [
			// changed: 2016-07-01 16:07:07
			// 'visa_p2p_privat_uah' => [
			'visa_p2p_tasprivat_uah' => [
				'title' => 'Visa (Privat24, UAH)',
				'icon'  => 'visa',
				'request_option' => [
					// 'paywayId' => '52e7f883e4ae1a2406000000', // visa_p2p_privat_uah
					'paywayId' => '5763bad33d1eaf04218b456a', // visa_p2p_tasprivat_uah
					'calcKey'  => 'psPayeeAmount',
				],
				'amount' => [
					'min' => 5,
					'max' => 200,
				],
				// 'is_fee' => true,
				'fee' => [
					'out' => [
						'rt'  => 1,
						'fix' => 10,
					],
				],
				'is_currency' => true,
				'currency' => [
					'UAH' => [
						'currency_id' => 'UAH',
						'active'      => true,
					],
				],
				'request_field' => [
					'amount',
					'paymentNo',
					'purseId',
					'paywayId',
				],
				'field' => [
					'card',
				],
				'order' => [
					'card',
				],
				'option' => [
					'card' => 'Номер карты',
				],
				'option_validation_js' => [
					'card'                       => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 13,
						'maxlength' => 16,
						// 'pattern'   => '^\d+$',
						'pattern'   => '^4((40588)|(14949)|(32339)|(32334)|(32338)|(32340)|(40535)|(73117)|(73121)|(13051)|(40509)|(24600)|(62708)|(76065)|(17649)|(32337)|(62705)|(14943)|(14961)|(14962)|(32575)|(58121)|(58122)|(14939)|(14960)|(24657)|(34156)|(32335)|(23396)|(73118)|(32336)|(40129)|(76339)|(14963)|(73114)|(04030)|(58120)|(10653))[0-9]{7}(?:[0-9]{3})?$',
					],
				],
				'option_validation' => [
					'card' => 'required|length[13,16]|regex:~^4((40588)\|(14949)\|(32339)\|(32334)\|(32338)\|(32340)\|(40535)\|(73117)\|(73121)\|(13051)\|(40509)\|(24600)\|(62708)\|(76065)\|(17649)\|(32337)\|(62705)\|(14943)\|(14961)\|(14962)\|(32575)\|(58121)\|(58122)\|(14939)\|(14960)\|(24657)\|(34156)\|(32335)\|(23396)\|(73118)\|(32336)\|(40129)\|(76339)\|(14963)\|(73114)\|(04030)\|(58120)\|(10653))[0-9]{7}(?:[0-9]{3})?$~',
				],
				'option_validation_message' => [
					'card' => 'обязательное поле от 13 до 16 цифр',
				],
			],
			// changed: 2016-07-01 16:07:07
			// 'visa_p2p_notprivat_uah' => [
			'visa_p2p_tasnotprivat_uah' => [
				'title'      => 'Visa (UAH)',
				'icon'       => 'visa',
				'request_option'     => [
					// 'paywayId' => '52ef9b77e4ae1a3008000000', // visa_p2p_notprivat_uah
					'paywayId' => '576658923d1eaf77398b4568', // visa_p2p_notprivat_uah
					'calcKey'  => 'psPayeeAmount',
				],
				'amount' => [
					'min' => 5,
					'max' => 200,
				],
				// 'is_fee' => true,
				'fee' => [
					'out' => [
						'rt'  => 1,
						'fix' => 20,
					],
				],
				'is_currency' => true,
				'currency' => [
					'UAH' => [
						'currency_id' => 'UAH',
						'active'      => true,
					],
				],
				'request_field' => [
					'amount',
					'paymentNo',
					'purseId',
					'paywayId',
				],
				'field' => [
					'card',
				],
				'order' => [
					'card',
				],
				'option' => [
					'card' => 'Номер карты',
				],
				'option_validation_js' => [
					'card'                       => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 13,
						'maxlength' => 16,
						// 'pattern'   => '^\d+$',
						'pattern'   => '^4(?:(?!(40588)|(14949)|(32339)|(32334)|(32338)|(32340)|(40535)|(73117)|(73121)|(13051)|(40509)|(24600)|(62708)|(76065)|(17649)|(32337)|(62705)|(14943)|(14961)|(14962)|(32575)|(58121)|(58122)|(14939)|(14960)|(24657)|(34156)|(32335)|(23396)|(73118)|(32336)|(40129)|(76339)|(14963)|(73114)|(04030)|(58120)|(10653)))[0-9]{12}(?:[0-9]{3})?$',
					],
				],
				'option_validation' => [
					'card' => 'required|length[13,16]|regex:~^4(?:(?!(40588)\|(14949)\|(32339)\|(32334)\|(32338)\|(32340)\|(40535)\|(73117)\|(73121)\|(13051)\|(40509)\|(24600)\|(62708)\|(76065)\|(17649)\|(32337)\|(62705)\|(14943)\|(14961)\|(14962)\|(32575)\|(58121)\|(58122)\|(14939)\|(14960)\|(24657)\|(34156)\|(32335)\|(23396)\|(73118)\|(32336)\|(40129)\|(76339)\|(14963)\|(73114)\|(04030)\|(58120)\|(10653)))[0-9]{12}(?:[0-9]{3})?$~',
				],
				'option_validation_message' => [
					'card' => 'обязательное поле от 13 до 16 цифр',
				],
			],
			// changed: 2016-07-01 16:07:07
			// 'mastercard_p2p_privat_uah' => [
			'mastercard_p2p_tasprivat_uah' => [
				'title'      => 'MasterCard (Privat24, UAH)',
				'icon'       => 'mastercard',
				'request_option'     => [
					// 'paywayId' => '52efa902e4ae1a780e000001', // mastercard_p2p_privat_uah
					'paywayId' => '5763bb6e3d1eaf4b218b4567', // mastercard_p2p_tasprivat_uah
					'calcKey'  => 'psPayeeAmount',
				],
				'amount' => [
					'min' => 5,
					'max' => 200,
				],
				// 'is_fee' => true,
				'fee' => [
					'out' => [
						'rt'  => 1,
						'fix' => 10,
					],
				],
				'is_currency' => true,
				'currency' => [
					'UAH' => [
						'currency_id' => 'UAH',
						'active'      => true,
					],
				],
				'request_field' => [
					'amount',
					'paymentNo',
					'purseId',
					'paywayId',
				],
				'field' => [
					'card',
				],
				'order' => [
					'card',
				],
				'option' => [
					'card' => 'Номер карты',
				],
				'option_validation_js' => [
					'card'                       => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 13,
						'maxlength' => 16,
						// 'pattern'   => '^\d+$',
						'pattern'   => '^((535145)|(536354)|(532957)|(521153)|(530217)|(545708)|(516915)|(558335)|(532032)|(544013)|(521152)|(516874)|(557721)|(545709)|(521857)|(516933)|(670509)|(676246)|(516875)|(516798)|(552324)|(558424)|(516936)|(513399)|(517691))[0-9]{7}(?:[0-9]{3})?$',
					],
				],
				'option_validation' => [
					'card' => 'required|length[13,16]|regex:~^((535145)\|(536354)\|(532957)\|(521153)\|(530217)\|(545708)\|(516915)\|(558335)\|(532032)\|(544013)\|(521152)\|(516874)\|(557721)\|(545709)\|(521857)\|(516933)\|(670509)\|(676246)\|(516875)\|(516798)\|(552324)\|(558424)\|(516936)\|(513399)\|(517691))[0-9]{7}(?:[0-9]{3})?$~',
				],
				'option_validation_message' => [
					'card' => 'обязательное поле от 13 до 16 цифр',
				],
			],
			// changed: 2016-07-01 16:07:07
			// 'mastercard_p2p_notprivat_uah' => [
			'mastercard_p2p_tasnotprivat_uah' => [
				'title'      => 'MasterCard (UAH)',
				'icon'       => 'mastercard',
				'request_option'     => [
					// 'paywayId' => '52efa871e4ae1a3008000002', // mastercard_p2p_notprivat_uah
					'paywayId' => '576658ac3d1eaf82398b4567', // mastercard_p2p_tasnotprivat_uah
					'calcKey'  => 'psPayeeAmount',
				],
				'amount' => [
					'min' => 5,
					'max' => 200,
				],
				// 'is_fee' => true,
				'fee' => [
					'out' => [
						'rt'  => 1,
						'fix' => 20,
					],
				],
				'is_currency' => true,
				'currency' => [
					'UAH' => [
						'currency_id' => 'UAH',
						'active'      => true,
					],
				],
				'request_field' => [
					'amount',
					'paymentNo',
					'purseId',
					'paywayId',
				],
				'field' => [
					'card',
				],
				'order' => [
					'card',
				],
				'option' => [
					'card' => 'Номер карты',
				],
				'option_validation_js' => [
					'card'                       => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 13,
						'maxlength' => 16,
						// 'pattern'   => '^\d+$',
						'pattern'   => '^(?:(?!(535145)|(536354)|(532957)|(521153)|(530217)|(545708)|(516915)|(558335)|(532032)|(544013)|(521152)|(516874)|(557721)|(545709)|(521857)|(516933)|(670509)|(676246)|(516875)|(516798)|(552324)|(558424)|(516936)|(513399)|(517691)))[5-6]{1}[0-9]{12}(?:[0-9]{3})?$',
					],
				],
				'option_validation' => [
					'card' => 'required|length[13,16]|regex:~^(?:(?!(535145)\|(536354)\|(532957)\|(521153)\|(530217)\|(545708)\|(516915)\|(558335)\|(532032)\|(544013)\|(521152)\|(516874)\|(557721)\|(545709)\|(521857)\|(516933)\|(670509)\|(676246)\|(516875)\|(516798)\|(552324)\|(558424)\|(516936)\|(513399)\|(517691)))[5-6]{1}[0-9]{12}(?:[0-9]{3})?$~',
				],
				'option_validation_message' => [
					'card' => 'обязательное поле от 13 до 16 цифр',
				],
			],
			// X-Plat
			'visa_xplat_transfer_rub' => [
				'title' => 'Visa (РФ, RUB)',
				'icon'  => 'visa',
				'request_option' => [
					'paywayId' => '53983b55bf4efc80714160ca',
					'calcKey'  => 'psPayeeAmount',
				],
				'amount' => [
					'min' => 5,
					'max' => 200,
				],
				// 'is_fee' => true,
				'fee' => [
					'out' => [
						'rt'  => 2,
						'fix' => 0,
						'min' => 60,
					],
				],
				'is_currency' => true,
				'currency' => [
					'RUB' => [
						'currency_id' => 'RUB',
						'active'      => true,
					],
				],
				'request_field' => [
					'amount',
					'paymentNo',
					'purseId',
					'paywayId',
				],
				'field' => [
					'cardNumber',
					'phone',
					'lname',
					'fname',
					'mname',
					'passport',
					'seconddocnumber',
				],
				'order' => [
					'cardNumber',
					'phone',
					'lname',
					'fname',
					'mname',
					'passport',
					'seconddocnumber',
				],
				'option' => [
					'cardNumber'      => 'Номер карты РФ',
					'phone'           => 'Номер телефона РФ',
					'lname'           => 'Фамилия',
					'fname'           => 'Имя',
					'mname'           => 'Отчество',
					'passport'        => 'Серия и номер паспорта РФ',
					'seconddocnumber' => 'ИНН',
				],
				'option_validation_js' => [
					'cardNumber' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 16,
						'maxlength' => 19,
						'pattern'   => '^\d{16,19}$',
					],
					'phone' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 11,
						'maxlength' => 11,
						'pattern'   => '^79\d{9}$',
					],
					'lname' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 3,
						'maxlength' => 40,
						'pattern'   => '^[а-яА-ЯёЁЇїa-zA-Z]{3,40}$',
					],
					'fname' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 3,
						'maxlength' => 40,
						'pattern'   => '^[а-яА-ЯёЁЇїa-zA-Z]{3,40}$',
					],
					'mname' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 3,
						'maxlength' => 40,
						'pattern'   => '^[а-яА-ЯёЁЇїa-zA-Z]{3,40}$',
					],
					'passport' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 10,
						'maxlength' => 11,
						'pattern'   => '^(\d{4})( )?(\d{6})$',
					],
					'seconddocnumber' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 6,
						'maxlength' => 14,
						'pattern'   => '^\d{6,14}$',
					],
				],
				'option_validation' => [
					'cardNumber'      => 'required|length[16,19]|regex : ~^\d{16,19}$~',
					'phone'           => 'required|length[11,11]|regex : ~^79\d{9}$~',
					'lname'           => 'required|length[3,40]|regex  : ~[а-яА-ЯёЁЇїa-zA-Z]{3,40}~',
					'fname'           => 'required|length[3,40]|regex  : ~[а-яА-ЯёЁЇїa-zA-Z]{3,40}~',
					'mname'           => 'required|length[3,40]|regex  : ~[а-яА-ЯёЁЇїa-zA-Z]{3,40}~',
					'passport'        => 'required|length[10,11]|regex : ~^(\d{4})( )?(\d{6})$~',
					'seconddocnumber' => 'required|length[6,14]|regex : ~^\d{6,14}$~',
				],
				'option_validation_message' => [
					'cardNumber'      => 'обязательное поле от 16 до 19 цифр',
					'phone'           => 'обязательное поле 11 цифр',
					'lname'           => 'обязательное поле от 3 до 40 символов',
					'fname'           => 'обязательное поле от 3 до 40 символов',
					'mname'           => 'обязательное поле от 3 до 40 символов',
					'passport'        => 'обязательное поле 10 цифр (пример: 1234 123456)',
					'seconddocnumber' => 'обязательное поле от 6 до 14 цифр',
				],
			],
			// X-Plat
			'mastercard_xplat_transfer_rub' => [
				'title' => 'MasterCard (РФ, RUB)',
				'icon'  => 'mastercard',
				'request_option' => [
					'paywayId' => '53983b55bf4efc80714160cb',
					'calcKey'  => 'psPayeeAmount',
				],
				'amount' => [
					'min' => 5,
					'max' => 200,
				],
				// 'is_fee' => true,
				'fee' => [
					'out' => [
						'rt'  => 2,
						'fix' => 0,
						'min' => 60,
					],
				],
				'is_currency' => true,
				'currency' => [
					'RUB' => [
						'currency_id' => 'RUB',
						'active'      => true,
					],
				],
				'request_field' => [
					'amount',
					'paymentNo',
					'purseId',
					'paywayId',
				],
				'field' => [
					'cardNumber',
					'phone',
					'lname',
					'fname',
					'mname',
					'passport',
					'seconddocnumber',
				],
				'order' => [
					'cardNumber',
					'phone',
					'lname',
					'fname',
					'mname',
					'passport',
					'seconddocnumber',
				],
				'option' => [
					'cardNumber'      => 'Номер карты РФ',
					'phone'           => 'Номер телефона РФ',
					'lname'           => 'Фамилия',
					'fname'           => 'Имя',
					'mname'           => 'Отчество',
					'passport'        => 'Серия и номер паспорта РФ',
					'seconddocnumber' => 'ИНН',
				],
				'option_validation_js' => [
					'cardNumber' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 16,
						'maxlength' => 19,
						'pattern'   => '^\d{16,19}$',
					],
					'phone' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 11,
						'maxlength' => 11,
						'pattern'   => '^79\d{9}$',
					],
					'lname' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 3,
						'maxlength' => 40,
						'pattern'   => '^[а-яА-ЯёЁЇїa-zA-Z]{3,40}$',
					],
					'fname' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 3,
						'maxlength' => 40,
						'pattern'   => '^[а-яА-ЯёЁЇїa-zA-Z]{3,40}$',
					],
					'mname' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 3,
						'maxlength' => 40,
						'pattern'   => '^[а-яА-ЯёЁЇїa-zA-Z]{3,40}$',
					],
					'passport' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 10,
						'maxlength' => 11,
						'pattern'   => '^(\d{4})( )?(\d{6})$',
					],
					'seconddocnumber' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 6,
						'maxlength' => 14,
						'pattern'   => '^\d{6,14}$',
					],
				],
				'option_validation' => [
					'cardNumber'      => 'required|length[16,19]|regex : ~^\d{16,19}$~',
					'phone'           => 'required|length[11,11]|regex : ~^79\d{9}$~',
					'lname'           => 'required|length[3,40]|regex  : ~[а-яА-ЯёЁЇїa-zA-Z]{3,40}~',
					'fname'           => 'required|length[3,40]|regex  : ~[а-яА-ЯёЁЇїa-zA-Z]{3,40}~',
					'mname'           => 'required|length[3,40]|regex  : ~[а-яА-ЯёЁЇїa-zA-Z]{3,40}~',
					'passport'        => 'required|length[10,11]|regex : ~^(\d{4})( )?(\d{6})$~',
					'seconddocnumber' => 'required|length[6,14]|regex : ~^\d{6,14}$~',
				],
				'option_validation_message' => [
					'cardNumber'      => 'обязательное поле от 16 до 19 цифр',
					'phone'           => 'обязательное поле 11 цифр',
					'lname'           => 'обязательное поле от 3 до 40 символов',
					'fname'           => 'обязательное поле от 3 до 40 символов',
					'mname'           => 'обязательное поле от 3 до 40 символов',
					'passport'        => 'обязательное поле 10 цифр (пример: 1234 123456)',
					'seconddocnumber' => 'обязательное поле от 6 до 14 цифр',
				],
			],
		],
	];

	public $_api_transform = [
		'operation_id'   => 'paymentNo',
		'transaction_id' => 'trnId',
		'account'        => 'card',
	];

	public $_api_transform_reverse = [
		'code'           => 'state',
	];

	public $_options_transform = [
		'amount'       => 'ik_am',
		'currency'     => 'ik_cur',
		'title'        => 'ik_desc',
		'description'  => 'ik_x_desc',
		'operation_id' => 'ik_pm_no',
		'public_key'   => 'ik_co_id',
		'key_public'   => 'ik_co_id',
		'test'         => 'test_mode',
	];

	public $_options_transform_reverse = [
		'ik_am'     => 'amount',
		'ik_cur'    => 'currency',
		'ik_desc'   => 'title',
		'ik_x_desc' => 'description',
		'ik_pm_no'  => 'operation_id',
		'ik_co_id'  => 'key_public',
	];

	public $_status = [
		'success'    => 'success',
		'new'        => 'processing',
		'waitAccept' => 'processing',
		'process'    => 'processing',
		'fail'       => 'refused',
		'canceled'   => 'refused',
	];

	public $_payin_status = [
		//     status          description                  финальный
		2  => 'processing', // Ожидает оплаты             - Нет
		3  => 'processing', // Обрабатывается             - Нет
		4  => 'processing', // В процессе возврата        - Нет
		5  => 'expired',    // Просрочен                  - Да
		7  => 'success',    // Зачислен                   - Да
		8  => 'refused',    // Отменен платежной системой - Да
		9  => 'refused',    // Возвращен                  - Да
	];

	public $_payin_status_message = [
		2  => 'Ожидает оплаты',
		3  => 'Обрабатывается',
		4  => 'В процессе возврата',
		5  => 'Просрочен',
		7  => 'Зачислен',
		8  => 'Отменен платежной системой',
		9  => 'Возвращен',
	];

	public $_payout_status = [
		//     status          description                    финальный
		1  => 'processing', // Ожидает проверки модерацией  - Нет
		2  => 'processing', // Проверен модерацией          - Нет
		3  => 'refused',    // Отозван модерацией           - Да
		4  => 'processing', // Заморожен                    - Нет
		5  => 'processing', // Разморожен                   - Нет
		6  => 'processing', // Обработка платежной системой - Нет
		7  => 'processing', // Зачисление                   - Нет
		8  => 'success',    // Проведен                     - Да
		9  => 'refused',    // Отменен                      - Да
		11 => 'refused',    // Возвращен                    - Да
		12 => 'processing', // Создан, но еще не проведен   - Нет
	];

	public $_payout_status_message = [
		1  => 'Ожидает проверки модерацией',
		2  => 'Проверен модерацией',
		3  => 'Отозван модерацией',
		4  => 'Заморожен',
		5  => 'Разморожен',
		6  => 'Обработка платежной системой',
		7  => 'Зачисление',
		8  => 'Проведен',
		9  => 'Отменен',
		11 => 'Возвращен',
		12 => 'Создан, но еще не проведен',
	];

	public $currency_default = 'USD';
	public $currency_allow = [
		'USD' => [
			'currency_id' => 'USD',
			'active'      => true,
		],
		'EUR' => [
			'currency_id' => 'EUR',
			'active'      => true,
		],
		'UAH' => [
			'currency_id' => 'UAH',
			'active'      => true,
		],
		'RUB' => [
			'currency_id' => 'RUB',
			'active'      => true,
		],
	];

	// public $fee = 5; // 5%

	public $service_allow = [
		'Visa',
		'Mastercard',
		// 'WebMoney',
		// 'LiqPay',
		// 'Privat24',
		// 'Yandex.Money',
		// 'Единый кошелек',
		// 'PerfectMoney',
		// 'Почта России',
		// 'Юнистрим',
		// 'Салоны связи',
		// 'Альфаклик (Альфабанк)',
		// 'Anelik',
		// 'ЛИДЕР',
		// 'Qiwi Кошелек',
		// 'Украинский банк',
		// 'Российский банк',
		// 'Терминалы России',
		// 'Терминалы Украины',
		// 'Тестовая платежная система',
		// 'Салоны связи «Альт-телеком»',
		// 'SWIFT Банковский перевод',
		// 'Интернет-банк «Связной Банк»',
		// 'Салоны связи «Форвард Мобайл»',
		// 'Интернет-банк «PSB-Retail» («Промсвязьбанк»)',
		// 'Сбербанк ОнЛ@йн',
		// 'OKPay',
		// 'Payeer',
		// 'Салоны связи «Диксис»',
		// 'Салоны связи «Евросеть»',
		// 'Салоны связи «Связной»',
		// 'Салоны связи «Цифроград»',
		// 'Салоны связи «Сотовый мир»',
	];

	public $url_result = null;
	public $url_server = null;

	public function _init() {
		if( !$this->ENABLE ) { return( null ); }
		$this->payment_api = _class( 'payment_api' );
		// load api
		require_once( __DIR__ . '/payment_provider/interkassa/Interkassa.php' );
		$this->api = new Interkassa( $this->KEY_PUBLIC, $this->KEY_PRIVATE, $this->KEY_PRIVATE_TEST, $this->HASH_METHOD, $this->TEST_MODE );
		$this->url_result = url_user( '/api/payment/provider?name=interkassa&operation=response' );
		$this->url_server = url_user( '/api/payment/provider?name=interkassa&operation=response&server=true' );
		// parent
		parent::_init();
	}

	public function key( $name = 'public', $value = null ) {
		if( !$this->ENABLE ) { return( null ); }
		$value = $this->api->key( $name, $value );
		return( $value );
	}

	public function key_reset() {
		if( !$this->ENABLE ) { return( null ); }
		$this->key( 'public',       $this->KEY_PUBLIC       );
		$this->key( 'private',      $this->KEY_PRIVATE      );
		$this->key( 'private_test', $this->KEY_PRIVATE_TEST );
	}

	public function hash_method( $value = null ) {
		if( !$this->ENABLE ) { return( null ); }
		$value = $this->api->hash_method( $value );
		return( $value );
	}

	public function hash_method_reset() {
		if( !$this->ENABLE ) { return( null ); }
		$this->api->hash_method( $this->HASH_METHOD );
	}

	public function signature( $options, $request = true ) {
		if( !$this->ENABLE ) { return( null ); }
		$result = $this->api->signature( $options, $request );
		return( $result );
	}

	public function _form_options( $options ) {
		if( !$this->ENABLE ) { return( null ); }
		$_ = $options;
		// transform
		foreach ((array)$this->_options_transform as $from => $to ) {
			if( isset( $_[ $from ] ) ) {
				$_[ $to ] = $_[ $from ];
				unset( $_[ $from ] );
			}
		}
		// url
		if( !empty( $_[ 'url_result' ] )
			|| empty( $_[ 'ik_suc_uc' ] )
			|| empty( $_[ 'ik_pnd_u'  ] )
			|| empty( $_[ 'ik_fal_u'  ] )
		) {
			$url = $this->_url( $options );
			$_[ 'ik_suc_u' ] = $url . '&status=success';
			$_[ 'ik_pnd_u' ] = $url . '&status=pending';
			$_[ 'ik_fal_u' ] = $url . '&status=fail';
			$_[ 'ik_suc_m' ] = 'post';
			$_[ 'ik_pnd_m' ] = 'post';
			$_[ 'ik_fal_m' ] = 'post';
			unset( $_[ 'url_result' ] );
		}
		if( !empty( $_[ 'url_server' ] ) || empty( $_[ 'ik_ia_u' ] ) ) {
			$url_server = $this->_url( $options, $is_server = true );
			$_[ 'ik_ia_u' ] = $url_server . '&status=interaction';
			$_[ 'ik_ia_m' ] = 'post';
			unset( $_[ 'url_server' ] );
		}
		// default
		$_[ 'ik_am' ] = number_format( $_[ 'ik_am' ], 2, '.', '' );
		empty( $_[ 'ik_co_id'   ] ) && $_[ 'ik_co_id'   ] = $this->KEY_PUBLIC;
		if( empty( $_[ 'ik_am' ] ) || empty( $_[ 'ik_co_id' ] ) ) { $_ = null; }
		if( !empty( $this->TEST_MODE ) || !empty( $_[ 'test_mode' ] ) ) {
			unset( $_[ 'test_mode' ] );
			$_[ 'ik_act'    ] = 'payway';
			$_[ 'ik_pw_via' ] = 'test_interkassa_test_xts';
		}
		return( $_ );
	}

	public function _url( $options, $is_server = false ) {
		if( !$this->ENABLE ) { return( null ); }
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( $is_server ) {
			$url = $_url_server ?: $this->url_server;
		} else {
			$url = $_url_result ?: $this->url_result;
		}
		$result = $url . '&operation_id=' . $_operation_id;
		return( $result );
	}

	public function _form( $data, $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		if( empty( $data ) ) { return( null ); }
		$_ = &$options;
		// START DUMP
		$payment_api = $this->payment_api;
		$payment_api->dump([ 'name' => 'Interkassa', 'operation_id' => @(int)$_[ 'data' ][ 'operation_id' ] ]);
		$is_array = (bool)$_[ 'is_array' ];
		$form_options = $this->_form_options( $data );
		// DUMP
		$payment_api->dump([ 'var' => $form_options ]);
		$signature    = $this->api->signature( $form_options );
		if( empty( $signature ) ) { return( null ); }
		$form_options[ 'ik_sign' ] = $signature;
		$url = &$this->URL;
		$result = [];
		if( $is_array ) {
			$result[ 'url' ] = $url;
		} else {
			$result[] = '<form id="_js_provider_interkassa_form" method="post" accept-charset="utf-8" action="' . $url . '" class="display: none;">';
		}
		foreach ((array)$form_options as $key => $value ) {
			if( $is_array ) {
				$result[ 'data' ][ $key ] = $value;
			} else {
				$result[] = sprintf( '<input type="hidden" name="%s" value="%s" />', $key, $value );
			}
		}
		if( !$is_array ) {
			$result[] = '</form>';
			$result = implode( PHP_EOL, $result );
		}
		return( $result );
	}

	public function _api_response() {
		if( !$this->ENABLE ) { return( null ); }
		$payment_api = $this->payment_api;
		$test_mode = &$this->TEST_MODE;
		$is_server = !empty( $_GET[ 'server' ] );
		$result = null;
		// check operation
		$operation_id = (int)$_GET[ 'operation_id' ];
		// START DUMP
		$payment_api->dump( [ 'name' => 'Interkassa', 'operation_id' => (int)$operation_id ]);
		/* // test data
		$this->key( 'private',      'xXceiJgnFURU0lq9' );
		$this->key( 'private_test', 'AxlrteZIreEpMddf' );
		$this->hash_method( 'sha256' );
		$_POST = array (
			'ik_co_id' => '54be5909bf4efc7f6b8ab8f5',
			'ik_co_prs_id' => '203295131974',
			'ik_inv_id' => '33274174',
			'ik_inv_st' => 'success',
			'ik_inv_crt' => '2015-01-23 11:20:09',
			'ik_inv_prc' => '2015-01-23 11:20:09',
			'ik_trn_id' => '',
			'ik_pm_no' => 'ID_4233',
			'ik_pw_via' => 'test_interkassa_test_xts',
			'ik_am' => '100.00',
			'ik_co_rfn' => '100.0000',
			'ik_ps_price' => '103.00',
			'ik_cur' => 'USD',
			'ik_desc' => 'Пополнение счета (Interkassa)',
			'ik_x_user_id' => '3',
			'_ik_x_user_id' => '3',
			'ik_sign' => 'mgNlOcdt6ydxAZZvAPEZYo7PZRoWnM/zvlgk2pdZe20=',
		); // */
/*
	&status=success
		string '$_GET' (length=5)
		array (size=4)
			'test_mode' => string '1' (length=1)
			'status' => string 'success' (length=7)
			'object' => string 'payment_test' (length=12)
			'action' => string 'provider' (length=8)
		string '$_POST' (length=6)
		array (size=12)
			'ik_co_id' => string '54be5909bf4efc7f6b8ab8f5' (length=24)
			'ik_inv_id' => string '33226688' (length=8)
			'ik_inv_st' => string 'success' (length=7)
			'ik_inv_crt' => string '2015-01-21 13:14:26' (length=19)
			'ik_inv_prc' => string '2015-01-21 13:14:26' (length=19)
			'ik_pm_no' => string 'ID_4233' (length=7)
			'ik_pw_via' => string 'test_interkassa_test_xts' (length=24)
			'ik_am' => string '100.00' (length=6)
			// Checkout Refund - Сумма зачисления на счет кассы.
			'ik_co_rfn' => string '97.0000' (length=7)
			// Paysystem Price - Сумма платежа в платежной системе.
			'ik_ps_price' => string '100.00' (length=6)
			'ik_cur' => string 'USD' (length=3)
			'ik_desc' => string 'Пополнение счета (Interkassa)' (length=44)
			'ik_x_user_id' => string '3' (length=1)
	&status=fail
		string '$_GET' (length=5)
		array (size=4)
			'status' => string 'fail' (length=4)
		string '$_POST' (length=6)
		array (size=12)
			'ik_inv_st' => string 'canceled' (length=8)
			'ik_inv_prc' => string '' (length=0)
	&status=pending
		string '$_GET' (length=5)
		array (size=4)
			'status' => string 'pending' (length=7)
		string '$_POST' (length=6)
		array (size=12)
			'ik_inv_st' => string 'waitAccept' (length=10)
			'ik_inv_prc' => string '' (length=0)
 */
		$response = $_POST;
		// response POST:
		$signature = $response[ 'ik_sign' ];
		// check signature
		if( !$test_mode && empty( $signature ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Пустая подпись',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
		$_signature = $this->signature( $response, false );
		if( !( $test_mode && empty( $signature ) ) && $signature != $_signature ) {
			$result = [
				'status'         => false,
				'status_message' => 'Неверная подпись',
			];
			// DUMP
			$payment_api->dump([ 'var' => $result ]);
			return( $result );
		}
		// update operation
		$_response = $this->_response_parse( $response );
		// check public key (ik_co_id)
		$key_public = $_response[ 'key_public' ];
		$_key_public = $this->key( 'public' );
		if( $key_public != $_key_public ) {
			$result = [
				'status'         => false,
				'status_message' => 'Неверный ключ (ik_co_id)',
			];
			return( $result );
		}
		// check status
		$state = $_response[ 'ik_inv_st' ];
		list( $status_name, $status_message ) = $this->_state( $state );
		// test
		// $_response[ 'operation_id' ] = '3304';
		// update account, operation data
		$result = $this->_api_deposition( [
			'provider_name'  => 'interkassa',
			'response'       => $_response,
			'status_name'    => $status_name,
			'status_message' => $status_message,
		]);
		return( $result );
	}

	public function _response_parse( $response ) {
		if( !$this->ENABLE ) { return( null ); }
		$_ = $response;
		// transform
		foreach( (array)$this->_options_transform_reverse as $from => $to  ) {
			if( isset( $_[ $from ] ) ) {
				$_[ $to ] = $_[ $from ];
				unset( $_[ $from ] );
			}
		}
		return( $_ );
	}

	public function api_request__checkout_b( $options = null ) {
		// get business account_id
		list( $status, $account ) = $this->api_request( 'account' );
		if( empty( $status )
			|| !empty( $account[ 'code' ] )
			|| empty( $account[ 'data' ] )
		) {
			$result = [
				'status'         => false,
				'status_message' => 'Ошибка при запросе бизнес счета',
			];
			return( $result );
		}
		// find business account_id
		$account_id = null;
		foreach( $account[ 'data' ] as $id => $item ) {
			if( @$item[ 'tp' ] == 'b' ) {
				$account_id = $item[ '_id' ];
				break;
			}
		}
		// get business account
		$request_option = [
			'method_id' => 'checkout',
			'header'    => [
				'Ik-Api-Account-Id: '. $account_id,
			],
		];
		$result = $this->api_request( $request_option );
		return( $result );
	}

	public function api_account( $options = null ) {
		// var
		$account_id = @$this->API_ACCOUNT;
		if( empty( $account_id ) ) { return( null ); }
		// business account id
		$result = [
			'header'    => [
				'Ik-Api-Account-Id: '. $account_id,
			],
		];
		return( $result );
	}

	public function api_request( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		if( is_string( $options ) ) { $_method_id = $options; }
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// method
		$method = $this->api_method( [
			'type'      => 'api',
			'method_id' => @$_method_id,
		]);
		if( empty( $method ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Метод запроса не найден',
			];
			return( $result );
		}
		// method handler
		if( !empty( $method[ 'is_handler' ] ) ) {
			$handler = 'api_request__'. $method[ 'is_handler' ];
			if( !method_exists( $this, $handler ) ) {
				$result = [
					'status'         => false,
					'status_message' => 'Опработчик метода запроса не найден',
				];
				return( $result );
			}
			$result = $this->{ $handler }( $options );
			return( $result );
		}
		// request
		$request = [];
		!empty( $_option ) && $request = $_option;
// DEBUG
// var_dump( $url, $request, $request_option );
// exit;
		// add options
		!empty( $method[ 'option' ] ) && $request = array_merge_recursive(
			$request, $method[ 'option' ]
		);
		// url
		$object = $this->api_url( $method, $options );
		if( isset( $object[ 'status' ] ) && $object[ 'status' ] === false ) { return( $object ); }
		$url = $object;
		// request options
		$request_option = [];
		@$_is_debug && $request_option[ 'is_debug' ] = true;
			// api authorization
			$_request_option = $this->api_authorization( $method );
			is_array( $_request_option ) && $request_option = array_merge_recursive( $request_option, $_request_option );
			// api account
			$_request_option = $this->api_account( $method );
			is_array( $_request_option ) && $request_option = array_merge_recursive( $request_option, $_request_option );
			// header
			is_array( $_header ) && $request_option = array_merge_recursive( $request_option, [ 'header' => $_header ] );
		// test
		if( $this->is_test() ) {
			switch( $_method_id ) {
				case 'withdraw-process':
					$request[ 'action' ] = 'calc';
					break;
			}
		}
		// request
// DEBUG
// var_dump( $url, $request, $request_option );
// exit;
		$result = $this->_api_request( $url, $request, $request_option );
// var_dump( $result );
// exit;
		return( $result );
	}

	public function api_payout( $options = null ) {
		if( !$this->ENABLE ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// method
		$method = $this->api_method( [
			'type'      => 'payout',
			'method_id' => @$_method_id,
		]);
		if( empty( $method ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Метод запроса не найден',
			];
			return( $result );
		}
		$payment_api = &$this->payment_api;
		// operation_id
		$_operation_id = (int)$_operation_id;
		$operation_id = $_operation_id;
		if( empty( $_operation_id ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Не определен код операции',
			];
			return( $result );
		}
		$amount = $_amount;
		// amount min/max
		$result = $this->amount_limit( [
			'amount'      => $amount,
			'currency_id' => $currency_id,
			'method'      => $method,
		]);
		// amount currency conversion
		$result = $this->currency_conversion_payout( [
			'options' => $options,
			'method'  => $method,
			'amount'  => $amount,
		]);
		if( empty( $result[ 'status' ] ) ) { return( $result ); }
		$amount_currency       = $result[ 'amount_currency' ];
		$amount_currency_total = $result[ 'amount_currency_total' ];
		$currency_id           = $result[ 'currency_id' ];
		if( empty( $result[ 'status' ] ) ) { return( $result ); }
		// default
		$amount = @$method[ 'is_fee' ] ? $amount_currency_total : $amount_currency;
		// request
		$request = [];
		@$method[ 'request_option' ] && $request = $method[ 'request_option' ];
		// add common fields
		!@$request[ 'purseId' ] && $request[ 'purseId' ] = $this->PURSE_ID[ $currency_id ];
		if( ! @$request[ 'purseId' ] ) {
			$result = [
				'status'         => false,
				'status_message' => 'Требуется настройка кошелька',
			];
			return( $result );
		}
		$request[ 'amount'       ] = $amount;
		$request[ 'operation_id' ] = $operation_id;
		// transform
		$this->option_transform( [
			'option'    => &$request,
			'transform' => $this->_api_transform,
		]);
		// add details
		$request[ 'details' ] = [];
		$request_details = $options;
		$this->option_transform( [
			'option'    => &$request_details,
			'transform' => $this->_api_transform,
		]);
		foreach( $method[ 'field' ] as $key ) {
			$value = &$request_details[ $key ];
			if( !isset( $value ) ) {
				$result = [
					'status'         => false,
					'status_message' => 'Отсутствуют данные запроса: '. $key,
				];
				return( $result );
			}
			$request[ 'details' ][ $key ] = &$request_details[ $key ];
		}
// DEBUG
// var_dump( $request );
		// START DUMP
		$payment_api->dump( [ 'name' => 'Interkassa', 'operation_id' => $operation_id,
			'var' => [ 'request' => $request ]
		]);
		// update processing
		$sql_datetime = $payment_api->sql_datetime();
		$operation_options = [
			'processing' => [ [
				'provider_name' => 'interkassa',
				'datetime'      => $sql_datetime,
			]],
		];
		$operation_update_data = [
			'operation_id'    => $operation_id,
			'datetime_update' => $sql_datetime,
			'options'         => $operation_options,
		];
		$payment_api->operation_update( $operation_update_data );
		// request options
		$request_option = [
			'method_id' => 'withdraw-process',
			'option'    => $request,
			'is_debug'  => @$_is_debug,
		];
		$result = $this->api_request( $request_option );
		// DUMP
		$payment_api->dump( [ 'var' => [ 'response' => $result ]]);
		if( empty( $result ) ) {
			$result = [
				'status'         => false,
				'status_message' => 'Невозможно отправить запрос',
			];
			return( $result );
		}
		@list( $status, $response ) = $result;
		// DEBUG
		/*
		$this->is_test() && $response = array (
			'status' => 'ok',
			'code' => 0,
			'data' =>
			array (
				'withdraw' => array (
					'state'         => '4',
					'state'         => '1',
					'result'        => '0',
					'stateName'     => 'success',
					'purseId'       => '300301404317',
					'accountId'     => '5534b0f13b1eaf67738b456a',
					'coId'          => '5534b12f3b1eaf07728b4569',
					'paymentNo'     => '1',
					'paywayId'      => '52efa902e4ae1a780e000001',
					'paywayPurseId' => '52efa952e4ae1a3008000003',
					'payerWriteoff' => 2063.3299999999999,
					'payeeReceive'  => 2063.3299999999999,
					'ikFee'         => 0,
					'ikPrice'       => 2063.3299999999999,
					'ikPsPrice'     => 2063.3299999999999,
					'psFeeIn'       => 0,
					'psFeeOut'      => 30.329999999999998,
					'psCost'        => 22.329999999999998,
					'ikIncome'      => 8,
					'psAmount'      => 2033,
					'psValue'       => 2033,
					'psPrice'       => 2055.3299999999999,
					'psCurRate'     => 1,
				),
				'transaction' => array (
					'payerPurseId' => '300301404317',
					'payerBalance' => 5714.3199999999997,
					'payeePurseId' => '304403706200',
					'payeeBalance' => 179616920.39289999,
					'payerAmount' => 2063.3299999999999,
					'payerPrice' => 2063.3299999999999,
					'payerFee' => 0,
					'payerExchFee' => 0,
					'payeeAmount' => 2063.3299999999999,
					'payeeFee' => 0,
					'payeePrice' => 2063.3299999999999,
					'exchRate' => 1,
				),
			),
			'message' => 'Success',
		); //*/
		if( !@$response ) {
			$result = [
				'status'         => false,
				'status_message' => 'Невозможно декодировать ответ: '. var_export( $response, true ),
			];
			return( $result );
		}
		// transform reverse
		foreach( $this->_api_transform_reverse as $from => $to ) {
			if( $from != $to && isset( $response[ $from ] ) ) {
				$response[ $to ] = $response[ $from ];
				unset( $response[ $from ] );
			}
		}
		// result
		$result = [
			'status'         => &$status_name,
			'status_message' => &$status_message,
		];
		$status_name         = false;
		$status_message = null;
		$state = (int)$response[ 'state' ];
		switch( $state ) {
			// success
			case 0:
				$status_name    = true;
				$status_message = 'Выполнено';
				break;
			// processing
			case 1106:
				$status_message = 'Не хватает средств';
				break;
			default:
				$status_message = 'Ошибка: '. $response[ 'message' ];
				break;
		}
		@$status_message && $response[ 'message' ] = $status_message;
		if( !$status_name ) { return( $result ); }
		// data
		$data = &$response[ 'data' ][ 'withdraw' ];
		if( !is_array( $data ) ) {
			$status_name    = false;
			$status_message = 'Невозможно декодировать ответ: '. var_export( $response, true );
			return( $result );
		}
		$data[ '_transaction' ] = &$response[ 'data' ][ 'transaction' ];
		// test mode
		$this->is_test() && $data += [
			'state' => 1,
			'id'    => 401040, // need real interkassa operation id
		];
		// check status
		$state = (int)$data[ 'state' ];
		list( $status_name, $status_message ) = $this->_state( $state
			, $this->_payout_status
			, $this->_payout_status_message
		);
		$status_message = @$status_message ?: @$data[ 'stateName' ];
		// update account, operation data
		$payment_type = 'payment';
		$operation_data = [
			'operation_id'   => $operation_id,
			'provider_force' => @$_provider_force,
			'provider_name'  => 'interkassa',
			'state'          => $state,
			'status_name'    => $status_name,
			'status_message' => $status_message,
			'payment_type'   => $payment_type,
			'response'       => $data,
		];
// DEBUG
// var_dump( $operation_data ); exit;
		// DUMP
		$payment_api->dump( [ 'var' => [ 'payment_type' => $payment_type, 'update operation' => $operation_data ]]);
		$result = $this->{ '_api_' . $payment_type }( $operation_data );
		// DUMP
		$payment_api->dump( [ 'var' => [ 'update result' => $result ]]);
		return( $result );
	}

}
