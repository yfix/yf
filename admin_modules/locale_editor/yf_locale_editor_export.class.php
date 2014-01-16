<?php

/**
*/
class yf_locale_editor_export {

	/**
	* Export vars
	*/
	function export_vars() {
		if (main()->is_post()) {
			if (empty($_POST['file_format']) || !isset($this->_file_formats[$_POST['file_format']])) {
				_re('Please select file format');
			}
			$IS_TEMPLATE = intval((bool)$_POST['is_template']);
			if (empty($_POST['lang_code']) && !$IS_TEMPLATE) {
				_re('Please select language to export');
			}
			$cur_locale = !empty($_POST['lang_code']) ? $_POST['lang_code'] : 'en';
			$cur_lang_info = array(
				'locale'	=> $cur_locale,
				'name'		=> $this->_cur_langs[$cur_locale],
			);
			if (!$IS_TEMPLATE) {
				$Q = db()->query('SELECT * FROM '.db('locale_translate').' WHERE locale = "'._es($cur_locale).'"');
				while ($A = db()->fetch_assoc($Q)) {
					$tr_vars[$A['var_id']] = $A['value'];
				}
			}
			$Q = db()->query('SELECT * FROM '.db('locale_vars').' ORDER BY value ASC');
			while ($A = db()->fetch_assoc($Q)) {
				$source			= $A['value'];
				$translation	= $IS_TEMPLATE ? $A['value'] : $tr_vars[$A['id']];
				// Skip not translated vars
				if (!$IS_TEMPLATE && empty($translation)) continue;
				// Export only for specified location
				if (!$IS_TEMPLATE && !empty($_POST['location']) && (false === strpos($A['location'], $_POST['location']))) {
					continue;
				}
				// Export only for specified module
				if (!empty($_POST['module'])) {
					$is_admin_module = false;
					if (substr($_POST['module'], 0, strlen($this->_admin_modules_prefix)) == $this->_admin_modules_prefix) {
						$_POST['module'] = substr($_POST['module'], strlen($this->_admin_modules_prefix));
						$is_admin_module = true;
					}
					if ((false === strpos($A['location'], ($is_admin_module ? ADMIN_MODULES_DIR : USER_MODULES_DIR).$_POST['module'].'.class.php'))
						&& (false === strpos($A['location'], '/'.$_POST['module'].'/') || false === strpos($A['location'], '.stpl'))
					) {
						continue;
					}
				}
				$tr_array[$A['id']] = array(
					'source'		=> trim($source),
					'translation'	=> trim($translation),
				);
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Get vars to export
				if ($_POST['file_format'] == 'csv') {
					$body .= "source;translation".PHP_EOL;
					// Process vars
					foreach ((array)$tr_array as $info) {
						$body .= "\"".str_replace("\"","\"\"",$info["source"])."\";\"".
							str_replace("\"","\"\"",$info["translation"])."\"".PHP_EOL;
					}
					// Generate result file_name
					$file_name = $cur_lang_info["locale"]."_translation.csv";
				} elseif ($_POST["file_format"] == "xml") {
					// Generate XML string
					$body .= "<!DOCTYPE tr><tr>".PHP_EOL;
					$body .= "\t<info>".PHP_EOL;
					$body .= "\t\t<locale>"._prepare_html($cur_lang_info["locale"])."</locale>".PHP_EOL;
					$body .= "\t\t<lang_name>"._prepare_html($cur_lang_info["name"])."</lang_name>".PHP_EOL;
					$body .= "\t</info>".PHP_EOL;
					// Process vars
					foreach ((array)$tr_array as $info) {
						$body .= "\t<message>".PHP_EOL;
						$body .= "\t\t<source>"._prepare_html($info["source"])."</source>".PHP_EOL;
						$body .= "\t\t<translation>"._prepare_html($info["translation"])."</translation>".PHP_EOL;
						$body .= "\t</message>".PHP_EOL;
					}
					$body .= "</tr>";
					// Generate result file_name
					$file_name = $cur_lang_info["locale"]."_translation.xml";
				}
			}
			if (!common()->_error_exists()) {
				if (empty($body)) {
					_re("Error while exporting data");
				}
			}
			if (!common()->_error_exists()) {
				main()->NO_GRAPHICS = true;

				header("Content-Type: application/force-download; name=\"".$file_name."\"");
				header("Content-Type: text/".$_POST["file_format"].";charset=utf-8");
				header("Content-Transfer-Encoding: binary");
				header("Content-Length: ".strlen($body));
				header("Content-Disposition: attachment; filename=\"".$file_name."\"");

				echo $body;
				exit();
			}
		}

		$this->_used_locations[''] = t('-- ALL --');
		foreach ((array)$this->_get_all_vars_locations() as $cur_location => $num_vars) {
			if (empty($num_vars)) {
				continue;
			}
			$this->_used_locations[$cur_location] = $cur_location.' ('.intval($num_vars).')';
		}
		$replace = array(
			'form_action'		=> './?object='.$_GET['object'].'&action='.$_GET['action'],
			'back_link'			=> './?object='.$_GET['object'],
			'error_message'		=> _e(),
			'langs_box'			=> $this->_box('cur_langs',		-1),
			'file_formats_box'	=> $this->_box('file_format',	'csv'),
			'location_box'		=> $this->_box('location',		-1),
			'modules_box'		=> $this->_box('module',		-1),
		);
		return tpl()->parse($_GET['object'].'/export_vars', $replace);
	}

}
