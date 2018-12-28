<?php

class yf_manage_shop__product_revisions
{
    public $temp_fields = [
        'product' => ['image', 'add_date', 'update_date', 'last_viewed_date', 'viewed', 'sold', 'status', 'origin_url', 'source', 'featured'],
        'orders' => [],
    ];

    public $all_queries = [
        'product' => [
            'product' => ['table' => 'shop_products',               'field' => 'id',         'multi' => false],
            'params' => ['table' => 'shop_products_productparams', 'field' => 'product_id', 'multi' => true],
            'product_to_category' => ['table' => 'shop_product_to_category',    'field' => 'product_id', 'multi' => true],
            'product_to_region' => ['table' => 'shop_product_to_region',      'field' => 'product_id', 'multi' => true],
            'product_related' => ['table' => 'shop_product_related',        'field' => 'product_id', 'multi' => true],
            'product_to_unit' => ['table' => 'shop_product_to_unit',        'field' => 'product_id', 'multi' => true],
        ],
        'order' => [
            'orders' => ['table' => 'shop_orders',      'field' => 'id',       'multi' => false],
            'order_items' => ['table' => 'shop_order_items', 'field' => 'order_id', 'multi' => true],
        ],
        'category' => [
            'sys_category_items' => ['table' => 'sys_category_items', 'field' => 'id', 'multi' => false],
        ],
    ];


    public function _product_check_first_revision($type = false, $ids = false)
    {
        $db = $this->get_revision_db($type);
        if ($type == 'product_images') {
            $sql = 'SELECT COUNT(*) as cnt FROM ' . $db . ' WHERE product_id=' . $ids;
            $first_revision = db()->get($sql);
            if ($first_revision['cnt'] == false) {
                $this->_product_images_add_revision('first', $ids, false);
            }
        } else {
            $ids = (array) $ids;
            foreach ($ids as $k => $v) {
                $first_revision = db()->get('SELECT COUNT(*) as cnt FROM ' . $db . ' WHERE item_id =' . $v);
                if ($first_revision['cnt'] == false) {
                    $this->_add_revision($type, 'first', $v);
                }
            }
        }
    }

    public function _product_add_revision($action = false, $ids = false)
    {
        return $this->_add_revision('product', $action, $ids);
    }

    public function _order_add_revision($action = false, $ids = false)
    {
        return $this->_add_revision('order', $action, $ids);
    }

    // new version
    public function _add_revision_group($type, $action = 'edit', $ids = false)
    {
        return $this->_add_revision($type, $action, $ids);
    }

    // old version (only for batch rename products)
    public function _add_group_revision($action = false, $ids = false, $group_id = false)
    {
        $revision_db = $this->get_revision_db($action);
        $admin_id = main()->ADMIN_ID ?: (int) ($_GET['admin_id']);
        if ( ! empty($ids)) {
            $data = json_encode($ids);
        }
        $insert = [
            'user_id' => $admin_id,
            'add_date' => time(),
            'action' => 'group',
            'item_id' => (int) $group_id,
            'data' => $data ?: '',
        ];
        db()->insert_safe($revision_db, $insert);
    }

    public function get_db($type)
    {
        $result = current($this->all_queries[$type]);
        return db($result['table']);
    }

    public function get_revision_db($type)
    {
        return db('shop_' . $type . '_revisions');
    }

