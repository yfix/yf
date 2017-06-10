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
		!$a['lang'] && $a['lang'] = 'en';
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
				'lang' => 'required',
				'lang_from' => 'required',
			])
			->on_validate_ok(array(&$this, '_on_validate_ok'))
			->select_box('lang', $langs)
			->select_box('lang_from', $this->_parent->_cur_langs)
			->yes_no_box('keep_existing')
			->save_and_back('', ['desc' => 'Translate'])
		;
	}

	/**
	*/
	function _on_validate_ok() {
		$p = &$_POST;
		$lang = $p['lang'];
		$lang_from = $p['lang_from'] ?: 'en';
		$keep_existing = $p['keep_existing'];

		foreach ((array)$this->_parent->_get_all_vars() as $source => $a) {
			if (!isset($a['translation'][$lang])) {
				continue;
			}
			$tr = $a['translation'][$lang];
			if (strlen($tr) && $tr == $source) {
				continue;
			}
			$to_tr[$source] = $var_id;
		}
		if (!$to_tr) {
			common()->message_error('Translate failed, no suitable variables found');
			return false;
		}
#		require_php_lib('google_translate');
d($to_tr);
		$services = services();
# var_id can be empty if it is got from files
		foreach ((array)$to_tr as $source => $var_id) {
			$tr = $services->google_translate($source, $lang_from, $lang);
d($tr);
		}

#		cache_del('locale_translate_'.$lang);
		return js_redirect('/@object/vars');
	}	
}
