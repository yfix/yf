<?php

/**
*/
class yf_locale_editor_autotranslate {

	/**
	*/
	function _init () {
		$this->_parent = module('locale_editor');
	}

	/**
	* Automatic translator via Google translate
	*/
	function autotranslate() {
		$a['back_link'] = url('/@object/vars');
		$a['redirect_link'] = $a['back_link'];
		!$a['lang_from'] && $a['lang_from'] = 'en';
		!isset($a['max_items']) && $a['max_items'] = 100;
		!isset($a['keep_existing']) && $a['keep_existing'] = 1;
		// To ensure that currently active langs are in top of the list
		$langs = [];
		foreach ((array)$this->_parent->_cur_langs as $lang => $name) {
			$langs[$lang] = $name;
		}
		$langs[''] = '-------------';
		foreach ((array)$this->_parent->_langs as $lang => $name) {
			$langs[$lang] = $name;
		}
		$display_func = function() { return !is_post(); };
		return $this->_parent->_header_links(). '<div class="col-md-12"><br>'. 
			form($a + (array)$_POST)
			->validate([
				'lang_from' => 'required',
				'lang_to' => 'required',
				'max_items' => 'required',
			])
			->on_validate_ok(function($data,$e,$vr,$form) { return $this->_on_validate_ok($data, $form); })
			->select_box('lang_from', $this->_parent->_cur_langs, ['display_func' => $display_func])
			->select_box('lang_to', $langs, ['display_func' => $display_func])
			->number('max_items', ['class_add' => 'input-small', 'display_func' => $display_func])
			->yes_no_box('keep_existing', ['display_func' => $display_func])
			->save_and_back('', ['desc' => 'Translate', 'display_func' => $display_func])
		.'</div>';
	}

	/**
	*/
	function _on_validate_ok($params = [], $form = null) {
		$p = $params ?: $_POST;
		$lang = $p['lang_to'];
		$lang_from = $p['lang_from'] ?: 'en';
		$max_items = (int)$p['max_items'];
		$keep_existing = $p['keep_existing'];

		$to_tr = [];
		foreach ((array)$this->_parent->_get_all_vars() as $source => $a) {
			// Fix for source vars written in russian
			if ($lang_from == 'en' && preg_match('~[а-яА-Я]+~imsu', $source)) {
				continue;
			}
			$tr = isset($a['translation'][$lang]) ? $a['translation'][$lang] : null;
			if ($keep_existing && $tr) {
				continue;
			}
			if (!strlen($tr) || _strtolower($tr) == _strtolower($source)) {
				$to_tr[$source] = $a['var_id'];
			}
			if (count($to_tr) >= $max_items) {
				break;
			}
		}
		if (!$to_tr) {
			common()->message_info('Translate finished, 0 variables to translate');
			return ;
		}
		set_time_limit(300 + $max_items * 5);

		$services = services();

		$to_update = [];
		$failed = [];
		// var_id can be empty if it is got from files
		foreach ((array)$to_tr as $source => $var_id) {
			$source_for_tr = $source;
			// cutoff all vars starting from % or @
			$map = [];
			$need_map = (false !== strpos($source_for_tr, '%') || false !== strpos($source_for_tr, '@'));
			if ($need_map && preg_match_all('~(?<var>[%@][a-z0-9_-]+)~ims', $source_for_tr, $m)) {
				foreach ((array)$m['var'] as $i => $str) {
					$map[$str] = '{'.$i.'}';
				}
				if ($map) {
					$source_for_tr = strtr($source_for_tr, $map);
				}
			}
			$source_for_tr = str_replace('_', ' ', $source_for_tr);
			$tr = $services->google_translate($source_for_tr, $lang_from, $lang);
			if (strlen($tr) && $map) {
				$tr = strtr($tr, array_flip($map));
			}
			if (strlen($tr) && _strtolower($tr) != _strtolower($source)) {
				$to_update[$source] = _strtolower($tr);
			} else {
				$not_tr[$source] = $tr;
				$stats['failed']++;
			}
		}
		$to_replace = [];
		$existing_tr = from('locale_translate')->where('locale', $lang)->get_2d('var_id,value');
		foreach ((array)$to_update as $source => $tr) {
			$var_id = $to_tr[$source];
			if (!$var_id) {
				db()->insert_safe('locale_vars', ['value' => $source]);
				$var_id = (int)db()->insert_id();
			}
			if ($var_id) {
				if (isset($existing_tr[$var_id]) && $existing_tr[$var_id] != $tr) {
					$to_replace[$var_id] = [
						'var_id' => (int)$var_id,
						'locale' => $lang,
						'value'  => $tr,
					];
				}
				$stats['updated']++;
			}
		}
		if ($to_replace) {
			db()->replace_safe('locale_translate', $to_replace);
		}
		$stats['failed']	&& common()->message_warning($stats['failed'].' variable(s) failed to translate');
		$stats['updated']	&& common()->message_success($stats['updated'].' variable(s) successfully translated');
		!$stats	&& common()->message_info('Translate done, nothing changed');

		cache_del('locale_translate_'.$lang);

		$form->container(a(['href' => '/@object/@action', 'title' => 'Back', 'icon' => 'fa fa-arrow-left', 'class' => 'btn btn-primary btn-small', 'target' => '']), ['wide' => true]);
		$to_update && $form->container($this->_parent->_pre_text(_var_export(_prepare_html($to_update), 1)), ['wide' => true]);
		$not_tr && $form->container($this->_parent->_pre_text(_var_export(_prepare_html($not_tr), 1)), ['wide' => true]);
	}	
}