    /*
     * $ids can be single item id or array of items' ids
     * when action equal 'delete' the data will be empty
     */
    public function _add_revision($type, $action, $ids = false)
    {
        if (empty($ids) || empty($action) || empty($type)) {
            return false;
        }
        if ( ! is_array($ids) && (int) $ids) {
            $ids = [(int) $ids];
        }

        $ids_with_comma = implode(',', $ids);

        //check SQL confs for getting data
        if ( ! isset($this->all_queries[$type])) {
            return false;
        }

        $revision_db = $this->get_revision_db($type);
        $all_data = [];

        if ($action != 'delete') {
            $revision_sql = 'SELECT item_id, data FROM (SELECT item_id, data FROM ' . $revision_db . ' WHERE item_id IN (' . $ids_with_comma . ') ORDER BY id DESC) as r GROUP BY item_id';
            $all_last_revision = db()->get_2d($revision_sql);
            foreach ($this->all_queries[$type] as $key => $info) {
                $sql_res = db()->query('SELECT * FROM ' . db($info['table']) . ' WHERE ' . $info['field'] . ' IN (' . $ids_with_comma . ');');
                foreach ($ids as $k => $id) {
                    $all_data[$id][$key] = [];
                }
                while ($row = db()->fetch_assoc($sql_res)) {
                    $complex_key = $row[$info['field']];
                    if ($info['multi']) {
                        $all_data[$complex_key][$key][] = $row;
                    } else {
                        $all_data[$complex_key][$key] = $row;
                    }
                }
            }
        }
        $add_rev_date = time();
        $insert_array = [];
        foreach ($ids as $id) {
            if ($action == 'delete') {
                $insert_array[] = [
                    'user_id' => (int) (main()->ADMIN_ID) ?: (int) ($_GET['admin_id']),
                    'add_date' => $add_rev_date,
                    'action' => $action,
                    'item_id' => $id,
                    'data' => '',
                ];
                continue;
            }
            if ( ! isset($all_data[$id])) {
                continue;
            }
            $new_revision = $all_data[$id];
            if (is_array($new_revision)) {
                foreach ((array) $this->temp_fields[$type] as $k => $v) {
                    _class('utils')->recursive_unset($new_revision, $v);
                }
            }
            $new_revision = json_encode($new_revision);
            if (isset($all_last_revision[$id])) {
                $old_revision = $all_last_revision[$id];
                if ($old_revision == $new_revision) {
                    continue;
                }
            }

            $insert_array[] = [
                'user_id' => (int) (main()->ADMIN_ID) ?: (int) ($_GET['admin_id']),
                'add_date' => $add_rev_date,
                'action' => $action,
                'item_id' => $id,
                'data' => $new_revision ?: '',
            ];
        }
        if ( ! empty($insert_array)) {
            //			$insert_array = array_chunk($insert_array, 100);
            foreach ($insert_array as $insert_item) {
                db()->insert_safe($revision_db, $insert_item);
                $revision_ids[] = db()->insert_id();
            }
        }
        return $revision_ids;
    }

    public function category_revisions()
    {
        $type = 'category';
        $object = 'category_editor';
        $action = 'edit_item';
        $db_revision = $this->get_revision_db($type);
        return table('SELECT * FROM ' . $db_revision, [
                'filter' => $_SESSION[$_GET['object'] . '__' . $_GET['action']],
                'filter_params' => [
                    'action' => ['eq', 'action'],
                    'user_id' => ['eq', 'user_id'],
                    'add_date' => ['dt_between', 'add_date'],
                    'item_id' => ['eq', 'item_id'],
                ],
                'hide_empty' => 1,
            ])
            ->date('add_date', ['format' => 'full', 'nowrap' => 1])
            ->link('item_id', ['desc' => 'Номер', 'link' => './?object=' . $object . '&action=' . $action . '&id=%d'])
            ->admin('user_id', ['desc' => 'admin'])
            ->text('action')
            ->btn_view('', './?object=manage_shop&action=' . $type . '_revisions_view&id=%d');
    }

