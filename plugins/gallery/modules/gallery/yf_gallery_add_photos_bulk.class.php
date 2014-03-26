<?php

/**
*/
class yf_gallery_add_photos_bulk {
	
	/**
	* Add photos in bulk mode (using zip archive with photos)
	*/
	function _add_photos_bulk($NEW_USER_ID = 0) {
		if (!module('gallery')->ALLOW_BULK_UPLOAD || !$NEW_USER_ID) {
			return false;
		}
		$ADD_PHOTOS_ALLOWED_NUM = 50;
		if (!empty(module('gallery')->MAX_TOTAL_PHOTOS)) {
			$num_photos = db()->query_num_rows('SELECT id FROM '.db('gallery_photos').' WHERE user_id='.intval($NEW_USER_ID));
			if ($num_photos >= module('gallery')->MAX_TOTAL_PHOTOS) {
				return _e(t('You can upload max @num photos!', array('@num' => intval(module('gallery')->MAX_TOTAL_PHOTOS))));
			} else {
				$ADD_PHOTOS_ALLOWED_NUM = module('gallery')->MAX_TOTAL_PHOTOS - $num_photos;
			}
		}
		// Extract archive
		$_ARCHIVE = $_FILES[module('gallery')->PHOTO_NAME_IN_FORM];
		$_tmp_dir = INCLUDE_PATH.'uploads/tmp/';
		if (!file_exists($_temp_dir)) {
			_mkdir_m($_tmp_dir);
		}
		$_tmp_name = time().'_'.abs(crc32(microtime(true).$_ARCHIVE['name']));
		$ext = strtolower(common()->get_file_ext($_ARCHIVE['name']));
		if ($ext == 'zip') {
			$_archive_uploaded_path = $_tmp_dir.$_tmp_name.'.zip';
			$_archive_extract_path	= $_tmp_dir.$_tmp_name.'/';
			if (!move_uploaded_file($_ARCHIVE['tmp_name'], $_archive_uploaded_path)) {
				return _e('GALLERY: upload internal error #1 in '.__FUNCTION__);
			}
			main()->load_class_file('pclzip', 'classes/');
			if (class_exists('pclzip')) {
				$zip = new pclzip($_archive_uploaded_path);
				if (!is_object($zip)) {
					return _e('GALLERY: upload internal error #2 in '.__FUNCTION__);
				} else {
					$result = $zip->extract(PCLZIP_OPT_PATH, $_archive_extract_path);
				}
			}
			if (!$result) {
				return _e('GALLERY: upload internal error #3 in '.__FUNCTION__);
			}
		} elseif ($ext == 'tar') {
// TODO
		}
		// Get photos availiable to process
		$photos = _class('dir')->scan_dir($_archive_extract_path, true, array('-f /\.(jpg|jpeg|gif|png)$/'), '/\.(svn|git)/');
		$photos = array_slice((array)$photos, -abs($ADD_PHOTOS_ALLOWED_NUM));

		$_POST['photo_name'] = module('gallery')->_filter_text($_POST['photo_name']);
		$_POST['photo_desc'] = module('gallery')->_filter_text($_POST['photo_desc']);
		$creation_time = time();
		$_max_id2 = _class('gallery_fix_id2', 'modules/gallery/')->_fix_id2($NEW_USER_ID);

		$ext_to_mime = array(
			'jpg'	=> 'image/jpeg',
			'jpeg'	=> 'image/jpeg',
			'png'	=> 'image/png',
			'gif'	=> 'image/gif',
		);
		foreach ((array)$photos as $_photo_path) {
			$file_ext = strtolower(common()->get_file_ext($_photo_path));
			if (!$ext_to_mime[$file_ext]) {
				continue;
			}
			if (_ee()) {
				break;
			}
			$SOURCE_PHOTO_NAME = module('gallery')->_prepare_photo_name(basename($_photo_path));

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
			if ($PHOTO_RECORD_ID) {
				// Create new photo name (using name template)
				$new_photo_info = array(
					'id'		=> $PHOTO_RECORD_ID,
					'id2'		=> intval($_max_id2 + 1),
					'user_id'	=> $NEW_USER_ID,
					'folder_id'	=> $_POST['folder_id'],
					'add_date'	=> $creation_time,
				);
				$load_result = _class('gallery_manage', 'modules/gallery/')->_load_photo(array(
					'name'		=> $SOURCE_PHOTO_NAME,
					'type'		=> $ext_to_mime[$file_ext],
					'tmp_name'	=> $_photo_path,
					'error'		=> 0,
					'size'		=> @filesize($_photo_path),
				), $new_photo_info, true);
				// Roll back uploaded photos
				if (!$load_result) {
					_class('gallery_manage', 'modules/gallery/')->_load_photo_rollback($new_photo_info);
				} else {
					module('gallery')->_update_other_info($new_photo_info);
				}
			}
			if (!_ee()) {
				db()->UPDATE('gallery_photos', array('active' => 1), 'id='.intval($PHOTO_RECORD_ID));
				db()->query('COMMIT');
				if (isset($_POST['tags'])) {
					module_safe('tags')->_save_tags($_POST['tags'], $PHOTO_RECORD_ID, 'gallery');
				}
			} else {
				db()->query('ROLLBACK');
			}
			// !! important !!
			$_max_id2++;
		}

 		if (!_ee()) {
			module('gallery')->_sync_public_photos($NEW_USER_ID);
			_class_safe('user_stats')->_update(array('user_id' => $NEW_USER_ID));
		}

		_class('dir')->delete_dir($_archive_extract_path, true);
		unlink($_archive_uploaded_path);

		if (_ee()) {
			return _e();
		}
		$redirect_folder_id = module('gallery')->HIDE_TOTAL_ID ? $user_folders[$_POST['folder_id']]['id2'] : $_POST['folder_id'];

		return js_redirect('./?object='.'gallery'.'&action='.(!empty($redirect_folder_id) ? 'view_folder&id='.$redirect_folder_id : 'show_gallery'). _add_get(array('page')));
	}
}
