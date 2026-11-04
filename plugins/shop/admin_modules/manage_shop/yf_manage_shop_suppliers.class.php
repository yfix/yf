<?php

class yf_manage_shop_suppliers
{
    public function suppliers()
    {
        return table('SELECT * FROM ' . db('shop_suppliers'), [
                'custom_fields' => ['num_products' => 'SELECT supplier_id, COUNT(*) AS num FROM ' . db('shop_products') . ' GROUP BY supplier_id'],
                'filter' => ($_SESSION[$_GET['object'] . '__suppliers'] ?? null),
                'hide_empty' => 1,
            ])
            ->image('id', 'uploads/shop/suppliers/%d.jpg', ['width' => '50px'])
            ->text('name')
            ->text('url')
            ->text('num_products')
            ->text('meta_keywords')
            ->text('meta_desc')
            ->btn_edit('', './?object=' . main()->_get('object') . '&action=supplier_edit&id=%d', ['no_ajax' => 1])
            ->btn_delete('', './?object=' . main()->_get('object') . '&action=supplier_delete&id=%d')
            ->footer_add('', './?object=' . main()->_get('object') . '&action=supplier_add');
    }


    public function supplier_add()
    {
        $url = $supplier_info = null;
        if (main()->is_post()) {
            if ( ! ($_POST['name'] ?? null)) {
                _re('Product name must be filled');
            }
            if ( ! common()->_error_exists()) {
                $sql_array = [
                    'name' => ($_POST['name'] ?? null),
                    'url' => ($_POST['url'] ?? null) ?: common()->_propose_url_from_name(($_POST['name'] ?? null)),
                    'desc' => ($_POST['desc'] ?? null),
                    'sort_order' => (int) (($_POST['sort_order'] ?? null)),
                ];
                db()->insert(db('shop_suppliers'), db()->es($sql_array));
                module('manage_shop_revisions')->new_revision(__FUNCTION__, db()->insert_id(), 'shop_suppliers');
                common()->admin_wall_add(['shop supplier added: ' . ($_POST['name'] ?? null), db()->insert_id()]);
                if ( ! empty($_FILES)) {
                    $man_id = ($_GET['id'] ?? null);
                    module('manage_shop')->_upload_image($man_id, $url);
                }
            }
            return js_redirect('./?object=' . main()->_get('object') . '&action=suppliers');
        }

        $thumb_path = module('manage_shop')->supplier_img_dir . $supplier_info['url'] . '_' . $supplier_info['id'] . module('manage_shop')->THUMB_SUFFIX . '.jpg';
        if ( ! file_exists($thumb_path)) {
            $thumb_path = '';
        } else {
            $thumb_path = module('manage_shop')->supplier_img_webdir . $supplier_info['url'] . '_' . $supplier_info['id'] . module('manage_shop')->THUMB_SUFFIX . '.jpg';
        }
        $replace = [
            'name' => '',
            'sort_order' => '',
            'desc' => '',
            'thumb_path' => '',
            'delete_image_url' => './?object=' . main()->_get('object') . '&action=delete_image&id=' . $supplier_info['id'],
            'form_action' => './?object=' . main()->_get('object') . '&action=supplier_add',
            'back_url' => './?object=' . main()->_get('object') . '&action=suppliers',
        ];
        return form($replace)
            ->text('name')
            ->textarea('desc', 'Description')
            ->text('url')
            ->text('meta_keywords')
            ->text('meta_desc')
            ->integer('sort_order')
            ->save_and_back();
    }