    public function category_revisions_view()
    {
        $type = 'category';
        $db_revision = $this->get_revision_db($type);
        $id = (int) $_GET['id'];
        $sql = 'SELECT * FROM ' . $db_revision . ' WHERE id=' . $id;
        $a = db()->get($sql);
        $info = [];
        $db = $this->get_db($type);
        $info = db()->get('SELECT * FROM ' . $db . ' WHERE id=' . $a['item_id']);
        if (empty($info)) {
            // return _e('No such revision');
            $info['item_id'] = $a['item_id'];
            $info['name'] = 'удаленный элемент';
        }
        return form($a, [
            'dd_mode' => 1,
        ])
        ->link('item_id', './?object=category_editor&action=edit_item&id=' . $a['item_id'], [
            'desc' => 'Edit',
            'data' => [$a['item_id'] => $info['name'] . ' [id=' . $a['item_id'] . ']'],
        ])
        ->admin_info('user_id')
        ->info_date('add_date', ['format' => 'full'])
        ->info('action')
        ->link('Activate new version', './?object=manage_shop&action=' . $type . '_revision_checkout&id=' . $a['id'])
        ->tab_start('View_difference')
            ->func('data', function ($extra, $r, $_this) use ($db_revision) {
                $origin = json_decode($r[$extra['name']], true);
                $before = db()->get('SELECT * FROM ' . $db_revision . ' WHERE id<' . $r['id'] . ' AND item_id=' . $r['item_id'] . ' ORDER BY id DESC');
                $before = json_decode($before[$extra['name']], true);
                $origin = var_export($origin, true);
                $before = var_export($before, true);
                return common()->get_diff($before, $origin);
            })
        ->tab_end()
        ->tab_start('New_version')
            ->func('data', function ($extra, $r, $_this) {
                return '<pre>' . var_export(json_decode($r[$extra['name']], true), 1) . '</pre>';
            })
        ->tab_end();
    }

    public function category_revision_checkout()
    {
        $type = 'category';
        $object = 'category_editor';
        $action = 'edit_item';
        $db_revision = $this->get_revision_db($type);
        $id = (int) $_GET['id'];
        $revision_data = db()->get('SELECT * FROM ' . $db_revision . ' WHERE id=' . $id);
        if (empty($revision_data)) {
            return _e('Revision not found');
        }
        db()->begin();
        $item_id = $revision_data['item_id'];
        if ($revision_data['action'] == 'delete') {
            foreach ($this->all_queries[$type] as $name => $item) {
                $table = $item['table'];
                $field = $item['field'];
                db()->query('DELETE FROM ' . db($table) . ' WHERE ' . $field . '=' . $item_id);
            }
            $object = 'manage_shop';
            $action = 'category_revisions';
            $url = './?object=' . $object . '&action=' . $action;
        } else {
            $data_stamp = json_decode($revision_data['data'], true);
            $db = $this->get_db($type);
            foreach ($data_stamp as $name => $array) {
                $table = $this->all_queries[$type][$name]['table'];
                $field = $this->all_queries[$type][$name]['field'];
                $multi = $this->all_queries[$type][$name]['multi'];
                if ( ! $multi) {
                    db()->replace_safe($table, $array);
                } else {
                    db()->query('DELETE FROM ' . db($table) . ' WHERE ' . $field . '=' . $item_id);
                    if ( ! empty($array)) {
                        db()->replace_safe($table, $array);
                    }
                }
            }
            $url = './?object=' . $object . '&action=' . $action . '&id=' . $item_id;
        }
        db()->commit();

        common()->message_success('Revision retrieved');
        common()->admin_wall_add(['checkout revision ' . $type . ': ' . $id . ', item: ' . $item_id, $id]);
        return js_redirect($url);
    }

    /**
     * @param mixed $action
     * @param mixed $product_id
     * @param mixed $image_id
     */
    public function _product_images_add_revision($action, $product_id, $image_id)
    {
        $images_ids = db()->get_2d('SELECT id, is_default FROM ' . db('shop_product_images') . ' WHERE product_id = ' . $product_id . ' AND active=1');
        db()->insert_safe(db('shop_product_images_revisions'), [
            'user_id' => (int) (main()->ADMIN_ID),
            'add_date' => $_SERVER['REQUEST_TIME'],
            'action' => $action,
            'product_id' => $product_id,
            'image_id' => $image_id,
            'data' => $images_ids ? json_encode($images_ids) : '[]',
        ]);
    }


