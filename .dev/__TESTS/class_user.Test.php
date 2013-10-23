<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_user_test extends PHPUnit_Framework_TestCase {
	public function setUp() {
/*
INSERT INTO test_user_data_info_fields (id, name, type, value_list, default_value, comment, `order`, active) VALUES
(1, 'blabla', 'varchar', '', '', '', 0, '1'),
(2, 'blabla2', 'varchar', '', '', '', 0, '1'),
(3, 'blabla3', 'varchar', '', '', '', 0, '1');

INSERT INTO test_user_data_info_values (user_id, field_id, value) VALUES
(1, 1, 'gdfgdfgdf'),
(2, 1, 'ttttttttttt'),
(1, 2, 'fsdsdfsdfsdfsf'),
(4, 2, 'rrrrrrrrrrrrrrrrrrrrrrrrr'),
(1, 3, 'e2222222222222'),
(4, 3, 'rrrtggvxcvvvvvvvvvv');


		for ($i = 1000; $i <= 1050; $i++) {
			db()->INSERT("user_data_info_fields", array(
				"id"		=> $i,
				"name"		=> "blabla".$i,
				"type"		=> "varchar",
				"active"	=> 1,
			));
		}

		$start = 2000000000;
		$end = $start + 1000;
		for ($i = $start; $i <= $end; $i++) {
			update_user($i, array("active" => 1, "blabla" => "test ".rand(1000, $start)." test"));
		}
*/
	}

	public function test_main() {
/*
		$this->assertEquals('', _bbcode('[img]http://www.google.com/intl/en_ALL/images/logo.gif[/img]'));
				user(1)
				user(array(1,2,3,4))
				user("1,2,3,4")
				user("1,2,3,4,,,,-1,-13131")
				user(1, array("login", "password", "nick"))
				user(array(1,2,3,4), array("login", "password", "nick"))
				user("1,2,3,4", "login, password, nick")
				user(1, "short")
				user(array(1,2,3,4), "short")
				user(array(1,2,3,4), "short", array("WHERE" => array("active" => 1)))
				user(0)
				user(array())
				user(array(), array())
				user(array("" => ""), array("" => ""))
				user(-1)
				user("blabla")
				user(1, "non_existent_field")
				user(1, array())
				user(1, -1)
				user(1, array("non_existent_field", "non_existent_field2", "non_existent_field2"))
				user(1, array("non_existent_field\\'&^%\$-", "non_existent_field2\\'&^%\$-", "non_existent_field2\\'&^%\$-"))
				user(array(1,2,3,4), array("login", "password", "nick"), null, true)
				user(array(1,2,3,4), array("login", "password", "nick"), array("WHERE" => array("active" => 1)), true)
				user(array(1,2,3,4), "full", null, true)
				user(array(1,2,3,4), array("blabla"))
				user(array(1,2,3,4), array("login", "nick", "blabla", "blabla2", "blabla3"))
				user(array(1,2,3,4), array("login", "nick", "blabla", "blabla2", "blabla3"), null, true)
				user(array(1,2,3,4), "dynamic")
				user(array(1,2,3,4), "dynamic", null, true)
				user("2000000000,2000000001,2000000002", "dynamic", null, true)

				update_user(2000000000)
				update_user(2000000000, array("active" => 1))
				update_user("2000000000,2000000001,2000000002", array("active" => 1))
				update_user("2000000000,2000000001,-1999999997,,,+", array("active" => 1))
				update_user(2000000000, array("non_existed_field" => 1))
				update_user(0, array("non_existed_field" => 1))
				update_user(-1, array("non_existed_field" => 1))
				update_user(array(), array())
				update_user(array("" => ""), array("" => ""))
				update_user(array(), array("non_existed_field" => 1))
				update_user(array(2000000000, 2000000001, 2000000002), array("active" => 1))
				update_user(array(2000000000, 2000000001, 2000000002), array("active" => 1), array("WHERE" => array("active" => 1)))
				update_user(array(2000000000, 2000000001, 2000000002), array("blabla" => "updated from php!"), array("WHERE" => array("active" => 1)))

				search_user(array("WHERE" => array("active" => 1)))
				search_user(array("WHERE" => array("active" => 1)), "short")
				search_user(array("WHERE" => array("active" => 1)), "full")
				search_user(array("WHERE" => array("active" => 1)), array("login","nick"))
				search_user("active = 1, `group` = 2")
				search_user(array("WHERE" => array("" => "")))
				search_user(array())
				search_user(array(""))
				search_user(-1)
				search_user(array("WHERE" => array("active" => 1)), "short", true)
				search_user(array("WHERE" => array("active" => 1)), "full", true)
				search_user(array("WHERE" => array("active" => 1)), array("login","nick"), true)
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 1), null)
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 1), null, true)
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 1, "OFFSET" => 1), null)
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 1, "OFFSET" => 1), null, true)
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 1, "OFFSET" => 1), array("nick", "login"))
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 1, "OFFSET" => 1), array("nick", "login"), true)
				search_user(array("WHERE" => array("active" => 1, "id" => 2000000000), "LIMIT" => 1), array("nick", "login"))
				search_user(array("WHERE" => array("active" => 1, "id" => 2000000000), "LIMIT" => 1), array("nick", "login"), true)
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 10, "ORDER BY" => "nick"), array("nick", "login"), true)
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 10, "ORDER BY" => "nick"), array("nick", "login"))
*/
	}
}
