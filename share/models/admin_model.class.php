<?php

class admin_model extends yf_model {
	protected $_table = 'sys_admin';
	public function groups() {
		return $this->belongs_to_many('admin_group');
	}
}
