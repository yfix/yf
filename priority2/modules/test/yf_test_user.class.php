<?php

/**
* Test sub-class
*/
class yf_test_user {

	/**
	* YF module constructor
	*/
	function _init () {
		$this->TEST_OBJ = module("test");
	}

	/**
	* Testing short functions: user(), update_user(), search_user()
	*/
	function run_test () {
		$OBJ = &main()->init_class("user_data", "classes/common/");
		if (!DEBUG_MODE) {
			return "Allowed to run only when DEBUG_MODE is enabled!";
		}

		$this->_on_before_run();
/*
		$OBJ->MODE = "SIMPLE";
		$body .= "<h2>Single table mode</h2>";
		$body .= $this->_test();
*/
		$OBJ->MODE = "DYNAMIC";
		$body .= "<h2>Dynamic table mode</h2>";
		$body .= $this->_test();

		return $body;
	}

	/**
	* 
	*/
	function _on_before_run () {
/*
INSERT INTO test_user_data_info_fields (id, name, type, value_list, default_value, comment, order, active) VALUES
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
*/
/*
		for ($i = 1000; $i <= 1050; $i++) {
			db()->INSERT("user_data_info_fields", array(
				"id"		=> $i,
				"name"		=> "blabla".$i,
				"type"		=> "varchar",
				"active"	=> 1,
			));
		}
*/
/*
		$start = 2000000000;
		$end = $start + 1000;
		for ($i = $start; $i <= $end; $i++) {
			update_user($i, array("active" => 1, "blabla" => "test ".rand(1000, $start)." test"));
		}
*/
	}

