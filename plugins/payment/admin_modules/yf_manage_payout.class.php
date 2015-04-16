<?php

class yf_manage_payout {

	/**
	*/
	function _init() {
	}

	/**
	*/
	function show() {
		$sql = "
			SELECT op.operation_id as id, op.provider_id, op.status_id as status, op.amount, op.balance, op.title, op.datetime_start
			FROM ".db('payment_operation')." as op
			INNER JOIN ".db('payment_provider')." as pp ON (pp.provider_id = op.provider_id AND pp.`system` = 0 AND pp.active = 1)
			WHERE op.direction = 'out'
				AND op.status_id = 1
			ORDER BY op.datetime_start DESC
		";
		$providers = _class( 'payment_api' )->provider( array(
			'all' => true,
		));
		$op_status = _class( 'payment_api' )->get_status();
		return table($sql)
			->text('id', '#')
			->text('title')
			->func('provider_id', function($value, $extra, $row_info) use ($providers){
				return $providers[$value]['name'];
			}, array('desc' => 'provider'))
			->text('amount')
			->text('balance')
			->func('status', function($value, $extra, $row_info) use ($op_status){
				return $op_status[1][$value]['title'];
			})
			->text('datetime_start', 'date')
			->btn_view()
			;
	}

	/**
	*/
	function view() {
		$operation_id = (int)$_GET[ 'id' ];
		$payment_api = _class( 'payment_api' );
		$operation = $payment_api->operation( array(
			'operation_id' => $operation_id,
		));
		// check operation
		if( empty( $operation ) ) {
			$result = array(
				'status'         => false,
				'status_message' => 'Ошибка: операция не найдена',
			);
			return( $this->_user_message( $result ) );
		}
		// import operation
		is_array( $operation ) && extract( $operation, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// check account
		$account_result = $payment_api->get_account( array( 'account_id' => $_account_id ) );
		if( empty( $account_result ) ) {
			$result = array(
				'status'         => false,
				'status_header'  => 'Ошибка',
				'status_message' => 'Счет пользователя не найден',
			);
			return( $this->_user_message( $result ) );
		}
		list( $account_id, $account ) = $account_result;
		// check user
		$user_id = $account[ 'user_id' ];
		$user    = user( $user_id );
		if( empty( $user ) ) {
			$result = array(
				'status'         => false,
				'status_header'  => 'Ошибка',
				'status_message' => 'Пользователь не найден: ' . $user_id,
			);
			return( $this->_user_message( $result ) );
		}
		$online_users = _class( 'online_users', null, null, true );
		$user_is_online = $online_users->_is_online( $user_id );
		// check provider
		$providers_user = $payment_api->provider();
		if( empty( $providers_user[ $_provider_id ] ) ) {
			$result = array(
				'status'         => false,
				'status_header'  => 'Ошибка',
				'status_message' => 'Неизвестный провайдер',
			);
			return( $this->_user_message( $result ) );
		}
		$provider = &$providers_user[ $_provider_id ];
		$provider_class = $payment_api->provider_class( array(
			'provider_name' => $provider[ 'name' ],
		));
		if( empty( $provider_class ) ) {
			$result = array(
				'status_header'  => 'Ошибка',
				'status_message' => 'Провайдер недоступный: ' . $provider[ 'title' ],
			);
			return( $this->_user_message( $result ) );
		}
		// check request
		if(
			empty( $_options[ 'request' ] )
			|| !is_array( $_options[ 'request' ] )
		) {
			$result = array(
				'status_header'  => 'Ошибка',
				'status_message' => 'Параметры запроса отсутствует',
			);
			return( $this->_user_message( $result ) );
		}
		$request = reset( $_options[ 'request' ] );
		// check method
		if( empty( $request[ 'options' ][ 'method_id' ] ) ) {
			$result = array(
				'status_header'  => 'Ошибка',
				'status_message' => 'Метод вывода средств отсутствует',
			);
			return( $this->_user_message( $result ) );
		}
		$method = $provider_class->api_method_payout( $request[ 'options' ][ 'method_id' ] );
		// prepare view: request options
		$content = array();
		foreach( $method[ 'option' ] as $key => $title ) {
			if( !empty( $request[ 'options' ][ $key ] ) ) {
				$content[ $title ] = $request[ 'options' ][ $key ];
			}
		}
		$html_request_options = _class( 'html' )->dd_table( $content, null, array( 'div_class' => ' ' ) );
		// prepare view: amount
		$html_user_url = url_admin( '/members/edit/' . $user_id );
		$html_amount = $payment_api->money_html( $_amount );
		$html_date   = $_datetime_start;
		$content = array(
			'Сумма'         => $html_amount,
			'Дата создания' => $html_date,
		);
		$html_operation_options = _class( 'html' )->dd_table( $content, null, array( 'div_class' => ' ' ) );
		// $html_request_options = table( $request_options )->auto();
// var_dump( $request_options, (string)$html_request_options );
// exit;
		// url
		$is_test = $provider_class->is_test();
		$url_base = 'https://cliff.ecommpay.com/';
		$is_test && $url_base = 'https://cliff-sandbox.ecommpay.com/';
		$url_operation_detail = $url_base . 'operations/detail/' . $operation_id;
		$url_payouts          = $url_base . 'payouts/index';
// var_dump( $operation, $request, $method );
		$body = '
<style>
.b-list-step > .list-item {
	margin: 0 0 1em 0;
}
.tab-content {
	padding: 1em 0;
}
.b-data .dl-horizontal dt {
	width      : 220px;
	text-align : left;
}
.b-data .dl-horizontal dd {
	margin-left : 240px;
	width       : 200px;
}
.b-data.operation .money,
.b-data.operation .currency {
	color       : #0a0;
	font-size   : 1.5em;
	line-height : 1em;
}
</style>
<div class="b-data operation">'. $html_operation_options .'</div>
<div class="b-content">
	<div class="info">
		<p>
			Выберите режим вывода средств
		</p>
	</div>
	<div role="tabpanel" class="col-md-6">
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation" class="active">
				<a href="#automatic" aria-controls="automatic" role="tab" data-toggle="tab">Автоматический</a>
			</li>
			<li role="presentation">
				<a href="#manual" aria-controls="manual" role="tab" data-toggle="tab">Ручной</a>
			</li>
		</ul>
		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="automatic">
				<ol class="b-list-step">
					<li class="list-item">
						<a href="'.url('/@object/request?operation_id='.$operation_id).'" class="btn btn-primary">Выполнить запрос вывода средств</a>
					</li>
					<li class="list-item">
						<a href="'. $url_operation_detail .'" class="btn btn-info">Проверьте детали выполненного запроса</a>
					</li>
				</ol>
			</div>
			<div role="tabpanel" class="tab-pane" id="manual">
				<ol class="b-list-step">
					<li class="list-item">
						<a href="'.url('/@object/csv?operation_id='.$operation_id).'" class="btn btn-primary">Скачать CSV файл для EcommPay</a>
					</li>
					<li class="list-item">
						<a href="'. $url_payouts .'" class="btn btn-info" target="_blank">Выполните вывод средств с помощь CSV файла</a>'
						.tip('На сайте необходимо выбрать вкладку "Массовые Выплаты" и загрузить скачанный CSV файл, затем подтвердить либо отклонить перевод денег.').'
					</li>
				</ol>
			</div>
		</div>
	</div>

	<div role="tabpanel" class="col-md-6">
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation" class="active">
				<a href="#data" aria-controls="data" role="tab" data-toggle="tab">Данные</a>
			</li>
		</ul>
		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="data">
				<div class="b-data">'. $html_request_options .'</div>
			</div>
		</div>
	</div>

	<div class="b-action">
		<p>
			3. Пометите операцию вывода средств:
		</p>
		<div class="action">
			<a href="'.url('/@object/update?state=success&operation_id='.$operation_id).'" class="btn btn-success">Выполнено</a>
			<a href="'.url('/@object/update?state=refused&operation_id='.$operation_id).'" class="btn btn-danger">Не выполнено</a>
		</div>
	</div>
</div>
		';
		return $body;
	}

	protected function _user_message( $options = null ) {
		// import operation
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( empty( $_status_message ) ) { return( null ); }
		switch( !empty( $_status ) ) {
			case true:
				$_css_panel_status = 'success';
				break;
			case false:
			default:
				$_css_panel_status = 'danger';
				break;
		}
		// body
		$content = htmlentities( $_status_message, ENT_HTML5, 'UTF-8', $double_encode = false );
		$panel_body = '<div class="panel-body">'. $content .'</div>';
		// header
		$content = 'Вывод средств';
		if( !empty( $_status_header ) ) { $content .= ': ' . $_status_header; }
		$content = htmlentities( $content, ENT_HTML5, 'UTF-8', $double_encode = false );
		$panel_header = '<div class="panel-heading">'. $content .'</div>';
		// footer
		$content = '<a href="'. $url .'" class="btn btn-success">Список операций</a>';
		if( !empty( $_status_footer ) ) {
			$url = url( '/@object' );
			$content = $_status_footer;
		}
		isset( $content ) && $panel_footer = '<div class="panel-footer">'. $content .'</div>';
		// panel
		$result =  <<<"EOS"
<div class="panel panel-{$_css_panel_status}">
	$panel_header
	$panel_body
	$panel_footer
</div>
EOS;
		return( $result );
	}

	/**
	*/
	function _array2csv(array &$array, $delim = ';') {
		if (count($array) == 0) {
			return null;
		}
		ob_start();
		$df = fopen('php://output', 'w');
		fputcsv($df, array_keys(reset($array)));
		foreach ($array as $row) {
			fputcsv($df, $row, $delim);
		}
		fclose($df);
		return ob_get_clean();
	}

	/**
	* https://cliff.ecommpay.com/download/%D0%98%D0%BD%D1%81%D1%82%D1%80%D1%83%D0%BA%D1%86%D0%B8%D1%8F%20%D0%BF%D0%BE%20%D0%B2%D1%8B%D0%BF%D0%BB%D0%B0%D1%82%D0%B0%D0%BC%20%D1%87%D0%B5%D1%80%D0%B5%D0%B7%20%D1%84%D0%B0%D0%B9%D0%BB.pdf
	*/
	function csv() {
		$operation_id = intval($_GET['operation_id']);
		$info = db()->from('payment_operation')->where('operation_id', $operation_id)->get();
		if (!$info) {
			return _404();
		}
		$info['options'] = json_decode($info['options'], true);
		$options = $info['options']['request'][0]['options'];
		$opt_data = $info['options']['request'][0]['data'];

		$data = array();
		$data['payment_group_id']	= 1; // Bank cards
		$data['site_id']			= '2415'; // Betonmoney.com
		$data['external_id']		= $operation_id;
		$data['comment']			= 'Payments out request. Date: '.date('Y-m-d_H-i-s').' OID: '.$operation_id;
		$data['phone']				= preg_replace('~[^0-9]~ims', '', $options['sender_phone']);
		$data['customer_purse']		= $options['card'];
#		$data['transaction_id'] = ''; // [обязательный, если customer_purse не используется; пустой, если используется customer_purse]
			// Номер транзакции в Клиентском интерфейсе, по которой ранее был осуществлен прием средств.
			// Обычно используется для выплат на банковские карты при отсутствии сертификата PCI DSS.
		// Валюта, в которой была указана сумма платежа. Если валюта запроса не соответствует валюте счета, с которого будет осуществлен платеж,
		// то система автоматически осуществит пересчет суммы по курсу ЦБ РФ.
#		$data['amount']				= intval($opt_data['amount'] * 100);
#		$data['currency']			= $opt_data['currency_id'];
		$data['amount']				= intval($options['amount'] * 100);
		$data['currency']			= 'USD';

		$data = array($data);
		$csv = $this->_array2csv($data);

		// Ecommpay wants ";" everywhere
		$csv = explode(PHP_EOL, $csv);
		$csv[0] = str_replace(',', ';', $csv[0]);
		$csv = trim(implode(PHP_EOL, $csv));

		no_graphics(true);
		if (DEBUG_MODE) {
			echo '<pre>'; print_r($csv); print_r($opt); print_r($info); print_r($data);
		} else {
			header('Content-disposition: attachment; filename=Ecommpay_out_'.intval($operation_id).'_'.date('Ymd_His').'.csv');
			header('Content-type: text/csv');
			echo $csv;
		}
		exit;
	}

	/**
	*/
	function update(){
		$operation_id = intval($_GET['operation_id']);
		$info = db()->from('payment_operation')->where('operation_id', $operation_id)->get();
		if (!$info) {
			return _404();
		}
		db()->query("START TRANSACTIONS");
		db()->query("UPDATE ".db('payment_operation')." SET status_id = 2 WHERE operation_id = ".$operation_id);
		db()->query("COMMIT");
		return js_redirect("./?object=".__CLASS__);
	}

	/**
	*/
	function reject(){
		$operation_id = intval($_GET['operation_id']);
		$info = db()->from('payment_operation')->where('operation_id', $operation_id)->get();
		if (!$info) {
			return _404();
		}
		db()->query("UPDATE ".db('payment_operation')." SET status_id = 3 WHERE operation_id = ".$operation_id);
		// TODO return money into user balance
		return js_redirect("./?object=".__CLASS__);
	}
}
