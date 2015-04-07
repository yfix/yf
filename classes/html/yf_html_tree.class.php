<?php

/**
*/
class yf_html_tree {

	/**
	*/
	function _init() {
		$this->_parent = _class('html');
	}

	/**
	*/
	function tree($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		if ($data) {
			$data = $this->_parent->_recursive_sort_items($data);
		}
		asset('yf_draggable_tree');

		$items = implode(PHP_EOL, (array)$this->_tree_items($data, $extra));
		$r = array(
			'form_action'	=> $extra['form_action'] ?: './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'add_link'		=> $extra['add_link'] ?: './?object='.$_GET['object'].'&action=add_item&id='.$_GET['id'],
			'back_link'		=> $extra['back_link'] ?: './?object='.$_GET['object'].'&action=show_items&id='.$_GET['id'],
		);
#		$btn_save	= '<button type="submit" class="btn btn-primary btn-mini btn-xs"><i class="icon-large fa-lg icon-save fa fa-save"></i> '.t('Save').'</button>';
#		$btn_back	= $r['back_link'] ? '<a href="'.$r['back_link'].'" class="btn btn-mini btn-xs"><i class="icon-large fa-lg icon-backward fa fa-backward"></i> '.t('Go Back').'</a>' : '';
#		$btn_add	= $r['add_link'] ? '<a href="'.$r['add_link'].'" class="btn btn-mini btn-xs ajax_add"><i class="icon-large fa-lg icon-plus-sign fa fa-plus-circle"></i> '.t('Add').'</a>' : '';
		$btn_expand = !$extra['no_expand'] ? '<a href="javascript:void(0);" class="btn btn-mini btn-xs draggable-menu-expand-all"><i class="icon-large fa-lg icon-expand-alt fa fa-expand"></i> '.t('Expand').'</a>' : '';
		$form_class = 'draggable_form'. ($extra['form_class'] ? ' '.$extra['form_class'] : ''). ($extra['class_add'] ? ' '.$extra['class_add'] : '');
		return '<form action="'.$r['form_action'].'" method="post" class="'.$form_class.'">
				<div class="controls">'
					. $btn_save
					. $btn_back
					. $btn_add
					. $btn_expand
				.'</div>
				<ul class="draggable_menu">'.$items.'</ul>
			</form>';
	}

