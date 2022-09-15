<?php

class yf_manage_shop_hook_side_column
{
    public function _hook_side_column()
    {
        if ($_GET['action'] == 'product_edit') {
            return $this->_product_revisions() . $this->_product_images_revisions();
        } elseif ($_GET['action'] == 'view_order') {
            return $this->_order_revisions();
        } elseif ($_GET['action'] == 'product_revisions_view') {
            return $this->_product_revisions_similar();
        } elseif ($_GET['action'] == 'product_images_revisions_view') {
            return $this->_product_images_revisions_similar();
        } elseif ($_GET['action'] == 'order_revisions_view') {
            return $this->_order_revisions_similar();
        }
        return '';
    }


    public function _product_revisions()
    {
        $product_id = (int) ($_GET['id']);
        $product_info = module('manage_shop')->_product_get_info($product_id);
        if ( ! $product_info) {
            return false;
        }
        $sql = 'SELECT id, user_id, add_date, action, item_id FROM ' . db('shop_product_revisions') . ' WHERE item_id=' . (int) $product_id . ' AND action != \'\' ORDER BY id DESC';
        return table($sql, [
                'caption' => t('Product revisions'),
                'no_records_html' => '',
                'pager_records_on_page' => 5,
                'no_pages' => true,
            ])
            ->date('add_date', ['format' => '%d/%m/%Y', 'nowrap' => 1])
            ->admin('user_id', ['desc' => 'admin'])
            ->text('action')
            ->btn_view('', './?object=manage_shop&action=product_revisions_view&id=%d', ['data-test' => 'view_revision_btn'])
            ->footer_link('All products revisions', './?object=manage_shop&action=product_revisions');
    }


    public function _product_images_revisions()
    {
        $product_id = (int) ($_GET['id']);
        $product_info = module('manage_shop')->_product_get_info($product_id);
        if ( ! $product_info) {
            return false;
        }
        $sql = 'SELECT id, user_id, add_date, action, product_id, image_id FROM ' . db('shop_product_images_revisions') . ' WHERE product_id=' . (int) $product_id . ' AND action != \'\' ORDER BY id DESC';
        return table($sql, [
                'caption' => t('Product images revisions'),
                'no_records_html' => '',
                'pager_records_on_page' => 5,
                'no_pages' => true,
            ])
            ->date('add_date', ['format' => '%d/%m/%Y', 'nowrap' => 1])
            ->admin('user_id', ['desc' => 'admin'])
/*			->image('image_id', 'Image', array('width' => '30px', 'img_path_callback' => function($_p1, $_p2, $row) {
                $dirs = sprintf('%06s', $row['product_id']);
                $dir2 = substr($dirs, -3, 3);
                $dir1 = substr($dirs, -6, 3);
                $m_path = $dir1.'/'.$dir2.'/';
                $image = SITE_IMAGES_DIR.$m_path.'product_'.$row['product_id'].'_'.$row['image_id'].'.jpg';
                return $image;
            }))
*/
            ->text('action')
            ->btn_view('', './?object=manage_shop&action=product_images_revisions_view&id=%d', ['data-test' => 'view_image_revision_btn'])
            ->footer_link('All images revisions', './?object=manage_shop&action=product_images_revisions');
    }


    public function _product_revisions_similar()
    {
        $rev = db()->get('SELECT * FROM ' . db('shop_product_revisions') . ' WHERE id=' . (int) ($_GET['id']));
        $product_id = (int) ($rev['item_id']);
        $product_info = module('manage_shop')->_product_get_info($product_id);
        if ( ! $product_info) {
            return false;
        }
        $sql = 'SELECT * FROM ' . db('shop_product_revisions') . ' WHERE item_id=' . (int) $product_id . ' AND action !=\'\' ORDER BY id DESC';
        return table($sql, [
                'caption' => t('Product revisions'),
                'no_records_html' => '',
                'tr' => [
                    $rev['id'] => ['class' => 'success'],
                ],
                'pager_records_on_page' => 10,
            ])
            ->date('add_date', ['format' => '%d/%m/%Y', 'nowrap' => 1])
            ->admin('user_id', ['desc' => 'admin'])
            ->text('action')
            ->btn_view('', './?object=manage_shop&action=product_revisions_view&id=%d&page=' . $_GET['page']);
    }


