<?php

/**
*/
class yf_locale_editor_user_vars {

	/**
	* Display list of user-specific vars
	*/
	function user_vars() {
		if (isset($_GET['id']) && !isset($_GET['page'])) {
			$_GET['page'] = $_GET['id'];
			$_GET['id'] = null;
		}
		// Group actions here
		if (!empty($_POST)) {
			if (isset($_POST['multi-push'])) {
				foreach ((array)$_POST['items'] as $_id) {
					$_id = intval($_id);
					if (!empty($_id)) {
						$this->user_var_push($_id);
					}
				}
			}
			return js_redirect('./?object='.$_GET['object'].'&action=user_vars'. _add_get());
		}

		$sql = 'SELECT * FROM '.db('locale_user_tr').'';
// TODO: add filter here with sorting selection, user id, etc
		$sql .= strlen($filter_sql) ? ' WHERE 1 '. $filter_sql : ' ORDER BY user_id DESC, name ASC';

		list($add_sql, $pages, $total) = common()->divide_pages($sql, '', '', 100);

		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$data[$A['id']] = $A;
			if ($A['user_id']) {
				$users_ids[$A['user_id']] = intval($A['user_id']);
			}
			if (strlen($A['name'])) {
				$vars_names[$A['name']] = $A['name'];
			}
		}
		if (!empty($users_ids)) {
			$Q = db()->query('SELECT * FROM '.db('user').' WHERE id IN('.implode(',', $users_ids).')');
			while ($A = db()->fetch_assoc($Q)) {
				$users_names[$A['id']] = $A['email'];
			}
		}
		// Check if var exists in the global table
		$global_vars = array();
		if (!empty($vars_names)) {
			foreach ((array)db()->query_fetch_all('SELECT * FROM '.db('locale_vars')." WHERE value IN('".implode("','", $vars_names)."')") as $A) {
				$global_vars[$A['value']] = $A['id'];
			}
		}

		$color_exists	= '#ff5';

