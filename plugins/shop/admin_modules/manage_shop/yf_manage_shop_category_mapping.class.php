<?php

class yf_manage_shop_category_mapping
{
    public function category_mapping()
    {
        $SUPPLIER_ID = module('manage_shop')->SUPPLIER_ID;
        $sql = 'SELECT * FROM ' . db('shop_suppliers_cat_aliases') . ' WHERE supplier_id = ' . (int) $SUPPLIER_ID;
        return table($sql, [
                'id' => 'cat_id',
            ])
            ->text('cat_id', '', ['data' => module('manage_shop')->_category_names, 'label' => 'info'])
            ->text('name')
            ->btn_edit('', './?object=' . main()->_get('object') . '&action=category_mapping_edit&id=%d')
            ->btn_delete('', './?object=' . main()->_get('object') . '&action=category_mapping_delete&id=%d')
            ->footer_add('', './?object=' . main()->_get('object') . '&action=category_mapping_add');
    }


    public function category_mapping_add()
    {
        $SUPPLIER_ID = module('manage_shop')->SUPPLIER_ID;
        $a = ['back_link' => './?object=' . main()->_get('object') . '&action=category_mapping'];
        return form($a, ['selected' => $_POST])
            ->validate(['cat_id' => 'trim|required|numeric|exists[category_items.id]', 'name' => 'trim|required'])
            ->db_insert_if_ok('shop_suppliers_cat_aliases', ['cat_id', 'name'], ['supplier_id' => (int) $SUPPLIER_ID])
            ->select_box('cat_id', module('manage_shop')->_cats_for_select)
            ->text('name')
            ->save_and_back();
    }


    public function category_mapping_edit()
    {
        $SUPPLIER_ID = module('manage_shop')->SUPPLIER_ID;
        $a = db()->get('SELECT * FROM ' . db('shop_suppliers_cat_aliases') . ' WHERE supplier_id=' . (int) $SUPPLIER_ID . ' AND cat_id=' . (int) $_GET['id']);
        if ( ! $a) {
            return _e('No such record');
        }
        $a['back_link'] = './?object=' . main()->_get('object') . '&action=category_mapping';
        return form($a, ['selected' => $a + $_POST])
            ->validate(['cat_id' => 'trim|required|numeric|exists[category_items.id]', 'name' => 'trim|required'])
            ->db_update_if_ok('shop_suppliers_cat_aliases', ['cat_id', 'name'], 'supplier_id=' . (int) $SUPPLIER_ID . ' AND cat_id=' . (int) $a['cat_id'])
            ->select_box('cat_id', module('manage_shop')->_cats_for_select)
            ->text('name')
            ->save_and_back();
    }


    public function category_mapping_delete()
    {
        $SUPPLIER_ID = module('manage_shop')->SUPPLIER_ID;
        $a = db()->get('SELECT * FROM ' . db('shop_suppliers_cat_aliases') . ' WHERE supplier_id=' . (int) $SUPPLIER_ID . ' AND cat_id=' . (int) $_GET['id']);
        if ( ! $a) {
            return _e('No such record');
        }
        db()->query('DELETE FROM ' . db('shop_suppliers_cat_aliases') . ' WHERE supplier_id=' . (int) $SUPPLIER_ID . ' AND cat_id=' . (int) $_GET['id'] . ' LIMIT 1');
        return js_redirect('./?object=' . main()->_get('object') . '&action=category_mapping');
    }
}
