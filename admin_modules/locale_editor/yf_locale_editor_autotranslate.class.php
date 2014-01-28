<?php

/**
*/
class yf_locale_editor_autotranslate {

	/**
	* Automatic translator via Google translate
	*/
	function autotranslate() {
		if ($_POST['translate'] && $_POST['locale']) {
			set_time_limit(1800); 
			$LOCALE_RES = $_POST['locale'];
	
			$base_url = 'http://ajax.googleapis.com/ajax/services/language/translate'.'?v=1.0';
			
			$vars = db()->query_fetch_all(
				"SELECT id,value FROM ".db('locale_vars')." WHERE id NOT IN( 
					SELECT var_id FROM ".db('locale_translate')." 
					WHERE locale = '".$LOCALE_RES."' AND value != '' 
				)");
			$_info = array();
			$max_threads = 4;
			$buffer = array();
			$translated = array();
_debug_log("LOCALE_NUM_VARS: ".count($vars));
			foreach ((array)$vars as $A) {
				$translated = array();
				$url = $base_url."&q=".urlencode(str_replace("_", " ", $A["value"]))."&langpair=en%7C".$LOCALE_RES;
				$_temp[$url] = $A["id"];
				if (count($buffer) < $max_threads) {
					$buffer[$url] = $url;
					continue;
				}
				foreach ((array)common()->multi_request($buffer) as $url => $response) {
					$response_array = json_decode($response);
					$response_text = trim($response_array->responseData->translatedText);
					$ID = $_temp[$url];
					$source = str_replace("_", " ", $vars[$ID]["value"]);
_debug_log("LOCALE: ".(++$j)." ## ".$ID." ## ".$source." ## ".$response_text." ## ".$url);
					if (_strlen($response_text) && $response_text != $source) {
						$translated[$ID] = $response_text;
					}
				}
				if ($translated) {
					$Q = db()->query(
						"DELETE FROM ".db('locale_translate')." 
						WHERE locale = '"._es($LOCALE_RES)."' 
							AND var_id IN(".implode(",", array_keys($translated)).")"
					);
				}
				foreach ((array)$translated as $_id => $_value) {
					db()->REPLACE('locale_translate', array(
						'var_id'	=> intval($_id),
						'value'		=> _es($_value),
						'locale'	=> _es($LOCALE_RES),
					));
				}
				$buffer = array();
				$_temp = array();
			}
			cache_del('locale_translate_'.$LOCALE_RES);
			return js_redirect('./?object='.$_GET['object']);
		}

		$Q = db()->query('SELECT * FROM '.db('locale_langs').' ORDER BY name');
		while($A = db()->fetch_assoc($Q)){
			$locales[$A['locale']] = $A['name'];
		}
		$replace = array(
			'locale_box' 		=> common()->select_box('locale', $locales),
			'locale_editor_url' => './?object=locale_editor',
			'form_action'		=> './?object='.$_GET['object'].'&action='.$_GET['action'],
		);
		return tpl()->parse($_GET['object'].'/autotranslate', $replace);
	}	
}
