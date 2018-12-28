<?php

/*
 * @property integer $staff_id
 * @property string $first_name
 * @property string $last_name
 * @property integer $address_id
 * @property string $picture
 * @property string $email
 * @property integer $store_id
 * @property integer $active
 * @property string $username
 * @property string $password
 * @property string $last_update
 *
 * @property payment[] $payments
 * @property rental[] $rentals
 * @property store $store
 * @property address $address
 * @property store[] $stores
 */
class staff_model extends yf_model
{
    public static function _name_column()
    {
        return 'first_name';
    }
    public function _rules()
    {
        return [
            'first_name, last_name, address_id, store_id, username, last_update' => 'required',
            'address_id, store_id, active' => 'integer',
            'first_name, last_name' => 'max_length[45]',
            'email' => 'max_length[50]',
            'username' => 'max_length[16]',
            'password' => 'max_length[40]',
            'picture' => 'safe',
            'picture, email, active, password' => 'default[NULL]',
            'staff_id, first_name, last_name, address_id, picture, email, store_id, active, username, password, last_update' => 'safe[on=search]',
        ];
    }
    public function payments()
    {
        return $this->has_many('payment', 'staff_id');
    }
    public function rentals()
    {
        return $this->has_many('rental', 'staff_id');
    }
    public function store()
    {
        return $this->belongs_to('store', 'store_id');
    }
    public function address()
    {
        return $this->belongs_to('address', 'address_id');
    }
    public function stores()
    {
        return $this->has_many('store', 'manager_staff_id');
    }
}
