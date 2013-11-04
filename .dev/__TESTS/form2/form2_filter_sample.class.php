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
// TODO
//				'class_for_all' => 'span2',
			))
			->text('title', array('class' => 'input-medium'))
			->select_box('type', $offer_types, array('show_text' => 1))
			->select_box('currency', $currencies, array('show_text' => 1))

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
				->select_box('split_period', $split_period, array('show_text' => 1))
			->row_end()
			/*
			->number('duration')
			->number('duration__and')
			*/
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
	}
}
