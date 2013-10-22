<?php

/**
*/
class yf_test_form2 {

	/**
	*/
	function test () {
		$data = array(
			'0'	=> 'value0',
			'1'	=> 'value1',
			'2'	=> 'value2',
		);
		$methods = array();
		$except = array('auto', 'validate', 'db_update_if_ok', 'db_insert_if_ok', 'form_begin', 'form_end', 'render', 'custom_fields', 'tpl_row','row_start','row_end','navbar_start','navbar_end');
		foreach (get_class_methods(form()) as $m) {
			if ($m[0] == '_' || in_array($m, $except)) {
				continue;
			}
			$methods[$m] = $m;
		}
		ksort($methods);
#return print_r($methods);
		$form = form();
		foreach ($methods as $m) {
			if (false !== strpos($m, '_box') || false !== strpos($m, 'select')) {
				$item = form('', array('no_form' => 1))->$m($m, $data, array('stacked' => 1));
			} else {
				$item = form('', array('no_form' => 1))->$m($m, array('stacked' => 1));
			}
			$form->container($item, array('desc' => $m));
		}
		return $form;
/*
		return form2()
			->input('input')
			->textarea('textarea')
			->container('container')
			->hidden('hide_me')
			->text('text')
			->password('password')
			->file('file')
			->email()
			->number('number')
			->integer('integer')
			->money('money')
			->url('url')
			->color('color')
			->date('date')
			->datetime('datetime')
			->datetime_local('datetime_local')
			->month('month')
			->range('range')
			->search('search')
			->tel('tel')
			->time('time')
			->week('week')
			->active_box()
			->allow_deny_box('allow')
			->yes_no_box('yes_no')
			->submit()
			->save()
			->save_and_back()
			->save_and_clear()
			->info('info')
			->select_box('select_box', $data)
			->multi_select_box('multi_select_box', $data)
			->check_box('check_box', $data)
			->multi_check_box('multi_check_box', $data)
			->radio_box('radio_box', $data)
			->date_box('date_box')
			->time_box('time_box')
			->datetime_box('datetime_box')
			->birth_box()
			->country_box()
			->region_box()
			->currency_box()
			->language_box()
			->timezone_box()
			->method_select_box()
			->template_select_box()
			->icon_select_box()
			->image("image")
//			->box('my_box')
//			->box_with_link('my_box')
			->tbl_link('test link', './?test_url')
			->tbl_link_edit()
			->tbl_link_delete()
			->tbl_link_clone()
			->tbl_link_active()
		;
*/
	}
}