    public function product_revisions()
    {
        $filter_params = [
            'name' => ['like', 'p.name'],
            'action' => ['eq', 'r.action'],
            'user_id' => ['eq', 'r.user_id'],
            'add_date' => ['dt_between', 'r.add_date'],
            'item_id' => ['eq', 'r.item_id'],
        ];
        $filter_params['cat_id'] = function ($a) {
            $top_cat_id = (int) $a['value'];
            if ($top_cat_id) {
                $cat_ids = (array) _class('cats')->_recursive_get_children_ids($top_cat_id, 'shop_cats', $sub_children = 1, $as_array = 1);
            }
            $cat_ids[$top_cat_id] = $top_cat_id;
            return $cat_ids ? 'p.cat_id IN(' . implode(',', $cat_ids) . ')' : '';
        };

        return table(
            'SELECT r.*, p.name, p.cat_id
						FROM ' . db('shop_product_revisions') . ' as r
						INNER JOIN ' . db('shop_products') . ' as p ON p.id=r.item_id',
            [
                'filter' => $_SESSION[$_GET['object'] . '__product_revisions'],
                'filter_params' => $filter_params,
                'hide_empty' => 1,
            ]
        )
            ->date('add_date', ['format' => 'full', 'nowrap' => 1])
            ->link('item_id', './?object=' . $_GET['object'] . '&action=product_edit&id=%d')
            ->text('name')
            ->link('cat_id', './?object=category_editor&action=edit_item&id=%d', _class('cats')->_get_items_names_cached('shop_cats'))
            ->admin('user_id', ['desc' => 'admin'])
            ->text('action')
            ->btn_view('', './?object=manage_shop&action=product_revisions_view&id=%d');
    }


    public function product_revisions_view()
    {
        $sql = 'SELECT * FROM ' . db('shop_product_revisions') . ' WHERE id=' . (int) ($_GET['id']);
        $a = db()->get($sql);
        $product_info = module('manage_shop')->_product_get_info($a['item_id']);
        if (empty($product_info['id'])) {
            return _e('Product not found');
        }
        return form($a, [
            'dd_mode' => 1,
        ])
        ->link('item_id', './?object=' . $_GET['object'] . '&action=product_edit&id=' . $product_info['id'], [
            'desc' => 'Product',
            'data' => [$a['item_id'] => $product_info['name'] . ' [id=' . $a['item_id'] . ']'],
        ])
        ->admin_info('user_id')
        ->info_date('add_date', ['format' => 'full'])
        ->info('action')
        ->link('Activate new version', './?object=manage_shop&action=checkout_product_revision&id=' . $a['id'])
        ->tab_start('View_difference')
            ->func('data', function ($extra, $r, $_this) {
                $origin = json_decode($r[$extra['name']], true);
                $before = db()->get('SELECT * FROM ' . db('shop_product_revisions') . ' WHERE id<' . $r['id'] . ' AND item_id=' . $r['item_id'] . ' ORDER BY id DESC');
                $before = json_decode($before[$extra['name']], true);
                $origin = var_export($origin, true);
                $before = var_export($before, true);
                return common()->get_diff($before, $origin);
                /*
                                $compare = function($a, $b){
                                    foreach($a as $name => $data){
                                        foreach($data as $k => $v){
                                        if($v != $b[$name][$k] || !isset($b[$name][$k])){
                                            $out[$name][$k] = $v;
                                        }
                                    }
                                    return $out;
                                };
                                $out = $compare($before, $origin);
                                return '<pre>'.print_r($out, true).'</pre>';
                */
            })
        ->tab_end()
        ->tab_start('New_version')
            ->func('data', function ($extra, $r, $_this) {
                return '<pre>' . var_export(json_decode($r[$extra['name']], true), 1) . '</pre>';
            })
        ->tab_end();
    }


