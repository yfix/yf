<?php

/**
 * Category display handler.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_cats
{
    /** @var mixed @conf_skip */
    public $_category_sets = null;
    /** @var mixed @conf_skip */
    public $_items_cache = null;
    /** @var mixed @conf_skip */
    public $_default_callback = null;
    /** @var mixed @conf_skip */
    public $_default_cats_block = null;
    /** @var bool */
    public $USE_DYNAMIC_ATTS = true;
    /** @var string */
    public $BOX_LEVEL_SPACER = '&nbsp;&nbsp;';
    /** @var string */
    public $BOX_LEVEL_MARKER = '&#0124;-- ';

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args);
    }


    public function _init()
    {
        $this->_category_sets = main()->get_data('category_sets');
        $this->_default_cats_block = $_GET['object'] . '_cats';
    }

    /**
     * Get all category items for the given block.
     * @param mixed $cat_name
     * @param mixed $recursive_sort
     * @param mixed $all
     */
    public function _get_items_array($cat_name = '', $recursive_sort = true, $all = false)
    {
        if (empty($cat_name)) {
            $cat_name = $this->_default_cats_block;
        }
        $cat_id = $this->_get_cat_id_by_name($cat_name);
        if (empty($cat_id)) {
            return false;
        }
        $cache_name = (int) $cat_id . '_' . (int) $all;
        if ( ! isset($this->_items_cache[$cache_name])) {
            $cat_id = $this->_get_cat_id_by_name($cat_name);
            $custom_fields = [];
            if ($cat_id) {
                foreach (explode(',', $this->_category_sets[$cat_id]['custom_fields']) as $f) {
                    $f = trim($f);
                    if ($f) {
                        $custom_fields[$f] = $f;
                    }
                }
            }
            $cat_items = $all ? 'category_items_all' : 'category_items';
            foreach ((array) main()->get_data($cat_items) as $a) {
                if ($a['cat_id'] != $cat_id) {
                    continue;
                }
                // Try to parse 'dynamic' attributes for the item
                if ($this->USE_DYNAMIC_ATTS && $custom_fields) {
                    $custom_attrs = [];
                    if ($a['other_info']) {
                        $custom_attrs = (array) _attrs_string2array($a['other_info']);
                    }
                    foreach ((array) $custom_fields as $f) {
                        $a[$f] = isset($custom_attrs[$f]) ? (string) $custom_attrs[$f] : '';
                    }
                }
                $raw_items[$a['id']] = $a;
            }
            $this->_items_cache[$cache_name] = $raw_items;
        } else {
            $raw_items = $this->_items_cache[$cache_name];
        }
        if ($recursive_sort && ! empty($raw_items)) {
            $raw_items = $this->_recursive_sort_items($raw_items);
        }
        return $raw_items ? $raw_items : false;
    }

    /**
     * Get and sort items ordered array (recursively).
     * @param mixed $items
     * @param mixed $skip_item_id
     * @param mixed $parent_id
     */
    public function _recursive_sort_items($items = [], $skip_item_id = 0, $parent_id = 0)
    {
        $children = [];
        $cur_group = MAIN_TYPE_USER ? $_SESSION['user_group'] : $_SESSION['admin_group'];
        foreach ((array) $items as $id => $info) {
            $parent_id = $info['parent_id'];
            if ($skip_item_id == $id) {
                continue;
            }
            $user_groups = [];
            if ( ! empty($info['user_groups'])) {
                foreach (explode(',', $info['user_groups']) as $v) {
                    if ( ! empty($v)) {
                        $user_groups[$v] = $v;
                    }
                }
                if ( ! empty($user_groups) && ! isset($user_groups[$cur_group])) {
                    continue;
                }
            }
            $children[$parent_id][$id] = $id;
        }
        $ids = $this->_count_levels($children, 0);
        $new_items = [];
        foreach ((array) $ids as $id => $level) {
            $new_items[$id] = $items[$id] + ['level' => $level];
        }
        return $new_items;
    }

    /**
     * @param mixed $start_id
     * @param mixed $level
     */
    public function _count_levels(&$children, $start_id = 0, $level = 0)
    {
        $ids = [];
        foreach ((array) $children[$start_id] as $id => $_tmp) {
            $ids[$id] = $level;
            if (isset($children[$id])) {
                foreach ((array) $this->_count_levels($children, $id, $level + 1) as $_id => $_level) {
                    $ids[$_id] = $_level;
                }
            }
        }
        return $ids;
    }

    /**
     * Get all category items names for the given block.
     * @param mixed $cat_name
     * @param mixed $recursive_sort
     * @param mixed $all
     */
    public function _get_items_names($cat_name = '', $recursive_sort = true, $all = false)
    {
        $items = [];
        foreach ((array) $this->_get_items_array($cat_name, $recursive_sort, $all) as $item_id => $item) {
            $id = $item['id'] ?? null;
            if ($id !== null) {
                $items[$id] = $item['name'];
            }
        }
        return $items;
    }

    /**
     * @param mixed $cat_name
     * @param mixed $recursive_sort
     * @param mixed $all
     */
    public function _get_items_names_cached($cat_name = '', $recursive_sort = true, $all = false)
    {
        $cache_name = 'cats__get_items_names__' . $cat_name . '_' . (int) $all . '_' . (int) $recursive_sort;
        $items = cache_get($cache_name);
        if ($items) {
            return $items;
        }
        $items = $this->_get_items_names($cat_name, $recursive_sort, $all);
        cache_set($cache_name, $items);
        return $items;
    }

    /**
     * @param mixed $cat_name
     * @param mixed $with_all
     * @param mixed $parent_item_id
     * @param mixed $all
     */
    public function _prepare_for_box_cached($cat_name = '', $with_all = 1, $parent_item_id = 0, $all = false)
    {
        $cache_name = 'cats__prepare_for_box__' . $cat_name . '_' . (int) $all . '_' . (int) $parent_item_id;
        $items = cache_get($cache_name);
        if ($items) {
            return $items;
        }
        $items = $this->_prepare_for_box($cat_name, $with_all, $parent_item_id, $all);
        cache_set($cache_name, $items);
        if ( ! $with_all) {
            unset($items[' ']);
        } elseif ( ! isset($items[' '])) {
            $items = [' ' => t('-- All --')] + $items;
        }
        return $items;
    }

    /**
     * Prepare category items for use in box.
     * @param mixed $cat_items
     * @param mixed $with_all
     * @param mixed $parent_item_id
     * @param mixed $all
     */
    public function _prepare_for_box($cat_items = [], $with_all = true, $parent_item_id = 0, $all = false)
    {
        if ( ! empty($cat_items) && is_string($cat_items)) {
            $cat_items = $this->_get_items_array($cat_items);
        }
        if (empty($cat_items)) {
            $cat_items = $this->_get_items_array($this->_default_cats_block, true, $all);
        }
        $items_for_box = [];
        if ($with_all) {
            $items_for_box[' '] = t('-- All --');
        }
        $only_children_ids = [];
        if ($parent_item_id) {
            // build list of children allowed and show only them
            $only_children_ids[$parent_item_id] = $parent_item_id;
            foreach ((array) $this->_recursive_get_children_ids($parent_item_id, $cat_items, $get_sub_children = true, $return_array = true) as $cid => $cinfo) {
                $only_children_ids[$cid] = $cid;
            }
        }
        foreach ((array) $cat_items as $cur_item_id => $cur_item_info) {
            if (empty($cur_item_id)) {
                continue;
            }
            if ($only_children_ids && ! isset($only_children_ids[$cur_item_id])) {
                continue;
            }
            $level = $cur_item_info['level'] ?? 0;
            $items_for_box[$cur_item_id] = str_repeat($this->BOX_LEVEL_SPACER, $level)
                . ($level > 0 ? $this->BOX_LEVEL_MARKER : '')
                . t($cur_item_info['name']);
        }
        return $items_for_box;
    }

    /**
     * Prepare category items for use in box.
     * @param mixed $cat_name
     * @param mixed $with_all
     */
    public function _get_items_for_box($cat_name = '', $with_all = true)
    {
        return $this->_prepare_for_box($this->_get_items_array($cat_name), $with_all);
    }

    /**
     * Display category block items box.
     * @param mixed $cat_name
     * @param mixed $selected
     * @param mixed $name_in_form
     * @param mixed $with_all
     */
    public function _cats_box($cat_name = '', $selected = '', $name_in_form = 'cat_id', $with_all = 1)
    {
        return common()->select_box($name_in_form, $this->_get_items_for_box($cat_name, $with_all), $selected, false, 2, '', false);
    }

    /**
     * @param mixed $cat_name
     */
    public function _get_cat_id_by_name($cat_name = '')
    {
        if (empty($cat_name)) {
            $cat_name = $this->_default_cats_block;
        }
        if (empty($cat_name)) {
            return false;
        }
        $cat_id = 0;
        foreach ((array) $this->_category_sets as $cur_cat_id => $cur_cat_info) {
            if ($cur_cat_info['name'] == $cat_name) {
                $cat_id = $cur_cat_id;
                break;
            }
        }
        return $cat_id;
    }

    /**
     * @param mixed $cat_id
     */
    public function _get_cat_name_by_id($cat_id = '')
    {
        if (empty($cat_id)) {
            return false;
        }
        return $this->_category_sets[$cat_id]['name'];
    }

    /**
     * @param mixed $cat_id
     * @param mixed $cat_items
     */
    public function _recursive_get_parents_ids($cat_id = 0, $cat_items = [])
    {
        $parents_ids = [];
        if (empty($cat_id)) {
            return $parents_ids;
        }
        if ( ! empty($cat_items) && is_string($cat_items)) {
            $cat_items = $this->_get_items_array($cat_items, false);
        }
        if (empty($cat_items)) {
            $cat_items = $this->_get_items_array($this->_default_cats_block, false);
        }
        $cur_func_name = __FUNCTION__;
        foreach ((array) $cat_items as $cur_item_info) {
            if ($cur_item_info['id'] != $cat_id) {
                continue;
            }
            if ( ! empty($cur_item_info['parent_id'])) {
                $parents_ids[$cur_item_info['parent_id']] = $cur_item_info['parent_id'];
                foreach ((array) $this->$cur_func_name($cur_item_info['parent_id'], $cat_items) as $cur_parent_id) {
                    $parents_ids[$cur_parent_id] = $cur_parent_id;
                }
            }
        }
        return $parents_ids;
    }

    /**
     * @param mixed $item_id
     * @param mixed $cat_items
     * @param mixed $STPL_NAME
     * @param null|mixed $prepare_link_callback
     */
    public function _get_nav_by_item_id($item_id = 0, $cat_items = [], $STPL_NAME = '', $prepare_link_callback = null)
    {
        if (empty($STPL_NAME)) {
            $STPL_NAME = __CLASS__ . '/nav_item';
        }
        if ( ! empty($cat_items) && is_string($cat_items)) {
            $cat_items = $this->_get_items_array($cat_items);
        }
        if (empty($cat_items)) {
            $cat_items = $this->_get_items_array($this->_default_cats_block);
        }
        if ( ! isset($this->_default_callback)) {
            $this->_default_callback = false;
            if ( ! empty($_GET['object'])) {
                $try_callback = [module($_GET['object']), '_callback_cat_link'];
            }
            if (is_callable($try_callback)) {
                $this->_default_callback = $try_callback;
            }
        }
        if (empty($prepare_link_callback) && ! empty($this->_default_callback)) {
            $prepare_link_callback = $this->_default_callback;
        }
        $USE_CALLBACK = ! empty($prepare_link_callback) && is_callable($prepare_link_callback);
        $parents_ids = $this->_recursive_get_parents_ids($item_id, $cat_items);
        if ( ! empty($parents_ids)) {
            $nav_items_ids = array_reverse($parents_ids, 1);
        }
        $nav_items_ids[$item_id] = $item_id;
        $body = '';
        $i = 0;
        foreach ((array) $nav_items_ids as $cur_item_id) {
            if ($USE_CALLBACK) {
                $item_link = call_user_func($prepare_link_callback, $cur_item_id);
            } else {
                $item_link = './?object=' . $_GET['object'] . '&action=view_cat&id=' . $cur_item_id;
            }
            $replace = [
                'item_link' => $item_link,
                'item_name' => _prepare_html($cat_items[$cur_item_id]['name']),
                'is_last' => (int) (++$i >= count((array) $nav_items_ids)),
            ];
            $body .= tpl()->parse($STPL_NAME, $replace);
        }
        return $body;
    }

    /**
     * @param mixed $cat_id
     * @param mixed $cat_items
     * @param mixed $get_sub_children
     * @param mixed $return_array
     */
    public function _recursive_get_children_ids($cat_id = 0, $cat_items = [], $get_sub_children = true, $return_array = false)
    {
        $children_ids = [];
        if (empty($cat_id)) {
            return $children_ids;
        }
        if ( ! empty($cat_items) && is_string($cat_items)) {
            $cat_items = $this->_get_items_array($cat_items);
        }
        if (empty($cat_items)) {
            $cat_items = $this->_get_items_array($this->_default_cats_block);
        }
        $cur_func_name = __FUNCTION__;
        foreach ((array) $cat_items as $cur_item_info) {
            if ($cur_item_info['parent_id'] != $cat_id) {
                continue;
            }
            $sub_children = [];
            if ($get_sub_children) {
                $sub_children = $this->$cur_func_name($cur_item_info['id'], $cat_items, $get_sub_children, $return_array);
            }
            if ($return_array) {
                $children_ids[$cur_item_info['id']] = $cur_item_info['id'];
                $children_ids = $children_ids + (array) $sub_children;
            } else {
                $children_ids[$cur_item_info['id']] = $sub_children;
            }
        }
        return $children_ids;
    }

    /**
     * @param mixed $item_id
     * @param mixed $cat_name
     */
    public function _get_item_name($item_id = '', $cat_name = '')
    {
        if (empty($cat_name)) {
            $cat_name = $this->_default_cats_block;
        }
        $cat_id = $this->_get_cat_id_by_name($cat_name);
        if (empty($cat_id) || empty($item_id)) {
            return false;
        }
        $items = $this->_get_items_names_cached($cat_name, $recursive_sort = true, $all = false);
        return $items[$item_id];
    }

    /**
     * @param mixed $cat_id
     * @param mixed $all_cats
     */
    public function _get_recursive_cat_ids($cat_id = 0, $all_cats = false)
    {
        $cat_id = (int) $cat_id;
        if (empty($all_cats)) {
            $all_cats = conf('all_cats');
            if (empty($all_cats)) {
                $all_cats = main()->get_data('category_items_all');
                if (empty($all_cats)) {
                    return false;
                }
                conf('all_cats', $all_cats);
            }
        }
        $current_func = __FUNCTION__;
        $ids[$cat_id] = $cat_id;
        foreach ((array) $all_cats as $key => $item) {
            if ($item['parent_id'] == $cat_id) {
                $ids += $this->$current_func($item['id'], $all_cats);
            }
        }
        return $ids;
    }

    /**
     * @param mixed $cat_name
     * @param mixed $recursive_sort
     * @param mixed $all_records
     */
    public function _get_all_parents_tree($cat_name, $recursive_sort = true, $all_records = false)
    {
        $cats_parents = [];
        foreach ((array) $this->_get_items_array($cat_name, $recursive_sort, $all_records) as $cid => $a) {
            $cats_parents[$cid] = $a['parent_id'];
        }
        $tree = [];
        foreach ((array) $cats_parents as $cid => $parent_id) {
            $parents = [];
            $parents[$cid] = $cid;
            do {
                $parents[$parent_id] = $parent_id;
            } while ($parent_id = $cats_parents[$parent_id]);

            if (isset($parents[0])) {
                unset($parents[0]);
            }
            $tree[$cid] = $parents;
        }
        return $tree;
    }
}
