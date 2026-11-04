<?php

class yf_manage_shop_manufacturers
{
    public function _purge_caches()
    {
        _class('_shop_manufacturers', 'modules/shop/')->_refresh_cache();
    }


    public function manufacturers()
    {
        return table('SELECT * FROM ' . db('shop_manufacturers'), [
                'custom_fields' => ['num_products' => 'SELECT manufacturer_id, COUNT(*) AS num FROM ' . db('shop_products') . ' GROUP BY manufacturer_id'],
                'filter' => ($_SESSION[$_GET['object'] . '__manufacturers'] ?? null),
                'hide_empty' => 1,
            ])
            ->image('id', 'uploads/shop/manufacturers/%d.jpg', ['width' => '50px'])
            ->text('name')
            ->text('url')
            ->text('num_products')
            ->text('meta_keywords')
            ->text('meta_desc')
            ->btn_edit('', './?object=' . main()->_get('object') . '&action=manufacturer_edit&id=%d', ['no_ajax' => 1])
            ->btn_delete('', './?object=' . main()->_get('object') . '&action=manufacturer_delete&id=%d')
            ->footer_add('', './?object=' . main()->_get('object') . '&action=manufacturer_add');
    }


    public function manufacturer_add()
    {
        $url = $manufacturer_info = null;
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
                db()->insert(db('shop_manufacturers'), db()->es($sql_array));
                common()->admin_wall_add(['shop manufacturer added: ' . ($_POST['name'] ?? null), db()->insert_id()]);
                if ( ! empty($_FILES)) {
                    $man_id = ($_GET['id'] ?? null);
                    module('manage_shop')->_upload_image($man_id, $url);
                }
                $this->_purge_caches();
            }
            return js_redirect('./?object=' . main()->_get('object') . '&action=manufacturers');
        }

        $thumb_path = module('manage_shop')->manufacturer_img_dir . $manufacturer_info['url'] . '_' . $manufacturer_info['id'] . module('manage_shop')->THUMB_SUFFIX . '.jpg';
        if ( ! file_exists($thumb_path)) {
            $thumb_path = '';
        } else {
            $thumb_path = module('manage_shop')->manufacturer_img_webdir . $manufacturer_info['url'] . '_' . $manufacturer_info['id'] . module('manage_shop')->THUMB_SUFFIX . '.jpg';
        }
        $replace = [
            'name' => '',
            'sort_order' => '',
            'desc' => '',
            'url' => '',
            'thumb_path' => '',
            'delete_image_url' => './?object=' . main()->_get('object') . '&action=delete_image&id=' . $manufacturer_info['id'],
            'form_action' => './?object=' . main()->_get('object') . '&action=manufacturer_add',
            'back_url' => './?object=' . main()->_get('object') . '&action=manufacturers',
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


    public function manufacturer_edit()
    {
        $url = null;
        $_GET['id'] = (int) (($_GET['id'] ?? null));
        if (empty($_GET['id'])) {
            return _e('Empty ID!');
        }
        $manufacturer_info = db()->query_fetch('SELECT * FROM ' . db('shop_manufacturers') . ' WHERE id=' . ($_GET['id'] ?? null));
        if (main()->is_post()) {
            if ( ! ($_POST['name'] ?? null)) {
                _re('Product name must be filled');
            }
            if ( ! common()->_error_exists()) {
                $sql_array = [
                    'name' => ($_POST['name'] ?? null),
                    'url' => ($_POST['url'] ?? null),
                    'desc' => ($_POST['desc'] ?? null),
                    'meta_keywords' => ($_POST['meta_keywords'] ?? null),
                    'meta_desc' => ($_POST['meta_desc'] ?? null),
                    'sort_order' => (int) (($_POST['sort_order'] ?? null)),
                ];
                module('manage_shop_revisions')->check_revision(__FUNCTION__, ($_GET['id'] ?? null), 'shop_manufacturers');
                db()->update('shop_manufacturers', db()->es($sql_array), 'id=' . ($_GET['id'] ?? null));
                module('manage_shop_revisions')->new_revision(__FUNCTION__, ($_GET['id'] ?? null), 'shop_manufacturers');
                common()->admin_wall_add(['shop manufacturer updated: ' . ($_POST['name'] ?? null), ($_GET['id'] ?? null)]);
                if ( ! empty($_FILES)) {
                    $man_id = ($_GET['id'] ?? null);
                    $this->_upload_image($man_id, $url);
                }
                $this->_purge_caches();
            }
            return js_redirect('./?object=' . main()->_get('object') . '&action=manufacturers');
        }
        $thumb_path = module('manage_shop')->manufacturer_img_dir . $manufacturer_info['url'] . '_' . $manufacturer_info['id'] . module('manage_shop')->THUMB_SUFFIX . '.jpg';
        if ( ! file_exists($thumb_path)) {
            $thumb_path = '';
        } else {
            $thumb_path = module('manage_shop')->manufacturer_img_webdir . $manufacturer_info['url'] . '_' . $manufacturer_info['id'] . module('manage_shop')->THUMB_SUFFIX . '.jpg';
        }
        $replace = [
            'name' => $manufacturer_info['name'],
            'desc' => $manufacturer_info['desc'],
            'url' => $manufacturer_info['url'],
            'meta_keywords' => $manufacturer_info['meta_keywords'],
            'meta_desc' => $manufacturer_info['meta_desc'],
            'sort_order' => $manufacturer_info['sort_order'],
            'thumb_path' => $thumb_path,
            'delete_image_url' => './?object=' . main()->_get('object') . '&action=delete_image&id=' . $manufacturer_info['id'],
            'form_action' => './?object=' . main()->_get('object') . '&action=manufacturer_edit&id=' . $manufacturer_info['id'],
            'back_url' => './?object=' . main()->_get('object') . '&action=manufacturers',
        ];
        return form($replace)
            ->text('name')
            ->textarea('desc', 'Description')
            ->text('url')
            ->text('meta_keywords')
            ->text('meta_desc')
    //		->integer('sort_order')
            ->save_and_back();
    }


    public function manufacturer_delete()
    {
        $_GET['id'] = (int) (($_GET['id'] ?? null));
        if ( ! empty($_GET['id'])) {
            $info = db()->query_fetch('SELECT * FROM ' . db('shop_manufacturers') . ' WHERE id=' . (int) (($_GET['id'] ?? null)));
        }
        if ( ! empty($info['id'])) {
            module('manage_shop_revisions')->check_revision(__FUNCTION__, $info['id'], 'shop_manufacturers');
            db()->query('DELETE FROM ' . db('shop_manufacturers') . ' WHERE id=' . (int) (($_GET['id'] ?? null)) . ' LIMIT 1');
            module('manage_shop_revisions')->new_revision(__FUNCTION__, $info['id'], 'shop_manufacturers');
            common()->admin_wall_add(['shop manufacturer deleted: ' . ($_GET['id'] ?? null), ($_GET['id'] ?? null)]);
            $this->_purge_caches();
        }
        if (($_POST['ajax_mode'] ?? null)) {
            main()->NO_GRAPHICS = true;
            echo ($_GET['id'] ?? null);
        } else {
            return js_redirect('./?object=' . main()->_get('object') . '&action=manufacturers');
        }
    }


    public function upload_image()
    {
        $_GET['id'] = (int) (($_GET['id'] ?? null));
        if (empty($_GET['id'])) {
            return _e('Empty ID!');
        }
        $this->_upload_image(($_GET['id'] ?? null));
        return js_redirect(($_SERVER['HTTP_REFERER'] ?? null));
    }

    /**
     * @param mixed $man_id
     * @param mixed $url
     */
    public function _upload_image($man_id, $url)
    {
        $img_properties = getimagesize($_FILES['image']['tmp_name']);
        if (empty($img_properties) || ! $man_id) {
            return false;
        }
        $img_path = module('manage_shop')->manufacturer_img_dir . $url . '_' . $man_id . module('manage_shop')->FULL_IMG_SUFFIX . '.jpg';
        $thumb_path = module('manage_shop')->manufacturer_img_dir . $url . '_' . $man_id . module('manage_shop')->THUMB_SUFFIX . '.jpg';
        $upload_result = common()->upload_image($img_path);
        if ($upload_result) {
            $resize_result = common()->make_thumb($img_path, $thumb_path, module('manage_shop')->THUMB_X, module('manage_shop')->THUMB_Y);
        }
        return true;
    }
}
