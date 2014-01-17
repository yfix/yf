<?php

/**
* Comments management
*/
class yf_comments_manage {

	/**
	* Form to add comments
	*/
	function _add ($params = array()) {
		if (empty(module('comments')->USER_ID) && MAIN_TYPE_USER && !$params['allow_guests_posts']) {
			return '';
		}
		$_GET['id'] = intval($_GET['id']);
		$OBJECT_NAME	= !empty($params['object_name']) ? $params['object_name'] : $_GET['object'];
		$OBJECT_ID		= !empty($params['object_id']) ? intval($params['object_id']) : intval($_GET['id']);
		$FORM_ACTION	= !empty($params['add_form_action']) ? $params['add_form_action'] : './?object='.$_GET['object'].'&action=add_comment&id='.$OBJECT_ID;
		$STPL_NAME_ADD	= !empty($params['stpl_add']) ? $params['stpl_add'] : 'comments/add_form';
		$RETURN_PATH	= $_SERVER['HTTP_REFERER'];
		if (!empty($params['return_path'])) {
			$RETURN_PATH = process_url($params['return_path']);
		} elseif (!empty($params['return_action'])) {
			$RETURN_PATH = process_url('./?object='.$_GET['object'].'&action='.$params['return_action'].'&id='.$OBJECT_ID);
		}
		if (empty($OBJECT_NAME) || empty($OBJECT_ID)) {
			return '';
		}
		$COMMENT_IS_ALLOWED = true;
		if (is_object(module($_GET['object']))) {
			$m = module('comments')->_add_allowed_method;
			$COMMENT_IS_ALLOWED = module($_GET['object'])->$m($params);
		}
		if (!$COMMENT_IS_ALLOWED && common()->_error_exists()) {
			return _e();
		}
		if (!$COMMENT_IS_ALLOWED) {
			return false;
		}
		if (count($_POST) > 0 && !isset($_POST['_not_for_comments'])) {
			if (isset($_POST['text2']) && !isset($_POST['text'])) {
				$_POST['text'] = $_POST['text2'];
				unset($_POST['text2']);
			}
			if (!common()->_error_exists()) {
				$_POST['text']	= substr($_POST['text'], 0, module('comments')->MAX_POST_TEXT_LENGTH);
				if (empty($_POST['text'])) {
					_re('Comment text required');
				}
			}
			if (module($_GET['object'])->USE_CAPTCHA) {
				if (module('comments')->USE_TREE_MODE && isset($_POST['parent_id'])) {
					if (empty(module('comments')->USER_ID)) {
						module($_GET['object'])->_captcha_check();
					}
				} else {
					module($_GET['object'])->_captcha_check();
				}
			}
			if (!common()->_error_exists() && MAIN_TYPE_USER) {
				$info_for_check = array(
					'comment_text'	=> $_POST['text'],
					'user_id'		=> module('comments')->USER_ID,
				);
				$USER_BANNED = _check_user_ban($info_for_check, module('comments')->_user_info);
				if ($USER_BANNED) {
					module('comments')->_user_info = user(module('comments')->USER_ID);
				}
				if (module('comments')->_user_info['ban_comments']) {
					return _e(
						'Sorry, you are not allowed to post comments!'.PHP_EOL.'Perhaps, you broke some of our rules and moderator has banned you from using this feature. Please, enjoy our site in some other way!'
						.'For more details <a href=\'./?object=faq&action=view&id=16\'>click here</a>'
					);
				}
			}
			if (!common()->_error_exists() && module('comments')->ANTI_FLOOD_TIME && MAIN_TYPE_USER) {
				$FLOOD_DETECTED = db()->query_fetch(
					'SELECT id,add_date FROM '.db('comments').' 
					WHERE '.(module('comments')->USER_ID ? 'user_id='.intval(module('comments')->USER_ID) : 'ip="'._es(common()->get_ip()).'"')
						.' AND add_date > '.(time() - module('comments')->ANTI_FLOOD_TIME).' 
					ORDER BY add_date DESC LIMIT 1'
				);
				if (!empty($FLOOD_DETECTED)) {
					_re('Please wait %num seconds before post comment.', array('%num' => intval(module('comments')->ANTI_FLOOD_TIME - (time() - $FLOOD_DETECTED['add_date'])) ));
				}
			}
			
			if (!common()->_error_exists()){
				if(module('comments')->ANTI_SPAM_DETECT){
					$this->_spam_check($_POST['text']);
				}
			}
			
			if(!empty($_POST['user_email'])){
				if (!preg_match('#^[0-9a-z]([-_.]?[0-9a-z])*@[0-9a-z]([-.]?[0-9a-z])*\.[a-z]{2,3}$#i', $_POST['user_email'])) {
					_re('Invalid e-mail, please check your spelling');
				}
			}
			
			if (!common()->_error_exists()) {
				if (module('comments')->AUTO_FILTER_INPUT_TEXT) {
					$_POST['text'] = _filter_text($_POST['text']);
				}
				// Do close BB Codes (if needed)
				if (module('comments')->USE_BB_CODES) {
					$BB_CODES_OBJ = _class('bb_codes');
					if (is_object($BB_CODES_OBJ)) {
						$_POST['text'] = $BB_CODES_OBJ->_force_close_bb_codes($_POST['text']);
					}
				}
				db()->INSERT('comments', array(
					'object_name'		=> _es($OBJECT_NAME),
					'object_id'			=> intval($OBJECT_ID),
					'parent_id'			=> intval(isset($_POST['parent_id'])?$_POST['parent_id']:0),
					'user_id'			=> intval(module('comments')->USER_ID),
					'user_name'			=> !module('comments')->USER_ID ? _es($_POST['user_name']) : '',
					'user_email'		=> !module('comments')->USER_ID ? _es($_POST['user_email']): '',
					'text' 				=> _es($_POST['text']),
					'add_date'			=> time(),
					'active'			=> 1,
					'ip'				=> _es(common()->get_ip()),
				));
				$RECORD_ID = db()->INSERT_ID();

				$try_trigger_callback = array(module($_GET['object']), module('comments')->_on_update_trigger);
				if (is_callable($try_trigger_callback)) {
					call_user_func($try_trigger_callback, $params);
				}
				common()->_add_activity_points(module('comments')->USER_ID, $OBJECT_NAME.'_comment', strlen($_POST['text']), $RECORD_ID);
				return js_redirect($RETURN_PATH, false);
			}
		}
		$error_message = _e();
		if (empty($_POST['go']) || !empty($error_message)) {
			if((module('comments')->VIEW_EMAIL_FIELD == true) AND (empty(module('comments')->USER_ID))){
				$view_user_email = '1';
			}else{
				$view_user_email = '0';
			}
			$replace = array(
				'form_action'		=> $FORM_ACTION,
				'error_message'		=> $error_message,
				'user_name'			=> $_POST['user_name'],
				'view_user_email'	=> $view_user_email,
				'user_email'		=> $_POST['user_email'],
				'text'				=> _prepare_html($_POST['text']),
				'object_name'		=> _prepare_html($OBJECT_NAME),
				'object_id'			=> intval($OBJECT_ID),
				'use_captcha'		=> intval((bool)module($_GET['object'])->USE_CAPTCHA),
				'captcha_block'		=> module($_GET['object'])->_captcha_block(),
				'bb_codes_block'	=> module('comments')->USE_BB_CODES ? _class('bb_codes')->_display_buttons(array('unique_id' => 'text')) : '',
				'submit_buttons'	=> _class_safe('preview')->_display_buttons(),
				'js_check'			=> intval((bool)module('comments')->JS_TEXT_CHECKING),
				'parent_id'			=> intval($_POST['parent_id']),
			);
			$body = tpl()->parse($STPL_NAME_ADD, $replace);
		}
		return $body;
	}

