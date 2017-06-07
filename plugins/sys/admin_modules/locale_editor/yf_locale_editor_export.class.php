<?php

/**
*/
class yf_locale_editor_export {

	/**
	*/
	function _init () {
		$this->_parent = module('locale_editor');
	}

	/**
	* Export vars
	*/
	function export() {
#		$plugins = [];
		$a['back_link'] = url('/@object/vars');
		$a['redirect_link'] = $a['back_link'];
		return form($a + (array)$_POST)
			->validate([
				'lang' => 'required',
				'format' => 'required'
			])
			->on_validate_ok(function(){
				$p = &$_POST;
				$lang = $p['lang'];
				$to_export = [];
				foreach ((array)$this->_parent->_get_all_vars() as $source => $a) {
					if (!isset($a['translation'][$lang])) {
						continue;
					}
					$tr = $a['translation'][$lang];
					if (!strlen($tr)) {
						continue;
					}
					$to_export[$source] = $tr;
				}
				if (!$to_export) {
					return false;
				}
				$format = $p['format'];
				$name = 'export_'.$lang.'_translation.'.$format;
				$body = '';
				if ($format == 'csv') {
					$tmp = [];
					foreach((array)$to_export as $k => $v) {
						$tmp[] = [
							'source' => $k,
							'translation' => $v,
						];
					}
					$to_export = $tmp;
					unset($tmp);
					$body = $this->_gen_csv($to_export);
				}
				$format == 'json'	&& $body = $this->_gen_json($to_export);
				$format == 'yaml'	&& $body = $this->_gen_yaml($to_export);
				$format == 'php'	&& $body = $this->_gen_php($to_export);
				if ($body) {
					return $this->_http_out($name, $body, $format, $p['no_download']);
				}
			})
			->select_box('lang', $this->_parent->_cur_langs)
			->select_box('format', $this->_parent->_import_export_file_formats)
#			->select_box('module', $this->_parent->_modules)
#			->select_box('plugin', $plugins)
			->yes_no_box('is_template')
			->yes_no_box('no_download')
			->save_and_back('Export')
		;
	}

	/**
	*/
	function _gen_csv(array $data = [], $delim = "\t", $enc = '"') {
		if (count($data) === 0) {
			return false;
		}
		ob_start();
		$df = fopen('php://output', 'w');
		fputcsv($df, array_keys(reset($data)), $delim, $enc);
		foreach ($data as $row) {
			fputcsv($df, $row, $delim, $enc);
		}
		fclose($df);
		return ob_get_clean();
	}

	/**
	*/
	function _gen_json(array $data = []) {
		if (count($data) === 0) {
			return false;
		}
		return json_encode($data, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE);
	}

	/**
	*/
	function _gen_yaml(array $data = []) {
		if (count($data) === 0) {
			return false;
		}
		return trim(yaml_emit($data, YAML_UTF8_ENCODING, YAML_CRLN_BREAK), '.-'.PHP_EOL);
	}

	/**
	*/
	function _gen_php(array $data = []) {
		if (count($data) === 0) {
			return false;
		}
		return '<?'.'php'.PHP_EOL.'return '._var_export($data, 1).';'.PHP_EOL;
	}

	/**
	*/
	function _http_out($name, $body, $format, $no_download = false) {
		no_graphics(true);
		$mime_map = [
			'csv'	=> 'text/csv',
			'json'	=> 'text/json',
			'yaml'	=> 'text/plain',
			'php'	=> 'text/plain',
		];
		!$name && $name = 'export_translation.'.$format;

		header('Content-Type: '.$mime_map[$format].';charset=utf-8');
		header('Content-Length: '.strlen($body));
		if (!$no_download) {
			header('Content-Type: application/force-download');
			header('Content-Type: application/octet-stream');
			header('Content-Type: application/download');
			header('Content-Disposition: attachment; filename="'.$name.'"');
			header('Content-Transfer-Encoding: binary');
		}
		echo $body;
		exit();
	}
}
