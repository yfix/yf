<?php

class form2_2forms {
	function show() {
		$request_types = array();

		$body[] = form(false, array(
				'legend' => t('Please specify fines:'),
				'name'   => 'form_add_fine',
				'id'     => 'add_fines'
			))
			->validate(array('__form_id__' => 'add_fines'))
			->db_update_if_ok('requests', array('fine', 'type', 'fine_delay', 'fine_increase_period'), $request_id , array(
				'redirect_link' => '/?object=offers&action=view&id='.$request_id,
				'add_fields'    => array('status' => 4),
			))
			->row_start(array('desc' => 'Rules for fine'))
				->number('fine', 'Amount fine', array('class' => 'input-small', 'min' => 1, 'max' => 1000))
				->button(conf('currency_list::'.$info['currency']), array('disabled' => 1))
				->select_box('type', array('fixed' => 'fixed', 'percent' => 'percent'))
			->row_end()
			->row_start(array('desc' => ''))
				->number('fine_delay', '', array('class' => 'input-small', 'min' => 1, 'max' => 1000))
				->number('fine_increase_period', '', array('class' => 'input-small', 'min' => 1, 'max' => 1000))
			->row_end()
			->submit('send', false, array('value' => 'Apply', 'class' => 'btn-success'));

		$body[] = form(false, array(
				'legend' => 'cancel',
				'name'   => 'cancel_request',
				'id'     => 'form_cancel_request'
			))
			->validate(array('__form_id__' => 'cancel_request'))
			->db_update_if_ok('requests', false, $request_id, array(
				'on_success_text' => t('Your request has been sent successfully!'), 
				'force'           => true,
				'add_fields'      => array('cancel_time' => time(), 'status' => $request_types),
			))
			->on_after_update(function($data, $table, $fields, $type, $extra) {
				common()->message_success('All ok');
			})
			->on_after_render(function($extra, $r, $_this) {
				if (main()->is_post()) {
					$_this->_rendered = '';
				}
			})
			->submit('cancel', false, array('value' => $request_owner == false ? 'Reject' : 'Cancel', 'class' => 'btn-danger'));

		return implode(PHP_EOL, $body);
	}
}