		foreach ((array)$data as $A) {
			$var_bg_color = '';
			$global_var_exists	= isset($global_vars[_strtolower(str_replace(' ', '_', $A['name']))]);
			if ($global_var_exists) {
				$var_bg_color = $color_exists;
			}
			$items[] = array(
				'id'			=> $A['id'],
				'bg_class'		=> $i++ % 2 ? 'bg1' : 'bg2',
				'id'			=> intval($A['id']),
				'user_id'		=> intval($A['user_id']),
				'user_name'		=> _prepare_html($users_names[$A['user_id']]),
				'user_link'		=> _profile_link($A['user_id']),
				'name'			=> _prepare_html(str_replace('_', ' ', $A['name'])),
				'translation'	=> _prepare_html($A['translation']),
				'locale'		=> _prepare_html($A['locale']),
				'site_id'		=> intval($A['site_id']),
				'last_update'	=> _format_date($A['last_update'], 'long'),
				'global_exists'	=> (int)$global_var_exists,
				'var_bg_color'	=> $var_bg_color,
				'active'		=> intval($A['active']),
				'edit_url'		=> './?object='.$_GET['object'].'&action=user_var_edit&id='.$A['id'],
				'delete_url'	=> './?object='.$_GET['object'].'&action=user_var_delete&id='.$A['id'],
				'push_url'		=> './?object='.$_GET['object'].'&action=user_var_push&id='.$A['id'],
			);
		}
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action']. ($_GET['id'] ? '&id='.$_GET['id'] : ''),
			'error'			=> _e(),
			'items'			=> $items,
			'pages'			=> $pages,
			'total'			=> $total,
			'show_vars_link' => './?object='.$_GET['object'].'&action=show_vars',
		);
		return tpl()->parse($_GET['object'].'/user_vars_main', $replace);
	}

	/**
	* Edit user var
	*/
	function user_var_edit() {
		$_GET['id'] = intval($_GET['id']);
		$A = db()->query_fetch('SELECT * FROM '.db('locale_user_tr').' WHERE id='.intval($_GET['id']));
		if (!$A) {
			return _e('No id');
		}
		if (!empty($_POST)) {
			db()->UPDATE('locale_user_tr', array(
				'name'			=> _es($_POST['name']),
				'translation'	=> _es($_POST['translation']),
				'last_update'	=> time(),
			), 'id='.intval($_GET['id']));
			return js_redirect('./?object='.$_GET['object'].'&action=user_vars');
		}
		$DATA = my_array_merge($A, $_POST);

		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action']. ($_GET['id'] ? '&id='.$_GET['id'] : ''),
			'back_url'		=> process_url('./?object='.$_GET['object'].'&action=user_vars'),
			'error'			=> _e(),
			'for_edit'		=> 1,
			'id'			=> _prepare_html($DATA['id']),
			'user_id'		=> _prepare_html($DATA['user_id']),
			'name'			=> _prepare_html($DATA['name']),
			'translation'	=> _prepare_html($DATA['translation']),
			'locale'		=> _prepare_html($DATA['locale']),
			'site_id'		=> _prepare_html($DATA['site_id']),
		);
		return tpl()->parse($_GET['object'].'/user_vars_edit', $replace);
	}

	/**
	* Delete user var
	*/
	function user_var_delete() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			db()->query('DELETE FROM '.db('locale_user_tr').' WHERE id='.intval($_GET['id']));
		}
		// Return user back
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object'].'&action=user_vars'. _add_get());
		}
	}

	/**
	* Push user var into main traslation table
	*/
	function user_var_push($FORCE_ID = false) {
		$_GET['id'] = intval($FORCE_ID ? $FORCE_ID : $_GET['id']);
		$A = db()->query_fetch('SELECT * FROM '.db('locale_user_tr').' WHERE id='.intval($_GET['id']));
		if (!$A) {
			return _e('No id');
		}
		$VAR_NAME	= $A['name'];
		if ($this->VARS_IGNORE_CASE) {
			$VAR_NAME = str_replace(' ', '_', _strtolower($VAR_NAME));
		}
		if (!strlen($VAR_NAME)) {
			return _e('Empty var name');
		}
		$CUR_LOCALE = $A['locale'];
		if (!$CUR_LOCALE) {
			return _e('Empty var locale');
		}
		$EDITED_VALUE = $A['translation'];
		if (!strlen($EDITED_VALUE)) {
			return _e('Empty var translation');
		}
		// Get main translation var (if exists)
		$var_info = db()->query_fetch('SELECT * FROM '.db('locale_vars').' WHERE value="'._es($VAR_NAME).'"');
		if (!$var_info) {
			$var_info = array(
				'value'		=> _es($VAR_NAME),
				'location'	=> '',
			);
			db()->INSERT('locale_vars', $var_info);
			$var_id = db()->INSERT_ID();
			if ($var_id) {
				$var_info['id'] = $var_id;
			}
		}
		if (!$var_info['id']) {
			return _e('No locale var id');
		}
		$sql_data = array(
			'var_id'	=> intval($var_info['id']),
			'value'		=> _es($EDITED_VALUE),
			'locale'	=> _es($CUR_LOCALE),
		);
		// Get translation for the current locale
		$Q = db()->query('SELECT * FROM '.db('locale_translate').' WHERE var_id='.intval($var_info['id']));
		while ($A = db()->fetch_assoc($Q)) {
			$var_tr[$A['locale']] = $A['value'];
		}
		if (isset($var_tr[$CUR_LOCALE])) {
			db()->UPDATE('locale_translate', $sql_data, 'var_id='.intval($var_info['id']).' AND locale="'._es($CUR_LOCALE).'"');
		} else {
			db()->INSERT('locale_translate', $sql_data);
		}
		return $FORCE_ID ? '' : js_redirect('./?object='.$_GET['object'].'&action=user_vars');
	}
}