	/**
	* Testing short functions: user(), update_user(), search_user()
	*/
	function _test () {
		$body .= "<small>";
		// user();
		$body .= "<h3>function user(/*int|array*/ \$user_id [, /*string|array*/ \$fields] [, /*array*/ \$params] [, /*bool*/ \$return_sql])</h3>\r\n";

		$body .= "<br /><b>user(1)</b><br /><p>".print_r(
				user(1)
			, 1)."</p>";
		$body .= "<br /><b>user(array(1,2,3,4))</b><br /><p>".print_r(
				user(array(1,2,3,4))
			, 1)."</p>";
		$body .= "<br /><b>user(\"1,2,3,4\")</b><br /><p>".print_r(
				user("1,2,3,4")
			, 1)."</p>";
		$body .= "<br /><b>user(\"1,2,3,4,,,,-1,-13131\")</b><br /><p>".print_r(
				user("1,2,3,4,,,,-1,-13131")
			, 1)."</p>";
		$body .= "<br /><b>user(1, array(\"login\", \"password\", \"nick\"))</b><br /><p>".print_r(
				user(1, array("login", "password", "nick"))
			, 1)."</p>";
		$body .= "<br /><b>user(array(1,2,3,4), array(\"login\", \"password\", \"nick\"))</b><br /><p>".print_r(
				user(array(1,2,3,4), array("login", "password", "nick"))
			, 1)."</p>";
		$body .= "<br /><b>user(\"1,2,3,4\", \"login, password, nick\")</b><br /><p>".print_r(
				user("1,2,3,4", "login, password, nick")
			, 1)."</p>";
		$body .= "<br /><b>user(1, \"short\")</b><br /><p>".print_r(
				user(1, "short")
			, 1)."</p>";
		$body .= "<br /><b>user(array(1,2,3,4), \"short\")</b><br /><p>".print_r(
				user(array(1,2,3,4), "short")
			, 1)."</p>";
		$body .= "<br /><b>user(array(1,2,3,4), \"short\", array(\"WHERE\" => array(\"active\" => 1)))</b><br /><p>".print_r(
				user(array(1,2,3,4), "short", array("WHERE" => array("active" => 1)))
			, 1)."</p>";
		$body .= "<br /><b>user(0)</b><br /><p>".print_r(
				user(0)
			, 1)."</p>";
		$body .= "<br /><b>user(array())</b><br /><p>".print_r(
				user(array())
			, 1)."</p>";
		$body .= "<br /><b>user(array(), array())</b><br /><p>".print_r(
				user(array(), array())
			, 1)."</p>";
		$body .= "<br /><b>user(array(\"\" => \"\"), array(\"\" => \"\"))</b><br /><p>".print_r(
				user(array("" => ""), array("" => ""))
			, 1)."</p>";
		$body .= "<br /><b>user(-1)</b><br /><p>".print_r(
				user(-1)
			, 1)."</p>";
		$body .= "<br /><b>user(\"blabla\")</b><br /><p>".print_r(
				user("blabla")
			, 1)."</p>";
		$body .= "<br /><b>user(1, \"non_existent_field\")</b><br /><p>".print_r(
				user(1, "non_existent_field")
			, 1)."</p>";
		$body .= "<br /><b>user(1, array())</b><br /><p>".print_r(
				user(1, array())
			, 1)."</p>";
		$body .= "<br /><b>user(1, -1)</b><br /><p>".print_r(
				user(1, -1)
			, 1)."</p>";
		$body .= "<br /><b>user(1, array(\"non_existent_field\", \"non_existent_field2\", \"non_existent_field2\"))</b><br /><p>".print_r(
				user(1, array("non_existent_field", "non_existent_field2", "non_existent_field2"))
			, 1)."</p>";
		$body .= "<br /><b>user(1, array(\"non_existent_field\\'&^%\$-\", \"non_existent_field2\\'&^%\$-\", \"non_existent_field3\\'&^%\$-\"))</b><br /><p>".print_r(
				user(1, array("non_existent_field\\'&^%\$-", "non_existent_field2\\'&^%\$-", "non_existent_field2\\'&^%\$-"))
			, 1)."</p>";
		$body .= "<br /><b>user(array(1,2,3,4), array(\"login\", \"password\", \"nick\"), null, true)</b><br /><p>".print_r(
				user(array(1,2,3,4), array("login", "password", "nick"), null, true)
			, 1)."</p>";
		$body .= "<br /><b>user(array(1,2,3,4), array(\"login\", \"password\", \"nick\"), array(\"WHERE\" => array(\"active\" => 1)), true)</b><br /><p>".print_r(
				user(array(1,2,3,4), array("login", "password", "nick"), array("WHERE" => array("active" => 1)), true)
			, 1)."</p>";
		$body .= "<br /><b>user(array(1,2,3,4), \"full\", null, true)</b><br /><p>".print_r(
				user(array(1,2,3,4), "full", null, true)
			, 1)."</p>";
		$body .= "<br /><b>user(array(1,2,3,4), array(\"blabla\"))</b><br /><p>".print_r(
				user(array(1,2,3,4), array("blabla"))
			, 1)."</p>";
		$body .= "<br /><b>user(array(1,2,3,4), array(\"login\", \"nick\", \"blabla\", \"blabla2\", \"blabla3\"))</b><br /><p>".print_r(
				user(array(1,2,3,4), array("login", "nick", "blabla", "blabla2", "blabla3"))
			, 1)."</p>";
		$body .= "<br /><b>user(array(1,2,3,4), array(\"login\", \"nick\", \"blabla\", \"blabla2\", \"blabla3\"), null, true)</b><br /><p>".print_r(
				user(array(1,2,3,4), array("login", "nick", "blabla", "blabla2", "blabla3"), null, true)
			, 1)."</p>";
		$body .= "<br /><b>user(array(1,2,3,4), \"dynamic\")</b><br /><p>".print_r(
				user(array(1,2,3,4), "dynamic")
			, 1)."</p>";
		$body .= "<br /><b>user(array(1,2,3,4), \"dynamic\", null, true)</b><br /><p>".print_r(
				user(array(1,2,3,4), "dynamic", null, true)
			, 1)."</p>";
		$body .= "<br /><b>user(\"2000000000,2000000001,2000000002\", \"dynamic\", null, true)</b><br /><p>".print_r(
				user("2000000000,2000000001,2000000002", "dynamic", null, true)
			, 1)."</p>";

		// update_user();
		$body .= "<h3>function update_user()</h3><br />\r\n";
		$body .= "<br /><b>update_user(2000000000)</b><br /><p>".print_r(
				update_user(2000000000)
			, 1)."</p>";
		$body .= "<br /><b>update_user(2000000000, array(\"active\" => 1))</b><br /><p>".print_r(
				update_user(2000000000, array("active" => 1))
			, 1)."</p>";
		$body .= "<br /><b>update_user(\"2000000000,2000000001,2000000002\", array(\"active\" => 1))</b><br /><p>".print_r(
				update_user("2000000000,2000000001,2000000002", array("active" => 1))
			, 1)."</p>";
		$body .= "<br /><b>update_user(\"2000000000,2000000001,-1999999997,,,+\", array(\"active\" => 1))</b><br /><p>".print_r(
				update_user("2000000000,2000000001,-1999999997,,,+", array("active" => 1))
			, 1)."</p>";
		$body .= "<br /><b>update_user(2000000000, array(\"non_existed_field\" => 1))</b><br /><p>".print_r(
				update_user(2000000000, array("non_existed_field" => 1))
			, 1)."</p>";
		$body .= "<br /><b>update_user(0, array(\"non_existed_field\" => 1))</b><br /><p>".print_r(
				update_user(0, array("non_existed_field" => 1))
			, 1)."</p>";
		$body .= "<br /><b>update_user(-1, array(\"non_existed_field\" => 1))</b><br /><p>".print_r(
				update_user(-1, array("non_existed_field" => 1))
			, 1)."</p>";
		$body .= "<br /><b>update_user(array(), array())</b><br /><p>".print_r(
				update_user(array(), array())
			, 1)."</p>";
		$body .= "<br /><b>update_user(array(\"\" => \"\"), array(\"\" => \"\"))</b><br /><p>".print_r(
				update_user(array("" => ""), array("" => ""))
			, 1)."</p>";
		$body .= "<br /><b>update_user(array(), array(\"non_existed_field\" => 1))</b><br /><p>".print_r(
				update_user(array(), array("non_existed_field" => 1))
			, 1)."</p>";
		$body .= "<br /><b>update_user(array(2000000000, 2000000001, 2000000002), array(\"active\" => 1))</b><br /><p>".print_r(
				update_user(array(2000000000, 2000000001, 2000000002), array("active" => 1))
			, 1)."</p>";
		$body .= "<br /><b>update_user(array(2000000000, 2000000001, 2000000002), array(\"active\" => 1), array(\"WHERE\" => array(\"active\" => 1)))</b><br /><p>".print_r(
				update_user(array(2000000000, 2000000001, 2000000002), array("active" => 1), array("WHERE" => array("active" => 1)))
			, 1)."</p>";
		$body .= "<br /><b>update_user(array(2000000000, 2000000001, 2000000002), array(\"blabla\" => \"updated from php!\"), array(\"WHERE\" => array(\"active\" => 1)))</b><br /><p>".print_r(
				update_user(array(2000000000, 2000000001, 2000000002), array("blabla" => "updated from php!"), array("WHERE" => array("active" => 1)))
			, 1)."</p>";

		// search_user();
		$body .= "<h3>function search_user()</h3><br />\r\n";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1)))</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1)))
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1)), \"short\")</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1)), "short")
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1)), \"full\")</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1)), "full")
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1)), array(\"login\",\"nick\"))</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1)), array("login","nick"))
			, 1)."</p>";
		$body .= "<br /><b>search_user(\"active = 1, group = 2\")</b><br /><p>".print_r(
				search_user("active = 1, group = 2")
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"\" => \"\")))</b><br /><p>".print_r(
				search_user(array("WHERE" => array("" => "")))
			, 1)."</p>";
		$body .= "<br /><b>search_user(array())</b><br /><p>".print_r(
				search_user(array())
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"\"))</b><br /><p>".print_r(
				search_user(array(""))
			, 1)."</p>";
		$body .= "<br /><b>search_user(-1)</b><br /><p>".print_r(
				search_user(-1)
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1)), \"short\", true)</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1)), "short", true)
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1)), \"full\", true)</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1)), "full", true)
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1)), array(\"login\",\"nick\"), true)</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1)), array("login","nick"), true)
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1), \"LIMIT\" => 1), null)</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 1), null)
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1), \"LIMIT\" => 1), null, true)</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 1), null, true)
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1), \"LIMIT\" => 1, \"OFFSET\" => 1), null)</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 1, "OFFSET" => 1), null)
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1), \"LIMIT\" => 1, \"OFFSET\" => 1), null, true)</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 1, "OFFSET" => 1), null, true)
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1), \"LIMIT\" => 1, \"OFFSET\" => 1), array(\"nick\", \"login\"))</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 1, "OFFSET" => 1), array("nick", "login"))
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1), \"LIMIT\" => 1, \"OFFSET\" => 1), array(\"nick\", \"login\"), true)</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 1, "OFFSET" => 1), array("nick", "login"), true)
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1, \"id\" => 2000000000), \"LIMIT\" => 1), array(\"nick\", \"login\"))</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1, "id" => 2000000000), "LIMIT" => 1), array("nick", "login"))
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1, \"id\" => 2000000000), \"LIMIT\" => 1), array(\"nick\", \"login\"), true)</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1, "id" => 2000000000), "LIMIT" => 1), array("nick", "login"), true)
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1), \"LIMIT\" => 10, \"ORDER BY\" => \"add_date\"), array(\"nick\", \"login\"), true)</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 10, "ORDER BY" => "nick"), array("nick", "login"), true)
			, 1)."</p>";
		$body .= "<br /><b>search_user(array(\"WHERE\" => array(\"active\" => 1), \"LIMIT\" => 10, \"ORDER BY\" => \"add_date\"), array(\"nick\", \"login\"))</b><br /><p>".print_r(
				search_user(array("WHERE" => array("active" => 1), "LIMIT" => 10, "ORDER BY" => "nick"), array("nick", "login"))
			, 1)."</p>";
/**/
		$body .= "</small>";
		return $body;
	}
}
