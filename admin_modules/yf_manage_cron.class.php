<?php

class yf_manage_cron {

	/**
	*/
	function _init() {
		$this->_interval = [
			'minutes',
			'hours',
			'days'
		];
		$this->_table = [
			'table' => 'cron_tasks',
			'fields' => [
				'name',
				'frequency',
				'active'
			],
		];
		$this->exec_type = [
			'sh' => 'sh', 
			'include_php' => 'include_php',
			'php_script' => 'php_script',
		];
	}

	/**
	*/
	function show() {
		$path = glob(YF_PATH.'share/cron_jobs/*cron.php') + glob(YF_PATH.'plugins/*/share/cron_jobs/*cron.php');

		foreach ((array)$path as $name) {
			$cron_name = basename($name);
			$cron_dir = str_replace($cron_name, '', $name);
			$return = db()->from('cron_tasks')->where('name', $cron_name)->get();
			if (empty($return)) {
				db()->insert_safe('cron_tasks', [
					'name'	=>	$cron_name,
					'dir'	=>  $cron_dir,
					'update_date'	=> time(),
					'admin_id'	=> main()->ADMIN_ID,
				]);
			}
		}
		return table(
				'SELECT c.*, a.login FROM '.db('cron_tasks').' as c
				LEFT JOIN '.db('sys_admin').' as a ON a.id = c.admin_id
				ORDER BY c.name ASC'
			)
			->text('name')
			->text('dir', 'Directory')
			->text('comment', ['width' => 300])
			->text('frequency')
			->func('exec_type', function($extra, $r, $_this) {
				return $this->exec_type[$extra];
			})
			->text('exec_time', 'Max exec time, s')
			->text('login')
			->date('update_date', ['format' => '%d-%m-%Y %H:%M'])
			->btn_edit(['no_ajax' => 1])
			->btn_func('Logs', function($value, $extra, $row_info){
				$action_url = url('/@object/cron_logs/'.$value['id']);
				return '<a href="'.$action_url.'" class="btn btn-default btn-mini btn-xs" title="Просмотр"><i class="fa fa-lg fa-eye eye_view"></i>Logs</a>';
			}, ['no_ajax' => 1])
			->btn_active();
	}

	/**
	*/
	function edit() {
		$id = intval($_GET['id']);
		if (!$id) {
			return js_redirect(url('/@object'), false, 'Empty ID');
		}
		$a = db()->from('cron_tasks')->whereid($id)->get();
		if (empty($a['id'])) {
			common()->message_error('Cron task not found');
			return js_redirect(url('/@object'), false, 'Cron task not found');
		}
		$file_content = nl2br(file_get_contents($a['dir'].$a['name']));
		return form($a, ['autocomplete' => 'off'])
			->validate([
				'exec_time' => 'trim|decimal|required',
			])
			->on_post(function(){
				db()->update_safe('cron_tasks', [
					'comment'		=> $_POST['comment'],
					'frequency'		=> $_POST['digits'].' '.$_POST['units'],
					'update_date'	=> time(),
					'admin_id'		=> main()->ADMIN_ID,	
					'active'		=> $_POST['active'],
					'exec_type'		=> $_POST['exec_type'],
					'exec_time'		=> $_POST['exec_time']? : '600',
				], 'id='.intval($_GET['id']));
				common()->admin_wall_add(['cron tasks updated: '.$a['name'], $a['id']]);
				return js_redirect(url('/@object')); 
			})
			->info('name')
			->container('<pre>'.$file_content.'</pre>', 'Content')
			->select_box('exec_type', $this->exec_type)
			->container($this->_select_f(), 'Start every')
			->integer('exec_time', 'Max exec time, s', [
				'placeholder'    => 'время выполнения, секунд',
			])
			->textarea('comment')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active($this->_table);
	}

	/**
	*/
	function cron_logs() {
		return table(
				db()->from('cron_logs')->where('cron_id', (int)$_GET['id'])->order_by('time_start', 'desc')
			)
			->date('time_start', ['format' => 'full'])
			->text('log')
			->text('time_spent')
		;
	}

	/**
	*/
	function _select_f() {
		$digits = range(1, 59);
		$units = [
			'minutes' => 'minutes',
			'hours' => 'hours',
			'days'	=> 'days',
		];
		foreach ($digits as $d) {
			$options_d .= '<option value="'.$d.'">'.$d.'</option>';
		}
		foreach ($units as $u) {
			$options_u .= '<option value="'.$u.'">'.$u.'</option>';	
		}	
		return '
			<select id="select_box_1" class="form-inline" class="span1" name="digits">'.$options_d.'</select>
			<select id="select_box_2" class="form-inline" class="span1" name="units">'.$options_u.'</select>
		';
	}
}
