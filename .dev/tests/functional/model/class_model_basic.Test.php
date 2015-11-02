<?php

require_once dirname(__DIR__).'/db_real_abstract.php';

/**
 * @requires extension mysqli
 */
class class_model_basic_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'mysqli';
		self::_connect();
		self::utils()->truncate_database(self::db_name());
		self::$_bak['ERROR_AUTO_REPAIR'] = self::db()->ERROR_AUTO_REPAIR;
		self::db()->ERROR_AUTO_REPAIR = true;
		$GLOBALS['db'] = self::db();

		// unit_tests == name of the custom storage used here
		// Ensure unit_tests will be on top of the storages list
		main()->_custom_class_storages['*_model'] = array('unit_tests' => array(__DIR__.'/fixtures/')) + (array)main()->_custom_class_storages['*_model'];
	}
	public static function tearDownAfterClass() {
		self::utils()->truncate_database(self::db_name());
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
		self::db()->ERROR_AUTO_REPAIR = self::$_bak['ERROR_AUTO_REPAIR'];
	}
	public static function db_name() {
		return self::$DB_NAME;
	}
	public static function table_name($name) {
		return $name;
	}

	/***/
	public function test_basic() {
		$model_base = _class('model');
		$this->assertTrue( is_object($model_base) );
		$this->assertTrue( is_a($model_base, 'yf_model') );
		$this->assertSame( $model_base, _class('yf_model') );

		$model_exists = main()->_class_exists('film_model');
		if (!$model_exists) {
			$this->assertTrue( main()->_class_exists('film_model') );
		}

		$film_model = _class('film_model');
		$this->assertTrue( is_object($film_model) );
		$this->assertTrue( is_a($film_model, 'film_model') );
		$this->assertTrue( is_a($film_model, 'yf_model') );

		$film_model2 = model('film');
		$this->assertNotSame( $film_model2, $film_model );
		$this->assertTrue( is_object($film_model2) );
		$this->assertTrue( is_a($film_model2, 'film_model') );
		$this->assertTrue( is_a($film_model2, 'yf_model') );

		$film_model3 = model('film');
		$this->assertNotSame( $film_model2, $film_model3 );
		$this->assertTrue( is_object($film_model2) );
		$this->assertTrue( is_a($film_model2, 'film_model') );
		$this->assertTrue( is_a($film_model2, 'yf_model') );
	}

	/***/
	public function test_short_name_autoload() {
		$model_base = _class('model');
		$m = __FUNCTION__.'_model';
		eval('class '.$m.' extends yf_model {}');
		self::utils()->create_table(__FUNCTION__, function($t) {
			$t->increments('id');
		});
		$m::create(array('id' => 1));
		$m::find(1);
		$m_short = __FUNCTION__;
#		$m_short::find(1);
	}

	/***/
	public function test_where() {
		$model_base = _class('model');
		$m = __FUNCTION__.'_model';
		eval('class '.$m.' extends yf_model {}');
		self::utils()->create_table(__FUNCTION__, function($t) {
			$t->increments('id')
			->string('name')
			->string('gender')
			->int('popularity');
		});
		$m1 = $m::create(array('name' => 'Susan', 'gender' => 'w', 'popularity' => 8));
		$m2 = $m::create(array('name' => 'Michael', 'gender' => 'm', 'popularity' => 12));

# 		$m::where_popular('>','10')->count();
#		$m::where_gender('w')->get();
#		$m::where_name($wildcard)->get();

#		$m1->where_popular('>','10')->count();
#		$m1->where_gender('w')->get();
#		$m1->where_name($wildcard)->get();
	}

	/***/
	public function test_scopes() {
		$model_base = _class('model');
		eval(
<<<'ND'
			class test_scopes_model extends yf_model {
				public function scope_popular($query) {
					return $query->where('popular','>','10');
#					return $query->where_popular('>','10');
				}
				public function scope_women($query) {
					return $query->where('gender','w');
#					return $query->where_gender('w');
				}
				public function scope_name($query, $wildcard) {
					return $query->where('name',$wildcard);
#					return $query->where_name($wildcard);
				}
			}
ND
		);
		self::utils()->create_table(__FUNCTION__, function($t) {
			$t->increments('id')
			->string('name')
			->string('gender')
			->int('popularity');
		});
		$m = __FUNCTION__.'_model';
#		test_scopes::create(array('name' => 'Susan', 'gender' => 'w', 'popularity' => 8));
		$m::create(array('name' => 'Susan', 'gender' => 'w', 'popularity' => 8));
		$m::create(array('name' => 'Michael', 'gender' => 'm', 'popularity' => 12));
		$m::create(array('name' => 'Marilyn', 'gender' => 'w', 'popularity' => 11));
		$m::create(array('name' => 'Brigitte', 'gender' => 'w', 'popularity' => 11));

#		$m::popular()->order_by('name')->get();
#		$m::popular()->women()->order_by('name', 'desc')->get();
#		$m::popular()->women()->name('mary*')->select('name')->one();
	}

	/***/
	public function test_accessors_and_mutators() {
		$model_base = _class('model');
		eval(
<<<'ND'
			class test_accessors_and_mutators_model extends yf_model {
				public function get_attr_name($value) {
					return strtoupper($value);
				}
				public function set_attr_name($value) {
					return strtolower($value);
				}
				public function get_attr_popularity($value) {
					return 'Popularity: '.$value;
				}
				public function set_attr_popularity($value) {
					return intval($value);
				}
			}
ND
		);
		self::utils()->create_table(__FUNCTION__, function($t) {
			$t->increments('id')
			->string('name')
			->string('gender')
			->int('popularity');
		});
		$m = __FUNCTION__.'_model';
		$m::create(array('name' => 'Susan', 'gender' => 'w', 'popularity' => 8));

// TODO: complete functionality for accesors and mutators

		$m1 = $m::find(1);
		$m1->popularity;

		$m1->popularity = '15';
		$m1->save();

		$m1->set('popularity', '15')->save();

		$m1->name;
		$m1->set('name', '15')->save();
	}

	/***/
	public function test_events() {
		$model_base = _class('model');
		$m = __FUNCTION__.'_model';
		eval('class '.$m.' extends yf_model {}');
		self::utils()->create_table(__FUNCTION__, function($t) {
			$t->increments('id')
			->string('name')
			->string('gender')
			->int('popularity');
		});
		$m::create(array('name' => 'Susan', 'gender' => 'w', 'popularity' => 8));

# $m->creating(function($model)) { if(!$model->is_valid()) return false; };
# $m->created(function($model)) { };
# $m->updating(function($model)) { };
# $m->updated(function($model)) { };
# $m->saving(function($model)) { };
# $m->saved(function($model)) { };
# $m->deleting(function($model)) { };
# $m->deleted(function($model)) { };
	}

	/***/
	public function test_has_many_through() {
		$model_base = _class('model');
		$m = __FUNCTION__.'_model';
		$t_countries = __FUNCTION__.'_countries';
		$t_users = __FUNCTION__.'_users';
		$t_posts = __FUNCTION__.'_posts';
		self::utils()->create_table($t_countries, function($t) {
			$t->increments('id')
			->string('name');
		});
		self::utils()->create_table($t_users, function($t) {
			$t->increments('id')
			->int('country_id')
			->string('name');
		});
		self::utils()->create_table($t_posts, function($t) {
			$t->increments('id')
			->int('user_id')
			->string('title');
		});
		eval(
<<<'ND'
			class test_has_many_through_model extends yf_model {
				protected $_table = 'test_has_many_through_countries';
				public function posts($value) {
					return $this->has_many_through('test_has_many_through_post', 'test_has_many_through_user');
				}
			}
			class test_has_many_through_post_model extends yf_model {
				protected $_table = 'test_has_many_through_posts';
			}
			class test_has_many_through_user_model extends yf_model {
				protected $_table = 'test_has_many_through_users';
			}
ND
		);
		$m::create(array('name' => 'Monaco'));
	}

	/***/
	public function test_morph_to_one() {
	}

	/***/
	public function test_morph_many() {
	}

	/***/
	public function test_morph_to_many() {
	}

	/***/
	public function test_validation() {
// TODO
/*
			->validate(array(
				'__before__'	=> 'trim',
				'login'			=> 'required|alpha_numeric|is_unique_without[admin.login.'.$id.']',
				'email'			=> 'required|valid_email|is_unique_without[admin.email.'.$id.']',
				'first_name'	=> 'required',
				'last_name'		=> 'required',
				'password'		=> 'password_update',
				'group'			=> 'required|exists[admin_groups.id]',
			))
*/
	}

	/***/
	public function test_form() {
// TODO
/*
		return model('admin')->form($id, $a, array('autocomplete' => 'off'))
			->validate(array(
				'__before__'	=> 'trim',
				'login'			=> 'required|alpha_numeric|is_unique_without[admin.login.'.$id.']',
				'email'			=> 'required|valid_email|is_unique_without[admin.email.'.$id.']',
				'first_name'	=> 'required',
				'last_name'		=> 'required',
				'password'		=> 'password_update',
				'group'			=> 'required|exists[admin_groups.id]',
			))
			->db_update_if_ok('admin', array('login','email','first_name','last_name','go_after_login','password','group'), 'id='.$id)
			->on_after_update(function() {
				common()->admin_wall_add(array(t('admin account edited: %login', array('%login' => $_POST['login'])), $id));
			})
			->login()
			->email()
			->password()
			->text('first_name')
			->text('last_name')
			->text('go_after_login', 'Url after login')
			->select_box('group', main()->get_data('admin_groups'), array('selected' => $a['group']))
			->active_box()
			->info_date('add_date','Added')
			->row_start()
				->save_and_back()
				->link('log auth', url_admin('/log_admin_auth/show_for_admin/'.$a['id']))
				->link('login as', url_admin('/@object/login_as/'.$a['id']), array('display_func' => $func))
			->row_end()
		;
*/
	}

	/***/
	public function test_table() {
// TODO
/*
		$admin_id = main()->ADMIN_ID;
		$func = function($row) use ($admin_id) {
			return !($row['id'] == $admin_id);
		};
		return model('admin')->table(array(
				'filter_params' => array(
					'login'	=> 'like',
					'email'	=> 'like',
				),
			))
			->text('login')
			->text('email')
			->link('group', url_admin('/admin_groups/edit/%d'), main()->get_data('admin_groups'))
			->text('first_name')
			->text('last_name')
			->text('go_after_login')
			->date('add_date')
			->btn_active(array('display_func' => $func))
			->btn_edit()
			->btn_delete(array('display_func' => $func))
			->btn('log_auth', url_admin('/log_admin_auth/show_for_admin/%d'))
			->btn('login', url_admin('/@object/login_as/%d'), array('display_func' => $func))
			->footer_link('Failed auth log', url_admin('/log_admin_auth_fails'))
			->footer_add();
*/
	}
}
