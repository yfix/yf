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
		return form($a + (array)$_POST)
			->validate([
				'lang_from' => 'required',
				'lang_to' => 'required',
			])
			->on_validate_ok(array(&$this, '_on_validate_ok'))
			->select_box('lang_from', $this->_parent->_cur_langs)
			->select_box('lang_to', $langs)
			->yes_no_box('keep_existing')
			->save_and_back('', ['desc' => 'Translate'])
		;
	}

	/**
	*/
	function _on_validate_ok() {
		$p = &$_POST;
		$lang = $p['lang_to'];
		$lang_from = $p['lang_from'] ?: 'en';
		$keep_existing = $p['keep_existing'];

		foreach ((array)$this->_parent->_get_all_vars() as $source => $a) {
			if (!isset($a['translation'][$lang])) {
				continue;
			}
			$tr = $a['translation'][$lang];
			if (!strlen($tr) || $tr == $source) {
				$to_tr[$source] = $a['var_id'];
			}
		}
		if (!$to_tr) {
			common()->message_info('Translate finished, 0 variables to translate');
			return js_redirect('/@object/@action');
		}
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
		return js_redirect('/@object/vars');
	}	
}
