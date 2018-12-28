<?php

/*
 * @property integer $customer_id
 * @property integer $store_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property integer $address_id
 * @property integer $active
 * @property string $create_date
 * @property string $last_update
 *
 * @property address $address
 * @property store $store
 * @property payment[] $payments
 * @property rental[] $rentals
 */
class customer_model extends yf_model
{
    public static function _name_column()
    {
        return 'first_name';
    }
    public function _rules()
    {
        return [
            'store_id, first_name, last_name, address_id, create_date, last_update' => 'required',
            'store_id, address_id, active' => 'integer',
            'first_name, last_name' => 'max_length[45]',
            'email' => 'max_length[50]',
            'email, active' => 'default[NULL]',
            'customer_id, store_id, first_name, last_name, email, address_id, active, create_date, last_update' => 'safe[on=search]',
        ];
    }
    public function address()
    {
        return $this->belongs_to('address', 'address_id');
    }
    public function store()
    {
        return $this->belongs_to('store', 'store_id');
    }
    public function payments()
    {
        return $this->has_many('payment', 'customer_id');
    }
    public function rentals()
    {
        return $this->has_many('rental', 'customer_id');
    }
}
