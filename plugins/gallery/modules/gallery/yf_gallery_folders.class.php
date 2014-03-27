<?php

/**
* Gallery virtual folders handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_gallery_folders {

	/**
	* View folder contents
	*/
	function view_folder () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e('Missing folder id!');
		}
		// Check if such folder exists
		$sql = 'SELECT * FROM '.db('gallery_folders').' WHERE ';
		if (module('gallery')->HIDE_TOTAL_ID) {
			$sql .= 'id2='.intval($_GET['id']).' AND user_id='.intval(main()->USER_ID);
		} else {
			$sql .= 'id='.intval($_GET['id']);
		}
		$cur_folder_info = db()->query_fetch($sql);
		if (empty($cur_folder_info)) {
			return _e('No such folder!');
		}
		$FOLDER_ID	= intval($cur_folder_info['id']);
		$user_id	= $cur_folder_info['user_id'];
		if (!empty($user_id)) {
			$user_info = user($user_id, '', array('WHERE' => array('active' => '1')));
		}
		if (empty($user_info)) {
			return _e('No such user in database!');
		}
		if (empty($GLOBALS['user_info'])) {
			$GLOBALS['user_info'] = $user_info;
		}
		if (MAIN_TYPE_USER) {
			module('gallery')->is_own_gallery = intval(main()->USER_ID == $cur_folder_info['user_id']);
		} elseif (MAIN_TYPE_ADMIN) {
			module('gallery')->is_own_gallery = true;
		}
		return module('gallery')->_show_user_photos($user_info, $FOLDER_ID, 'folder_');
	}

	/**
	* Add new folder
	*/
	function add_folder () {
		if (empty(main()->_user_info) && MAIN_TYPE_USER) {
			return _error_need_login();
		}
		if (main()->_user_info['ban_images']) {
			return module('gallery')->_error_msg('ban_images');
		}
		$user_folders = module('gallery')->_get_user_folders(main()->USER_ID);
		$max_folders = module('gallery')->MAX_TOTAL_FOLDERS;
		if ($max_folders && count($user_folders) >= $max_folders) {
			return _e(t('You can create max %num folders!', array('%num' => intval($max_folders))));
		}
		$_max_folder_id2 = $this->_fix_folder_id2(main()->USER_ID);
		$replace = array(
			'form_action'	=> './?object='.'gallery'.'&action='.$_GET['action']._add_get(array('page')),
			'back_link'		=> './?object='.'gallery'.'&action=show_gallery'._add_get(array('page')),
		);
		return form($replace + $_POST, array('for_upload' => 1, '__form_id__' => 'gallery_add_folder'))
			->validate(array(
				'title'		=> 'trim|xss_clean|strip_tags|max_length['.module('gallery')->MAX_FOLDER_TITLE_LENGTH.']',
				'comment'	=> 'trim|xss_clean|strip_tags|max_length['.module('gallery')->MAX_FOLDER_COMMENT_LENGTH.']',
			))
			->db_insert_if_ok('gallery_folders', array('title', 'comment', 'privacy', 'allow_comments', 'password', 'active'),
				array(
					'user_id'	=> main()->USER_ID, 
					'add_date'	=> time(), 
					'id2'		=> intval($_max_folder_id2 + 1)
				),
				array('on_after_update' => function() {
					module('gallery')->_sync_public_photos();
					_class_safe('user_stats')->_update(array('user_id' => main()->USER_ID));
					$id = module('gallery')->HIDE_TOTAL_ID ? ($_max_folder_id2 + 1) : db()->insert_id();
					return js_redirect('./?object=gallery&action=edit_folder&id='. (int)$id. _add_get(array('page')));
				})
			)
			->text('title')
			->textarea('comment')
			->select_box('privacy', module('gallery')->_privacy_types)
			->select_box('allow_comments', module('gallery')->_comments_types)
			->active_box()
			->text('password')
			->save();
	}

	/**
	* Edit folder
	*/
	function edit_folder () {
		if (empty(main()->_user_info) && MAIN_TYPE_USER) {
			return _error_need_login();
		}
		if (main()->_user_info['ban_images']) {
			return module('gallery')->_error_msg('ban_images');
		}
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e('Missing folder id!');
		}
		$_max_folder_id2 = $this->_fix_folder_id2(main()->USER_ID);
		$sql = 'SELECT * FROM '.db('gallery_folders').' WHERE ';
		if (module('gallery')->HIDE_TOTAL_ID) {
			$sql .= 'id2='.intval($_GET['id']).' AND user_id='.intval(main()->USER_ID);
		} else {
			$sql .= 'id='.intval($_GET['id']);
		}
		$cur_folder_info = db()->query_fetch($sql);
		if (empty($cur_folder_info)) {
			return _e('No such folder!');
		}
		// Fix owner for the admin section
		if (MAIN_TYPE_ADMIN && empty(main()->USER_ID)) {
			main()->USER_ID = $cur_folder_info['user_id'];
		}
		$FOLDER_ID	= intval($cur_folder_info['id']);
		if ($cur_folder_info['user_id'] != main()->USER_ID) {
			return _e('Not your folder!');
		}
		if (main()->is_post()) {
			$_POST['title']		= substr($_POST['title'], 0, module('gallery')->MAX_FOLDER_TITLE_LENGTH);
			$_POST['comment']	= substr($_POST['comment'], 0, module('gallery')->MAX_FOLDER_COMMENT_LENGTH);
			$_POST['password']	= substr($_POST['password'], 0, 32);
			if (!strlen($_POST['title'])) {
				_re('Folder title is required');
			}
			if (!_ee()) {
				$_POST['title']		= module('gallery')->_filter_text($_POST['title']);
				$_POST['comment']	= module('gallery')->_filter_text($_POST['comment']);
				$creation_time = time();
				db()->update('gallery_folders', array(
					'user_id'		=> intval(main()->USER_ID),
					'title'			=> _es($_POST['title']),
					'comment'		=> _es($_POST['comment']),
					'content_level'	=> intval($_POST['content_level']),
					'privacy'		=> intval($_POST['privacy']),
					'allow_comments'=> intval($_POST['allow_comments']),
					'password'		=> _es($_POST['password']),
					'active' 		=> 1,
					'allow_tagging'	=> $_POST['allowed_group'] ? $_POST['allowed_group'] : module_safe('tags')->ALLOWED_GROUP,
				), 'id='.intval($FOLDER_ID));
				module('gallery')->_sync_public_photos();
				_class_safe('user_stats')->_update(array('user_id' => main()->USER_ID));
				return js_redirect('./?object='.'gallery'.'&action=edit_folder&id='.(module('gallery')->HIDE_TOTAL_ID ? $cur_folder_info['id2'] : intval($FOLDER_ID)). _add_get(array('page')));
			}
		}
		foreach ((array)$cur_folder_info as $k => $v) {
			$DATA[$k] = isset($_POST[$k]) ? $_POST[$k] : $v;
		}
		$replace = array(
			'form_action'			=> './?object='.'gallery'.'&action='.$_GET['action'].'&id='.intval($_GET['id'])._add_get(array('page')),
			'error_message'			=> _e(),
			'max_title_length'		=> intval(module('gallery')->MAX_FOLDER_TITLE_LENGTH),
			'max_comment_length'	=> intval(module('gallery')->MAX_FOLDER_COMMENT_LENGTH),
			'title'					=> _prepare_html($DATA['title']),
			'comment'				=> _prepare_html($DATA['comment']),
			'password'				=> _prepare_html($DATA['password']),
			'content_level_box'		=> module('gallery')->_box('content_level',	$DATA['content_level']),
			'privacy_box'			=> module('gallery')->_box('privacy',			$DATA['privacy']),
			'allow_comments_box'	=> module('gallery')->_box('allow_comments',	$DATA['allow_comments']),
			'user_id'				=> intval(main()->USER_ID),
			'back_link'				=> './?object='.'gallery'.'&action=view_folder&id='.$_GET['id']. _add_get(array('page')),
			'is_default'			=> intval((bool)$cur_folder_info['is_default']),
			'content_level'			=> module('gallery')->_content_levels[$cur_folder_info['content_level']],
			'warn_user'				=> intval($WARN_USER),
			'folder_tagging_box'	=> module('gallery')->ALLOW_TAGGING ? module_safe('tags')->_mod_spec_settings(array('module'=>'gallery', 'object_id'=>$DATA['id'])) : '',			
		);
		return tpl()->parse('gallery'.'/edit_folder_form', $replace);
	}

	/**
	* Delete folder
	*/
	function delete_folder () {
		if (empty(main()->_user_info) && MAIN_TYPE_USER) {
			return _error_need_login();
		}
		if (main()->_user_info['ban_images']) {
			return module('gallery')->_error_msg('ban_images');
		}
		$_max_folder_id2 = $this->_fix_folder_id2(main()->USER_ID);
		$sql = 'SELECT * FROM '.db('gallery_folders').' WHERE ';
		if (module('gallery')->HIDE_TOTAL_ID) {
			$sql .= 'id2='.intval($_GET['id']).' AND user_id='.intval(main()->USER_ID);
		} else {
			$sql .= 'id='.intval($_GET['id']);
		}
		$cur_folder_info = db()->query_fetch($sql);
		if (empty($cur_folder_info)) {
			return _e('No such folder!');
		}
		// Fix owner for the admin section
		if (MAIN_TYPE_ADMIN && empty(main()->USER_ID)) {
			main()->USER_ID = $cur_folder_info['user_id'];
		}
		$FOLDER_ID	= intval($cur_folder_info['id']);
		if ($cur_folder_info['user_id'] != main()->USER_ID) {
			return _e('Not your folder!');
		}
		$user_folders = module('gallery')->_get_user_folders(main()->USER_ID);
		$def_folder_id = $this->_get_def_folder_id($user_folders);
		$Q = db()->query('SELECT * FROM '.db('gallery_photos').' WHERE folder_id='.intval($FOLDER_ID));
		while ($A = db()->fetch_assoc($Q)) {
			$folder_photos[$A['id']] = $A;
		}
		if (main()->is_post()) {
			$NEW_FOLDER_ID = intval($_POST['new_folder_id']);
			if ($NEW_FOLDER_ID && !isset($user_folders[$NEW_FOLDER_ID])) {
				$NEW_FOLDER_ID = 0;
			}
			if (count($user_folders) <= 1) {
				return _re('This is your last folder. You cannot delete it');
			} elseif (!empty($folder_photos)) {
				if (empty($_POST['choose'])) {
					_re('Please select action with folder photos: delete or move');
				}
				if ($_POST['choose'] == 'move') {
					if (empty($NEW_FOLDER_ID)) {
						_re('Please select folder to move photos into');
					} elseif ($NEW_FOLDER_ID == $FOLDER_ID) {
						_re('Please select other folder');
					}
				}
			}
			if (!_ee()) {
				if ($_POST['choose'] == 'delete') {
					foreach ((array)$folder_photos as $photo_info) {
						foreach ((array)module('gallery')->PHOTO_TYPES as $format_name => $format_info) {
							$thumb_path = module('gallery')->_photo_fs_path($photo_info, $format_name);
							if (!file_exists($thumb_path)) {
								continue;
							}
							@unlink($thumb_path);
						}
					}
					db()->query('DELETE FROM '.db('gallery_photos').' WHERE folder_id='.intval($FOLDER_ID));
				} elseif ($NEW_FOLDER_ID) {
					// Assign default folder id to photos from the deleting folder
					db()->update('gallery_photos', array('folder_id' => intval($NEW_FOLDER_ID)), 'folder_id='.intval($FOLDER_ID));
				}
				if ($FOLDER_ID == $def_folder_id) {
					unset($user_folders[$FOLDER_ID]);
					reset($user_folders);
					$def_folder_id = key($user_folders);
					db()->UPDATE('gallery_folders', array('is_default' => 1), 'id='.intval($def_folder_id));
				}
				db()->query('DELETE FROM '.db('gallery_folders').' WHERE id='.intval($FOLDER_ID).' LIMIT 1');
				_class_safe('user_stats')->_update(array('user_id' => main()->USER_ID));
				module('gallery')->_sync_public_photos();
				return js_redirect('./?object='.'gallery'.'&action=show_gallery');
			}
		}
		foreach ((array)$user_folders as $_folder_id => $_folder_info) {
			if ($_folder_id == $FOLDER_ID) {
				continue;
			}
			$new_folders[$_folder_id] = _prepare_html($_folder_info['title']);
		}
		$replace = array(
			'form_action'		=> './?object='.'gallery'.'&action='.$_GET['action'].'&id='.$_GET['id']. _add_get(array('page')),
			'back_link'			=> './?object='.'gallery'.'&action=view_folder&id='.$_GET['id']. _add_get(array('page')),
			'error_message'		=> _e(),
			'folders_box'		=> common()->select_box('new_folder_id', $new_folders, 0, 0, 2, '', false),
			'folder_name'		=> _prepare_html($cur_folder_info['title']),
			'contains_photos'	=> !empty($folder_photos) ? 1 : 0,
			'is_last_folder'	=> count($user_folders) <= 1 ? 1 : 0,
		);
		return tpl()->parse('gallery'.'/delete_folder', $replace);
	}
	
	/**
	* Fix second id (used for HIDE_TOTAL_ID)
	*/
	function _fix_folder_id2($user_id = 0) {
		if (empty($user_id) || !module('gallery')->HIDE_TOTAL_ID) {
			return false;
		}
		$_max_folder_id2 = 0;
		$Q = db()->query('SELECT id,id2 FROM '.db('gallery_folders').' WHERE user_id='.intval($user_id).' ORDER BY id ASC');
		while ($A = db()->fetch_assoc($Q)) {
			$folders[$A['id']] = $A;
			if ($A['id2'] > $_max_folder_id2) {
				$_max_folder_id2 = $A['id2'];
			}
		}
		$folders_to_update	= array();
		$existed_second_ids	= array();
		foreach ((array)$folders as $_folder_id => $_info) {
			if (empty($_info['id2'])) {
				$folders_to_update[$_folder_id] = $_info;
				continue;
			}
			if (isset($existed_second_ids[$_info['id2']])) {
				$folders_to_update[$_folder_id] = $_info;
			}
			$existed_second_ids[$_info['id2']] = $_info['id2'];
		}
		foreach ((array)$folders_to_update as $_folder_id => $_info) {
			$_max_folder_id2++;
			db()->update('gallery_folders', array('id2' => intval($_max_folder_id2)), 'id='.intval($_folder_id));
		}
		return $_max_folder_id2;
	}

	/**
	*/
	function _enter_pswd ($FOLDER_ID = 0) {
		if (empty($FOLDER_ID)) {
			return _e('Missing folder id!');
		}
		$user_folders = module('gallery')->_user_folders_infos;
		$cur_folder_info = $user_folders[$FOLDER_ID];
		if (empty($cur_folder_info)) {
			return _e('No such folder!');
		}
		if (main()->is_post()) {
			if (!empty($cur_folder_info['password']) && $_POST['pswd'] == $cur_folder_info['password']) {
				$_SESSION[module('gallery')->SESSION_PSWD_FIELD][$FOLDER_ID] = $cur_folder_info['password'];
			} else {
				_re('Wrong password!');
			}
			if (!_ee()) {
				return js_redirect('./?object='.'gallery'.'&action='.$_GET['action']. (!empty($_GET['id']) ? '&id='.$_GET['id'] : ''));
			}
		}
		$replace = array(
			'error_message'		=> _e(),
			'enter_pswd_action'	=> './?object='.'gallery'.'&action='.$_GET['action']. (!empty($_GET['id']) ? '&id='.$_GET['id'] : ''),
		);
		return tpl()->parse('gallery'.'/enter_password', $replace);
	}

	/**
	* Get user's available folders
	*/
	function _get_user_folders ($user_id = 0) {
		if (empty($user_id)) {
			return false;
		}
		if (isset($GLOBALS['_FOLDERS_CACHE'][$user_id])) {
			return $GLOBALS['_FOLDERS_CACHE'][$user_id];
		} else {
			$GLOBALS['_FOLDERS_CACHE'][$user_id] = array();
		}
		$Q = db()->query('SELECT * FROM '.db('gallery_folders').' WHERE user_id='.intval($user_id));
		while ($A = db()->fetch_assoc($Q)) {
			$folders_infos[$A['id']] = $A;
		}
		// Do create default folder if not exists one
		if (empty($folders_infos)) {
			$info = array(
				'id2'			=> 1,
				'user_id'		=> $user_id,
				'title'			=> module('gallery')->DEFAULT_FOLDER_NAME,
				'is_default'	=> 1,
				'add_date'		=> time(),
			);
			db()->insert('gallery_folders', $info);

			$new_folder_id = db()->INSERT_ID();

			$info['id'] = $new_folder_id;
			$folders_infos[$new_folder_id] = $info;
			if (!empty($new_folder_id)) {
				$def_folder_id = $new_folder_id;
				db()->update('gallery_photos', array('folder_id' => intval($def_folder_id)), 'user_id='.intval($user_id));
			}
		}
		$GLOBALS['_FOLDERS_CACHE'][$user_id] = $folders_infos;
		return $folders_infos;
	}

	/**
	* Get users available folders (for many users at one time)
	*/
	function _get_user_folders_for_ids ($users_ids = array()) {
		if (empty($users_ids)) {
			return false;
		}
		$output = array();
		foreach ((array)$users_ids as $_user_id) {
	  		if (isset($GLOBALS['_FOLDERS_CACHE'][$_user_id])) {
				$output[$_user_id] = $GLOBALS['_FOLDERS_CACHE'][$_user_id];
				unset($users_ids);
			}
		}
		if (!empty($users_ids)) {
			$Q = db()->query('SELECT * FROM '.db('gallery_folders').' WHERE user_id IN('.implode(',', $users_ids).')');
			while ($A = db()->fetch_assoc($Q)) $folders_infos[$A['user_id']][$A['id']] = $A;
		}
		foreach ((array)$users_ids as $_user_id) {
			if (isset($output[$_user_id])) {
				continue;
			}
			if (empty($folders_infos[$_user_id])) {
				$creation_date = time();
				$sql_array = array(
					'id2'			=> 1,
					'user_id'		=> $_user_id,
					'title'			=> module('gallery')->DEFAULT_FOLDER_NAME,
					'is_default'	=> 1,
					'add_date'		=> $creation_date,
				);
				db()->INSERT('gallery_folders', $sql_array);
				$new_folder_id = db()->INSERT_ID();
				$sql_array['id'] = $new_folder_id;
				$output[$_user_id][$new_folder_id] = $sql_array;
			} else {
				$output[$_user_id] = $folders_infos[$_user_id];
			}
			$GLOBALS['_FOLDERS_CACHE'][$_user_id] = $output[$_user_id];
		}
		return $output;
	}

	/**
	*/
	function _get_def_folder_id ($user_folders = array()) {
		if (empty($user_folders) || !is_array($user_folders)) {
			return false;
		}
		$def_folder_id = key($user_folders);
		foreach ((array)$user_folders as $_folder_id => $_folder_info) {
			if ($_folder_info['is_default']) {
				$def_folder_id = $_folder_id;
				break;
			}
		}
		return $def_folder_id;
	}
}