	/**
	* This pure-php method needed to greatly speedup page rendering time for 100+ items
	*/
	function _tree_items(&$data, $extra = array()) {
		if ($extra['show_controls']) {
			$r = array(
				'edit_link'		=> $extra['edit_link'] ?: './?object='.$_GET['object'].'&action=edit_item&id=%d',
				'delete_link'	=> $extra['delete_link'] ?: './?object='.$_GET['object'].'&action=delete_item&id=%d',
				'clone_link'	=> $extra['clone_link'] ?: './?object='.$_GET['object'].'&action=clone_item&id=%d',
			);
			$form_controls = form_item($r)->tbl_link_edit()
				. form_item($r)->tbl_link_delete()
				. form_item($r)->tbl_link_clone()
			;
		}
		$opened_levels = isset($extra['opened_levels']) ? $extra['opened_levels'] : 1;
		$is_draggable = isset($extra['draggable']) ? $extra['draggable'] : true;
		$keys = array_keys($data);
		$keys_counter = array_flip($keys);
		$items = array();
		$ul_opened = false;
		foreach ((array)$data as $id => $item) {
			$next_item = $data[ $keys[$keys_counter[$id] + 1] ];
			$has_children = false;
			$close_li = 1;
			$close_ul = 0;
			if ($next_item) {
				if ($next_item['level'] > $item['level']) {
					$has_children = true;
				}
				$close_li = $item['level'] - $next_item['level'] + 1;
				if ($close_li < 0) {
					$close_li = 0;
				}
			}
			$expander_icon = '';
			if ($has_children) {
				$expander_icon = $item['level'] >= $opened_levels ? 'icon-caret-right fa fa-caret-right' : 'icon-caret-down fa fa-caret-down';
			}
			$content = ($item['icon_class'] ? '<i class="'.$item['icon_class'].'"></i>' : ''). $item['name'];
			if ($item['link']) {
				$content = '<a href="'.$item['link'].'">'.$content. '</a>';
			}
			if (is_callable($extra['show_controls'])) {
				$func = $extra['show_controls'];
				$controls = $func($id, $item);
			} else {
				$controls = $extra['show_controls'] ? str_replace('%d', $id, $form_controls) : '';
			}
			$badge = $item['badge'] ? ' <sup class="badge badge-'.($item['class_badge'] ?: 'info').'">'.$item['badge'].'</sup>' : '';
			$controls_style = 'float:right;'.($extra['class_add'] != 'no_hide_controls' ? 'display:none;' : '');
			$items[] = '
				<li id="item_'.$id.'"'.(!$is_draggable ? ' class="not_draggable"' : '').'>
					<div class="dropzone"></div>
					<dl>
						<a href="'.$item['link'].'" class="expander"><i class="icon '.$expander_icon.'"></i></a>&nbsp;'
						.$content
						.$badge
						.($is_draggable ? '&nbsp;<span class="move" title="'.t('Move').'"><i class="icon icon-move fa fa-arrows"></i></span>' : '')
						.($controls ? '<div style="'.$controls_style.'" class="controls_over">'.$controls.'</div>' : '')
					.'</dl>'
				;
			if ($has_children) {
				$ul_opened = true;
				$items[] = PHP_EOL. '<ul class="'.($item['level'] >= $opened_levels ? 'closed' : '').'">'. PHP_EOL;
			} elseif ($close_li) {
				if ($ul_opened && !$has_children && $item['level'] != $next_item['level']) {
					$ul_opened = false;
					$close_ul = 1;
				}
				$tmp = str_repeat(PHP_EOL. ($close_ul ? '</li></ul>' : '</li>'). PHP_EOL, $close_li);
				if ($close_li > 1 && $close_ul) {
					$tmp = substr($tmp, 0, -strlen('</ul>'.PHP_EOL)). PHP_EOL;
				}
				$items[] = $tmp;
			}
		}
		return $items;
	}

	/**
	*/
	function li_tree($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		if ($data) {
			$data = $this->_parent->_recursive_sort_items($data);
		}
		if (!$data) {
			return false;
		}
		$opened_levels = isset($extra['opened_levels']) ? $extra['opened_levels'] : 1;
		$keys = array_keys($data);
		$keys_counter = array_flip($keys);
		$items = array();
		$ul_opened = false;
		foreach ((array)$data as $id => $item) {
			$next_item = $data[ $keys[$keys_counter[$id] + 1] ];
			$has_children = false;
			$close_li = 1;
			$close_ul = 0;
			if ($next_item) {
				if ($next_item['level'] > $item['level']) {
					$has_children = true;
				}
				$close_li = $item['level'] - $next_item['level'] + 1;
				if ($close_li < 0) {
					$close_li = 0;
				}
			}
			$body = $item['name'] ?: $item['body'];
			$content = ($item['icon_class'] ? '<i class="'.$item['icon_class'].'"></i>' : ''). (strlen($body) ? '<span class="li-content">'.$body.'</span>' : '');
			if ($item['link']) {
				$content = '<a href="'.$item['link'].'">'.$content. '</a>';
			}
			$items[] = '<li id="'.($item['id'] ?: ($extra['id'] ?: 'item').'_'.$id).'" class="li-header li-level-'.$item['level'].'">'. $content;
			if ($has_children) {
				$ul_opened = true;
				$items[] = PHP_EOL. '<ul class="'.($item['level'] >= $opened_levels ? 'closed' : '').'">'. PHP_EOL;
			} elseif ($close_li) {
				if ($ul_opened && !$has_children && $item['level'] != $next_item['level']) {
					$ul_opened = false;
					$close_ul = 1;
				}
				$tmp = str_repeat(PHP_EOL. ($close_ul ? '</li></ul>' : '</li>'). PHP_EOL, $close_li);
				if ($close_li > 1 && $close_ul) {
					$tmp = substr($tmp, 0, -strlen('</ul>'.PHP_EOL)). PHP_EOL;
				}
				$items[] = $tmp;
			}
		}
		return implode(PHP_EOL, $items);
	}
}
