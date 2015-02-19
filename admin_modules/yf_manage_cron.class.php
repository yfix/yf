<?php

class yf_manage_cron {

	/**
	*/
	function _init() {
		$this->_interval = array(
			'minutes',
			'hours',
			'days'
		);
		$this->_table = array(
			'table' => 'cron_tasks',
			'fields' => array(
				'name',
				'frequency',
				'active'
			),
		);
		$this->_minutes = array('*' => '*') + range(0, 59);
		$this->_hours 	= array('*' => '*') + range(0, 23);
		$this->_days 	= array('*' => '*') + array_combine(range(1, 31),range(1, 31));
	}

	/**
	*/
	function show() {
		$pattern = 'share/cron_jobs/*cron.php';
		$globs = array(
			'yf_main'			=> YF_PATH. $pattern,
			'yf_plugins'		=> YF_PATH. 'plugins/*'.$pattern,
			'project_main'		=> PROJECT_PATH. $pattern,
			'project_plugins'	=> PROJECT_PATH. 'plugins/*'.$pattern,
			'app_main'			=> APP_PATH. $pattern,
			'app_plugins'		=> APP_PATH. 'plugins/*'.$pattern,
		);
		$paths = array();
		foreach((array)$globs as $glob) {
			foreach (glob($glob) as $path) {
				$paths[$path] = $path;
			}
		}
		foreach((array)$paths as $path) {
			$name = basename($path);
			$data = db()->from('cron_tasks')->where('name', $name)->get();
			if (empty($data)) {
				db()->insert_safe('cron_tasks', array(
					'name'	=>	$name,
				));
			}
		}
		return table('SELECT * FROM '.db('cron_tasks').' ORDER BY `name` ASC')
			->text('name','',array('badge' => 'info'))
			->text('comment', array('width' => 300))
			->text('frequency')
			->btn_edit(array('no_ajax' => 1))
			->btn_active();
	}

	/**
	*/
	function edit() {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e('No id');
		}
		$a = db()->query_fetch('SELECT * FROM '.db('cron_tasks').' WHERE id='.intval($_GET['id']));
		if (empty($a['id'])) {
			return _e('Cron task not found');
		}
		if (main()->is_post()) {
			if (!common()->_error_exists()) {
				$frequency = $_POST['minutes'].' '.$_POST['hours'].' '.$_POST['days'];
				db()->update_safe('cron_tasks', array(
					'comment'		=> $_POST['comment'],
					'frequency'		=> $frequency,
					'update_date'	=> time(),
					'admin_id'		=> main()->ADMIN_ID,	
					'active'		=> $_POST['active'],
				), 'id='.$a['id']);
				common()->admin_wall_add(array('cron tasks updated: '.$a['name'], $a['id']));
			}
		}
		$a['redirect_link'] = url_admin('/@object');
		$cron_timer = explode(' ', $a['frequency']);
		$a['minutes'] 	= $cron_timer[0];
		$a['hours'] 	= $cron_timer[1];
		$a['days'] 		= $cron_timer[2];
		return form((array)$_POST + (array)$a, array('autocomplete' => 'off'))
			->info('name')
			->textarea('comment')
			->select_box('minutes', $this->_minutes, array('class_add' => 'span1 col-md-1', 'type' => 1))
			->select_box('hours', $this->_hours, array('class_add' => 'span1 col-md-1', 'type' => 1))
			->select_box('days', $this->_days, array('class_add' => 'span1 col-md-1', 'type' => 1))
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active($this->_table);
	}
}