	/**
	* Do edit own comment
	*/
	function _edit ($params = array()) {
		if (empty(module('comments')->USER_ID) && MAIN_TYPE_USER) {
			return _error_need_login();
		}
		$_GET['id'] = intval($_GET['id']);
		$comment_info = db()->query_fetch('SELECT * FROM '.db('comments').' WHERE id='.intval($_GET['id']));
		if (empty($comment_info['id'])) {
			return _e('No such comment!');
		}
		$OBJECT_NAME	= !empty($params['object_name']) ? $params['object_name'] : $_GET['object'];
		$OBJECT_ID		= !empty($params['object_id']) ? intval($params['object_id']) : intval($_GET['id']);
		$FORM_ACTION	= !empty($params['add_form_action']) ? $params['add_form_action'] : './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$OBJECT_ID;
		$STPL_NAME_EDIT	= !empty($params['stpl_edit']) ? $params['stpl_edit'] : 'comments/edit_form';
		$RETURN_PATH	= $_SERVER['HTTP_REFERER'];
		if (!empty($params['return_path'])) {
			$RETURN_PATH = process_url($params['return_path']);
		} elseif (!empty($params['return_action'])) {
			$RETURN_PATH = process_url('./?object='.$_GET['object'].'&action='.$params['return_action'].'&id='.$comment_info['object_id']);
		}
		if (empty($OBJECT_NAME) || empty($OBJECT_ID)) {
			return '';
		}
		$edit_allowed = false;
		$edit_allowed_check_method	= is_object(module($_GET['object'])) && method_exists(module($_GET['object']), module('comments')->_edit_allowed_method);
		if ($edit_allowed_check_method) {
			$m = module('comments')->_edit_allowed_method;
			$edit_allowed	= (bool)module($_GET['object'])->$m(array(
				'user_id' => $comment_info['user_id'],
				'object_id' => $comment_info['object_id']
			));
		} else {
			$edit_allowed = module('comments')->USER_ID && $comment_info['user_id'] == module('comments')->USER_ID;
		}
		if (MAIN_TYPE_ADMIN) {
			$edit_allowed	= true;
		} else {
			if(!empty(module('comments')->EDIT_LIMIT_TIME)){
				$elapse_time = time() - $comment_info['add_date'];
				if($elapse_time > module('comments')->EDIT_LIMIT_TIME){
					return _e('allowed time to edit has expired');
				}
			}
		}
		if (!$edit_allowed) {
			return _e('You are not allowed to perform this action');
		}
		$user_info = user($comment_info['user_id'], array('id','name',module('comments')->_user_nick_field,'photo_verified'), array('WHERE' => array('active' => 1)));
		if (count($_POST) > 0 && !isset($_POST['_not_for_comments'])) {
			$_POST['text'] = substr($_POST['text'], 0, module('comments')->MAX_POST_TEXT_LENGTH);
			if (empty($_POST['text'])) {
				_re('Comment text required');
			}
			if (module($_GET['object'])->USE_CAPTCHA) {
				module($_GET['object'])->_captcha_check();
			}
			if (!common()->_error_exists() && MAIN_TYPE_USER) {
				$info_for_check = array(
					'comment_text'	=> $_POST['text'],
					'user_id'		=> module('comments')->USER_ID,
				);
				$USER_BANNED = _check_user_ban($info_for_check, module('comments')->_user_info);
				if ($USER_BANNED) {
					module('comments')->_user_info = user(module('comments')->USER_ID);
				}
				if (module('comments')->_user_info['ban_comments']) {
					return _e(
						'Sorry, you are not allowed to post comments!'.PHP_EOL.'Perhaps, you broke some of our rules and moderator has banned you from using this feature. Please, enjoy our site in some other way!'
						.'For more details <a href=\'./?object=faq&action=view&id=16\'>click here</a>'
					);
				}
			}
			// Anti-flood check
			if (!common()->_error_exists() && module('comments')->ANTI_FLOOD_TIME && MAIN_TYPE_USER) {
				$FLOOD_DETECTED = db()->query_fetch(
					'SELECT id,add_date FROM '.db('comments').' WHERE '
						.(module('comments')->USER_ID ? 'user_id='.intval(module('comments')->USER_ID) : 'ip="'._es(common()->get_ip()).'"')
						.' AND add_date > '.(time() - module('comments')->ANTI_FLOOD_TIME).' 
					ORDER BY add_date DESC 
					LIMIT 1');
				if (!empty($FLOOD_DETECTED)) {
					_re('Please wait %num seconds before post comment.', array('%num' => intval(module('comments')->ANTI_FLOOD_TIME - (time() - $FLOOD_DETECTED['add_date'])) ));
				}
			}
			
			// Anti-spam check
			if (!common()->_error_exists()){
				if(module('comments')->ANTI_SPAM_DETECT){
					$this->_spam_check($_POST['text']);
				}
			}

			if (!common()->_error_exists()) {
				if (module('comments')->AUTO_FILTER_INPUT_TEXT) {
					$_POST['text'] = _filter_text($_POST['text']);
				}
				if (module('comments')->USE_BB_CODES) {
					$BB_CODES_OBJ = _class('bb_codes');
					if (is_object($BB_CODES_OBJ)) {
						$_POST['text'] = $BB_CODES_OBJ->_force_close_bb_codes($_POST['text']);
					}
				}
				db()->UPDATE('comments', array(
					'text' 			=> _es($_POST['text']),
				), 'id='.intval($comment_info['id']));

				$try_trigger_callback = array(module($_GET['object']), module('comments')->_on_update_trigger);
				if (is_callable($try_trigger_callback)) {
					call_user_func($try_trigger_callback, $params);
				}
				$RETURN_PATH = !empty($params['return_path']) ? process_url($params['return_path']) : (!empty($params['return_action']) ? process_url('./?object='.$_GET['object'].'&action='.$params['return_action'].'&id='.$comment_info['object_id']) : $_SERVER['HTTP_REFERER']);
				return js_redirect($RETURN_PATH, false);
			}
		} else {
			$_POST['text'] = $comment_info['text'];
		}
		$error_message = _e();
		if (empty($_POST['go']) || !empty($error_message)) {
			$replace = array(
				'form_action'		=> $FORM_ACTION,
				'error_message'		=> $error_message,
				'user_id'			=> intval(module('comments')->USER_ID),
				'user_name'			=> _prepare_html(_display_name($user_info)),
				'user_avatar'		=> _show_avatar($comment_info['user_id'], $user_info, 1, 1),
				'user_profile_link'	=> _profile_link($comment_info['user_id']),
				'user_email_link'	=> _email_link($comment_info['user_id']),
				'text'				=> _prepare_html($_POST['text']),
				'back_url'			=> $_SERVER['HTTP_REFERER'],
				'object_name'		=> _prepare_html($OBJECT_NAME),
				'object_id'			=> intval($OBJECT_ID),
				'use_captcha'		=> intval((bool)module($_GET['object'])->USE_CAPTCHA),
				'captcha_block'		=> module($_GET['object'])->_captcha_block(),
				'bb_codes_block'	=> module('comments')->USE_BB_CODES ? _class('bb_codes')->_display_buttons(array('unique_id' => 'text')) : '',
				'js_check'			=> intval((bool)module('comments')->JS_TEXT_CHECKING),
			);
			$body = tpl()->parse($STPL_NAME_EDIT, $replace);
		}
		return $body;
	}

	/**
	* Do delete comment
	*/
	function _delete ($params = array()) {
		if (empty(module('comments')->USER_ID) && MAIN_TYPE_USER) {
			return _error_need_login();
		}
		$_GET['id'] = intval($_GET['id']);
		$comment_info = db()->query_fetch('SELECT * FROM '.db('comments').' WHERE id='.intval($_GET['id']));
		if (empty($comment_info['id'])) {
			return _e('No such comment!');
		}
		$OBJECT_NAME	= !empty($params['object_name']) ? $params['object_name'] : $_GET['object'];
		$OBJECT_ID		= !empty($params['object_id']) ? intval($params['object_id']) : intval($_GET['id']);
		$SILENT_MODE	= !empty($params['silent_mode']) ? 1 : 0;
		$RETURN_PATH	= $_SERVER['HTTP_REFERER'];
		if (!empty($params['return_path'])) {
			$RETURN_PATH = process_url($params['return_path']);
		} elseif (!empty($params['return_action'])) {
			$RETURN_PATH = process_url('./?object='.$_GET['object'].'&action='.$params['return_action'].'&id='.$comment_info['object_id']);
		}
		if (empty($OBJECT_NAME) || empty($OBJECT_ID)) {
			return '';
		}
		if (module('comments')->_user_info['ban_comments'] && MAIN_TYPE_USER) {
			return _e(
				'Sorry, you are not allowed to post comments!'.PHP_EOL.'Perhaps, you broke some of our rules and moderator has banned you from using this feature. Please, enjoy our site in some other way!'
				.'For more details <a href=\'./?object=faq&action=view&id=16\'>click here</a>'
			);
		}
		$module_obj = module($_GET['object']);
		// Check if user is allowed to perform this action
		$delete_allowed = false;
		$delete_allowed_check_method	= is_object($module_obj) && method_exists($module_obj, module('comments')->_delete_allowed_method);
		if ($delete_allowed_check_method) {
			$m = module('comments')->_delete_allowed_method;
			$delete_allowed	= (bool)module($_GET['object'])->$m(array(
				'user_id' => $comment_info['user_id'],
				'object_id' => $comment_info['object_id']
			));
		} else {
			$delete_allowed = module('comments')->USER_ID && $comment_info['user_id'] == module('comments')->USER_ID;
		}
		if (MAIN_TYPE_ADMIN || $SILENT_MODE) {
			$delete_allowed	= true;
		} else {
			// get elapse time
			if(!empty(module('comments')->EDIT_LIMIT_TIME)){
				$elapse_time = time() - $comment_info['add_date'];
				if($elapse_time > module('comments')->EDIT_LIMIT_TIME){
					return _e('allowed time to delete has expired');
				}
			}
		}
		
		if (!$delete_allowed) {
			return _e('You are not allowed to perform this action');
		}
		module('unread')->_set_read('comments', $_GET['id']);

		if (module('comments')->USE_TREE_MODE) {
			$have_children = db()->query_fetch(
				'SELECT id FROM '.db('comments').' 
				WHERE object_name="'.$comment_info['object_name'].'" AND object_id='.$comment_info['object_id'].' AND parent_id='.$comment_info['id'].' 
				LIMIT 1');

			if ($have_children) {
				db()->UPDATE('comments', array(
					'text'		=> '__comment was deleted__',
					'user_id'	=> 0,
				), 'id='.intval($_GET['id']));
			} else {
				db()->query('DELETE FROM '.db('comments').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			}
		}  else {
			db()->query('DELETE FROM '.db('comments').' WHERE id='.intval($_GET['id']).' LIMIT 1');
		}
		// Execute custom on_update trigger (if exists one)
		$try_trigger_callback = array(module($_GET['object']), module('comments')->_on_update_trigger);
		if (is_callable($try_trigger_callback)) {
			call_user_func($try_trigger_callback, $params);
		}
		return !$SILENT_MODE ? js_redirect($RETURN_PATH, false) : '';
	}

	/**
	* Check spam
	*/
	function _spam_check($text){
		preg_match_all(module('comments')->HTML_LINK_REGEX, $text, $result);
		preg_match_all(module('comments')->BBCODE_LINK_REGEX, $text, $result2);
		$count_links = count($result[1]) + count($result2[1]);
		if (empty(module('comments')->USER_ID)) {
			if ($count_links > 1) {
				_re('Too many links');
			}
		} else {
			if ($count_links > 3) {
				_re('Too many links');
			}
		}
	}
}
