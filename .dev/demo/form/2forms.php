<?php

return function() {
	$request_types = [];

	$body[] = form(false, [
			'legend' => t('Please specify fines:'),
			'name'   => 'form_add_fine',
			'id'     => 'add_fines'
		])
		->validate(['__form_id__' => 'add_fines'])
		->db_update_if_ok('requests', ['fine', 'type', 'fine_delay', 'fine_increase_period'], $request_id , [
			'redirect_link' => url('/@object/@action/'.$request_id),
			'add_fields'    => ['status' => 4],
		])
		->row_start(['desc' => 'Rules for fine', 'label_tip' => 'test label tip'])
			->number('fine', 'Amount fine', ['class_add' => 'input-small', 'min' => 1, 'max' => 1000])
			->button(conf('currency_list::'.$info['currency']), ['disabled' => 1])
			->select_box('type', ['fixed' => 'fixed', 'percent' => 'percent'])
		->row_end()
		->row_start(['desc' => ''])
			->number('fine_delay', '', ['class_add' => 'input-small', 'min' => 1, 'max' => 1000])
			->number('fine_increase_period', '', ['class_add' => 'input-small', 'min' => 1, 'max' => 1000])
		->row_end()
		->submit('send', false, ['value' => 'Apply', 'class_add' => 'btn-success']);

	$body[] = form(false, [
			'legend' => 'cancel',
			'name'   => 'cancel_request',
			'id'     => 'form_cancel_request'
		])
		->validate(['__form_id__' => 'cancel_request'])
		->db_update_if_ok('requests', false, $request_id, [
			'on_success_text' => t('Your request has been sent successfully!'), 
			'force'           => true,
			'add_fields'      => ['cancel_time' => time(), 'status' => $request_types],
		])
		->on_after_update(function($data, $table, $fields, $type, $extra) {
			common()->message_success('All ok');
		})
		->on_after_render(function($extra, $r, $_this) {
			if (is_post()) {
				$_this->_rendered = '';
			}
		})
		->submit('cancel', false, ['value' => $request_owner == false ? 'Reject' : 'Cancel', 'class_add' => 'btn-danger']);

	return implode(PHP_EOL, $body);
};
