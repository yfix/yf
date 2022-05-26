<?php


class yf_html_tree
{
    public function _init()
    {
        $this->_parent = _class('html');
    }

    /**
     * @param mixed $data
     * @param mixed $extra
     */
    public function tree($data = [], $extra = [])
    {
        $extra['id'] = $extra['id'] ?: __FUNCTION__ . '_' . ++$this->_ids[__FUNCTION__];
        if ($data) {
            $data = $this->_parent->_recursive_sort_items($data);
        }
        asset('yf_draggable_tree');

        $items = implode(PHP_EOL, (array) $this->_tree_items($data, $extra));
        $r = [
            'form_action' => isset($extra['form_action']) ? $extra['form_action'] : url('/@object/@action/@id/@page'),
            'add_link' => isset($extra['add_link']) ? $extra['add_link'] : url('/@object/add_item/@id/@page'),
            'back_link' => isset($extra['back_link']) ? $extra['back_link'] : url('/@object/show_items/@id/@page'),
        ];
        $form_class = 'draggable_form' . ($extra['form_class'] ? ' ' . $extra['form_class'] : '') . ($extra['class_add'] ? ' ' . $extra['class_add'] : '');
        $add_link_class = 'btn btn-mini btn-xs btn-default' . ( ! $extra['add_no_ajax'] ? ' ajax_add' : '');
        return
            '<form action="' . $r['form_action'] . '" method="post" class="' . $form_class . '">
				<div class="controls">'
                    . ($r['form_action'] ? '<button type="submit" class="btn btn-primary btn-mini btn-xs"><i class="fa fa-save"></i> ' . t('Save') . '</button>' : '')
                    . ($r['back_link'] ? '<a href="' . $r['back_link'] . '" class="btn btn-mini btn-xs btn-default"><i class="fa fa-backward"></i> ' . t('Go Back') . '</a>' : '')
                    . ($r['add_link'] ? '<a href="' . $r['add_link'] . '" class="' . $add_link_class . '"><i class="fa fa-plus"></i> ' . t('Add') . '</a>' : '')
                    . ( ! $extra['no_expand'] ? '<a href="javascript:void(0);" class="btn btn-mini btn-xs btn-default draggable-menu-expand-all"><i class="fa fa-expand"></i> ' . t('Expand') . '</a>' : '')
                . '</div>
				<ul class="draggable_menu">' . $items . '</ul>
			</form>';
    }

