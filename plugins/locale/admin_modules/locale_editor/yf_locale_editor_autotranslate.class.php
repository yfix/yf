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
			])
			->on_validate_ok(function($data,$e,$vr,$form) { return $this->_on_validate_ok($data, $form); })
			->select_box('lang_from', $this->_parent->_cur_langs, ['display_func' => $display_func])
			->select_box('lang_to', $langs, ['display_func' => $display_func])
			->yes_no_box('keep_existing', ['display_func' => $display_func])
			->save_and_back('', ['desc' => 'Translate', 'display_func' => $display_func])
		.'</div>';
	}

	/**
	*/
	function _on_validate_ok($form = null) {
		$p = $params ?: $_POST;
		$lang = $p['lang_to'];
		$lang_from = $p['lang_from'] ?: 'en';
		$keep_existing = $p['keep_existing'];

		foreach ((array)$this->_parent->_get_all_vars() as $source => $a) {
			if (!isset($a['translation'][$lang])) {
				continue;
			}
			$tr = $a['translation'][$lang];
d($tr, $source);
			if (!strlen($tr) || $tr == $source) {
				$to_tr[$source] = $a['var_id'];
			}
		}
		if (!$to_tr) {
			common()->message_info('Translate finished, 0 variables to translate');
#			return js_redirect('/@object/@action');
			return ;
		}
		set_time_limit(600);

		$to_update = [];
		$services = services();
		// var_id can be empty if it is got from files
		foreach ((array)$to_tr as $source => $var_id) {
			$tr = $services->google_translate(str_replace('_', ' ', $source), $lang_from, $lang);
			if (strlen($tr) && $tr != $source) {
				$to_update[$source] = $tr;
			} else {
				$stats['failed']++;
			}
		}
		foreach ((array)$to_update as $source => $tr) {
			$var_id = $to_tr[$source];
			if (!$var_id) {
				db()->insert_safe('locale_vars', ['value' => $source]);
				$var_id = (int)db()->insert_id();
			}
			if ($var_id) {
# TODO: count update and insert separately
				db()->replace_safe('locale_translate', [
					'var_id' => (int)$var_id,
					'locale' => $lang,
					'value'  => $tr,
				]);
				$stats['updated']++;
			}
		}
		$stats['failed']	&& common()->message_error($stats['failed'].' variable(s) failed to translate');
		$stats['updated']	&& common()->message_success($stats['updated'].' variable(s) successfully translated');
		!$stats	&& common()->message_info('Translate done, nothing changed');

		cache_del('locale_translate_'.$lang);

		$form->container(a(['href' => '/@object/@action', 'title' => 'Back', 'icon' => 'fa fa-arrow-left', 'class' => 'btn btn-primary btn-small', 'target' => '']), ['wide' => true]);
		$form->container($this->_parent->_pre_text(_var_export(_prepare_html($to_update), 1)), ['wide' => true]);
	}	
}
