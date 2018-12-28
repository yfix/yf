<?php

class yf_manage_shop_filter
{
    public $_avail_filters = ['products', 'users', 'orders', 'suppliers', 'manufacturers', 'product_sets', 'attributes', 'feedback', 'product_revisions', 'order_revisions', 'category_revisions', 'product_images_revisions', 'region'];


    public function filter_save()
    {
        $_GET['id'] = preg_replace('~[^0-9a-z_]+~ims', '', $_GET['id']);
        if ($_GET['id'] && false !== strpos($_GET['id'], $_GET['object'] . '__')) {
            $filter_name = $_GET['id'];
            list(, $action) = explode('__', $filter_name);
        }
        if ( ! in_array($action, $this->_avail_filters)) {
            return js_redirect('./?object=' . $_GET['object']);
        }
        if ($_GET['page'] == 'clear') {
            $_SESSION[$filter_name] = [];
        } else {
            $_SESSION[$filter_name] = $_POST;
            foreach (explode('|', 'clear_url|form_id|submit') as $f) {
                if (isset($_SESSION[$filter_name][$f])) {
                    unset($_SESSION[$filter_name][$f]);
                }
            }
        }
        return js_redirect('./?object=' . $_GET['object'] . '&action=' . $action);
    }


    public function _show_filter()
    {
        if ( ! in_array($_GET['action'], $this->_avail_filters)) {
            return false;
        }
        $filter_name = $_GET['object'] . '__' . $_GET['action'];
        $replace = [
            'form_action' => './?object=' . $_GET['object'] . '&action=filter_save&id=' . $filter_name,
            'clear_url' => './?object=' . $_GET['object'] . '&action=filter_save&id=' . $filter_name . '&page=clear',
        ];
        $filters = [
            'products' => function ($filter_name, $replace) {
                $fields = ['id', 'name', 'cat_id', 'price', 'active', 'quantity', 'manufacturer_id', 'supplier_id', 'region_id', 'add_date', 'update_date'];
                foreach ((array) $fields as $v) {
                    $order_fields[$v] = $v;
                }
                $_region = _class('_shop_region', 'modules/shop/')->_get_list();
                return form($replace, ['selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'])
                    ->container(_class('manage_shop_filter')->_product_search_widget('id', $_SESSION[$filter_name]['id'], true), 'Id')
                    ->text('name')
                    ->text('articul')
                    ->row_start(['desc' => 'price'])
                        ->price('price', ['prepend' => ''])
                        ->price('price__and', ['prepend' => ''])
                    ->row_end()
                    ->datetime_select('add_date', null, ['with_time' => 1])
                    ->datetime_select('add_date__and', null, ['with_time' => 1])
                    ->chosen_box('cat_id', module('manage_shop')->_cats_for_select, ['desc' => 'Main category', 'show_text' => 1, 'no_translate' => 1])
                    ->chosen_box('supplier_id', _class('manage_shop')->_suppliers_for_select, ['desc' => 'Supplier', 'no_translate' => 1, 'hide_empty' => 1])
                    ->chosen_box('manufacturer_id', _class('manage_shop')->_man_for_select, ['desc' => 'Manufacturer', 'no_translate' => 1])
                    ->hidden(['name' => 'region_id', 'value' => 1])
                    ->select2_box([
                        'desc' => 'Регион',
                        'name' => 'region',
                        'multiple' => true,
                        'values' => $_region,
                        'type' => 1,
                    ])
                    ->active_box('active', ['horizontal' => 1])
                    ->select_box('status', _class('manage_shop')->_products_statuses, ['desc' => 'Status', 'no_translate' => 0, 'hide_empty' => 1, 'show_text' => 1])
                    ->yes_no_box('image', ['horizontal' => 1])
                    ->select_box('order_by', $order_fields, ['show_text' => 1, 'translate' => 1]);
            },
            'users' => function ($filter_name, $replace) {
                $fields = ['add_date', 'id', 'name', 'email', 'phone'];
                foreach ((array) $fields as $v) {
                    $order_fields[$v] = $v;
                }
                return form($replace, ['selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'])
                    ->number('id', ['class' => 'span1', 'min' => 0])
                    ->datetime_select('add_date', null, ['with_time' => 1])
                    ->datetime_select('add_date__and', null, ['with_time' => 1])
                    ->text('name')
                    ->text('email')
                    ->text('phone')
                    ->text('address')
                    ->select_box('order_by', $order_fields, ['show_text' => 1, 'translate' => 1]);
            },
            'orders' => function ($filter_name, $replace) {
                $fields = ['id', 'date', 'name', 'phone', 'email', 'total_sum', 'user_id', 'status'];
                foreach ((array) $fields as $v) {
                    $order_fields[$v] = $v;
                }
                return form($replace, ['selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'])
                    ->row_start(['desc' => 'id'])
                        ->number('id', ['min' => 0])
                        ->number('id__and', ['min' => 0])
                    ->row_end()
                    ->datetime_select('date', null, ['with_time' => 1])
                    ->datetime_select('date__and', null, ['with_time' => 1])
                    ->text('name')
                    ->text('phone')
                    ->text('email')
                    ->number('user_id')
                    ->row_start(['desc' => 'total_sum'])
                        ->price('total_sum', ['prepend' => ''])
                        ->price('total_sum__and', ['prepend' => ''])
                    ->row_end()
                    ->select_box('status', common()->get_static_conf('order_status'), ['show_text' => 1])
                    ->select_box('order_by', $order_fields, ['show_text' => 1, 'translate' => 1]);
            },
            'manufacturers' => function ($filter_name, $replace) {
                $fields = ['id', 'name', 'add_date'];
                foreach ((array) $fields as $v) {
                    $order_fields[$v] = $v;
                }
                return form($replace, ['selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'])
                    ->text('name')
                    ->select_box('order_by', $order_fields, ['show_text' => 1, 'translate' => 1]);
            },
            'suppliers' => function ($filter_name, $replace) {
                $fields = ['id', 'name', 'add_date'];
                foreach ((array) $fields as $v) {
                    $order_fields[$v] = $v;
                }
                return form($replace, ['selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'])
                    ->text('name')
                    ->select_box('order_by', $order_fields, ['show_text' => 1, 'translate' => 1]);
            },
            'product_sets' => function ($filter_name, $replace) {
                $fields = ['id', 'name', 'cat_id', 'add_date'];
                foreach ((array) $fields as $v) {
                    $order_fields[$v] = $v;
                }
                return form($replace, ['selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'])
                    ->text('name')
                    ->chosen_box('cat_id', module('manage_shop')->_cats_for_select, ['desc' => 'Main category', 'show_text' => 1, 'no_translate' => 1])
                    ->select_box('order_by', $order_fields, ['show_text' => 1, 'translate' => 1]);
            },
            'attributes' => function ($filter_name, $replace) {
                $fields = ['id', 'title', 'add_date'];
                foreach ((array) $fields as $v) {
                    $order_fields[$v] = $v;
                }
                return form($replace, ['selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'])
                    ->text('title')
                    ->select_box('order_by', $order_fields, ['show_text' => 1, 'translate' => 1]);
            },
            'feedback' => function ($filter_name, $replace) {
                $fields = ['id', 'product_id', 'name', 'email', 'content', 'pros', 'cons'];
                foreach ((array) $fields as $v) {
                    $order_fields[$v] = $v;
                }
                return form($replace, ['selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'])
                    ->number('id', ['class' => 'span1', 'min' => 0])
                    ->container(_class('manage_shop_filter')->_product_search_widget('product_id', $_SESSION[$filter_name]['product_id']), 'product_id')
                    ->datetime_select('add_date', null, ['with_time' => 1])
                    ->datetime_select('add_date__and', null, ['with_time' => 1])
                    ->text('name')
                    ->text('email')
                    ->text('content')
                    ->text('pros')
                    ->text('cons')
                    ->active_box('active', ['horizontal' => 1])
                    ->select_box('order_by', $order_fields, ['show_text' => 1, 'translate' => 1]);
            },
            'product_revisions' => function ($filter_name, $replace) {
                $fields = ['user_id', 'add_date', 'item_id', 'action', 'name'];
                foreach ((array) $fields as $v) {
                    $order_fields[$v] = $v;
                }
                return form($replace, ['selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'])
                    ->container(_class('manage_shop_filter')->_product_search_widget('item_id', $_SESSION[$filter_name]['item_id']), 'Item id')
                    ->text('name')
                    ->text('user_id', 'Admin')
                    ->datetime_select('add_date', null, ['with_time' => 1])
                    ->datetime_select('add_date__and', null, ['with_time' => 1])
                    ->chosen_box('cat_id', module('manage_shop')->_cats_for_select, ['desc' => 'Main category', 'show_text' => 1, 'no_translate' => 1])
                    ->select_box('action', common()->get_static_conf('product_revisions', false, false), ['show_text' => 1])
                    ->select_box('order_by', $order_fields, ['show_text' => 1, 'translate' => 1]);
            },
            'order_revisions' => function ($filter_name, $replace) {
                $fields = ['user_id', 'add_date', 'item_id', 'action'];
                foreach ((array) $fields as $v) {
                    $order_fields[$v] = $v;
                }
                return form($replace, ['selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'])
                    ->text('item_id', 'Order id')
                    ->text('user_id', 'Admin')
                    ->datetime_select('add_date', null, ['with_time' => 1])
                    ->datetime_select('add_date__and', null, ['with_time' => 1])
                    ->select_box('action', common()->get_static_conf('order_revisions', false, false), ['show_text' => 1])
                    ->select_box('order_by', $order_fields, ['show_text' => 1, 'translate' => 1]);
            },
            'product_images_revisions' => function ($filter_name, $replace) {
                $fields = ['user_id', 'add_date', 'product_id', 'action'];
                foreach ((array) $fields as $v) {
                    $order_fields[$v] = $v;
                }
                return form($replace, ['selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'])
                    ->container(_class('manage_shop_filter')->_product_search_widget('product_id', $_SESSION[$filter_name]['product_id']), 'Product id')
                    ->text('user_id', 'Admin')
                    ->datetime_select('add_date', null, ['with_time' => 1])
                    ->datetime_select('add_date__and', null, ['with_time' => 1])
                    ->select_box('action', common()->get_static_conf('images_revisions', false, false), ['show_text' => 1])
                    ->select_box('order_by', $order_fields, ['show_text' => 1, 'translate' => 1]);
            },
            'category_revisions' => function ($filter_name, $replace) {
                $fields = ['user_id', 'add_date', 'item_id', 'action'];
                $fields = array_combine(array_values($fields), array_values($fields));
                $action = ['first', 'edit', 'delete'];
                $action = array_combine(array_values($action), array_values($action));
                return form($replace, ['selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'])
                    ->text('item_id', 'item id')
                    ->text('user_id', 'Admin')
                    ->datetime_select('add_date', null, ['with_time' => 1])
                    ->datetime_select('add_date__and', null, ['with_time' => 1])
                    ->select_box('action', $action, ['show_text' => 1, 'translate' => 1])
                    ->select_box('order_by', $fields, ['show_text' => 1, 'translate' => 1]);
            },
            'region' => function ($filter_name, $replace) {
                return form($replace, ['selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'])
                    ->text('id', 'Номер')
                    ->text('value', 'Название')
                    ->active_box('active', ['horizontal' => 1]);
            },
        ];
        $action = $_GET['action'];
        if (isset($filters[$action])) {
            return $filters[$action]($filter_name, $replace)
                ->radio_box('order_direction', ['asc' => 'Ascending', 'desc' => 'Descending'], ['horizontal' => 1])
                ->save_and_clear();
        }
        return false;
    }

    public function _product_search_widget($input_name, $input_value, $multiple = false)
    {
        return tpl()->parse('manage_shop/product_search_filter', [
            'ajax_search_url' => ADMIN_WEB_PATH . '?object=manage_shop&action=product_search_autocomplete',
            'input_name' => $input_name,
            'input_value' => $input_value,
            'multiple' => $multiple ? 'true' : 'false',
        ]);
    }
}
