<?php

class form2_events {
	function show () {
		$a = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'],
		);
		return form((array)$_POST + $a)
			->on_post(function(){
				common()->message_info('on post');
			})
			->on_before_render(function(){
				common()->message_info('on before render');
			})
			->on_after_render(function(){
				common()->message_info('on after render');
			})
			->on_validate_error(function(){
				common()->message_info('on validate error');
			})
			->on_before_validate(function(){
				common()->message_info('on before validate');
			})
			->on_after_validate(function(){
				common()->message_info('on after validate');
			})
			->on_before_update(function(){
				common()->message_info('on before update');
			})
			->on_after_update(function(){
				common()->message_info('on after update');
				cache_del('forum_categories');
			})
			->validate(array(
				'name'	=> 'trim|required',
			))
			->db_insert_if_ok('forum_categories', array('name','desc','order','status'))
			->text('name')
			->textarea('desc', 'Description')
			->number('order')
			->active_box('status')
			->save();
	}
}