    public function product_images_revisions()
    {
        return table('SELECT * FROM ' . db('shop_product_images_revisions'), [
                'filter' => $_SESSION[$_GET['object'] . '__product_images_revisions'],
                'filter_params' => [
                    'action' => ['eq', 'action'],
                    'user_id' => ['eq', 'user_id'],
                    'add_date' => ['dt_between', 'add_date'],
                    'product_id' => ['eq', 'product_id'],
                ],
                'hide_empty' => 1,
            ])
            ->date('add_date', ['format' => 'full', 'nowrap' => 1])
            ->link('product_id', './?object=' . $_GET['object'] . '&action=product_edit&id=%d')
            ->admin('user_id', ['desc' => 'admin'])
            ->image('image_id', 'Image', ['width' => '70px', 'img_path_callback' => function ($_p1, $_p2, $row) {
                $dirs = sprintf('%06s', $row['product_id']);
                $dir2 = substr($dirs, -3, 3);
                $dir1 = substr($dirs, -6, 3);
                $m_path = $dir1 . '/' . $dir2 . '/';
                $image = SITE_IMAGES_DIR . $m_path . 'product_' . $row['product_id'] . '_' . $row['image_id'] . '.jpg';
                return $image;
            }])
            ->text('action')
            ->btn_view('', './?object=manage_shop&action=product_images_revisions_view&id=%d');
    }


    public function product_images_revisions_view()
    {
        $sql = 'SELECT r.*, p.name
				FROM ' . db('shop_product_images_revisions') . ' as r
				RIGHT JOIN ' . db('shop_products') . ' as p
				ON r.product_id = p.id
				WHERE r.id=' . (int) ($_GET['id']);
        $a = db()->get($sql);
        if (empty($a)) {
            return _e('Revision not found');
        }
        $data_stamp = json_decode($a['data'], true);
        foreach ($data_stamp as $image_id => $v) {
            $image_url = common()->shop_generate_image_name($a['product_id'], $image_id, true);
            $image_html = tpl()->parse($_GET['object'] . '/image_revision_item', $image_url);
            if ($v) {
                $main_image = $image_html;
            }
            $images_stamp .= $image_html;
        }
        if ($a['image_id']) {
            $changed_image = common()->shop_generate_image_name($a['product_id'], $a['image_id'], true);
            $changed_image = tpl()->parse($_GET['object'] . '/image_revision_item', $changed_image);
        }
        return form($a, [
            'dd_mode' => 1,
        ])
        ->link('product_id', './?object=' . $_GET['object'] . '&action=product_edit&id=' . $a['product_id'], [
            'desc' => t('Product'),
            'data' => [$a['product_id'] => $a['name'] . ' [id=' . $a['product_id'] . ']'],
        ])
        ->admin_info('user_id', 'Editor')
        ->info_date('add_date', ['format' => 'full'])
        ->info('action')
//		->container($changed_image)
        ->container($images_stamp, 'Product foto')
        ->container($main_image, 'Main image')
        ->link('Retrieve current stamp', './?object=manage_shop&action=checkout_images_revision&id=' . $a['id']);
    }


    public function checkout_images_revision()
    {
        $_GET['id'] = (int) ($_GET['id']);
        $revision_data = db()->get('SELECT * FROM ' . db('shop_product_images_revisions') . ' WHERE id=' . $_GET['id']);
        if (empty($revision_data)) {
            return _e('Revision not found');
        }
        $product_id = $revision_data['product_id'];
        $data_stamp = json_decode($revision_data['data'], true);
        db()->begin();
        $images = db()->get_all('SELECT id FROM ' . db('shop_product_images') . ' WHERE product_id=' . $product_id);
        foreach ($images as $id => $data) {
            $reset[] = [
                'id' => $id,
                'is_default' => 0,
                'active' => 0,
            ];
        }
        db()->update_batch('shop_product_images', db()->es($reset));
        if (empty($data_stamp)) {
            db()->query('UPDATE ' . db('shop_products') . ' SET image=0 WHERE id=' . $product_id);
        } else {
            foreach ($data_stamp as $id => $default_val) {
                $set[] = [
                    'id' => $id,
                    'is_default' => $default_val,
                    'active' => 1,
                ];
            }
            db()->update_batch('shop_product_images', db()->es($set));
            db()->query('UPDATE ' . db('shop_products') . ' SET image=1 WHERE id=' . $product_id);
        }
        module('manage_shop')->_product_images_add_revision('rollback', $product_id, false);
        db()->commit();
        module('manage_shop')->_product_cache_purge($product_id);
        common()->message_success('Revision retrieved');
        common()->admin_wall_add(['shop product_image checkout revision: ' . $_GET['id'], $product_id]);
        return js_redirect('./?object=manage_shop&action=product_edit&id=' . $product_id);
    }


