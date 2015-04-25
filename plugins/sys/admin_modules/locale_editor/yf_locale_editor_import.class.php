<?php

/**
*/
class yf_locale_editor_import {

	/**
	*/
	function import_vars() {
		if (main()->is_post()) {
			if (empty($_FILES['import_file']['name'])) {
				_re('Please select file to process', 'name');
			}
			if (empty($_POST['file_format']) || !isset($this->_file_formats[$_POST['file_format']])) {
				_re('Please select file format', 'file_format');
			}
			$cur_locale = $_POST['lang_code'];
			if (empty($cur_locale)) {
				_re('Please select language', 'lang');
			}
			$raw_langs = $this->_get_iso639_list();
			if (!isset($raw_langs[$cur_locale])) {
				common()->_error_exists('Wrong language code');
			}
			if (!common()->_error_exists() && !isset($this->_cur_langs[$cur_locale])) {
				if (!common()->_error_exists()) {
					db()->INSERT('locale_langs', array(
						'locale'		=> _es($cur_locale),
						'name'			=> _es($raw_langs[$cur_locale][0]),
						'charset'		=> _es('utf-8'),
						'active'		=> 1,
						'is_default'	=> 0,
					));
					$this->_create_empty_vars_for_locale($cur_locale);
					cache_del('locale_langs');
				}
			}
			$file_format = $_POST['file_format'];
			$IMPORT_MODE = !empty($_POST['mode']) ? intval($_POST['mode']) : 1;
			if (!common()->_error_exists()) {
				$new_file_name = $_FILES['import_file']['name'];
				$new_file_path = INCLUDE_PATH.$new_file_name;
				move_uploaded_file($_FILES['import_file']['tmp_name'], $new_file_path);

				if ($file_format == 'csv') {

					$handle = fopen($new_file_path, 'r');
					while (($data = fgetcsv($handle, 2048, ";", "\"")) !== false) {
						if ($i++ == 0) continue; // Skip header
						$found_vars[trim($data[0])] = trim($data[1]);
					}
					fclose($handle);

				} elseif ($file_format == 'xml') {

					$xml_parser = xml_parser_create();
					xml_parse_into_struct($xml_parser, file_get_contents($new_file_path), $xml_values);
					foreach ((array)$xml_values as $k => $v) {
						if ($v['type'] != 'complete') continue;
						if ($v['tag'] == 'SOURCE') {
							$source = $v['value'];
						}
						if ($v['tag'] == 'TRANSLATION') {
							$translation = $v['value'];
						}
						if (!empty($source) && !empty($translation)) {
							$found_vars[trim($source)] = trim($translation);
							$source			= '';
							$translation	= '';
						}
					}
					xml_parser_free($xml_parser);
				}
				$Q = db()->query("SELECT id, ".($this->VARS_IGNORE_CASE ? "LOWER(REPLACE(CONVERT(value USING utf8), ' ', '_'))" : "value")." AS val FROM ".db('locale_vars')." ORDER BY val ASC");
				while ($A = db()->fetch_assoc($Q)) $cur_vars_array[$A["id"]] = $A["val"];

				$Q = db()->query("SELECT * FROM ".db('locale_translate')." WHERE locale = '"._es($cur_locale)."'");
				while ($A = db()->fetch_assoc($Q)) $cur_tr_vars[$A["var_id"]] = $A["value"];

				foreach ((array)$found_vars as $source => $translation) {
					$var_id = 0;
					if ($this->VARS_IGNORE_CASE) {
						$source = str_replace(' ', '_', strtolower($source));
					}
					foreach ((array)$cur_vars_array as $cur_var_id => $cur_var_value) {
						if ($cur_var_value == $source) {
							$var_id = intval($cur_var_id);
							break;
						}
					}
					if (empty($var_id)) {
						db()->INSERT('locale_vars', array('value'	=> _es($source)));
						$var_id = db()->INSERT_ID();
					}
					$sql_array = array(
						'var_id'	=> intval($var_id),
						'locale'	=> _es($cur_locale),
						'value'		=> _es($translation),
					);
					if (isset($cur_tr_vars[$var_id])) {
						if ($IMPORT_MODE == 2 || $translation == $cur_tr_vars[$var_id]) continue;
						db()->UPDATE('locale_translate', $sql_array, 'var_id='.intval($var_id).' AND locale="'._es($cur_locale).'"');
					} else {
						db()->INSERT('locale_translate', $sql_array);
					}
				}
				unlink($new_file_path);
				cache_del('locale_translate_'.$cur_locale);
				return js_redirect('./?object='.$_GET['object'].'&action=show_vars');
			}
		}
		if (!$_POST || common()->_error_exists()) {
			$replace = array(
				'form_action'		=> './?object='.$_GET['object'].'&action='.$_GET['action'],
				'back_link'			=> './?object='.$_GET['object'],
				'error_message'		=> _e(),
				'langs_box'			=> $this->_box('lang_code',		-1),
				'file_formats_box'	=> $this->_box('file_format',	'csv'),
				'modes_box'			=> $this->_box('mode',			1),
			);
			return tpl()->parse($_GET['object'].'/import_vars', $replace);
		}
	}

}
