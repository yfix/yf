<?php

class form2_filter_sample {
	function show() {
		$filter_name = __CLASS__.'__'.__FUNCTION__;
		$offer_types = array(
			'buy' => 'buy',
			'ask' => 'ask',
		);
		$currencies = array(
			'UAH' => 'UAH',
			'USD' => 'USD',
		);
		$split_period = array(
			'1 day' => '1 day',
			'2 days' => '2 days',
			'3 days' => '3 days',
		);
		$order_fields = array(
			'id', 'title', 'amount', 'percent'
		);
		return form($replace, array(
				'selected' => $_SESSION[$filter_name],
				'class' => 'form-inline',
			))
			->text('title', array('class' => 'input-medium', 'tip' => 'Title field helping description'))
			->select_box('type', $offer_types, array('show_text' => 1, 'class' => 'input-medium'))
			->select_box('currency', $currencies, array('show_text' => 1, 'class' => 'input-medium'))

			->ui_range('amount')

			->row_start(array('desc' => 'Amount from/to'))
				->money('amount')
				->money('amount__and')
			->row_end()
			->row_start(array('desc' => 'Interest rate from/to'))
				->number('percent', array('class' => 'input-small'))
				->number('percent__and', array('class' => 'input-small'))
			->row_end()
			->row_start(array('desc' => 'per'))
				->select_box('split_period', $split_period, array('show_text' => 1, 'class' => 'input-medium'))
			->row_end()
			->select_box('order_by', $order_fields, array('show_text' => 1, 'class' => 'input-medium'))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending')/*, array('selected' => 'asc')*/)
			->save_and_clear()
			. $this->_self_source(__FUNCTION__)
		;
	}
	function _self_source($method) {
		asset('highlightjs');
		$source = _class('core_api')->get_method_source(__CLASS__, $method);
		return '<div id="func_self_source_'.$name.'"><pre class="prettyprint lang-php"><code>'._prepare_html($source['source']).'</code></pre></div> ';
	}
}