    public function supplier_edit()
    {
        $url = null;
        $_GET['id'] = (int) (($_GET['id'] ?? null));
        if (empty($_GET['id'])) {
            return _e('Empty ID!');
        }
        $supplier_info = db()->query_fetch('SELECT * FROM ' . db('shop_suppliers') . ' WHERE id=' . ($_GET['id'] ?? null));
        if (main()->is_post()) {
            if ( ! ($_POST['name'] ?? null)) {
                _re('Product name must be filled');
            }
            if ( ! common()->_error_exists()) {
                $sql_array = [
                    'name' => ($_POST['name'] ?? null),
                    'url' => ($_POST['url'] ?? null) ?: common()->_propose_url_from_name(($_POST['name'] ?? null)),
                    'desc' => ($_POST['desc'] ?? null),
                    'meta_keywords' => ($_POST['meta_keywords'] ?? null),
                    'meta_desc' => ($_POST['meta_desc'] ?? null),
                    'sort_order' => (int) (($_POST['sort_order'] ?? null)),
                ];
                module('manage_shop_revisions')->check_revision(__FUNCTION__, ($_GET['id'] ?? null), 'shop_suppliers');
                db()->update('shop_suppliers', db()->es($sql_array), 'id=' . ($_GET['id'] ?? null));
                module('manage_shop_revisions')->new_revision(__FUNCTION__, ($_GET['id'] ?? null), 'shop_suppliers');
                common()->admin_wall_add(['shop supplier updated: ' . ($_POST['name'] ?? null), ($_GET['id'] ?? null)]);
                if ( ! empty($_FILES)) {
                    $man_id = ($_GET['id'] ?? null);
                    $this->_upload_image($man_id, $url);
                }
            }
            return js_redirect('./?object=' . main()->_get('object') . '&action=suppliers');
        }
        $thumb_path = module('manage_shop')->supplier_img_dir . $supplier_info['url'] . '_' . $supplier_info['id'] . module('manage_shop')->THUMB_SUFFIX . '.jpg';
        if ( ! file_exists($thumb_path)) {
            $thumb_path = '';
        } else {
            $thumb_path = module('manage_shop')->supplier_img_webdir . $supplier_info['url'] . '_' . $supplier_info['id'] . module('manage_shop')->THUMB_SUFFIX . '.jpg';
        }
        $replace = [
            'name' => $supplier_info['name'],
            'sort_order' => $supplier_info['sort_order'],
            'desc' => $supplier_info['desc'],
            'thumb_path' => $thumb_path,
            'delete_image_url' => './?object=' . main()->_get('object') . '&action=delete_image&id=' . $supplier_info['id'],
            'form_action' => './?object=' . main()->_get('object') . '&action=supplier_edit&id=' . $supplier_info['id'],
            'back_url' => './?object=' . main()->_get('object') . '&action=suppliers',
        ];
        return form($replace)
            ->text('name')
            ->textarea('desc', 'Description')
            ->text('url')
            ->text('meta_keywords')
            ->text('meta_desc')
//			->integer('sort_order')
            ->save_and_back();
    }


    public function supplier_delete()
    {
        $_GET['id'] = (int) (($_GET['id'] ?? null));
        if ( ! empty($_GET['id'])) {
            $info = db()->query_fetch('SELECT * FROM ' . db('shop_suppliers') . ' WHERE id=' . (int) (($_GET['id'] ?? null)));
        }
        if ( ! empty($info['id'])) {
            module('manage_shop_revisions')->check_revision(__FUNCTION__, ($_GET['id'] ?? null), 'shop_suppliers');
            db()->query('DELETE FROM ' . db('shop_suppliers') . ' WHERE id=' . (int) (($_GET['id'] ?? null)) . ' LIMIT 1');
            module('manage_shop_revisions')->new_revision(__FUNCTION__, ($_GET['id'] ?? null), 'shop_suppliers');
            common()->admin_wall_add(['shop supplier deleted: ' . $info['name'], ($_GET['id'] ?? null)]);
        }
        if (($_POST['ajax_mode'] ?? null)) {
            main()->NO_GRAPHICS = true;
            echo ($_GET['id'] ?? null);
        } else {
            return js_redirect('./?object=' . main()->_get('object') . '&action=suppliers');
        }
    }
}