    public function checkout_product_revision()
    {
        $_GET['id'] = (int) ($_GET['id']);
        $revision_data = db()->get('SELECT * FROM ' . db('shop_product_revisions') . ' WHERE id=' . $_GET['id']);
        if (empty($revision_data)) {
            return _e('Revision not found');
        }
        $product_id = $revision_data['item_id'];
        $data_stamp = json_decode($revision_data['data'], true);

        db()->begin();
        foreach ($data_stamp as $type => $array) {
            $table = $this->all_queries['product'][$type]['table'];
            $field = $this->all_queries['product'][$type]['field'];
            $multi = $this->all_queries['product'][$type]['multi'];
            if ( ! $multi) {
                db()->update_safe($table, $array, $field . '=' . $array['id']);
            } else {
                db()->query('DELETE FROM ' . db($table) . ' WHERE ' . $field . '=' . $product_id);
                if ( ! empty($array)) {
                    db()->insert_safe($table, $array);
                }
            }
        }
        module('manage_shop')->_product_add_revision('rollback', $product_id);
        db()->commit();

        module('manage_shop')->_product_cache_purge($product_id);
        common()->message_success('Revision retrieved');
        common()->admin_wall_add(['shop product checkout revision: ' . $_GET['id'], $product_id]);
        return js_redirect('./?object=manage_shop&action=product_edit&id=' . $product_id);
    }


    public function order_revisions()
    {
        return table('SELECT * FROM ' . db('shop_order_revisions'), [
                'filter' => $_SESSION[$_GET['object'] . '__order_revisions'],
                'filter_params' => [
                    'action' => ['eq', 'action'],
                    'user_id' => ['eq', 'user_id'],
                    'add_date' => ['dt_between', 'add_date'],
                    'item_id' => ['eq', 'item_id'],
                ],
                'hide_empty' => 1,
            ])
            ->date('add_date', ['format' => 'full', 'nowrap' => 1])
            ->link('item_id', './?object=' . $_GET['object'] . '&action=view_order&id=%d')
            ->admin('user_id', ['desc' => 'admin'])
            ->text('action')
            ->btn_view('', './?object=manage_shop&action=order_revisions_view&id=%d');
    }


    public function order_revisions_view()
    {
        $sql = 'SELECT * FROM ' . db('shop_order_revisions') . ' WHERE id=' . (int) ($_GET['id']);
        $a = db()->get($sql);
        $order_info = db()->get('SELECT * FROM ' . db('shop_orders') . ' WHERE id=' . $a['item_id']);
        if (empty($order_info)) {
            return _e('No such order');
        }
        return form($a, [
            'dd_mode' => 1,
        ])
        ->link('item_id', './?object=' . $_GET['object'] . '&action=view_order&id=' . $order_info['id'], [
            'desc' => 'Order',
            'data' => [$a['item_id'] => $order_info['name'] . ' [id=' . $a['item_id'] . ']'],
        ])
        ->admin_info('user_id')
        ->info_date('add_date', ['format' => 'full'])
        ->info('action')
        ->link('Activate new version', './?object=manage_shop&action=checkout_order_revision&id=' . $a['id'])
        ->tab_start('View_difference')
            ->func('data', function ($extra, $r, $_this) {
                $origin = json_decode($r[$extra['name']], true);
                $before = db()->get('SELECT * FROM ' . db('shop_order_revisions') . ' WHERE id<' . $r['id'] . ' AND item_id=' . $r['item_id'] . ' ORDER BY id DESC');
                $before = json_decode($before[$extra['name']], true);
                $origin = var_export($origin, true);
                $before = var_export($before, true);
                return common()->get_diff($before, $origin);
            })
        ->tab_end()
        ->tab_start('New_version')
            ->func('data', function ($extra, $r, $_this) {
                return '<pre>' . var_export(json_decode($r[$extra['name']], true), 1) . '</pre>';
            })
        ->tab_end();
    }


