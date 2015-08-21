<?php

class yf_manage_news {

	const table = 'news';

	/**
	*/
	function _init() {
// TODO: integrate for edit/upload images
#		require_php_lib('kcfinder');
	}

	/**
	*/
	function _get_info($id = null) {
		$id = isset($id) ? $id : $_GET['id'];
		return db()->from(self::table)
			->where('title', _strtolower(urldecode($id)) )
			->or_where('id', (int)$id)->get();
	}

	/**
	*/
	function show() {
		$data = db()->from(self::table)->order_by('add_date', 'desc')->get_all();
		return table($data, array(
				'pager_records_on_page' => 1000,
			))
			->text('id')
			->date('add_date', array('format' => 'long', 'nowrap' => 1))
			->text('title', array('link' => '/news/show/%url/?lang=%locale', 'rewrite' => 'user'))
			->lang('locale')
			->btn_view('', '/news/show/%url/?lang=%locale', array('rewrite' => 'user', 'btn_no_text' => 1, 'no_ajax' => 1))
			->btn_edit(array('btn_no_text' => 1, 'no_ajax' => 1))
			->btn_delete(array('btn_no_text' => 1))
			->btn_active()
			->footer_add(array('no_ajax' => 1))
		;
	}

	/**
	*/
	function add() {
		db()->insert_safe(self::table, array(
			'add_date'	=> time(),
			'active'	=> 0,
		));
		$id = db()->insert_id();
		module_safe('manage_revisions')->add(self::table, $id, 'add');
		return js_redirect(url('/@object/edit/'.$id));
	}

	/**
	*/
	function edit() {
		$a = $this->_get_info();
		if (!$a) {
			return _404();
		}
		$a['redirect_link'] = url('/@object/@action/@id');
		$a['back_link'] = url('/@object');
		// Prevent execution of template tags when editing page content
		$exec_fix = array('{' => '&#123;', '}' => '&#125;');
		$keys_to_fix = array('head_text', 'full_text');
		foreach ((array)$keys_to_fix as $k) {
			if (false !== strpos($a[$k], '{') && false !== strpos($a[$k], '}')) {
				$a[$k] = str_replace(array_keys($exec_fix), array_values($exec_fix), $a[$k]);
			}
		}
		$a = (array)$_POST + (array)$a;
		if (is_post()) {
			foreach ((array)$keys_to_fix as $k) {
				if (false !== strpos($_POST[$k], '{') && false !== strpos($_POST[$k], '}')) {
					$_POST[$k] = str_replace(array_values($exec_fix), array_keys($exec_fix), $_POST[$k]);
				}
			}
		}
		$_this = $this;
		return form($a)
			->validate(array(
				'__before__'=> 'trim',
				'title'		=> 'required',
				'head_text'	=> 'required',
				'full_text'	=> 'required',
				'url'		=> 'required',
				'locale'	=> 'required',
			))
			->on_post(function() {
				if (strlen($_POST['url'])) {
					$_POST['url'] = preg_replace('~[\s/]+~', '-', trim($_POST['url']));
				}
				if (strlen($_POST['title']) && !strlen($_POST['url'])) {
					$_POST['url'] = common()->_propose_url_from_name($_POST['title']);
				} else
				if (!strlen($_POST['head_text']) && strlen($_POST['full_text'])) {
					$_POST['head_text'] = _truncate($_POST['full_text'], 200, false, false);
				}
			})
			->update_if_ok(self::table, array('title','head_text','full_text','meta_keywords','meta_desc','url','active','locale'))
			->on_before_update(function() use ($a, $_this) {
				module_safe('manage_revisions')->add(array(
					'object_name'	=> $_this::table,
					'object_id'		=> $a['id'],
					'old'			=> $a,
					'new'			=> $_POST,
					'action'		=> 'update',
				));
			})
			->on_after_update(function() {
				common()->admin_wall_add(array('news updated: '.$a['id'], $a['id']));
			})
			->text('title')
			->textarea('head_text', array('cols' => 200, 'rows' => 5, 'ckeditor' => array('config' => _class('admin_methods')->_get_cke_config())))
			->textarea('full_text', array('cols' => 200, 'rows' => 20, 'ckeditor' => array('config' => _class('admin_methods')->_get_cke_config())))
			->text('url')
			->text('meta_keywords')
			->text('meta_desc')
			->locale_box('locale')
			->active_box()
			->save_and_back()
		;
	}

	/**
	*/
	function delete() {
		$id = (int)$_GET['id'];
		if ($id) {
			$a = $this->_get_info();
			module_safe('manage_revisions')->add(array(
				'object_name'	=> self::table,
				'object_id'		=> $a['id'],
				'old'			=> $a,
				'action'		=> 'delete',
			));
			db()->delete(self::table, $id);
		}
		if (is_ajax()) {
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
			$n = $a;
			$n['active'] = (int)!$a['active'];
			module_safe('manage_revisions')->add(array(
				'object_name'	=> self::table,
				'object_id'		=> $a['id'],
				'old'			=> $a,
				'new'			=> $n,
				'action'		=> 'active',
			));
			db()->update_safe(self::table, array('active' => (int)!$a['active']), 'id='.intval($a['id']));
		}
		if (is_ajax()) {
			no_graphics(true);
			return print intval(!$a['active']);
		}
		return js_redirect(url('/@object'));
	}
}
