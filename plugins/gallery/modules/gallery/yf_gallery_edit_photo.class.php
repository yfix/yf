<?php

/**
*/
class yf_gallery_edit_photo {

	/**
	*/
	function edit_photo() {
		$photo = _class('gallery_manage', 'modules/gallery/')->_acl_manage_checks();
		if (!is_array($photo)) {
			return $photo;
		}
		$folder_id = intval($photo['folder_id']);
		$user_folders = module('gallery')->_get_user_folders($photo['user_id']);
		$def_folder_id = module('gallery')->_get_def_folder_id($user_folders);
		if (empty($folder_id)) {
			db()->update('gallery_photos', array('folder_id' => intval($def_folder_id)), 'id='.intval($photo['id']));
		}
		if (!empty($folder_id)) {
			$cur_folder_info = $user_folders[$folder_id];
		}
		foreach ((array)$user_folders as $_folder_id => $_folder_info) {
			module('gallery')->_folders_for_select[$_folder_id] = _prepare_html($_folder_info['title']);
		}
		$_max_id2 = _class('gallery_fix_id2', 'modules/gallery/')->_fix_id2($photo['user_id']);
		if (main()->is_post()) {
/*
			if (isset($_POST['tags'])) {
				module_safe('tags')->_save_tags($_POST['tags'], $photo['id'], 'gallery');
			}
			$_POST['photo_name']	= substr($_POST['photo_name'], 0, module('gallery')->MAX_NAME_LENGTH);
			$_POST['photo_desc']	= substr($_POST['photo_desc'], 0, module('gallery')->MAX_DESC_LENGTH);
			$_POST['folder_id']		= intval($_POST['folder_id']);
			if (empty($_POST['folder_id']) || !isset($user_folders[$_POST['folder_id']])) {
				_re('Wrong selected folder');
			}
			$_PHOTO = $_FILES[module('gallery')->PHOTO_NAME_IN_FORM];
			if (!_ee()) {
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
				db()->UPDATE('gallery_photos', $sql_array, 'id='.intval($photo['id']));
			}
			if (!_ee() && !empty($_PHOTO['size'])) {
				$new_photo_info = array(
					'id'		=> $photo['id'],
					'id2'		=> $photo['id2'],
					'user_id'	=> $photo['user_id'],
					'folder_id'	=> $_POST['folder_id'],
					'add_date'	=> $photo['add_date'],
				);
				$load_result = _class('gallery_manage', 'modules/gallery/')->_load_photo($_PHOTO, $new_photo_info);
				if (!$load_result) {
					_class('gallery_manage', 'modules/gallery/')->_load_photo_rollback($new_photo_info);
				} else {
					module('gallery')->_update_other_info($new_photo_info);
				}
				$_SESSION['_refresh_image_in_browser'] = true;
			}
			if (!_ee()) {
				db()->query('COMMIT');
				module('gallery')->_sync_public_photos(main()->USER_ID);
				$redirect_folder_id = module('gallery')->HIDE_TOTAL_ID ? $cur_folder_info['id2'] : $cur_folder_info['id'];
				if ($_POST['folder_id'] && $_POST['folder_id'] != $cur_folder_info['id']) {
					$redirect_folder_id = module('gallery')->HIDE_TOTAL_ID ? $user_folders[$_POST['folder_id']]['id2'] : $_POST['folder_id'];
				}
				return js_redirect('./?object=gallery&action='.(!empty($redirect_folder_id) ? 'view_folder&id='.$redirect_folder_id : 'show_gallery')._add_get(array('page')));
			}
*/
		}
		if (_ee()) {
			$error_message = _e();
			db()->query('ROLLBACK');
		}
/*
		if (module('gallery')->ALLOW_TAGGING) {
			$_prefetched_tags = module('gallery')->_get_tags($photo['id']);
			foreach ((array)$GLOBALS['_gallery_tags'][$photo['id']] as $_name) {
				$tags[$_name] = './?object=gallery&action=tag&id='.urlencode($_name);
			}
		}
		$allow_edit_tags = module('gallery')->ALLOW_TAGGING ? true : false;
*/
		module('gallery')->_cur_rand = microtime(true);

		$cur_photo_type = 'thumbnail';
		$_fs_thumb_src = module('gallery')->_photo_fs_path($photo, $cur_photo_type);
		$thumb_web_path = '';
		if (file_exists($_fs_thumb_src)) {
			$thumb_web_path = module('gallery')->_photo_web_path($photo, $cur_photo_type);
		}
		$replace = array(
			'form_action'		=> './?object=gallery&action='.$_GET['action'].'&id='.$_GET['id']._add_get(array('page')),
			'thumb_src'			=> $thumb_web_path,
			'crop_link'			=> './?object=gallery&action=crop_photo&id='.$_GET['id']._add_get(array('page')),
			'rotate_link'		=> './?object=gallery&action=rotate_photo&id='.$_GET['id']._add_get(array('page')),
			'back_link'			=> './?object=gallery&action='.(!empty($photo['folder_id']) ? 'view_folder&id='.$photo['folder_id'] : 'show_gallery')._add_get(array('page')),
#			'rate_enabled'		=> intval((bool) module('gallery')->ALLOW_RATE),
#			'rating'			=> round($photo['rating'], 1),
#			'rate_num_votes'	=> intval($photo['num_votes']),
#			'rate_last_voted'	=> _format_date($photo['last_vote_date']),
#			'tagging_enabled'	=> intval((bool) module('gallery')->ALLOW_TAGGING),
#			'tags'				=> module('gallery')->ALLOW_TAGGING && !empty($tags) ? $tags : '',
			'edit_tags_link'	=> $allow_edit_tags ? process_url('./?object=gallery&action=edit_tags_popup&id='.$photo['id']._add_get(array('page'))) : '',
#			'allow_rate_box'	=> module('gallery')->_box('allow_rate', $photo['allow_rate']),
#			'allow_tagging_box'	=> module('gallery')->_box('allow_tagging', $photo['allow_tagging']),
			'edit_folder_link'	=> './?object=gallery&action=edit_folder&id='.intval($folder_id),
#			'tags'				=> module_safe('tags')->_collect_tags($photo['id'], 'gallery'),
		);
		return form($replace + $photo + $_POST, array('for_upload' => 1, '__form_id__' => 'gallery_edit_photo'))
			->validate(array(
				'folder_id'		=> 'required|integer',
				'photo_file'	=> 'required', // valid_image[jpeg,png]|image_max_size[500000]|image_height[100,1000],image_width[100,1000],
				'name'			=> 'trim|xss_clean|strip_tags|max_length['.module('gallery')->MAX_NAME_LENGTH.']',
				'desc'			=> 'trim|xss_clean|strip_tags|max_length['.module('gallery')->MAX_DESC_LENGTH.']',
				'tags'			=> 'trim|xss_clean|strip_tags',
			))
#			->image('photo_file', $thumb_web_path, array('desc' => 'Image'))
			->file_uploader('photo_file')
			->row_start()
				->link('crop_photo', './?object=gallery&action=crop_photo&id='.$photo['id'])
				->link('rotate_photo', './?object=gallery&action=rotate_photo&id='.$photo['id'])
				->link('adjust_photo', './?object=gallery&action=adjust_photo&id='.$photo['id'])
			->row_end()
			->select_box('folder_id', module('gallery')->_folders_for_select, array('desc' => 'Folder', 'edit_link' => './?object=gallery&action=edit_folder&id='.$photo['folder_id']))
			->text('name')
			->textarea('desc')
			->textarea('tags')
			->save('Upload');
	}
}
