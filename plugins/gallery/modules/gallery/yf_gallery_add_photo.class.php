<?php

/**
*/
class yf_gallery_add_photo {
	
	/**
	* Add Photo
	*/
	function add_photo($NEW_USER_ID = 0) {
		if (empty($NEW_USER_ID) && !empty(main()->USER_ID)) {
			$NEW_USER_ID = main()->USER_ID;
		}
		if (empty($NEW_USER_ID)) {
			return false;
		}
		if (!main()->_user_info && MAIN_TYPE_USER) {
			return _error_need_login();
		}
		if (main()->_user_info['ban_images'] && MAIN_TYPE_USER) {
			return module('gallery')->_error_msg('ban_images');
		}
		$FOLDER_ID = intval($_GET['id']);
		$user_folders = module('gallery')->_get_user_folders($NEW_USER_ID);
		$def_folder_id = module('gallery')->_get_def_folder_id($user_folders);
		if (empty($FOLDER_ID) && !empty($def_folder_id)) {
			$FOLDER_ID = $def_folder_id;
		}
		if (!empty($FOLDER_ID)) {
			$cur_folder_info = $user_folders[$FOLDER_ID];
		}
		if (!empty($cur_folder_info['user_id']) && $cur_folder_info['user_id'] != $NEW_USER_ID && MAIN_TYPE_USER) {
			return _e('Not your folder!');
		}
		foreach ((array)$user_folders as $_folder_id => $_folder_info) {
			module('gallery')->_folders_for_select[$_folder_id] = _prepare_html($_folder_info['title']);
		}
		$_max_id2 = _class('gallery_fix_id2', 'modules/gallery/')->_fix_id2($NEW_USER_ID);
		if (main()->is_post()) {
			$_POST['photo_name']	= substr($_POST['photo_name'], 0, module('gallery')->MAX_NAME_LENGTH);
			$_POST['photo_desc']	= substr($_POST['photo_desc'], 0, module('gallery')->MAX_DESC_LENGTH);
			$_POST['folder_id']		= intval($_POST['folder_id']);
			if (empty($_POST['folder_id']) || !isset($user_folders[$_POST['folder_id']])) {
				_re('Wrong selected folder');
			}
			db()->query('DELETE FROM '.db('gallery_photos').' WHERE user_id='.intval($NEW_USER_ID).' AND active=0');
			if (!empty(module('gallery')->MAX_TOTAL_PHOTOS)) {
				$num_photos = db()->query_num_rows('SELECT id FROM '.db('gallery_photos').' WHERE user_id='.intval($NEW_USER_ID));
				if ($num_photos >= module('gallery')->MAX_TOTAL_PHOTOS) {
					_re(t('You can upload max @num photos!', array('@num' => intval(module('gallery')->MAX_TOTAL_PHOTOS))));
				}
			}
			// Shortcut for the uploaded photo info
			$_PHOTO = $_FILES[module('gallery')->PHOTO_NAME_IN_FORM];
			if (empty($_PHOTO) || empty($_PHOTO['size'])) {
				_re('Photo file required');
			}
			if (!common()->_error_exists()) {
				$ext = strtolower(common()->get_file_ext($_PHOTO['name']));
				if (module('gallery')->ALLOW_BULK_UPLOAD && in_array($ext, array('zip', 'tar'))) {
					return _class('gallery_add_photos_bulk', 'modules/gallery/')->_add_photos_bulk($NEW_USER_ID);
				}
			}
			if (!common()->_error_exists()) {
				$_POST['photo_name'] = module('gallery')->_filter_text($_POST['photo_name']);
				$_POST['photo_desc'] = module('gallery')->_filter_text($_POST['photo_desc']);
				$SOURCE_PHOTO_NAME = module('gallery')->_prepare_photo_name($_PHOTO['name']);
				$creation_time = time();
				db()->query('BEGIN');
				$sql_array = array(
					'user_id'		=> intval($NEW_USER_ID),
					'folder_id'		=> intval($_POST['folder_id']),
					'img_name'		=> _es($SOURCE_PHOTO_NAME),
					'name'			=> _es($_POST['photo_name']),
					'desc'			=> _es($_POST['photo_desc']),
					'add_date'		=> $creation_time,
					'active' 		=> 0,
					'allow_rate'	=> intval((bool) $_POST['allow_rate']),
					'allow_tagging'	=> intval((bool) $_POST['allow_tagging']),
					'id2'			=> intval($_max_id2 + 1),
					'is_featured'	=> intval((bool) $_POST['is_featured']),
				);
				db()->INSERT('gallery_photos', $sql_array);
				$PHOTO_RECORD_ID = intval(db()->INSERT_ID());
				if (empty($PHOTO_RECORD_ID)) {
					_re('Cant insert record into db');
				}
				if (isset($_POST['tags'])) {
					module_safe('tags')->_save_tags($_POST['tags'], $PHOTO_RECORD_ID, 'gallery');
				}
			}
			if (!common()->_error_exists()) {
				$new_photo_info = array(
					'id'		=> $PHOTO_RECORD_ID,
					'id2'		=> intval($_max_id2 + 1),
					'user_id'	=> $NEW_USER_ID,
					'folder_id'	=> $_POST['folder_id'],
					'add_date'	=> $creation_time,
				);
				$load_result = _class('gallery_manage', 'modules/gallery/')->_load_photo($_PHOTO, $new_photo_info);
				if (!$load_result) {
					_class('gallery_manage', 'modules/gallery/')->_load_photo_rollback($new_photo_info);
				} else {
					module('gallery')->_update_other_info($new_photo_info);
				}
			}
			if (!common()->_error_exists()) {
				db()->UPDATE('gallery_photos', array('active' => 1), 'id='.intval($PHOTO_RECORD_ID));
			} 
			if (!common()->_error_exists()) {
				db()->query('COMMIT');
				module('gallery')->_sync_public_photos(main()->USER_ID);
				_class_safe('user_stats')->_update(array('user_id' => $NEW_USER_ID));
				$redirect_folder_id = module('gallery')->HIDE_TOTAL_ID ? $user_folders[$_POST['folder_id']]['id2'] : $_POST['folder_id'];
				return js_redirect('./?object='.'gallery'.'&action='.(!empty($redirect_folder_id) ? 'view_folder&id='.$redirect_folder_id : 'show_gallery'). _add_get(array('page')));
			}
		}
		if (common()->_error_exists()) {
			$error_message = _e();
			db()->query('ROLLBACK');
		}
		$allow_edit_tags = module('gallery')->ALLOW_TAGGING ? true : false;
		$replace = array(
			'form_action'		=> './?object='.'gallery'.'&action='.$_GET['action']._add_get(array('page')),
		);
		return form($replace + $_POST, array('for_upload' => 1, '__form_id__' => 'gallery_add_photo'))
			->validate(array(
				'folder_id'		=> 'required|integer',
				'photo_file'	=> 'required', // valid_image[jpeg,png]|image_max_size[500000]|image_height[100,1000],image_width[100,1000],
				'title'			=> 'trim|xss_clean|strip_tags',
				'comments'		=> 'trim|xss_clean|strip_tags',
				'tags'			=> 'trim|xss_clean|strip_tags',
			))
			->select_box('folder_id', module('gallery')->_folders_for_select, array('desc' => 'Folder', 'edit_link' => './?object=gallery&action=add_folder'))
			->file('photo_file', array('desc' => 'Image'))
			->text('title')
			->textarea('comments')
			->textarea('tags')
			->save('Upload');
	}
}
