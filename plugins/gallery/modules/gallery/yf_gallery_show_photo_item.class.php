<?php

/**
*/
class yf_gallery_show_photo_item {

	/**
	*/
	function _show_photo_item ($photo_info = array(), $stpl_prefix = 'show_') {
		$PARAMS = array();
		if (is_array($stpl_prefix)) {
			$PARAMS = $stpl_prefix;
			$stpl_prefix = '';
		}
		if (!empty($PARAMS['stpl_prefix'])) {
			$stpl_prefix = $PARAMS['stpl_prefix'];
		}
		if (!$stpl_prefix) {
			$stpl_prefix = 'show_';
		}
		$ITEM_STPL = $PARAMS['stpl_full_path'];
		if (empty($ITEM_STPL)) {
			$ITEM_STPL = 'gallery'.'/'.$stpl_prefix.'photo_item';
		}
		$user_name = $PARAMS['user_name'];
		if (!strlen($user_name)) {
			if (isset(module('gallery')->_users_names[$photo_info['user_id']])) {
				$user_name = module('gallery')->_users_names[$photo_info['user_id']];
			} else {
				$user_name = _display_name($GLOBALS['user_info']);
			}
		}
		$cur_photo_type = module('gallery')->PHOTO_ITEM_DISPLAY_TYPE;
		$_fs_img_path = module('gallery')->_photo_fs_path($photo_info, $cur_photo_type);
		if (empty($_fs_img_path) || !file_exists($_fs_img_path) || !filesize($_fs_img_path)) {
			if (module('gallery')->SKIP_NOT_FOUND_PHOTOS) {
				return false;
			}
		}
		// Get available user folders
		$user_folders_infos = module('gallery')->_get_user_folders($photo_info['user_id']);
		// Get photo folder info
		$FOLDER_ID = $photo_info['folder_id'];
		if (empty($FOLDER_ID)) {
			$FOLDER_ID = module('gallery')->_fix_and_get_folder_id($photo_info);
		}
		if (!empty($FOLDER_ID)) {
			$cur_folder_info = $user_folders_infos[$FOLDER_ID];
		}
		if (!isset(module('gallery')->CUR_SETTINGS)) {
			module('gallery')->CUR_SETTINGS = module('gallery')->_get_settings($photo_info['user_id']);
		}
		$settings = module('gallery')->CUR_SETTINGS;

		$other_info = array();
		if (!empty($photo_info['other_info'])) {
			$other_info = unserialize($photo_info['other_info']);
		}
		$real_w = $other_info[$cur_photo_type]['w'];
		$real_h = $other_info[$cur_photo_type]['h'];
		$_real_coef = $real_h ? $real_w / $real_h : 0;
		$_max_w = module('gallery')->PHOTO_TYPES[$cur_photo_type]['max_x'];
		$_max_h = module('gallery')->PHOTO_TYPES[$cur_photo_type]['max_y'];
		$force_resize = false;
		if ($_max_w && $real_w > $_max_w) {
			$real_w = $_max_w * ($real_w > $real_h ? 1 : $_real_coef);
			$force_resize = true;
		}
		if ($_max_h && $real_h > $_max_h) {
			$real_h = $_max_h * ($real_w > $real_h ? $_real_coef : 1);
			$force_resize = true;
		}
		if (conf('HIGH_CPU_LOAD') == 1) {
			$force_resize	= false;
		}
		if ($force_resize && $cur_photo_type != 'original') {
			common()->make_thumb($_fs_img_path, $_fs_img_path, $_max_w, $_max_h);
			$other_info = module('gallery')->_update_other_info($photo_info);
		}

		$tags_block = module('gallery')->_show_tags($photo_info['id']);
		$tags_block = $tags_block[$photo_info['id']];

		$_web_photo_id = $photo_info['id'];
		if (module('gallery')->HIDE_TOTAL_ID) {
			$_web_photo_id = $photo_info['id2'];
		}
		$show_pswd_protected = false;
		if ($stpl_prefix == 'tag_search_' && strlen($cur_folder_info['password'])) {
			$show_pswd_protected = true;
		}

		$_sort_link		= './?object='.$_GET['object'].'&action=sort_photo&id='.$_web_photo_id.'&page=';
		$_sort_add		= $_GET['action'] == 'view_folder' ? '_in_folder' : '';
		$_sort_up_link	= $_sort_link.'up'.$_sort_add;
		$_sort_down_link= $_sort_link.'down'.$_sort_add;

		$is_own_gallery = module('gallery')->is_own_gallery;

		$replace = array(
			'photo_number'			=> intval(++$GLOBALS['_photo_items_counter']),
			'photo_id'				=> intval($photo_info['id']),
			'photo_id2'				=> module('gallery')->HIDE_TOTAL_ID ? intval($photo_info['id2']) : $photo_info['id'],
			'img_src'				=> !$show_pswd_protected ? module('gallery')->_photo_web_path($photo_info, $cur_photo_type) : '',
			'photo_name'			=> _prepare_html($photo_info['name']),
			'photo_desc'			=> _prepare_html($photo_info['desc']),
			'medium_size_link'		=> './?object=gallery&action=show_medium_size&id='.$_web_photo_id. _add_get(array('page')),
			'large_size_link'		=> './?object=gallery&action=show_full_size&id='.$_web_photo_id. _add_get(array('page')),
			'edit_photo_link'		=> $is_own_gallery ? './?object=gallery&action=edit_photo&id='.$_web_photo_id. _add_get(array('page')) : '',
			'delete_photo_link'		=> $is_own_gallery ? './?object=gallery&action=delete_photo&id='.$_web_photo_id. _add_get(array('page')) : '',
			'make_default_link'		=> $is_own_gallery && !$params['no_make_default'] ? './?object=gallery&action=make_default&id='.$_web_photo_id. _add_get(array('page')) : '',
			'is_own_gallery'		=> intval((bool) $is_own_gallery),
			'need_divider'			=> !($GLOBALS['_photo_items_counter'] % module('gallery')->PHOTOS_IN_COLUMN),
			'user_name'				=> _prepare_html($user_name),
			'user_id'				=> intval($photo_info['user_id']),
			'rate_box'				=> $rate_box,
			'real_w'				=> intval($real_w),
			'real_h'				=> intval($real_h),
			'folder_name'			=> $FOLDER_ID ? _prepare_html($user_folders_infos[$FOLDER_ID]['title']) : '',
			'folder_comment'		=> nl2br(_prepare_html($cur_folder_info['comment'])),
			'folder_add_date'		=> $cur_folder_info['add_date'] ? _format_date($cur_folder_info['add_date']) : '',
			'folder_content_level'	=> module('gallery')->_content_levels[$cur_folder_info['content_level']],
			'folder_privacy'		=> module('gallery')->_privacy_types[$cur_folder_info['privacy']],
			'rate_enabled'			=> intval((bool) module('gallery')->ALLOW_RATE),
			'rate_allowed'			=> module('gallery')->ALLOW_RATE ? intval((bool) $photo_info['allow_rate']) : 0,
			'rating'				=> $photo_info['allow_rate'] ? round($photo_info['rating'], 1) : '',
			'rate_num_votes'		=> $photo_info['allow_rate'] ? intval($photo_info['num_votes']) : '',
			'rate_last_voted'		=> $photo_info['allow_rate'] ? _format_date($photo_info['last_vote_date']) : '',
			'rate_block'			=> $photo_info['allow_rate'] ? module('gallery')->_show_rate_block($photo_info) : '',
			'change_rate_link'		=> module('gallery')->ALLOW_RATE && $is_own_gallery ? './?object=gallery&action=change_rate_allowed&id='.$_web_photo_id. _add_get(array('page')) : '',
			'tagging_enabled'		=> intval((bool) module('gallery')->ALLOW_TAGGING),
			'tagging_allowed'		=> module('gallery')->ALLOW_TAGGING ? intval((bool) $photo_info['allow_tagging']) : 0,
			'change_tagging_link'	=> module('gallery')->ALLOW_TAGGING && $is_own_gallery ? './?object=gallery&action=change_tagging_allowed&id='.$_web_photo_id. _add_get(array('page')) : '',
			'tags'					=> module('gallery')->ALLOW_TAGGING && !empty($tags) ? $tags : '',
			'allow_add_tag'			=> $allow_add_tag ? 1 : 0,
			'edit_tag_link'			=> $allow_add_tag ? process_url('./?object=gallery&action=edit_tags_popup&id='.$_web_photo_id. _add_get(array('page'))) : '',
			'tags_block'			=> $tags_block,
			'show_pswd_protected'	=> $show_pswd_protected ? 1 : 0,
			'slideshow_mode'		=> intval($settings['slideshow_mode']),
			'img_m_src'				=> !$show_pswd_protected ? module('gallery')->_photo_web_path($photo_info, 'medium') : '',
			'sort_up_link'			=> $is_own_gallery ? $_sort_up_link : '',
			'sort_down_link'		=> $is_own_gallery ? $_sort_down_link : '',
		);
		return tpl()->parse($ITEM_STPL, $replace);
	}
}
