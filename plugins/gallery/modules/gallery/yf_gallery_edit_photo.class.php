<?php

/**
*/
class yf_gallery_edit_photo {

	/**
	*/
	function edit_photo() {
		$photo_info = _class('gallery_manage', 'modules/gallery/')->_acl_manage_checks();
		if (!is_array($photo_info)) {
			return $photo_info;
		}
		$FOLDER_ID = intval($photo_info['folder_id']);
		$user_folders = module('gallery')->_get_user_folders($photo_info['user_id']);
		$def_folder_id = module('gallery')->_get_def_folder_id($user_folders);
		if (empty($FOLDER_ID)) {
			db()->UPDATE('gallery_photos', array(
				'folder_id'	=> intval($def_folder_id),
			), 'id='.intval($photo_info['id']));
		}
		if (!empty($FOLDER_ID)) {
			$cur_folder_info = $user_folders[$FOLDER_ID];
		}
		foreach ((array)$user_folders as $_folder_id => $_folder_info) {
			module('gallery')->_folders_for_select[$_folder_id] = _prepare_html($_folder_info['title']);
		}
		$_max_id2 = _class('gallery_fix_id2', 'modules/gallery/')->_fix_id2($photo_info['user_id']);
		if (main()->is_post()) {
			if (isset($_POST['tags'])) {
				module_safe('tags')->_save_tags($_POST['tags'], $photo_info['id'], 'gallery');
			}
			$_POST['photo_name']	= substr($_POST['photo_name'], 0, module('gallery')->MAX_NAME_LENGTH);
			$_POST['photo_desc']	= substr($_POST['photo_desc'], 0, module('gallery')->MAX_DESC_LENGTH);
			$_POST['folder_id']		= intval($_POST['folder_id']);
			if (empty($_POST['folder_id']) || !isset($user_folders[$_POST['folder_id']])) {
				_re('Wrong selected folder');
			}
			$_PHOTO = $_FILES[module('gallery')->PHOTO_NAME_IN_FORM];
			if (!common()->_error_exists()) {
				$_POST['photo_name'] = module('gallery')->_filter_text($_POST['photo_name']);
				$_POST['photo_desc'] = module('gallery')->_filter_text($_POST['photo_desc']);
				$SOURCE_PHOTO_NAME = module('gallery')->_prepare_photo_name($_PHOTO['name']);
				db()->query('BEGIN');
				$sql_array = array(
					'folder_id'		=> intval($_POST['folder_id']),
					'name'			=> _es($_POST['photo_name']),
					'desc'			=> _es($_POST['photo_desc']),
					'allow_rate'	=> intval((bool) $_POST['allow_rate']),
					'allow_tagging'	=> intval((bool) $_POST['allow_tagging']),
					'is_featured'	=> intval((bool) $_POST['is_featured']),
				);
				if (!empty($_PHOTO['size'])) {
					$sql_array['img_name']	= _es($SOURCE_PHOTO_NAME);
				}
				db()->UPDATE('gallery_photos', $sql_array, 'id='.intval($photo_info['id']));
			}
			if (!common()->_error_exists() && !empty($_PHOTO['size'])) {
				$new_photo_info = array(
					'id'		=> $photo_info['id'],
					'id2'		=> $photo_info['id2'],
					'user_id'	=> $photo_info['user_id'],
					'folder_id'	=> $_POST['folder_id'],
					'add_date'	=> $photo_info['add_date'],
				);
				$load_result = _class('gallery_manage', 'modules/gallery/')->_load_photo($_PHOTO, $new_photo_info);
				if (!$load_result) {
					_class('gallery_manage', 'modules/gallery/')->_load_photo_rollback($new_photo_info);
				} else {
					module('gallery')->_update_other_info($new_photo_info);
				}
				$_SESSION['_refresh_image_in_browser'] = true;
			}
			if (!common()->_error_exists()) {
				db()->query('COMMIT');
				module('gallery')->_sync_public_photos(main()->USER_ID);
				$redirect_folder_id = module('gallery')->HIDE_TOTAL_ID ? $cur_folder_info['id2'] : $cur_folder_info['id'];
				if ($_POST['folder_id'] && $_POST['folder_id'] != $cur_folder_info['id']) {
					$redirect_folder_id = module('gallery')->HIDE_TOTAL_ID ? $user_folders[$_POST['folder_id']]['id2'] : $_POST['folder_id'];
				}
				return js_redirect('./?object=gallery&action='.(!empty($redirect_folder_id) ? 'view_folder&id='.$redirect_folder_id : 'show_gallery')._add_get(array('page')));
			}
		} else {
			$_POST['photo_name']	= $photo_info['name'];
			$_POST['photo_desc']	= $photo_info['desc'];
			$_POST['folder_id']		= $photo_info['folder_id'];
		}
		if (common()->_error_exists()) {
			$error_message = _e();
			db()->query('ROLLBACK');
		}
		if (module('gallery')->ALLOW_TAGGING) {
			$_prefetched_tags = module('gallery')->_get_tags($photo_info['id']);
			foreach ((array)$GLOBALS['_gallery_tags'][$photo_info['id']] as $_name) {
				$tags[$_name] = './?object=gallery&action=tag&id='.urlencode($_name);
			}
		}
		$allow_edit_tags = module('gallery')->ALLOW_TAGGING ? true : false;

		module('gallery')->_cur_rand = microtime(true);

		$cur_photo_type = 'thumbnail';
		$_fs_thumb_src = module('gallery')->_photo_fs_path($photo_info, $cur_photo_type);
		$thumb_web_path = '';
		if (file_exists($_fs_thumb_src)) {
			$thumb_web_path = module('gallery')->_photo_web_path($photo_info, $cur_photo_type);
		}
		if ($_SESSION['_refresh_image_in_browser']) {
			$thumb_web_path .= '?'.module('gallery')->_cur_rand;
		}
		$replace = array(
			'form_action'		=> './?object=gallery&action='.$_GET['action'].'&id='.$_GET['id']._add_get(array('page')),
			'error_message'		=> $error_message,
			'folders_box'		=> module('gallery')->_box('folder_id', !empty($_POST['folder_id']) ? $_POST['folder_id'] : $FOLDER_ID),
			'max_image_size'	=> intval(module('gallery')->MAX_IMAGE_SIZE),
			'max_name_length'	=> intval(module('gallery')->MAX_NAME_LENGTH),
			'max_desc_length'	=> intval(module('gallery')->MAX_DESC_LENGTH),
			'photo_name'		=> _prepare_html($_POST['photo_name']),
			'photo_desc'		=> _prepare_html($_POST['photo_desc']),
			'thumb_src'			=> $thumb_web_path,
			'user_id'			=> intval(main()->USER_ID),
			'crop_link'			=> './?object=gallery&action=crop_photo&id='.$_GET['id']._add_get(array('page')),
			'rotate_link'		=> './?object=gallery&action=rotate_photo&id='.$_GET['id']._add_get(array('page')),
			'back_link'			=> './?object=gallery&action='.(!empty($photo_info['folder_id']) ? 'view_folder&id='.$photo_info['folder_id'] : 'show_gallery')._add_get(array('page')),
			'refresh_image_code'=> _class('gallery_manage', 'modules/gallery/')->_refresh_images_in_browser($photo_info['id']),
			'rate_enabled'		=> intval((bool) module('gallery')->ALLOW_RATE),
			'rating'			=> round($photo_info['rating'], 1),
			'rate_num_votes'	=> intval($photo_info['num_votes']),
			'rate_last_voted'	=> _format_date($photo_info['last_vote_date']),
			'tagging_enabled'	=> intval((bool) module('gallery')->ALLOW_TAGGING),
			'tags'				=> module('gallery')->ALLOW_TAGGING && !empty($tags) ? $tags : '',
			'edit_tags_link'	=> $allow_edit_tags ? process_url('./?object=gallery&action=edit_tags_popup&id='.$photo_info['id']._add_get(array('page'))) : '',
			'allow_rate_box'	=> module('gallery')->_box('allow_rate', $photo_info['allow_rate']),
			'allow_tagging_box'	=> module('gallery')->_box('allow_tagging', $photo_info['allow_tagging']),
			'edit_folder_link'	=> './?object=gallery&action=edit_folder&id='.intval($FOLDER_ID),
			'tags'				=> module_safe('tags')->_collect_tags($photo_info['id'], 'gallery'),
			'max_num_tags'		=> module_safe('tags')->TAGS_PER_OBJ,
			'min_tag_len'		=> module_safe('tags')->MIN_KEYWORD_LENGTH,
			'max_tag_len'		=> module_safe('tags')->MAX_KEYWORD_LENGTH,
			'is_featured_box'	=> module('gallery')->_box('is_featured', $photo_info['is_featured']),
		);
		return tpl()->parse('gallery'.'/edit_photo_form', $replace);
	}
}
