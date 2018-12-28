<?php

/*
 * @property integer $address_id
 * @property string $address
 * @property string $address2
 * @property string $district
 * @property integer $city_id
 * @property string $postal_code
 * @property string $phone
 * @property string $last_update
 *
 * @property city $city
 * @property customer[] $customers
 * @property staff[] $staffs
 * @property store[] $stores
*/
class address_model extends yf_model
{
    public static function _name_column()
    {
        return 'address';
    }
    public function _rules()
    {
        return [
            'address, district, city_id, phone, last_update' => 'required',
            'city_id' => 'integer',
            'address, address2' => 'max_length[50]',
            'district, phone' => 'max_length[20]',
            'postal_code' => 'max_length[10]',
            'address2, postal_code' => 'default[NULL]',
            'address_id, address, address2, district, city_id, postal_code, phone, last_update' => 'safe[on=search]',
        ];
    }
    public function city()
    {
        return $this->belongs_to('city', 'city_id');
    }
    public function customers()
    {
        return $this->has_many('customer', 'address_id');
    }
    public function staffs()
    {
        return $this->has_many('staff', 'address_id');
    }
    public function stores()
    {
        return $this->has_many('store', 'address_id');
    }
}
