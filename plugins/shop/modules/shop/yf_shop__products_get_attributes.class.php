<?php

class yf_shop__products_get_attributes
{
    public function _products_get_attributes($products_ids = [])
    {
        if (is_numeric($products_ids)) {
            $return_single_id = $products_ids;
            $products_ids = [$products_ids];
        }
        if (empty($products_ids)) {
            return [];
        }
        $fields_info = main()->get_data('shop_product_attributes_info');

        $Q = db()->query('SELECT * FROM ' . db('shop_product_attributes_values') . ' WHERE category_id=1 AND object_id IN (' . implode(',', $products_ids) . ')');
        while ($A = db()->fetch_assoc($Q)) {
            $_product_id = $A['object_id'];

            $A['value'] = strlen($A['value']) ? unserialize($A['value']) : [];
            $A['add_value'] = strlen($A['add_value']) ? unserialize($A['add_value']) : [];
            foreach ((array) $A['value'] as $_attr_id => $_dummy) {
                $_price = $A['add_value'][$_attr_id];
                $_item_id = $A['field_id'] . '_' . $_attr_id;
                $_field_info = $fields_info[module('shop')->ATTRIBUTES_CAT_ID][$A['field_id']];
                $_field_info['value_list'] = strlen($_field_info['value_list']) ? unserialize($_field_info['value_list']) : [];
                $data[$_product_id][$_item_id] = [
                    'id' => $_item_id,
                    'price' => $_price,
                    'name' => $_field_info['name'],
                    'value' => $_field_info['value_list'][$_attr_id],
                    'product_id' => $_product_id,
                ];
            }
        }
        if ($return_single_id) {
            return $data[$return_single_id];
        }
        return $data;
    }
}
