<?php

class yf_manage_shop_feedback
{
    public function feedback()
    {
        if (empty($_SESSION[$_GET['object'] . '__feedback'])) {
            $_SESSION[$_GET['object'] . '__feedback'] = [
                'order_by' => 'add_date',
                'order_direction' => 'desc',
            ];
        }
        $sql = 'SELECT f.*,p.name as product_name FROM ' . db('shop_product_feedback') . ' AS f
					INNER JOIN ' . db('shop_products') . ' AS p ON f.product_id = p.id';
        return table($sql, [
                'filter' => $_SESSION[$_GET['object'] . '__feedback'],
                'filter_params' => [
                    'id' => ['like', 'f.id'],
                    'product_id' => ['like', 'f.product_id'],
                    'name' => ['like', 'f.name'],
                    'email' => ['like', 'f.email'],
                    'content' => ['like', 'f.content'],
                    'pros' => ['like', 'f.pros'],
                    'cons' => ['like', 'f.cons'],
                    'active' => ['eq', 'f.active'],
                    'add_date' => ['dt_between', 'f.add_date'],
                ],
                'hide_empty' => 1,
            ])
            ->text('id')
            ->user('user_id')
            ->link('product_id', './?object=' . main()->_get('object') . '&action=product_edit&id=%d')
            ->text('product_name')
            ->text('name')
            ->text('email')
            ->text('content')
            ->text('rating')
            ->text('pros')
            ->text('cons')
            ->date('add_date', ['format' => 'full', 'nowrap' => 1])
            ->btn_active('', './?object=' . main()->_get('object') . '&action=feedback_activate&id=%d')
            ->btn_delete('', './?object=' . main()->_get('object') . '&action=feedback_delete&id=%d');
    }


    public function feedback_delete()
    {
        $_GET['id'] = (int) ($_GET['id']);
        $field_info = db()->query_fetch('SELECT * FROM ' . db('shop_product_feedback') . ' WHERE id = ' . (int) ($_GET['id']));
        if (empty($field_info)) {
            return _e('no field');
        }
        if ($_GET['id']) {
            db()->query('DELETE FROM ' . db('shop_product_feedback') . ' WHERE id=' . $_GET['id']);
            common()->admin_wall_add(['feedback deleted: ' . $_GET['id'], $_GET['id']]);
            $this->_recalc_rating($field_info['product_id']);
        }
        if ($_POST['ajax_mode']) {
            main()->NO_GRAPHICS = true;
            echo $_GET['id'];
        } else {
            return js_redirect($_SERVER['HTTP_REFERER'], 0);
        }
    }

    public function feedback_activate()
    {
        if ($_GET['id']) {
            $a = db()->query_fetch('SELECT * FROM ' . db('shop_product_feedback') . ' WHERE id = ' . (int) ($_GET['id']));
        }
        if ($a['id']) {
            if ($a['active'] == 1) {
                $active = 0;
            } elseif ($a['active'] == 0) {
                $active = 1;
            }
            db()->update_safe(db('shop_product_feedback'), ['active' => $active], 'id=' . (int) ($_GET['id']));
        }
        if ($_POST['ajax_mode']) {
            main()->NO_GRAPHICS = true;
            echo $active ? 1 : 0;
        } else {
            return js_redirect('./?object=' . main()->_get('object') . '');
        }
    }


    public function _recalc_rating($product_id)
    {
        if ((int) $product_id == 0) {
            return false;
        }
        $data = db()->get('SELECT SUM(`rating`) AS `sum`,COUNT(`rating`) AS `cnt` FROM `' . db('shop_product_feedback') . "` WHERE `product_id`='" . (int) $product_id . "' AND `rating`!=0");
        $rating_avg = $data['cnt'] > 0 ? round($data['sum'] / $data['cnt'], 1) : 0;
        db()->query('REPLACE INTO `' . db('shop_product_feedback_ratings') . '` (
			`product_id`,
			`rating_avg`,
			`num_votes`
		) VALUES (
			' . (int) $product_id . ",
			'" . number_format($rating_avg, 1, '.', '') . "',
			" . (int) ($data['cnt']) . '
		)');
    }
}
