<?php

class form2_events {
	function show () {
		$a = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'],
		);
		return form((array)$_POST + $a)
			->on_post(function(){
				echo 'on post<br />'.PHP_EOL;
			})
			->on_before_render(function(){
				echo 'on before render<br />'.PHP_EOL;
			})
			->on_after_render(function(){
				echo 'on after render<br />'.PHP_EOL;
			})
			->on_validate_error(function(){
				echo 'on validate error<br />'.PHP_EOL;
			})
			->on_before_validate(function(){
				echo 'on before validate<br />'.PHP_EOL;
			})
			->on_after_validate(function(){
				echo 'on after validate<br />'.PHP_EOL;
			})
			->on_before_update(function(){
				echo 'on before update<br />'.PHP_EOL;
			})
			->on_after_update(function(){
				echo 'on after update<br />'.PHP_EOL;
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