    /**
     * This pure-php method needed to greatly speedup page rendering time for 100+ items.
     * @param mixed $extra
     * @param mixed $data
     */
    public function _tree_items(&$data, $extra = [])
    {
        if ($extra['show_controls'] && ! is_callable($extra['show_controls'])) {
            $r = [
                'edit_link' => isset($extra['edit_link']) ? $extra['edit_link'] : url('/@object/edit_item/%d/@page'),
                'delete_link' => isset($extra['delete_link']) ? $extra['delete_link'] : url('/@object/delete_item/%d/@page'),
                'clone_link' => isset($extra['clone_link']) ? $extra['clone_link'] : url('/@object/clone_item/%d/@page'),
            ];
            $form_controls = form_item($r)->tbl_link_edit()
                . form_item($r)->tbl_link_delete()
                . form_item($r)->tbl_link_clone();
        }
        $opened_levels = isset($extra['opened_levels']) ? $extra['opened_levels'] : 1;
        $is_draggable = isset($extra['draggable']) ? $extra['draggable'] : true;
        $keys = array_keys($data);
        $keys_counter = array_flip($keys);
        $items = [];
        $ul_opened = false;
        foreach ((array) $data as $id => $item) {
            $next_item = $data[$keys[$keys_counter[$id] + 1]];
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
            $content = ($item['icon_class'] ? '<i class="' . $item['icon_class'] . '"></i>' : '') . $item['name'];
            if ($item['link']) {
                $content = '<a href="' . $item['link'] . '">' . $content . '</a>';
            }
            if (is_callable($extra['show_controls'])) {
                $func = $extra['show_controls'];
                $controls = $func($id, $item);
            } else {
                $controls = $extra['show_controls'] ? str_replace('%d', $id, $form_controls) : '';
            }
            $badge = $item['badge'] ? ' <sup class="badge badge-' . ($item['class_badge'] ?: 'info') . '">' . $item['badge'] . '</sup>' : '';
            $controls_style = 'float:right;' . ($extra['class_add'] != 'no_hide_controls' ? 'display:none;' : '');
            $items[] = '
				<li id="item_' . $id . '"' . ( ! $is_draggable ? ' class="not_draggable"' : '') . '>
					<div class="dropzone"></div>
					<dl>
						<a href="' . $item['link'] . '" class="expander"><i class="icon ' . $expander_icon . '"></i></a>&nbsp;'
                        . $content
                        . $badge
                        . ($is_draggable ? '&nbsp;<span class="move" title="' . t('Move') . '"><i class="icon icon-move fa fa-arrows"></i></span>' : '')
                        . ($controls ? '<div style="' . $controls_style . '" class="controls_over">' . $controls . '</div>' : '')
                    . '</dl>';
            if ($has_children) {
                $ul_opened = true;
                $items[] = PHP_EOL . '<ul class="' . ($item['level'] >= $opened_levels ? 'closed' : '') . '">' . PHP_EOL;
            } elseif ($close_li) {
                if ($ul_opened && ! $has_children && $item['level'] != $next_item['level']) {
                    $ul_opened = false;
                    $close_ul = 1;
                }
                $tmp = str_repeat(PHP_EOL . ($close_ul ? '</li></ul>' : '</li>') . PHP_EOL, $close_li);
                if ($close_li > 1 && $close_ul) {
                    $tmp = substr($tmp, 0, -strlen('</ul>' . PHP_EOL)) . PHP_EOL;
                }
                $items[] = $tmp;
            }
        }
        return $items;
    }

    /**
     * @param mixed $data
     * @param mixed $extra
     */
    public function li_tree($data = [], $extra = [])
    {
        $extra['id'] = $extra['id'] ?: __FUNCTION__ . '_' . ++$this->_ids[__FUNCTION__];
        if ($data) {
            $data = $this->_parent->_recursive_sort_items($data);
        }
        if ( ! $data) {
            return false;
        }
        $opened_levels = isset($extra['opened_levels']) ? $extra['opened_levels'] : 1;
        $keys = array_keys($data);
        $keys_counter = array_flip($keys);
        $items = [];
        $ul_opened = false;
        foreach ((array) $data as $id => $item) {
            $next_item = $data[$keys[$keys_counter[$id] + 1]];
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
            $content = ($item['icon_class'] ? '<i class="' . $item['icon_class'] . '"></i>' : '') . (strlen($body) ? '<span class="li-content">' . $body . '</span>' : '');
            if ($item['link']) {
                $content = '<a href="' . $item['link'] . '">' . $content . '</a>';
            }
            $items[] = '<li id="' . ($item['id'] ?: ($extra['id'] ?: 'item') . '_' . $id) . '" class="li-header li-level-' . $item['level'] . '">' . $content;
            if ($has_children) {
                $ul_opened = true;
                $items[] = PHP_EOL . '<ul class="' . ($item['level'] >= $opened_levels ? 'closed' : '') . '">' . PHP_EOL;
            } elseif ($close_li) {
                if ($ul_opened && ! $has_children && $item['level'] != $next_item['level']) {
                    $ul_opened = false;
                    $close_ul = 1;
                }
                $tmp = str_repeat(PHP_EOL . ($close_ul ? '</li></ul>' : '</li>') . PHP_EOL, $close_li);
                if ($close_li > 1 && $close_ul) {
                    $tmp = substr($tmp, 0, -strlen('</ul>' . PHP_EOL)) . PHP_EOL;
                }
                $items[] = $tmp;
            }
        }
        return implode(PHP_EOL, $items);
    }
}
