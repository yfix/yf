<?php

/*
 * @property integer $payment_id
 * @property integer $customer_id
 * @property integer $staff_id
 * @property integer $rental_id
 * @property string $amount
 * @property string $payment_date
 * @property string $last_update
 *
 * @property rental $rental
 * @property customer $customer
 * @property staff $staff
 */
class payment_model extends yf_model
{
    public static function _name_column()
    {
        return 'amount';
    }
    public function _rules()
    {
        return [
            'customer_id, staff_id, amount, payment_date, last_update' => 'required',
            'customer_id, staff_id, rental_id' => 'integer',
            'amount' => 'max_length[5]',
            'rental_id' => 'default[NULL]',
            'payment_id, customer_id, staff_id, rental_id, amount, payment_date, last_update' => 'safe[on=search]',
        ];
    }
    public function rental()
    {
        return $this->belongs_to('rental', 'rental_id');
    }
    public function customer()
    {
        return $this->belongs_to('customer', 'customer_id');
    }
    public function staff()
    {
        return $this->belongs_to('staff', 'staff_id');
    }
}
