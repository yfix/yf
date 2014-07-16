<?php

class user_model extends yf_model {
	protected $_table = 'user';
/*
	public function scope_popular($query) {
		$query->where('votes > 100');
	}
	public function scope_men($query) {
		$query->where('gender = M');
	}
	public function scope_women($query) {
		$query->where('gender = W');
	}
	public function scope_of_type($query, $type) {
		$query->where('type = '.$type);
	}
*/
	public function phone() {
		return $this->has_one('phone');
	}
	public function roles() {
		return $this->belongs_to_many('role');
	}
}
/*
class phone_model extends yf_model {
	public function user() {
		return $this->belongs_to('user');
	}
}
class post_model extends yf_model {
	public function comments() {
		return $this->has_many('comment');
	}
}
class comment_model extends yf_model {
	public function post() {
		return $this->belongs_to('post');
	}
}
class role_model extends yf_model {
	public function roles() {
		return $this->belongs_to_many('user');
	}
}
class country_model extends yf_model {
	public function posts() {
		return $this->has_many_through('post', 'user');
	}
}
*/
/*
class photo_model extends yf_model {
	public function imageable() {
		return $this->morph_to();
	}
}
class staff_model extends yf_model {
	public function photos() {
		return $this->morph_many('photo', 'imageable');
	}
}
class order_model extends yf_model {
	public function photos() {
		return $this->morph_many('photo', 'imageable');
	}
}
*/