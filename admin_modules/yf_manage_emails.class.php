<?php

class yf_manage_emails {

	const table = 'emails_templates';

	/**
	*/
	function _get_info($id = null) {
		$id = isset($id) ? $id : $_GET['id'];
		return db()->from(self::table)
			->where('name', _strtolower(urldecode($id)) )
			->or_where('id', (int)$id)->get();
	}

	/**
	*/
	function show() {
		$data = db()->from(self::table)->get_all();
		return table($data, array('pager_records_on_page' => 100))
			->text('name', array('link' => url('/@object/view/%d')))
			->text('subject')
			->func('parent_id', function($pid) use ($data) { return $pid ? $data[$pid]['name'] : ''; } )
			->func('text', function($text) { return strlen($text); }, array('desc' => 'Text length') )
			->btn('Raw', url('/@object/raw/%d'), array('target' => '_blank', 'btn_no_text' => 1))
			->btn_view('Preview', url('/@object/view/%d'), array('btn_no_text' => 1))
			->btn('Test send', url('/@object/test_send/%d'), array('icon' => 'fa fa-send', 'no_ajax' => 1, 'btn_no_text' => 1))
			->btn_edit(array('no_ajax' => 1, 'btn_no_text' => 1))
			->btn_delete(array('btn_no_text' => 1))
			->btn_active()
			->footer_add(array('no_ajax' => 1))
		;
	}

	/**
	*/
	function test_send() {
		$a = $this->_get_info();
		if (empty($a)) {
			return _404();
		}
		$cur_admin_email = db()->select('email')->from('admin')->whereid(main()->ADMIN_ID)->get_one();
		$a = (array)$_POST + array(
			'to_email'		=> $cur_admin_email ?: SITE_ADMIN_EMAIL,
			'to_name'		=> 'test email receiver',
			'to_subject'	=> '[email tpl #'.$a['name'].'] '.$a['subject'],
		) + $a;
		return form($a)
			->validate(array('to_email' => 'trim|email|required'))
			->on_validate_ok(function($post) use ($a) {
				$result = _class('email')->_send_email_safe($a['to_email'], $a['to_name'], $a['name'], $a, $instant_send = true, array('subject' => $a['to_subject'], 'force_send' => true));
				if ($result) {
					common()->message_success('Test email sent successfully');
 				} else {
					common()->message_error('Test email sending failed');
				}
			})
			->email('to_email')
			->text('to_name')
			->text('to_subject')
			->submit(array('icon' => 'fa fa-send', 'value' => 'Send'))
			->info('name', 'Email template')
			->container('<iframe src="'.url('/@object/raw/@id').'" width="100%" height="600" frameborder="0" vspace="0" style="background:white;">Your browser does not support iframes!</iframe>')
			->container('<iframe src="'.url('/@object/raw_text/@id').'" width="100%" height="600" frameborder="0" vspace="0" style="background:white;">Your browser does not support iframes!</iframe>')
		;
	}

	/**
	*/
	function raw() {
		$a = $this->_get_info();
		if (empty($a)) {
			return _404();
		}
		list($subject, $html) = _class('email')->_get_email_text($replace, array('tpl_name' => $a['name']));
		no_graphics(true);
		$charset = conf('charset');
		header('Content-type: text/html, charset='.$charset);
		header('Content-language: '.conf('language'));
		return print $html;
	}

	/**
	*/
	function raw_text() {
		$a = $this->_get_info();
		if (empty($a)) {
			return _404();
		}
		list($subject, $html) = _class('email')->_get_email_text($replace, array('tpl_name' => $a['name']));
		$text = _class('email')->_text_from_html($html);
		no_graphics(true);
		$charset = conf('charset');
		header('Content-type: text/plain, charset='.$charset);
		header('Content-language: '.conf('language'));
		return print $text;
	}

	/**
	*/
	function view() {
		$a = $this->_get_info();
		if (empty($a)) {
			return _404();
		}
		return 
			'<h3>HTML</h3>'.
			'<iframe src="'.url('/@object/raw/@id').'" width="100%" height="600" frameborder="0" vspace="0" style="background:white;">Ваш браузер не поддерживает ифреймы!</iframe>'.
			'<h3>TEXT</h3>'.
			'<iframe src="'.url('/@object/raw_text/@id').'" width="100%" height="600" frameborder="0" vspace="0" style="background:white;">Ваш браузер не поддерживает ифреймы!</iframe>'
		;
	}

	/**
	*/
	function add() {
		db()->insert_safe(self::table, array(
			'name' => date('YmdHis'),
			'active' => 0,
		));
		return js_redirect(url('/@object/edit/'.db()->insert_id()));
	}

	/**
	*/
	function edit() {
		$a = $this->_get_info();
		if (!$a) {
			return _404();
		}
		$a = (array)$_POST + (array)$a;
		$a['redirect_link'] = url('/@object/@action/@id');
		$a['back_link'] = url('/@object');

		$div_id = 'text_div';
		$hidden_id = 'text';

		$_this = $this;
		return form($a, array(
				'data-onsubmit' => '$(this).find("#'.$hidden_id.'").val( $("#'.$div_id.'").data("ace_editor").session.getValue() );',
			))
			->validate(array(
				'__before__'=> 'trim',
				'name'		=> 'required|alpha_dash',
				'subject'	=> 'required',
				'text'		=> 'required',
			))
			->db_update_if_ok(self::table, array('name','subject','text','active','parent_id'))
			->on_after_update(function() use ($a) {
				common()->admin_wall_add(array('Email template updated: '.$a['name'], $a['id']));
			})
			->text('name')
			->text('subject')
			->container('<div id="'.$div_id.'" style="width: 90%; height: 500px;">'._prepare_html($a['text']).'</div>', '', array(
				'id'	=> $div_id,
				'wide'	=> 1,
				'ace_editor' => array('mode' => 'html'),
			))
			->hidden($hidden_id)
			->select_box('parent_id', db()->from(self::table)->where('id != '.$a['id'])->get_2d('id, name'), array('show_text' => '== Select parent template =='))
			->active_box()
			->save_and_back()
		;
	}

	/**
	*/
	function delete() {
		$id = (int)$_GET['id'];
		if ($id) {
			db()->delete(self::table, $id);
			common()->admin_wall_add(array('Email temptate deleted: '.$id, $id));
		}
		if (main()->is_ajax()) {
			no_graphics(true);
			echo $id;
		} else {
			return js_redirect(url('/@object'));
		}
	}

	/**
	*/
	function active() {
		$id = (int)$_GET['id'];
		if ($a = $this->_get_info()) {
			db()->update_safe(self::table, array('active' => (int)!$a['active']), 'id='.intval($a['id']));
			common()->admin_wall_add(array('Email template: '.$a['name'].' '.($a['active'] ? 'inactivated' : 'activated'), $a['id']));
		}
		if (main()->is_ajax()) {
			no_graphics(true);
			return print intval(!$a['active']);
		}
		return js_redirect(url('/@object'));
	}

	/**
	*/
	function test_send_from_console() {
		if (!is_console()) {
			return _404('Only for console testing');
		}
		_class('email')->_send_admin_email('moved_to_arbitration', 1);
	}
}