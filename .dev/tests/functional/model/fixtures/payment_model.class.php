<?php

class payment_model extends yf_model {
/*
 * @property integer $payment_id
 * @property integer $customer_id
 * @property integer $staff_id
 * @property integer $rental_id
 * @property string $amount
 * @property string $payment_date
 * @property string $last_update
 *
 * @property Rental $rental
 * @property Customer $customer
 * @property Staff $staff
 */
/*
	public static function representingColumn() {
		return 'amount';
	}

	public function rules() {
		return array(
			array('customer_id, staff_id, amount, payment_date, last_update', 'required'),
			array('customer_id, staff_id, rental_id', 'numerical', 'integerOnly'=>true),
			array('amount', 'length', 'max'=>5),
			array('rental_id', 'default', 'setOnEmpty' => true, 'value' => null),
			array('payment_id, customer_id, staff_id, rental_id, amount, payment_date, last_update', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'rental' => array(self::BELONGS_TO, 'Rental', 'rental_id'),
			'customer' => array(self::BELONGS_TO, 'Customer', 'customer_id'),
			'staff' => array(self::BELONGS_TO, 'Staff', 'staff_id'),
		);
	}
*/
}