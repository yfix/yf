<?php

/*
 * @property integer $rental_id
 * @property string $rental_date
 * @property integer $inventory_id
 * @property integer $customer_id
 * @property string $return_date
 * @property integer $staff_id
 * @property string $last_update
 *
 * @property payment[] $payments
 * @property staff $staff
 * @property inventory $inventory
 * @property customer $customer
 */
class rental_model extends yf_model
{
    public static function _name_column()
    {
        return 'rental_date';
    }
    public function _rules()
    {
        return [
            'rental_date, inventory_id, customer_id, staff_id, last_update' => 'required',
            'inventory_id, customer_id, staff_id' => 'integer',
            'return_date' => 'safe',
            'return_date' => 'default[NULL]',
            'rental_id, rental_date, inventory_id, customer_id, return_date, staff_id, last_update' => 'safe[on=search]',
        ];
    }
    public function payments()
    {
        return $this->has_many('payment', 'rental_id');
    }
    public function staff()
    {
        return $this->belongs_to('staff', 'staff_id');
    }
    public function inventory()
    {
        return $this->belongs_to('inventory', 'inventory_id');
    }
    public function customer()
    {
        return $this->belongs_to('customer', 'customer_id');
    }
}
