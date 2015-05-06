<?php

class yf_manage_tips {

	const table = 'tips';

	/**
	*/
	function show() {
		$data = db()->from(self::table)->order_by('name ASC, locale ASC')->get_all();
		foreach ((array)$data as $k => $v) {
			$data[$k]['text'] = strip_tags($v['text']);
		}
		return table($data, array(
				'condensed' => true,
				'pager_records_on_page' => 1000,
				'group_by' => 'name',
			))
			->text('name', array('link' => url('/@object/edit/%id')))
			->lang('locale')
			->text('text')
			->btn_edit(array('no_ajax' => 1, 'btn_no_text' => 1))
			->btn_delete(array('btn_no_text' => 1))
			->btn_active()
			->header_add(array('no_ajax' => 1));
	}

	/**
	*/
	function add() {
		$a = array();
		$a['back_link'] = url('/@object');
		!$a['locale'] && $a['locale'] = conf('language');
		$_this = $this;
		return form((array)$_POST + (array)$a)
			->validate(array(
				'__before__'=> 'trim',
				'name' => 'required',
				'text' => 'required',
				'locale' => 'required',
			))
			->insert_if_ok(self::table, array('name','text','active','locale'))
			->on_after_update(function() use ($_this) {
				$id = db()->insert_id();
				module_safe('manage_revisions')->add($_this::table, $id, 'add');
			})
			->text('name')
			->textarea('text', array('id' => 'text', 'cols' => 200, 'rows' => 10, 'ckeditor' => array('config' => _class('admin_methods')->_get_cke_config())))
			->locale_box('locale')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function edit() {
		$a = $this->_get_info();
		if (!$a) {
			return _404();
		}
		$a['back_link'] = url('/@object');
		return form((array)$_POST + (array)$a)
			->validate(array(
				'__before__'=> 'trim',
				'name' => 'required',
				'text' => 'required',
			))
			->update_if_ok(self::table, array('name','text','active','locale'))
			->on_before_update(function() use ($a, $_this) {
				module_safe('manage_revisions')->add(array(
					'object_name'	=> $_this::table,
					'object_id'		=> $a['id'],
					'old'			=> $a,
					'new'			=> $_POST,
					'action'		=> 'update',
				));
			})
			->container($this->_get_lang_links($a['locale'], $a['name'], 'edit'))
			->text('name')
			->textarea('text', array('id' => 'text', 'cols' => 200, 'rows' => 10, 'ckeditor' => array('config' => _class('admin_methods')->_get_cke_config())))
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		return _class('admin_methods')->delete(array('table' => self::table, 'revisions' => true));
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active(array('table' => self::table, 'revisions' => true));
	}

	/**
	*/
	function clone_item() {
		return _class('admin_methods')->clone_item(array('table' => self::table, 'revisions' => true));
	}

	/**
	*/
	function _get_lang_links($cur_lang = null, $cur_name = null, $link_for = 'edit') {
		asset('bfh-select');
		$this->lang_def_country = main()->get_data('lang_def_country');

		foreach((array)db()->select('name, locale')->from(self::table)->get_all() as $p) {
			$this->pages_langs[$p['name']][$p['locale']] = $p['locale'];
		}

		$lang_links = array();
		foreach (main()->get_data('locale_langs') as $lang => $l) {
			$is_selected = ($lang === $cur_lang);
			$icon = 'bfh-flag-'.$this->lang_def_country[$lang];
			if (!isset($this->pages_langs[$cur_name][$lang])) {
				$icon = array('fa fa-plus', $icon);
				$class = 'btn-warning';
			} else {
				$class = 'btn-success'. ($is_selected ? ' disabled' : '');
			}
			$lang_links[] = a('/@object/'.$link_for.'/'.urlencode($cur_name).'/'.$lang, strtoupper($lang), $icon, null, $class, '');
		}
		return implode(PHP_EOL, $lang_links).' '.a('/locale_editor', 'Edit locales', 'fa fa-edit');
	}

	/**
	*/
	function _get_info($id = null, $lang = null) {
		$id = isset($id) ? $id : $_GET['id'];
		$lang = isset($lang) ? $lang : $_GET['page'];
		$a = db()->from(self::table)
			->where('locale', $lang ? strtolower($lang) : '')
			->where('name', _strtolower(urldecode($id)) )
			->or_where('id', (int)$id)
			->get()
		;
		if ($a) {
			return $a;
		} elseif ($lang) {
			$all_langs = main()->get_data('locale_langs');
			if (!isset($all_langs[$lang])) {
				return false;
			}
			// Try with first lang as fallback
			$a = db()->from(self::table)
				->where('name', _strtolower(urldecode($id)) )
				->or_where('id', (int)$id)
				->get()
			;
			// Create missing page
			if ($a && $a['locale'] && $lang !== $locale) {
				$new = $a;
				unset($new['id']);
				$new['active'] = 0;
				$new['locale'] = $lang;
				db()->insert_safe(self::table, $new);
				$new['id'] = db()->insert_id();
				return $new;
			}
			return $a;
		}
		return false;
	}
}
