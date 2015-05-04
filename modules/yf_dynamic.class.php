<?php

/**
*/
class yf_dynamic {

	/** @var bool */
	public $ERROR_IMAGE_INTERNAL	= false;
	/** @var bool */
	public $ALLOW_LANG_CHANGE		= true;
	/** @var bool */
	public $VARS_IGNORE_CASE		= true;
	/** @var int Quantity of finded users by user search (for 'find_users' function)*/
	public $USER_RESULTS_LIMIT = 20;
	/** @var array */
	public $AJAX_VALIDATE_ALLOWED = array(
		'user.login',
		'user.email',
		'user.nick',
		'captcha',
	);

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}
	
	/**
	* Default method
	*/
	function show () {
		no_graphics(true);
		return _404();
	}

	/**
	* Execute selected php func
	*/
	function php_func () {
		no_graphics(true);
		if (!main()->CONSOLE_MODE) {
			exit('No direct access to method allowed');
		}
		$params = common()->get_console_params();

		$func = preg_replace('#[^a-z0-9\_]+#', '', substr(trim($params['func']), 0, 32));
		if (function_exists($func)) {
			echo $func($params['name']);
		} else {
			echo 'Error: no such func: '.$func;
		}

		exit();
	}

	/**
	* Display image with error text inside
	*/
	function _show_error_image () {
		@header('Content-Type: image/gif', $force = true);
		print base64_decode('R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
		exit();
	}

	/**
	* Display 'dynamic' image (block hotlinking)
	*/
	function image () {
		no_graphics(true);
		if (empty($_SERVER['HTTP_REFERER']) || !defined('WEB_PATH') || substr($_SERVER['HTTP_REFERER'], 0, strlen(WEB_PATH)) !== WEB_PATH) {
			return $this->_show_error_image();
		}
		$img = trim($_GET['id'], '/');
		$path = PROJECT_PATH. $img;
		if (!strlen($img) || false !== strpos($img, '..')) {
			return $this->_show_error_image();
		}
		if (!file_exists($path) || !filesize($path)) {
			return $this->_show_error_image();
		}
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		$allowed_exts = array(
			'jpg'	=> 'image/jpeg',
			'jpeg'	=> 'image/jpeg',
			'gif'	=> 'image/gif',
			'png'	=> 'image/png',
		);
		if (!$ext || !isset($allowed_exts[$ext])) {
			return $this->_show_error_image();
		}
		@header('Content-Type: '.$allowed_exts[$ext], $force = true);
		readfile($path);
		exit();
	}

	/**
	* Display 'dynamic' CSS (to allow get CSS also from framework)
	*/
	function css () {
		no_graphics(true);
		$name = preg_replace('~[^a-z0-9\./_-]+~ims', '', trim($_GET['id']));
		$ext = '.css';
		if (!strlen($name) || false !== strpos($name, '..') || substr($name, -strlen($ext)) != $ext) {
			_404();
			exit();
		}
// TODO: improve according to new YF architecture
/*
		$fs_path = PROJECT_PATH. tpl()->TPL_PATH. $name;
		if (file_exists($fs_path)) {
			$css = file_get_contents($fs_path);
		}
		if (empty($css) && MAIN_TYPE_ADMIN) {
			$fs_path = YF_PATH. 'templates/admin/'. $name;
			if (file_exists($fs_path)) {
				return file_get_contents($fs_path);
			}
		}
		if (empty($css)) {
			$fs_path = YF_PATH. 'templates/user/'. $name;
			if (file_exists($fs_path)) {
				$css = file_get_contents($fs_path);
			}
		}
		@header('Content-Type: text/css');
		echo $css;
*/
		exit();
	}

	/**
	* Change current user language
	*/
	function change_lang () {
		if (!$this->ALLOW_LANG_CHANGE) {
			return _e('Changing language not allowed!');
		}
		$new_lang = _prepare_html($_REQUEST['lang_id']);
		if (!empty($new_lang) && conf('languages::'.$new_lang.'::active')) {
			$_SESSION['user_lang'] = $new_lang;
			$old_location = './?object=account';
			if (!empty($_POST['back_url'])) {
				$old_location = str_replace(WEB_PATH, './', $_POST['back_url']);
			}
			return js_redirect($old_location/*. '&lang='.(!isset($_GET['language']) ? $_SESSION['user_lang'] : $_GET['language'])*/);
		}
		return js_redirect($_SERVER['HTTP_REFERER']);
	}

	/**
	* Display form
	*/
	function change_lang_form () {
		return $this->_change_lang_form();
	}
	
	/**
	* BLock with change lang and skin selects
	*/
	function _change_lang_form () {
		if (!$this->ALLOW_LANG_CHANGE) {
			return false;
		}
		foreach ((array)conf('languages') as $lang_info) {
			if (!$lang_info['active']) {
				continue;
			}
			$lang_names[$lang_info['locale']] = $lang_info['name'];
		}
		if (empty($lang_names)) {
			return false;
		}
		$atts = " onchange=\"this.form.submit();\"";
		$replace = array(
			'form_action'	=> './?object='.str_replace(YF_PREFIX, '', __CLASS__).'&action=change_lang',
			'lang_box'		=> common()->select_box('lang_id', array(t('Language') => $lang_names), conf('language'), false, 2, $atts, false),
			'back_url'		=> WEB_PATH.'?object='.$_GET['object'].($_GET['action'] != 'show' ? '&action='.$_GET['action'] : ''). (!empty($_GET['id']) ? '&id='.$_GET['id'] : ''). (!empty($_GET['page']) ? '&page='.$_GET['page'] : ''),
		);
		return tpl()->parse('system/change_lang_form', $replace);
	}

	/**
	* AJAX-based method save current locale variable
	*/
	function save_locale_var () {
		no_graphics(true);
		if (!DEBUG_MODE && !$_SESSION['locale_vars_edit']) {
			return print('Access denied');
		}
		$SOURCE_VAR_NAME	= str_replace('%20', ' ',trim($_POST['source_var']));
		$EDITED_VALUE		= str_replace('%20', ' ',trim($_POST['edited_value']));
		$CUR_LOCALE			= conf('language');
		// First we need to check if such var exists
		if (!strlen($SOURCE_VAR_NAME)) {
			return print('Empty source var');
		}
		if (!strlen($EDITED_VALUE)) {
			return print('Empty edited value');
		}
		if ($this->VARS_IGNORE_CASE) {
			$SOURCE_VAR_NAME = str_replace(' ', '_', _strtolower($SOURCE_VAR_NAME));
			$sql = "SELECT * FROM ".db('locale_vars')." WHERE REPLACE(CONVERT(value USING utf8), ' ', '_') = '"._es($SOURCE_VAR_NAME)."'";
		} else {
			$sql = "SELECT * FROM ".db('locale_vars')." WHERE value='"._es($SOURCE_VAR_NAME)."'";
		}
		$var_info = db()->query_fetch($sql);
		// Create variable record if not found
		if (empty($var_info['id'])) {
			$sql = array('value'	=> _es($SOURCE_VAR_NAME));
			db()->INSERT('locale_vars', $sql);
			$var_info['id'] = db()->INSERT_ID();
		}
		$sql_data = array(
			'var_id'	=> intval($var_info['id']),
			'value'		=> _es($EDITED_VALUE),
			'locale'	=> _es($CUR_LOCALE),
		);
		// Check if record is already exists
		$Q = db()->query('SELECT * FROM '.db('locale_translate').' WHERE var_id='.intval($var_info['id']));
		while ($A = db()->fetch_assoc($Q)) {
			$var_tr[$A['locale']] = $A['value'];
		}
		if (isset($var_tr[$CUR_LOCALE])) {
			db()->UPDATE('locale_translate', $sql_data, 'var_id='.intval($var_info['id'])." AND locale='"._es($CUR_LOCALE)."'");
		} else {
			db()->INSERT('locale_translate', $sql_data);
		}
		$sql = db()->UPDATE('locale_translate', $sql_data, 'var_id='.intval($var_info['id'])." AND locale='"._es($CUR_LOCALE)."'", true);
		db()->INSERT('revisions', array(
			'user_id'		=> intval(MAIN_TYPE_USER ? main()->USER_ID : main()->ADMIN_ID),
			'object_name'	=> _es('locale_var'),
			'object_id'		=> _es($var_info['id']),
			'old_text'		=> _es($var_tr[$CUR_LOCALE]),
			'new_text'		=> _es($EDITED_VALUE),
			'date'			=> time(),
			'ip'			=> common()->get_ip(),
			'comment'		=> _es('locale: '.$CUR_LOCALE),
		));
		cache_del('locale_translate_'.$CUR_LOCALE);
		return print('Save OK');
	}

	/**
	* AJAX-based method edit selected template for the current locale
	*/
	function edit_locale_stpl () {
		no_graphics(true);
		if (!DEBUG_MODE || !tpl()->ALLOW_INLINE_DEBUG) {
			return print('Access denied');
		}
		// Prepare template name to get
		$STPL_NAME = trim($_GET['id']);
		// Some security checks
		$STPL_NAME = preg_replace('/[^a-z0-9_\-\/]/i', '', $STPL_NAME);
		$STPL_NAME = trim($STPL_NAME, '/');
		$STPL_NAME = preg_replace('#[\/]{2,}#', '/', $STPL_NAME);
		if (empty($STPL_NAME)) {
			return print('STPL name required!');
		}
		// Path to the lang-based theme
		$_lang_theme_path = INCLUDE_PATH. tpl()->_THEMES_PATH. conf('theme'). '.'.conf('language').'/';
		// Try to get template
		$text = tpl()->_get_template_file($STPL_NAME.tpl()->_STPL_EXT);
		$text = str_replace("\r", '', $text);
		// Determine used source
		$_source = tpl()->CACHE[$STPL_NAME]['storage'];
		// Try to save template
		if (isset($_POST['text'])) {
			// Compare source and result
			$result = 'Nothing changed';
			if ($_POST['text'] != $text) {
				$locale_stpl_path = $_lang_theme_path.$STPL_NAME.tpl()->_STPL_EXT;
				// First try to create subdir
				if (!file_exists(dirname($locale_stpl_path))) {
					_mkdir_m(dirname($locale_stpl_path));
				}
				// Save file
				file_put_contents($locale_stpl_path, $_POST['text']);
				// Save revision
				db()->INSERT('revisions', array(
					'user_id'		=> intval(MAIN_TYPE_USER ? main()->USER_ID : main()->ADMIN_ID),
					'object_name'	=> _es('locale_stpl'),
					'object_id'		=> _es($STPL_NAME),
					'old_text'		=> _es($text),
					'new_text'		=> _es($_POST['text']),
					'date'			=> time(),
					'ip'			=> common()->get_ip(),
					'comment'		=> _es('saved into file: '.$locale_stpl_path),
				));
				// Success output
				$result = 'Saved successfully';
			}
			return print $result;
		}
		// Show template contents by default
		return print $text;
	}

	/**
	* AJAX-based method edit selected tooltip
	*/
	function edit_tip () {
		no_graphics(true);
		if (!DEBUG_MODE || !tpl()->ALLOW_INLINE_DEBUG) {
			return print('Access denied');
		}
		$CUR_LOCALE	= conf('language');

		if (isset($_POST['text']) && isset($_POST['name'])) {
			$A = db()->query_fetch('SELECT * FROM '.db('tips')." WHERE name='".$_POST["name"]."' AND locale='".$CUR_LOCALE."'");
			if (!$A) {
				db()->INSERT('tips', array(
					'name'		=> _es($_POST['name']),
					'locale'	=> _es($CUR_LOCALE),
					'text'		=> _es($_POST['text']),
					'type' 		=> 1,
					'active'	=> 1,
				));
			} else {
				db()->UPDATE('tips', array(
					'text'	=> _es($_POST['text']),
				), "name='".$_POST["name"]."' AND locale='".$CUR_LOCALE."'");
			}
		}
		cache_del('tips');
		echo 'Saved successfully';
	}

	/**
	* Show bookmarks method
	*/
	function show_bookmarks () {
		return _class('graphics_bookmarks', 'classes/graphics/')->_show_bookmarks_extended();
	}

	/**
	* Show rss method
	*/
	function show_rss () {
		return _class('graphics_bookmarks', 'classes/graphics/')->_show_rss_extended();
	}

	/**
	* find users over nick or email
	*/
	function find_users() {
		no_graphics(true);
		if (!$_POST || !main()->USER_ID || IS_ADMIN != 1) {
			echo '';
		}
		// Continue execution
		$Q = db()->query(
			"SELECT id, nick 
			FROM ".db('user')." 
			WHERE "._es($_POST["search_field"])." LIKE '"._es($_POST["param"])."%' 
			LIMIT ".intval($this->USER_RESULTS_LIMIT));
		while($A = db()->fetch_assoc($Q)) {
			$finded_users[$A['id']] = $A['nick'];
		}
		echo $finded_users ? json_encode($finded_users) : '*';
	}

	/**
	* find users over nick or email
	*/
	function find_ids() {
		no_graphics(true);
		if (!$_POST || !main()->USER_ID || IS_ADMIN != 1/* || !strlen($_POST['param'])*/) {
			echo '';
			exit;
		}
		// Continue execution
		if ($_POST['search_table'] == 'user'){
			// Find account ids of this user
			$Q = db()->query(
				"SELECT a.id
						, a.account_name
						, a.user_id
						, u.nick
						, u.id AS 'uid' 
				FROM ".db('host_accounts')." AS a, ".db('user')." AS u 
				WHERE a.user_id=u.id 
					AND u.id IN( 
						SELECT id 
						FROM ".db('user')." 
						WHERE "._es($_POST["search_field"])." LIKE '"._es($_POST["param"])."%'
					) 
				LIMIT ".intval($this->USER_RESULTS_LIMIT)
			);
			while($A = db()->fetch_assoc($Q)) {
				$finded_ids[$A['nick']][$A['id']] = $A['account_name'];
			}
		} elseif ($_POST['search_table'] == 'host_accounts') {
			$Q = db()->query(
				"SELECT a.id
						, a.account_name
						, a.user_id
						, u.nick
						, u.id AS 'uid' 
				FROM ".db('host_accounts')." AS a
					, ".db('user')." AS u 
				WHERE a."._es($_POST['search_field'])." LIKE '"._es($_POST['param'])."%' 
					AND a.user_id=u.id 
				LIMIT ".intval($this->USER_RESULTS_LIMIT)
			);
			while($A = db()->fetch_assoc($Q)) {
				$finded_ids[$A['nick']][$A['id']] = $A['account_name'];
			}
		}
		echo $finded_ids ? json_encode($finded_ids) : '*';
	}

	/**
	*/
	function captcha_image() {
		return _class('captcha')->show_image();
	}

	/**
	*/
	function ajax_validate() {
		no_graphics(true);
		header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');

		$allowed_params = $this->AJAX_VALIDATE_ALLOWED;

		$rules = array();
		$errors = array();
		if (isset($_POST['rules']) && is_array($_POST['rules'])) {
			$rules = $_POST['rules'];
		} elseif (isset($_GET['rules']) && is_array($_GET['rules'])) {
			$rules = $_GET['rules'];
		} else {
			$rules[] = array(
				'func'	=> preg_replace('~[^a-z0-9_]+~ims', '', (isset($_POST['func']) ? $_POST['func'] : (isset($_GET['func']) ? $_GET['func'] : $_GET['id']))),
				'data'	=> isset($_POST['data']) ? $_POST['data'] : $_GET['data'],
				'param'	=> isset($_POST['param']) ? $_POST['param'] : $_GET['param'],
				'field'	=> isset($_POST['field']) ? $_POST['field'] : $_GET['field'],
			);
		}
		$class_validate = _class('validate');
		$is_valid = false;
		foreach ((array)$rules as $rule) {
			if (is_null($rule['data'])) {
				$errors[] = 'empty data';
			}
			if (strlen($rule['param'])) {
				$not_allowed_param = true;
				if (in_array($rule['param'], $allowed_params)) {
					$not_allowed_param = false;
				} else {
					foreach ((array)$allowed_params as $aparam) {
						// is_unique_without[user.login.1]
						if ($rule['param'] && strpos($rule['param'], $aparam.'.') === 0) {
							$not_allowed_param = false;
							break;
						}
					}
				}
				if ($not_allowed_param) {
					$errors[] = 'not allowed param';
				}
			}
			if (!preg_match('~^[a-z][a-z0-9_]+$~ims', $rule['func'])) {
				$errors[] = 'wrong func name';
			} elseif (!method_exists($class_validate, $rule['func'])) {
				$errors[] = 'no such func';
			}
			if ($errors) {
				break;
			}
			if ($rule['param'] == 'user.email') {
				$email_valid = $class_validate->valid_email($rule['data'], array(), array(), $error_msg);
				if (!$email_valid) {
					break;
				}
			}
			$is_valid = $class_validate->$rule['func']($rule['data'], array('param' => $rule['param']), array(), $error_msg);
			if (!$is_valid) {
				if (!$error_msg) {
					$error_msg = t('form_validate_'.$rule['func'], array('%field' => $rule['field'], '%param' => $rule['param']));
				}
				break;
			}
		}
		if ($errors) {
			$out = array('error' => $errors);
		} else {
			if ($is_valid) {
				$out = array('ok' => 1);
			} else {
				$out = array('ko' => 1);
			}
		}
		if ($error_msg) {
			$out['error_msg'] = $error_msg;
		}
		$is_ajax = conf('IS_AJAX');
		if ($is_ajax) {
			header('Content-type: application/json');
		}
		print json_encode($out);
		if ($is_ajax) {
			exit;
		}
	}

	/**
	* Output sample placeholder image, useful for designing wireframes and prototypes
	*/
	function placeholder() {
		no_graphics(true);

		list($id, $ext) = explode('.', $_GET['id']);
		list($w, $h) = explode('x', $id);
		$w = (int)$w ?: 100;
		$h = (int)$h ?: 100;
		$params['color_bg'] = $_GET['page'] ? preg_replace('[^a-z0-9]', '', $_GET['page']) : '';

		require_once YF_PATH.'share/functions/yf_placeholder_img.php';
		echo yf_placeholder_img($w, $h, $params);

		exit;
	}

	/**
	* Helper to output placeholder image, by default output is data/image
	*/
	function placeholder_img($extra = array()) {
		if (!is_array($extra)) {
			$extra = array();
		}
		$w = (int)$extra['width'];
		$h = (int)$extra['height'];
		if ($extra['as_url']) {
			$extra['src'] = url('/dynamic/placeholder/'.$w.'x'.$h);
		} else {
			require_once YF_PATH.'share/functions/yf_placeholder_img.php';
			$img_data = yf_placeholder_img($w, $h, array('no_out' => true) + (array)$extra);
			$extra['src'] = 'data:image/png;base64,'.base64_encode($img_data);
		}
		return '<img'._attrs($extra, array('src', 'type', 'class', 'id')).' />';
	}

	/**
	*/
	function preview($extra = array()) {
		conf('ROBOTS_NO_INDEX', true);
		no_graphics(true);
		if (main()->USER_ID != 1) {
			return print _403('You should be logged as user 1');
		}
		// Example of url: /dynamic/preview/static_pages/29/
		$object = preg_replace('~[^a-z0-9_]+~ims', '', $_GET['id']);
		$id = preg_replace('~[^a-z0-9_]+~ims', '', $_GET['page']);
		if (!strlen($object)) {
			return print _403('Object is required');
		}
		$ref = $_SERVER['HTTP_REFERER'];
		$body = '';
		if (main()->is_post() && isset($_POST['text'])) {
			$u_ref	= parse_url($ref);
			$u_self	= parse_url(WEB_PATH);
			$u_adm	= parse_url(ADMIN_WEB_PATH);
			if ($u_ref['host'] && $u_ref['host'] == $u_self['host'] && $u_ref['host'] == $u_adm['host'] && $u_ref['path'] == $u_adm['path']) {
				$body = $_POST['text'];
			} else {
				return print _403('Preview security check not passed');
			}
		}
		if (!$body) {
			if ($object == 'static_pages') {
				$body = db()->from($object)->whereid($id)->get_one('text');
			} elseif ($object == 'tips') {
				$body = db()->from($object)->whereid($id)->get_one('text');
			} elseif ($object == 'faq') {
				$body = db()->from($object)->whereid($id)->get_one('text');
			} elseif ($object == 'news') {
				$body = db()->from($object)->whereid($id)->get_one('full_text');
			}
		}
		$body = '<div class="container">'.$body.'</div>';
		return print common()->show_empty_page($body);
	}
}