    public function checkout_order_revision()
    {
        $_GET['id'] = (int) ($_GET['id']);
        $revision_data = db()->get('SELECT * FROM ' . db('shop_order_revisions') . ' WHERE id=' . $_GET['id']);
        if (empty($revision_data)) {
            return _e('Revision not found');
        }
        $order_id = $revision_data['item_id'];
        $data_stamp = json_decode($revision_data['data'], true);

        db()->begin();
        foreach ($data_stamp as $type => $array) {
            $table = $this->all_queries['order'][$type]['table'];
            $field = $this->all_queries['order'][$type]['field'];
            $multi = $this->all_queries['order'][$type]['multi'];
            if ( ! $multi) {
                db()->update_safe($table, $array, $field . '=' . $array['id']);
            } else {
                db()->query('DELETE FROM ' . db($table) . ' WHERE ' . $field . '=' . $order_id);
                if ( ! empty($array)) {
                    db()->insert_safe($table, $array);
                }
            }
        }
        module('manage_shop')->_order_add_revision('rollback', $order_id);
        db()->commit();

        common()->message_success('Revision retrieved');
        common()->admin_wall_add(['shop order checkout revision: ' . $_GET['id'], $order_id]);
        return js_redirect('./?object=manage_shop&action=view_order&id=' . $order_id);
    }

    public function checkout_group_revision()
    {
        $_GET['id'] = (int) ($_GET['id']);
        $db = db('shop_product_revisions');
        $ids = db()->get_one('SELECT data FROM ' . $db . ' WHERE item_id=' . $_GET['id'] . ' AND data IS NOT NULL ORDER BY id DESC');
        if (empty($ids)) {
            return _e('Revision not found');
        }
        $revisions_ids = json_decode($ids, true);
        $products = db()->get_2d('SELECT id, item_id FROM ' . $db . ' WHERE id IN (' . implode(',', $revisions_ids) . ') ORDER BY id DESC');
        foreach ($products as $id => $item_id) {
            $Q[] = '(SELECT * FROM ' . $db . ' WHERE item_id =' . $item_id . ' AND id<' . $id . ' ORDER BY id DESC LIMIT 1)';
        }
        $Q = implode(' UNION ALL ', $Q);
        $revisions = db()->query_fetch_all($Q);

        db()->begin();
        foreach ($revisions as $data) {
            $product_id = $data['item_id'];
            $data_stamp = json_decode($data['data'], true);
            foreach ($data_stamp as $array) {
                db()->update_safe('shop_products', ['name' => $array['name']], 'id=' . $array['id']);
            }
            module('manage_shop')->_product_cache_purge($product_id);
        }
        $group_ids = module('manage_shop')->_product_add_revision('rollback', $products);
        if ($group_ids) {
            module('manage_shop')->_add_group_revision('product', $group_ids, $_GET['id']);
        }
        db()->commit();

        common()->message_success('Group revision retrieved');
        common()->admin_wall_add(['checkout group revision: ' . $_GET['id'], $_GET['id']]);
        return js_redirect('./?object=manage_shop&action=clear_patterns');
    }
}
