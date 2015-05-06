<?php

class yf_manage_faq {

	const table = 'faq';

	/***/
	function show() {
		asset('bfh-select');
		if (!isset($this->lang_def_country)) {
			$this->lang_def_country = main()->get_data('lang_def_country');
		}
		$all_langs = main()->get_data('locale_langs');
		foreach ($all_langs as $lang => $info) {
			$body[] = '<h3>'.html()->icon('bfh-flag-'.$this->lang_def_country[$lang], strtoupper($lang)).'</h3>';
			$body[] = $this->_show_for_lang($lang);
		}
		css('.draggable-menu-expand-all { display: none; } .draggable_menu .controls_over { display: block; }');
		return implode(PHP_EOL, $body);
	}

	/***/
	function _show_for_lang($lang) {
		$all = db()->from(self::table)->where('locale', $lang)->get_all();
		$items = array();
		foreach ($all as $a) {
			$items[$a['id']] = array(
				'parent_id'	=> $a['parent_id'],
				'name'		=> _truncate($a['title'], 60, true, '...'),
				'link'		=> url('/@object/edit/'.$a['id']),
				'active'	=> $a['active'],
			);
		}
		return html()->tree($items, array(
			'draggable' => false,
			'class_add' => 'no_hide_controls',
			'show_controls' => function($id, $item) {
				$form = form_item($item + array(
					'add_link'		=> url('/@object/add/'.$id),
					'edit_link'		=> url('/@object/edit/'.$id),
					'delete_link'	=> url('/@object/delete/'.$id),
					'active_link'	=> url('/@object/active/'.$id),
				));
				return implode(PHP_EOL, array(
					$form->tbl_link_add(array('hide_text' => 1, 'no_ajax' => 1)),
					$form->tbl_link_edit(array('hide_text' => 1, 'no_ajax' => 1)),
					$form->tbl_link_delete(array('hide_text' => 1)),
					$form->tbl_link_active(),
				));
			},
		))
		. form_item(array('add_link' => url('/@object/add/0/'.$lang)))->tbl_link_add();
	}

	/***/
	function add() {
		$a['locale'] = substr($_GET['page'], 0, 2);
		$a['parent_id'] = (int)$_GET['id'];
		$parent = $a['parent_id'] ? (array)db()->from(self::table)->whereid($a['parent_id'])->get() : array();
		if (!$a['locale'] && $parent['locale']) {
			$a['locale'] = $parent['locale'];
		}
		$a['parent_name'] = $parent ? $parent['title'] : '';
		$a['back_link'] = url('/@object');
		$_this = $this;
		return form((array)$_POST + (array)$a)
			->validate(array(
				'title' => 'trim|required',
				'text' => 'trim',
			))
			->db_insert_if_ok(self::table, array('title','text','parent_id','active','locale'), array('add_date' => time(), 'author_id' => main()->ADMIN_ID))
			->on_after_update(function() use ($_this) {
				$id = db()->insert_id();
				module_safe('manage_revisions')->add($_this::table, $id, 'add');
				js_redirect(url('/@object'));
			})
			->hidden('parent_id')
			->hidden('locale')
			->info_lang('locale')
			->info('parent_name', array('display_func' => function($extra, $row) { return (bool)strlen($row['parent_name']); }))
			->text('title')
			->textarea('text', array('id' => 'text', 'cols' => 200, 'rows' => 10, 'ckeditor' => array('config' => _class('admin_methods')->_get_cke_config())))
			->active_box()
			->save_and_back()
		;
	}

	/***/
	function edit() {
		$id = (int)$_GET['id'];
		if ($id) {
			$a = db()->from(self::table)->whereid($id)->get();
		}
		if (!$a) {
			return _404();
		}
		$a['parent_name'] = $a['parent_id'] ? db()->select('title')->from(self::table)->whereid($a['parent_id'])->get_one() : '';
		$a['back_link'] = url('/@object');
		$_this = $this;
		return form((array)$_POST + (array)$a)
			->validate(array(
				'title' => 'trim|required',
				'text' => 'trim',
			))
			->update_if_ok(self::table, array('title','text','active','locale'))
			->on_before_update(function() use ($a, $_this) {
				module_safe('manage_revisions')->add(array(
					'object_name'	=> $_this::table,
					'object_id'		=> $a['id'],
					'old'			=> $a,
					'new'			=> $_POST,
					'action'		=> 'update',
				));
			})
			->on_after_update(function(){ js_redirect(url('/@object')); })
			->hidden('parent_id')
			->info_lang('locale')
			->info('parent_name', array('display_func' => function($extra, $row) { return (bool)strlen($row['parent_name']); }))
			->text('title')
			->textarea('text', array('id' => 'text', 'cols' => 200, 'rows' => 10, 'ckeditor' => array('config' => _class('admin_methods')->_get_cke_config())))
			->active_box()
			->save_and_back()
		;
	}

	/**
	*/
	function delete() {
		$id = (int)$_GET['id'];
		if ($id) {
			$a = db()->from(self::table)->whereid($id)->get();
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
	function active () {
		$id = (int)$_GET['id'];
		if ($id) {
			$a = db()->from(self::table)->whereid($id)->get();
		}
		if ($a) {
			$n = $a;
			$n['active'] = (int)!$a['active'];
			module_safe('manage_revisions')->add(array(
				'object_name'	=> self::table,
				'object_id'		=> $a['id'],
				'old'			=> $a,
				'new'			=> $n,
				'action'		=> 'active',
			));
			db()->update_safe(self::table, array('active' => (int)!$a['active']), $id);
		}
		if (is_ajax()) {
			no_graphics(true);
			echo (int)(!$a['active']);
		} else {
			return js_redirect(url('/@object'));
		}
	}
}