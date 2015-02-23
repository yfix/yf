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
		$this->exec_type = array(
			'sh' => 'sh', 
			'include_php' => 'include_php',
			'php_script' => 'php_script',
		);
	}

	/**
	*/
	function show() {
		$path = glob(YF_PATH.'share/cron_jobs/*cron.php') + glob(YF_PATH.'plugins/*/share/cron_jobs/*cron.php');

		foreach($path as $name){
			$cron_name = basename($name);
			$cron_dir = str_replace($cron_name, '', $name);
			$return = db()->get("SELECT * FROM ".db('cron_tasks')." WHERE `name`= '".$cron_name."'");
			if(empty($return)){
				db()->insert_safe('cron_tasks', array(
					'name'	=>	$cron_name,
					'dir'	=>  $cron_dir,
					'update_date'	=> time(),
					'admin_id'	=> main()->ADMIN_ID,
				));
			}
		}
		return table("SELECT * FROM ".db('cron_tasks')." ORDER BY `name` ASC")
			->text('name','',array('badge' => 'info'))
			->text('frequency')
			->func('exec_type', function($extra, $r, $_this) {
				return $this->exec_type[$extra];
			})
			->text('exec_time')
			->text('comment', array('width' => 300))
			->text('dir', 'Directory')
			->text('admin_id')
			->date('update_date', array('format' => '%d-%m-%Y'))
			->btn_edit(array('no_ajax' => 1))
			->btn_func('Logs', function($value, $extra, $row_info){
				$action_url = url_admin('/@object/cron_logs/'.$value['id']);
				return '<a href="'.$action_url.'" class="btn btn-default btn-mini btn-xs" title="Просмотр"><i class="fa fa-lg fa-eye eye_view"></i>Logs</a>';
			},array('no_ajax' => 1))
			->btn_active();
	}

	/**
	*/

	function edit() {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return js_redirect(url_admin('/@object'), false, 'Empty ID');
		}
		$a = db()->query_fetch('SELECT * FROM '.db('cron_tasks').' WHERE id='.intval($_GET['id']));
		if (empty($a['id'])) {
			common()->message_error('Cron task not found');
			return js_redirect(url_admin('/@object'), false, 'Cron task not found');
		}
		$file_content = nl2br(file_get_contents($a['dir'].$a['name']));
		return form($a, array('autocomplete' => 'off'))
			->on_post(function(){
				db()->update_safe('cron_tasks', array(
					'comment'		=> $_POST['comment'],
					'frequency'		=> $_POST['digits'].' '.$_POST['units'],
					'update_date'	=> time(),
					'admin_id'		=> main()->ADMIN_ID,	
					'active'		=> $_POST['active'],
					'exec_type'		=> $_POST['exec_type'],
					'exec_time'		=> $_POST['exec_time']? : '600',
				), 'id='.intval($_GET['id']));
				common()->admin_wall_add(array('cron tasks updated: '.$a['name'], $a['id']));
				return js_redirect(url_admin('/@object')); 
			})
			->info('name')
			->container('<pre>'.$file_content.'</pre>')
			->select_box('exec_type', $this->exec_type)
			->container($this->select_f(), 'Start every')
			->text('exec_time', array(
				'placeholder'    => "время выполнения, секунд",
			))
			->textarea('comment')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active($this->_table);
	}

	function cron_logs(){
		return table("SELECT * FROM ".db('cron_logs')." WHERE cron_id=".intval($_GET['id'])." ORDER BY time_start DESC")
			->date('time_start', array('format' => 'full'))
			->text('log')
			->text('time_spent');

	}

	function select_f(){
		$digits = range(1,59);
		$units = array(
			'minutes' => 'minutes',
			'hours' => 'hours',
			'days'	=> 'days',
		);
		foreach($digits as $d){
			$options_d .= "<option value=".$d.">".$d."</option>";
		}
		foreach($units as $u){
			$options_u .= "<option value=".$u.">".$u."</option>";	
		}	
		return '
				<select id="select_box_1" class="form-inline" class="span1" name="digits">'.$options_d.'</select>
				<select id="select_box_2" class="form-inline" class="span1" name="units">'.$options_u.'</select>
		';
	}
}

