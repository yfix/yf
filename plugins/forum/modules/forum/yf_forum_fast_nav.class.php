<?php

/**
* Fast navigation box
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_forum_fast_nav {

	/** @var string */
	public $_text_forum_divider	= '&nbsp;&nbsp;&#0124;-- ';
	/** @var string */
	public $_text_level_filler		= '&nbsp;&nbsp;&nbsp;&nbsp;';
	
	/**
	* Board Fast Navigation Box
	*/
	function _board_fast_nav_box() {
		// Create site jump array
		$site_jump_array = array(
			'sj_home'	=> 'Forum Home',
			'sj_search'	=> 'Search',
			'sj_help'	=> 'Help',
		);
		// Prepare array for processing (to avoid slow manipulations)
		foreach ((array)module('forum')->_forums_array as $_info) {
			// Skip non-active forums
			if ($_info['status'] != 'a') {
				continue;
			}
			$cat_info = module('forum')->_forum_cats_array[$_info['category']];
			// Skip non-active categories
			if ($cat_info['status'] != 'a') {
				break;
			}
			$this->_forum_parents[$_info['parent']][$_info['id']] = $_info['category'];
			$this->_forum_cat_names[$_info['category']] = $cat_info['name'];
		}
		// Create forum jump array
		$forum_jump_array = $this->_prepare_parents_for_select();
		// Try to set selected index
		if ($_GET['action'] == 'show' && empty($_GET['id']))		$selected = 'sj_home';
		elseif ($_GET['action'] == 'show' && !empty($_GET['id']))	$selected = 'cat_'.$_GET['id'];
		elseif ($_GET['action'] == 'search')						$selected = 'sj_search';
		elseif ($_GET['action'] == 'help')							$selected = 'sj_help';
		elseif ($_GET['action'] == 'view_forum')					$selected = $_GET['id'];
		elseif ($_GET['action'] == 'view_topic')					$selected = module('forum')->_topic_info['forum'];
		else														$selected = 'sj_home';
		// Process main template
		$replace = array(
			'form_action'	=> './?object='.'forum'.'&action=site_jump'._add_get(array('page')),
			'fast_nav_box'	=> common()->select_box('fast_nav', array('Site Jump' => $site_jump_array, 'Forum Jump' => $forum_jump_array), $selected, false, 2, 
				" onchange=\"if(this.options[this.selectedIndex].value != -1){ document.jumpmenu.submit() }\"", false),
		);
		return tpl()->parse('forum'.'/board_fast_nav', $replace);
	}
	
	/**
	* Recurse prepare forums tree for the select-box
	*/
	function _prepare_parents_for_select ($skip_id = 0, $parent_id = 0, $level = 0) {
		$f = __FUNCTION__;
		$forums = array();
		// Prepare categories for select box
		foreach ((array)$this->_forum_parents[$parent_id] as $_forum_id => $_cat_id) {
			if ($_forum_id == $skip_id) {
				continue;
			}
			// Add category (with prefix: 'c_')
			if (!isset($forums['c_'.$_cat_id])) {
				$forums['cat_'.$_cat_id] = $this->_forum_cat_names[$_cat_id];
			}
			// Add current forum
			$forums[$_forum_id] = str_repeat($this->_text_level_filler, $level). $this->_text_forum_divider. module('forum')->_forums_array[$_forum_id]['name'];
			// Try to find sub-forums
			foreach ((array)$this->$f($skip_id, $_forum_id, $level + 1) as $_sub_id => $_sub_name) {
				$forums[$_sub_id] = $_sub_name;
			}
		}
		return $forums;
	}
}