    public function _product_images_revisions_similar()
    {
        $rev = db()->get('SELECT * FROM ' . db('shop_product_images_revisions') . ' WHERE id=' . (int) ($_GET['id']));
        $product_id = (int) ($rev['product_id']);
        $product_info = module('manage_shop')->_product_get_info($product_id);
        if ( ! $product_info) {
            return false;
        }
        $sql = 'SELECT * FROM ' . db('shop_product_images_revisions') . ' WHERE product_id=' . (int) $product_id . ' AND action !=\'\' ORDER BY id DESC';
        return table($sql, [
                'caption' => t('Product images revisions'),
                'no_records_html' => '',
                'tr' => [
                    $rev['id'] => ['class' => 'success'],
                ],
                'pager_records_on_page' => 10,
            ])
            ->date('add_date', ['format' => '%d/%m/%Y', 'nowrap' => 1])
            ->admin('user_id', ['desc' => 'admin'])
            ->text('action')
            ->btn_view('', './?object=manage_shop&action=product_images_revisions_view&id=%d&page=' . $_GET['page']);
    }


    public function _order_revisions()
    {
        $order_id = (int) ($_GET['id']);
        $order_info = db()->get('SELECT * FROM ' . db('shop_orders') . ' WHERE id=' . $order_id);
        if (empty($order_info)) {
            return _e('No such order');
        }
        $sql = 'SELECT * FROM ' . db('shop_order_revisions') . ' WHERE item_id=' . (int) $order_id . ' AND action !=\'\' ORDER BY id DESC';
        return table($sql, [
                'caption' => t('Order revisions'),
                'no_records_html' => '',
                'pager_records_on_page' => 5,
                'no_pages' => true,
            ])
            ->date('add_date', ['format' => 'full', 'nowrap' => 1])
            ->admin('user_id', ['desc' => 'admin'])
            ->text('action')
            ->btn_view('', './?object=manage_shop&action=order_revisions_view&id=%d')
            ->footer_link('All orders revisions', './?object=manage_shop&action=order_revisions');
    }


    public function _order_revisions_similar()
    {
        $rev = db()->get('SELECT * FROM ' . db('shop_order_revisions') . ' WHERE id=' . (int) ($_GET['id']));
        $order_id = (int) ($rev['item_id']);
        $order_info = db()->get('SELECT * FROM ' . db('shop_orders') . ' WHERE id=' . $order_id);
        if (empty($order_info)) {
            return false;
        }
        $sql = 'SELECT * FROM ' . db('shop_order_revisions') . ' WHERE item_id=' . (int) $order_id . ' AND action !=\'\' ORDER BY id DESC';
        return table($sql, [
                'caption' => t('Order revisions'),
                'no_records_html' => '',
                'tr' => [
                    $rev['id'] => ['class' => 'success'],
                ],
                'pager_records_on_page' => 10,
            ])
            ->date('add_date', ['format' => 'full', 'nowrap' => 1])
            ->admin('user_id', ['desc' => 'admin'])
            ->text('action')
            ->btn_view('', './?object=manage_shop&action=order_revisions_view&id=%d&page=' . $_GET['page']);
    }


    public function _revisions($type)
    {
        $id = (int) ($_GET['id']);
        $db = $_class_revision->get_db($type);
        $info = db()->get('SELECT * FROM ' . $db . ' WHERE id=' . $id);
        if (empty($info)) {
            return _e('No such revision: ' . $id);
        }
        $_class_revision = _class('manage_shop__product_revisions', 'admin_modules/manage_shop/');
        $db_revision = $_class_revision->get_revision_db($type);
        $sql = 'SELECT * FROM ' . db('shop_order_revisions') . ' WHERE item_id=' . (int) $order_id . ' AND action !=\'\' ORDER BY id DESC';
        return table($sql, [
                'caption' => t('Order revisions'),
                'no_records_html' => '',
                'pager_records_on_page' => 5,
                'no_pages' => true,
            ])
            ->date('add_date', ['format' => 'full', 'nowrap' => 1])
            ->admin('user_id', ['desc' => 'admin'])
            ->text('action')
            ->btn_view('', './?object=manage_shop&action=order_revisions_view&id=%d')
            ->footer_link('All orders revisions', './?object=manage_shop&action=order_revisions');
    }
}
