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
		main()->NO_GRAPHICS = true;
		return '';
	}

	/**
	* Execute selected php func
	*/
	function php_func () {
		main()->NO_GRAPHICS = true;
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
	* Display 'dynamic' image (block hotlinking)
	*/
	function image () {
		main()->NO_GRAPHICS = true;
		// Prepare path to image we need to display
		$IMAGE_PATH = trim($_GET['id'], '/');
		if (empty($IMAGE_PATH)) {
			return $this->_show_error_image();
		}
		// Check permissions
		if (substr($IMAGE_PATH, 0, strlen(SITE_ACCOUNT_VERIFY_DIR)) != SITE_ACCOUNT_VERIFY_DIR) {
			return $this->_show_error_image();
		}
		// Prevent hotlinking
		if (empty($_SERVER['HTTP_REFERER']) || substr($_SERVER['HTTP_REFERER'], 0, strlen(WEB_PATH)) != WEB_PATH) {
			return $this->_show_error_image();
		}
		// Check if target file exists
		if (!file_exists(INCLUDE_PATH. $IMAGE_PATH) || !filesize(INCLUDE_PATH. $IMAGE_PATH)) {
			return $this->_show_error_image();
		}
		@header('Content-Type: image/jpeg');
		readfile(INCLUDE_PATH. $IMAGE_PATH);
		exit();
	}

	/**
	* Display 'dynamic' CSS (to allow get CSS also from framework)
	*/
	function css () {
		main()->NO_GRAPHICS = true;
		// Prepare CSS file name
		$name = preg_replace('/[^a-z0-9\_\.]+/ims', '', trim($_GET['id']));
		// Do nothing if something wrong from input
		if (empty($name) || substr($name, -strlen('.css')) != '.css') {
			exit();
		}
		// Common way
		$FS_PATH = INCLUDE_PATH. tpl()->TPL_PATH. $name;
		if (file_exists($FS_PATH)) {
			$css = file_get_contents($FS_PATH);
		}
		// Try to load from admin section
		if (empty($css) && MAIN_TYPE_ADMIN) {
			$FS_PATH = YF_PATH. 'templates/admin/'. $name;
			if (file_exists($FS_PATH)) {
				return file_get_contents($FS_PATH);
			}
		}
		// Try framework user section
		if (empty($css)) {
			$FS_PATH = YF_PATH. 'templates/user/'. $name;
			if (file_exists($FS_PATH)) {
				$css = file_get_contents($FS_PATH);
			}
		}
		@header('Content-Type: text/css');
		echo $css;
		exit();
	}

	/**
	* Display image with error text inside
	*/
	function _show_error_image () {
		if ($this->ERROR_IMAGE_INTERNAL) {
			@header('Content-Type: image/gif');
			print base64_decode('R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
		} else {
			@header('Content-Type: image/jpeg');
			readfile(INCLUDE_PATH. 'images/no_hotlinking.gif');
		}
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
		// If new language found - check it
		if (!empty($new_lang) && conf('languages::'.$new_lang.'::active')) {
			$_SESSION['user_lang'] = $new_lang;
			// Try to get user back
			$old_location = './?object=account';
			if (!empty($_POST['back_url'])) {
				$old_location = str_replace(WEB_PATH, './', $_POST['back_url']);
			}
			return js_redirect($old_location/*. '&language='.(!isset($_GET['language']) ? $_SESSION['user_lang'] : $_GET['language'])*/);
		}
		// Default return path
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
		// Get available languages
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
		// Process footer
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
		main()->NO_GRAPHICS = true;
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
		cache()->refresh('locale_translate_'.$CUR_LOCALE);
		return print('Save OK');
	}

	/**
	* AJAX-based method edit selected template for the current locale
	*/
	function edit_locale_stpl () {
		main()->NO_GRAPHICS = true;
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
		main()->NO_GRAPHICS = true;
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
		cache()->refresh('locale:tips');
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
		main()->NO_GRAPHICS = true;
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
		main()->NO_GRAPHICS = true;
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
		main()->NO_GRAPHICS = true;
		header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
		$in = null;
		if (isset($_POST['data'])) {
			$in = $_POST['data'];
		} elseif (isset($_GET['data'])) {
			$in = $_GET['data'];
		} elseif (isset($_GET['page'])) {
			$in = $_GET['page'];
		}
		if (is_null($in)) {
			return print 'Error: empty data';
		}
		$func = preg_replace('~[^a-z0-9_]+~ims', '', (isset($_POST['func']) ? $_POST['func'] : (isset($_GET['func']) ? $_GET['func'] : $_GET['id'])));
		if (!preg_match('~^[a-z][a-z0-9_]+$~ims', $func)) {
			return print 'Error: wrong func name';
		}
		if (!method_exists(_class('validate'), $func)) {
			return print 'Error: no such func';
		}
		$param = null;
		if (isset($_POST['param'])) {
			$param = $_POST['param'];
		} elseif (isset($_GET['param'])) {
			$param = $_GET['param'];
		}
// TODO: need to set list of allowed "param" values, example: user.login, user.email, etc
		print ( _class('validate')->$func($in, array('param' => $param)) ? 'ok' : 'ko' );
		return true;
	}
}
