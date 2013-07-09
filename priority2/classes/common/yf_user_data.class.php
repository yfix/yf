<?php

/**
* User data handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_user_data {

	/** @var string can be "SIMPLE" (default), "DYNAMIC" */
	public $MODE				= "SIMPLE";
	/** @var bool */
	public $CACHE_IN_MEMORY	= true;
	/** @var bool */
	public $DEF_RECORDS_LIMIT	= 100;
	/** @var bool Prevent possibly broken calls with huge number of user ids */
	public $GET_USERS_LIMIT	= 500;
	/** @var array @conf_skip (Will be replaced in _init() with real values) */
	public $_user_tables = array(
		"m" => "DB_PREFIX.user_data_main", //main table must be always on a first place!!!
		"s" => "DB_PREFIX.user_data_stats",
	//	"b" => "DB_PREFIX.user_data_ban",
	);
	/** @var array */
	public $_fields_groups = array(
		"main"	=> array(
			"group",
			"name",
			"nick",
			"login",
			"email",
			"password",
			"phone",
			"city",
			"zip_code",
			"state",
			"country",
			"sex",
			"profile_url",
			"active",
		),
		"info"	=> array(
			"age",
			"verify_code",
			"admin_comments",
			"avatar",
			"lon",
			"lat",
		),
		"stats"	=> array(
			"visits",
		//	"emails",
		//	"emailssent",
		//	"sitevisits",
			"add_date",
			"last_update",
			"last_login",
			"num_logins",
			"ip",	
		),
		"ban"	=> array(
		//	"ban_email",
		//	"ban_images",
		//	"ban_forum",
		//	"ban_comments",
		//	"ban_blog",
		//	"ban_reput",
		),
		"short"	=> array(
			"group",
			"name",
			"nick",
			"city",
			"country",
			"state",
		),
		"full"	=> array(
			"*", // Not really used, but needed for checks
		),
		"dynamic"	=> array(
			"__dummy" // "Magick" placeholder, filled up automatically in constructor
		),
	);
	/** @var array */
	public $_allowed_sql_params = array(
		"WHERE",
		"ORDER BY",
		"GROUP BY",
		"LIMIT",
		"OFFSET",
		"ASC",
		"DESC",
	);

	/**
	* Framework construct
	*/
	function _init() {
		if (empty($this->MODE)) {
			$this->MODE = "SIMPLE";
		}
		if (main()->USER_INFO_DYNAMIC) {
			$this->MODE = "DYNAMIC";
		}
		// Array that defines tables in which user info stored
		foreach ((array)$this->_user_tables as $k => $v) {
			$this->_user_tables[$k] = str_replace("DB_PREFIX.", DB_PREFIX, $v);
		}
		$this->_fields_groups["dynamic"] = array(); // Reset
		// Info fields from table "user_data_info_fields"
		if ($this->MODE == "DYNAMIC") {
			$cache_name = "dynamic_fields";
			// Create array of dynamic info fields
			if (main()->USE_SYSTEM_CACHE) {
				$this->_avail_dynamic_fields = cache()->get($cache_name);
			}
			if (empty($this->_avail_dynamic_fields)) {
				$Q = db()->query("SELECT * FROM ".db('user_data_info_fields')." WHERE active='1'");
				while ($A = db()->fetch_assoc($Q)) {
					$this->_avail_dynamic_fields[$A["id"]] = $A["name"];
				}
				if (main()->USE_SYSTEM_CACHE) {
					cache()->put($cache_name, $this->_avail_dynamic_fields);
				}
			}
			// "Magick" field group
			$this->_fields_groups["dynamic"] = $this->_avail_dynamic_fields;

			$this->_avail_dynamic_fields_ids = array_flip((array)$this->_avail_dynamic_fields);
		}
	}

	/**
	* Get user info(s) by id(s)
	*/
	function _user($user_id, $fields = "full", $params = array(), $return_sql = false) {
		// Save inital call and convert single user id into array
		$result_single = false;
		if (is_numeric($user_id)) {
			$user_id = array($user_id => $user_id);
			$result_single = true;
		}
		// Do check and clean
		$user_id = $this->_cleanup_input_user_id($user_id);
		if (empty($user_id) || $user_id < 0) {
			return false;
		}
		$fields = $this->_cleanup_input_fields($fields);
		$params = $this->_cleanup_input_params($params);

		$result = false;
		if ($this->MODE == "SIMPLE") {
			$result = $this->_get_user_info_simple($user_id, $fields, $params, $return_sql);
		} elseif ($this->MODE == "DYNAMIC") {
			$result = $this->_get_user_info_dynamic($user_id, $fields, $params, $return_sql);
		}
		// Make 1-dimensional array from 2-dimensional if needed
		if ($result_single && is_array($result)) {
			$result = current($result);
		}
		return $result;
	}

	/**
	* Update user info by id
	*/
	function _update_user($user_id, $data = array(), $params = array()) {
		// Convert single user id into array
		if (is_numeric($user_id)) {
			$user_id = array($user_id => $user_id);
		}
		// Do check and clean
		$user_id = $this->_cleanup_input_user_id($user_id);
		if (empty($user_id) || $user_id < 0) {
			return false;
		}
		$data = $this->_cleanup_input_data($data);
		if (empty($data) || !is_array($data)) {
			return false;
		}
		$params = $this->_cleanup_input_params($params);

		$result = false;
		if ($this->MODE == "SIMPLE") {
			$result = $this->_update_user_simple($user_id, $data, $params);
		} elseif ($this->MODE == "DYNAMIC") {
			$result = $this->_update_user_dynamic($user_id, $data, $params);
		}
		return $result;
	}

	/**
	* Search user(s) by given params (main difference from _user() is that $user_id is unknown when searching)
	*/
	function _search_user($params, $fields = array(), $return_sql = false) {
		$params = $this->_cleanup_input_params($params);
		// Params required here
		if (empty($params)) {
			return false;
		}
		$fields = $this->_cleanup_input_fields($fields, "short");

		$result = false;
		if ($this->MODE == "SIMPLE") {
			$result = $this->_search_user_simple($params, $fields, $return_sql);
		} elseif ($this->MODE == "DYNAMIC") {
			$result = $this->_search_user_dynamic($params, $fields, $return_sql);
		}
		return $result;
	}

	/**
	* Search user (simple)
	*/
	function _search_user_simple($params = array(), $fields = array(), $return_sql = false) {
		// Create additional SQL from "params" array
		$add_sql = $this->_create_add_sql($params);
		if (!strlen($add_sql)) {
			return false;
		}
		$sql = "";
		// Fields is just simple array
		if (is_array($fields)) {
			$sql = "SELECT id,".implode(",", $fields)." FROM ".db('user')." WHERE 1 ".$add_sql;
		// Fields is a group name
		} elseif (is_string($fields)) {
			// We do not need to enumerate fields in "full" mode, we could just pass * here
			if ($fields == "full") {
				$sql = "SELECT * FROM ".db('user')." WHERE 1 ".$add_sql;
			} else {
				$_fields = $this->_fields_groups[$fields];
				$sql = "SELECT id".($_fields ? ", ".implode(",", $_fields)."" : "")." FROM ".db('user')." WHERE 1 ".$add_sql;
			}
		}
		$sql = str_replace(array("WHERE 1 AND", "WHERE 1  AND"), "WHERE", $sql);
		// Prepare LIMIT
		$params["LIMIT"]	= intval($params["LIMIT"]);
		$params["OFFSET"]	= intval($params["OFFSET"]);
		if ($params["LIMIT"] > 0) {
			if ($params["OFFSET"] > 0) {
				$limit_sql = " LIMIT ".$params["OFFSET"].",".$params["LIMIT"];
			} else {
				$limit_sql = " LIMIT ".$params["LIMIT"];
			}
		}
		$sql .= $limit_sql;

		$result = false;
		if ($return_sql) {
			$result = $sql;
		} elseif (strlen($sql)) {
			$result = $this->_get_result($sql);
		}
		return $result;
	}

	/**
	* Search user (dynamic)
	*/
	function _search_user_dynamic($params = array(), $fields = array(), $return_sql = false) {
		// Create additional SQL from "params" array
		$add_sql = $this->_create_add_sql($params);
		$sql = "";

		$user_tables	= $this->_user_tables;
		reset($user_tables);
		$first_table	= current($user_tables);
		$tables_shorts	= array_flip($user_tables);

		// Process fields string macros
		if (is_string($fields)) {
			if ($fields == "full") {
				$fields = $this->_get_avail_fields();
			} else {
				$fields = $this->_fields_groups[$fields];
			}
		}
		// Do not move up!
		$table_names	= $this->_arrange_fields($fields);

//		$result_dynamic = $this->_get_dynamic_fields_data($user_id, $fields, $params, $return_sql);

		// Selects data for one or more users by specified fields
		// Creating SQL query for select data
		$id_corresp		= array();
		foreach ((array)$table_names as $_table_name => $_fields_array) {
			foreach ((array)$_fields_array as $_cur_field) {
				$fields_to_select[] = $tables_shorts[$_table_name].".".$_cur_field."";
			}
		}
/*
		// Try to add dynamic fields
		if (!empty($this->_dynamic_fields_enum)) {
			foreach ((array)$this->_dynamic_fields_enum as $_name) {
				$fields_to_select[] = $_name;
			}
		}
*/
		// 
		foreach ((array)$table_names as $_table_name => $_fields_array) {
			if ($first_table == $tables_shorts[$_table_name]) {
				continue;
			}
			// Id's in all tables must be equal
			$id_corresp[$_table_name] = $tables_shorts[$_table_name].".id=".$tables_shorts[$first_table].".id"; 
		}

		// Create common query
		$sql = "SELECT ".$tables_shorts[$first_table].".id" 
					. ($fields_to_select ? ", ".implode(", ", $fields_to_select) : "")." 
				FROM ".$first_table." AS ".$tables_shorts[$first_table];

		foreach ((array)$id_corresp as $_table_name => $v) {
			if ($_table_name == $first_table) {
				continue;
			}
			$sql .=	" NATURAL LEFT JOIN ".$_table_name." AS ".$tables_shorts[$_table_name]." \n";
		}
		if ($return_sql) {
			$sql .= $result_dynamic;
		}
		$sql .= "\n WHERE 1 ".$add_sql;

		$sql = str_replace(array("WHERE 1 AND", "WHERE 1  AND"), "WHERE", $sql);
		// Prepare LIMIT
		$params["LIMIT"]	= intval($params["LIMIT"]);
		$params["OFFSET"]	= intval($params["OFFSET"]);
		if ($params["LIMIT"] > 0) {
			if ($params["OFFSET"] > 0) {
				$limit_sql = " LIMIT ".$params["OFFSET"].",".$params["LIMIT"];
			} else {
				$limit_sql = " LIMIT ".$params["LIMIT"];
			}
		}
		$sql .= $limit_sql;

		$result = false;
		if ($return_sql) {
			$result = $sql;
		} elseif (strlen($sql)) {
			$result = $this->_get_result($sql);
			if (!empty($result_dynamic)) {
				$result = my_array_merge($result, $result_dynamic);
			}
		}
		return $result;
	}

	/**
	* Get user info(s) by id(s)
	*/
	function _get_user_info_simple($user_id = array(), $fields = "full", $params = array(), $return_sql = false) {
		$sql = "";
		// Dynamic fields group not availiable here
		if ($fields == "dynamic") {
			$fields = "full";
		}
		// Fields is just simple array
		if (is_array($fields)) {
			$sql = "SELECT id,".implode(",", $fields)." FROM ".db('user')." WHERE id IN(".implode(",", $user_id).")";
		// Fields is a group name
		} elseif (is_string($fields)) {
			// We do not need to enumerate fields in "full" mode, we could just pass * here
			if ($fields == "full") {
				$sql = "SELECT * FROM ".db('user')." WHERE id IN(".implode(",", $user_id).")";
			} else {
				$_fields = $this->_fields_groups[$fields];
				$sql = "SELECT id".($_fields ? ", ".implode(",", $_fields)."" : "")." FROM ".db('user')." WHERE id IN(".implode(",", $user_id).")";
			}
		}
		// Create additional SQL from "params" array
		if (strlen($sql)) {
			$sql .= $this->_create_add_sql($params);
		}
		$result = false;
		if ($return_sql) {
			$result = $sql;
		} elseif (strlen($sql)) {
			$result = $this->_get_result($sql);
		}
		return $result;
	}

	/**
	* Get user info(s) by id(s)
	*/
	function _get_user_info_dynamic($user_id = array(), $fields = "full", $params = array(), $return_sql = false) {

		$user_tables	= $this->_user_tables;
		reset($user_tables);
		$first_table	= current($user_tables);
		$tables_shorts	= array_flip($user_tables);

		// Create additional SQL from "params" array
		$add_sql = $this->_create_add_sql($params);

		// Process fields string macros
		if (is_string($fields)){
			if ($fields == "full") {
				$fields = $this->_get_avail_fields();
			} else {
				$fields = $this->_fields_groups[$fields];
			}
		}
		// Do not move up!
		$table_names	= $this->_arrange_fields($fields);

		$result_dynamic = $this->_get_dynamic_fields_data($user_id, $fields, $params, $return_sql);

		// Selects data for one or more users by specified fields
		// Creating SQL query for select data
		$id_corresp		= array();
		foreach ((array)$table_names as $_table_name => $_fields_array) {
			foreach ((array)$_fields_array as $_cur_field) {
				$fields_to_select[] = $tables_shorts[$_table_name].".".$_cur_field."";
			}
		}
		// Try to add dynamic fields
		if (!empty($this->_dynamic_fields_enum)) {
			foreach ((array)$this->_dynamic_fields_enum as $_name) {
				$fields_to_select[] = $_name;
			}
		}

		// 
		foreach ((array)$table_names as $_table_name => $_fields_array) {
			if ($first_table == $tables_shorts[$_table_name]) {
				continue;
			}
			// Id's in all tables must be equal
			$id_corresp[$_table_name] = $tables_shorts[$_table_name].".id=".$tables_shorts[$first_table].".id"; 
		}

		// Create common query
		$sql = "SELECT ".$tables_shorts[$first_table].".id" 
					. ($fields_to_select ? ", ".implode(", ", $fields_to_select) : "")." 
				FROM ".$first_table." AS ".$tables_shorts[$first_table];

		foreach ((array)$id_corresp as $_table_name => $v) {
			if ($_table_name == $first_table) {
				continue;
			}
			$sql .=	" NATURAL LEFT JOIN ".$_table_name." AS ".$tables_shorts[$_table_name]." \n";
		}
		if ($return_sql) {
			$sql .= $result_dynamic;
		}
		$sql .= "\n WHERE ".$tables_shorts[$first_table].".id IN(".implode(",", (array)$user_id).")";
		$sql .= $add_sql;

		if ($return_sql) {
			return $sql;
		} else {
			$result = $this->_get_result($sql);
			// Remove unexisted users from dynamic result
			foreach ((array)$result_dynamic as $_user_id => $_v) {
				if (!isset($result[$_user_id])) {
					unset($result_dynamic[$_user_id]);
				}
			}
			if (!empty($result_dynamic)) {
				$result = my_array_merge($result, $result_dynamic);
//				foreach ((array)array_keys((array)$result) as $_user_id) {
//					$result[$_user_id] = my_array_merge($result[$_user_id], $result_dynamic[$_user_id]);
//				}
			}
		}
		return $result;
	}

	/*
	* Do multiple actions
	*/
	function _get_dynamic_fields_data ($user_id, $fields, $params = array(), $return_sql = false) {
		if (empty($user_id)) {
			return false;
		}
		$this->_dynamic_fields_enum = false;

		if ($fields == "all" || $fields == "full") {
			$fields = $this->_avail_dynamic_fields;
		}

		if (!is_array($fields) || empty($fields)) {
			return false;
		}

		$_dynamic_fields = array();
		foreach ((array)$this->_avail_dynamic_fields as $_field_id => $_field_name) {
			if (in_array($_field_name, $fields)) {
				$_dynamic_fields[$_field_id] = $_field_name;
				$this->_dynamic_fields_enum[$_field_name] = "IFNULL(__d_".$_field_name.".".$_field_name.",'') AS ".$_field_name."";
			}
		}
		$cache_name = "cache_dynamic_fields_all";
		if (!empty($_dynamic_fields)) {
			// Create SQL for use in other queries
			if ($return_sql) {
				$sql = "";
				foreach ((array)$_dynamic_fields as $_field_id => $_field_name) {
					$sql .= "\nNATURAL LEFT JOIN ( 
						SELECT user_id AS id
							, IFNULL(value, '') AS ".$_field_name." 
						FROM ".db('user_data_info_values')." 
						WHERE field_id = ".$_field_id." 
							AND user_id IN(".implode(",", $user_id).")
					) AS __d_".$_field_name."\n";
				}
				return $sql;
			// Just get given fields values
			} else {
				$this->_dynamic_fields_enum = false;

				$result = false;

				$sql = "SELECT user_id AS id
							,field_id
							, IFNULL(value, '') AS value 
						FROM ".db('user_data_info_values')." 
						WHERE field_id IN(".implode(",", array_keys($_dynamic_fields)).") 
							AND user_id IN(".implode(",", $user_id).")";

				// Get from cache
				if ($this->CACHE_IN_MEMORY && isset($this->$cache_name[$sql])) {
					return $this->$cache_name[$sql];
				}
				$Q = db()->query($sql);
				while ($A = db()->fetch_assoc($Q)) {
					$result[$A["id"]]["id"] = $A["id"];
					$result[$A["id"]][$this->_avail_dynamic_fields[$A["field_id"]]] = $A["value"];
				}
				// Restore empty dynamic fields inside result (Do not move up!)
				foreach ((array)$user_id as $_user_id) {
					foreach ((array)$_dynamic_fields as $_field_id => $_field_name) {
						if (isset($result[$_user_id][$_field_name])) {
							continue;
						}
						$result[$_user_id][$_field_name] = "";
					}
				}
				// Put into cache
				if ($this->CACHE_IN_MEMORY && !isset($this->$cache_name)) {
					$this->$cache_name[$sql] = $result;
				}
				return $result;
			}
		}
		return false;
	}

	/**
	* Update user info by id (single table)
	*/
	function _update_user_simple($user_id, $data = array(), $params = array()) {
		// Currently use only WHERE condition
		$params = $params["WHERE"] ? array("WHERE" => $params["WHERE"]) : false;
		// Create additional SQL from "params" array
		$add_sql = $this->_create_add_sql($params);

		foreach ((array)$user_id as $_user_id) {
			if (db()->query_num_rows("SELECT id FROM ".db('user')." WHERE id=".intval($_user_id))) {
				db()->UPDATE("user", _es($data), "id=".intval($_user_id)." ".$add_sql);
			} else {
				$data_to_insert = $data;
				$data_to_insert["id"] = $_user_id;
				db()->INSERT("user", _es($data_to_insert));
			}
		}
		$result = true; // Temporary
		return $result;
	}

	/**
	* Update user info by id (dynamic table)
	*/
	function _update_user_dynamic($user_id = array(), $data = array(), $params = array()) {
		// Array that defines tables in which user info stored
		$user_tables = $this->_user_tables;

		$tables_shorts = array_flip($user_tables);
		$fields = array_keys($data);

		// Check for all tables have record with this id
		$cache_name = "cache_tables_exists";
		$users_not_in_cache = $user_id;
// TODO: complete caching here
		if (!empty($users_not_in_cache)) {
			$sql = array();
			foreach ((array)$user_tables as $_table_name) {
				$sql[$_table_name] = "SELECT id, '".$tables_shorts[$_table_name]."' AS cur_table FROM ".$_table_name." WHERE id IN (".implode(",",(array)$users_not_in_cache).")";
			}
			$sql = "( ". implode(" ) UNION ALL ( ", $sql). " )";

			$Q = db()->query($sql);	
			while($A = db()->fetch_assoc($Q)) {
				$table_id_exists[$A["id"]][$A["cur_table"]] = $A["cur_table"];
			}
		}

		// Iterate over users array and insert missing records
		foreach ((array)$user_id as $_user_id) {
			$tables_to_repair = $user_tables;
			foreach ((array)$tables_to_repair as $k => $v) {
				if (isset($table_id_exists[$_user_id][$k])) {
					unset($tables_to_repair[$k]);
				}
			}
			foreach ((array)$tables_to_repair as $_table_name) {
				db()->INSERT($_table_name, array("id" => intval($_user_id)));
			}
		}

		// Get dynamic values
		$sql = "SELECT user_id, field_id, value FROM ".db('user_data_info_values')." WHERE user_id IN(".implode(",", (array)$user_id).")";
		$Q = db()->query($sql);			
		while ($A = db()->fetch_assoc($Q)) {
			$_user_avail_dynamic_fields[$A["user_id"]][$A["field_id"]] = $A["value"];
		}

		// Iterate over users array and insert missing records
		foreach ((array)$user_id as $_user_id) {
			$fields_to_insert = $this->_avail_dynamic_fields;
			foreach ((array)$fields_to_insert as $k => $v) {
				if (isset($_user_avail_dynamic_fields[$_user_id][$k])) {
					unset($fields_to_insert[$k]);
				}
			}
			foreach ((array)$fields_to_insert as $v) {
				db()->INSERT(db('user_data_info_values'), array(
					"user_id"	=> intval($_user_id), 
					"field_id"	=> _es($this->_avail_dynamic_fields_ids[$v])
				));
			}
		}

		$table_names = $this->_arrange_fields($fields, $user_tables);

		// Update user record in each table
		foreach ((array)$user_id as $_user_id) {
			foreach ((array)$table_names as $_table_name => $field_array) {
				$curr_table_data = array_intersect_key($data, array_flip($field_array));
				if (!$curr_table_data) {
					continue;
				}
				db()->UPDATE($_table_name, _es($curr_table_data), "id = ".intval($_user_id));
			}
		}

		$_dynamic_fields = array();
		foreach ((array)$this->_avail_dynamic_fields as $_field_id => $_field_name) {
			if (in_array($_field_name, $fields)) {
				$_dynamic_fields[$_field_id] = $_field_name;
			}
		}

		foreach ((array)$user_id as $_user_id) {
			foreach ((array)$data as $_field_name => $val) {
				if (!in_array($_field_name, $_dynamic_fields)){
					continue;
				}
				$_field_id = $this->_avail_dynamic_fields_ids[$_field_name];
				// Check if value not changed, so we could skip this record for UPDATE
				if (isset($_user_avail_dynamic_fields[$_user_id][$_field_id]) && $_user_avail_dynamic_fields[$_user_id][$_field_id] == $val) {
					continue;
				}
				db()->UPDATE(db('user_data_info_values'), array(
					"value"	=> _es($val),
				), "user_id=".intval($_user_id)." AND field_id='".$this->_avail_dynamic_fields_ids[$_field_name]."'");
			}
		}
		return true;
	}

	/**
	* Get columns for user table
	*/
	function _get_fields_map_simple() {
		$cache_name = "fields_map_simple";
		// Get from local cache
		if ($this->CACHE_IN_MEMORY && isset($this->$cache_name)) {
			return $this->$cache_name;
		}
		// Create array of fields in tables
		if (main()->USE_SYSTEM_CACHE) {
			$db_cols = cache()->get($cache_name);
		}
		if (empty($db_cols)) {
			$Q = db()->query("SHOW COLUMNS FROM ".db('user')."");
			while ($A = db()->fetch_assoc($Q)) {
				if ($A["Field"] == "id") {
					continue;
				}	
				$db_cols[$A["Field"]] = $A["Field"];
			}
			if (main()->USE_SYSTEM_CACHE) {
				cache()->put($cache_name, $db_cols);
			}
		}
		// Put to local cache
		if ($this->CACHE_IN_MEMORY && !isset($this->$cache_name)) {
			$this->$cache_name	= $db_cols;
		}

		return $db_cols;
	}

	/**
	* Creates user tables map(tables->fields)
	*/
	function _get_fields_map_dynamic($plain_array = false) {
		$cache_name = "fields_map_dynamic";
		// Get from local cache
		if ($this->CACHE_IN_MEMORY && isset($this->$cache_name)) {
			if ($plain_array) {
				$_avail_fields = array();
				foreach ((array)$this->$cache_name as $table_name => $fields) {
					foreach ((array)$fields as $k => $v) {
						$_avail_fields[$k] = $v;
					}
				}
				return $_avail_fields;
			}
			return $this->$cache_name;
		}
		// Create array of fields in tables
		if (main()->USE_SYSTEM_CACHE) {
			$db_cols = cache()->get($cache_name);
		}
		if (empty($db_cols)) {
			foreach ((array)$this->_user_tables as $_table_name) {

				$Q = db()->query("SHOW COLUMNS FROM ".$_table_name."");
				while ($A = db()->fetch_assoc($Q)) {
					if ($A["Field"] == "id") {
						continue;
					}	
					$db_cols[$_table_name][$A["Field"]] = $A["Field"];
				}
			}
			foreach ((array)$this->_avail_dynamic_fields as $_field_name) {
				$db_cols["_dynamic"][$_field_name] = $_field_name;
			}
			if (main()->USE_SYSTEM_CACHE) {
				cache()->put($cache_name, $db_cols);
			}
		}
		// Put to local cache
		if ($this->CACHE_IN_MEMORY && !isset($this->$cache_name)) {
			$this->$cache_name	= $db_cols;
		}
		// Convert into plain array
		if ($plain_array) {
			$_avail_fields = array();
			foreach ((array)$db_cols as $table_name => $fields) {
				foreach ((array)$fields as $k => $v) {
					$_avail_fields[$k] = $v;
				}
			}
			return $_avail_fields;
		}
		return $db_cols;
	}

	/**
	* Build tables->fields map from void fields array
	*/
	function _arrange_fields($fields) {
		$db_cols = $this->_get_fields_map_dynamic();
		// Determine which field is in which table
		foreach ((array)$fields as $field){
			foreach ((array)$this->_user_tables as $_table_name){
				if (in_array($field, (array)$db_cols[$_table_name])) {
					$table_names[$_table_name][$field] = $field;
				}
			}
		}
		return $table_names;
	}

	/*
	* Create additional SQL from '$params' array
	*/
	function _create_add_sql($params) {
		$tables_shorts	= array_flip($this->_user_tables);
		$add_sql = " ";
		if (empty($params)) {
			return "";
		}
		foreach ((array)$params as $mod => $val) {
			$mod = strtoupper($mod);
			if (!in_array($mod, $this->_allowed_sql_params)) {
				continue;
			}
			if (in_array($mod, array("LIMIT", "OFFSET"))) {
				continue;
			}
			if ($mod == "WHERE") {
				$add_sql .= "AND ";
			} else {
				$add_sql .= $mod." ";
			}
			if ($this->MODE != "SIMPLE") {
				if (is_array($val)) {
					$table_names = $this->_arrange_fields(array_keys($val), $this->_user_tables);
					$i = count($val);
					foreach ((array)$val as $fld => $v) {
						foreach ((array)$table_names as $_table_name => $fields) {
							if (in_array($fld, $fields)) {
								$table = $_table_name;
								break;
							}
						}
						if (strpos($v, " ")) {
						// $v is a statement ("LIKE value%")
							$add_sql .= " ".$tables_shorts[$table].".".$fld." ".$v." "; 
						} else {
						// $v is the value
							$add_sql .= " ".$tables_shorts[$table].".".$fld."='".$v."' ";
						}
						if (--$i) {
							$add_sql .= "AND";
						}
					}
				} elseif (!$val) {
					$add_sql .= "";
				} else {
				// for constructions like "ORDER BY field"
					$table_names = $this->_arrange_fields($val, $this->_user_tables);
					foreach ((array)$table_names as $_table_name => $fields) {
						if (in_array($val, $fields)) {
							$table = $_table_name;
							break;
						}
					}
					$add_sql .= " ".$tables_shorts[$table].".".$val." ";
				}	
			} else {
				if (is_array($val)) {
					$i = count($val);
					foreach ((array)$val as $fld => $v) {
						if (strpos($v, " ")) {
						// $v is a statement ("LIKE value%")
							$add_sql .= " ".$fld." ".$v." "; 
						} else {
						// $v is the value
							$add_sql .= " ".$fld."='".$v."' ";
						}
						if (--$i) {
							$add_sql .= "AND";
						}
					}
				} elseif (!$val) {
					$add_sql .= "";
				} else {
				// for constructions like "ORDER BY field"
					$add_sql .= " ".$val." ";
				}	
			}
		}
		return $add_sql;
	}

	/*
	* Do multiple actions
	*/
	function _get_result($sql) {
		// Get from local cache
		if (isset($this->_cache_get_result[$sql])) {
			return $this->_cache_get_result[$sql];
		}
		$result = false;
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$result[$A["id"]] = $A;
		}
		// Put into local cache
		$this->_cache_get_result[$sql] = $result;

		return $result;
	}

	/**
	* Cleanup input "user_id" field
	*/
	function _cleanup_input_user_id($user_id = 0) {
		// Common checks here
		if (!$user_id || !(is_array($user_id) || is_numeric($user_id) || is_string($user_id))) {
			return false;
		}
		// Users are comma-separated string list
		if (!is_array($user_id) && false !== strpos($user_id, ",")) {
			$_tmp = array();
			foreach ((array)explode(",", $user_id) as $_user_id) {
				$_user_id = intval($_user_id);
				if ($_user_id <= 0) {
					continue;
				}
				$_tmp[$_user_id] = $_user_id;
			}
			$user_id = $_tmp;
			unset($_tmp);
		}
		// Cleanup user_ids
		if (is_array($user_id)) {
			$_tmp = array();
			foreach ((array)$user_id as $_user_id) {
				$_user_id = intval($_user_id);
				if ($_user_id <= 0) {
					continue;
				}
				$_tmp[$_user_id] = $_user_id;
			}
			$user_id = $_tmp;
			unset($_tmp);
			if ($this->GET_USERS_LIMIT && count($user_id) >= $this->GET_USERS_LIMIT) {
				trigger_error("USER_DATA: huge number of user_ids passed (".count($user_id)."), cannot handle them all, slice them up to ".$this->GET_USERS_LIMIT." entries", E_USER_WARNING);
				$user_id = array_slice($user_id, 0, $this->GET_USERS_LIMIT);
			}
		}
		if (!is_array($user_id)) {
			$user_id = intval($user_id);
		}
		return $user_id;
	}

	/**
	* Cleanup input "fields" field
	*/
	function _cleanup_input_fields($fields = "", $def_value = "full") {
		// Fields are comma-separated string list
		if (is_string($fields) && false !== strpos($fields, ",")) {
			$_tmp = array();
			foreach ((array)explode(",", $fields) as $_field) {
				$_field = trim($_field);
				if (!strlen($_field)) {
					continue;
				}
				$_tmp[$_field] = $_field;
			}
			$fields = $_tmp;
			unset($_tmp);
		}
		// Cleanup fields array
		if (is_array($fields)) {
			$_tmp = array();
			foreach ((array)$fields as $_field) {
				$_field = trim($_field);
				if (!strlen($_field)) {
					continue;
				}
				$_tmp[$_field] = $_field;
			}
			$fields = $_tmp;
			unset($_tmp);
		}
		// Default fields group
		if (empty($fields) || (!is_array($fields) && !in_array($fields, array_keys($this->_fields_groups)))) {
			$fields = $def_value;
		}
		// Remove non-existed fields from query
		if (is_array($fields)) {
			$avail_fields = $this->_get_avail_fields();
			foreach ((array)$fields as $k => $v) {
				if (is_numeric($k) || !isset($avail_fields[$v])) {
					unset($fields[$k]);
				}
			}
			if (empty($fields)) {
				$fields = $def_value;
			}
		}
		return $fields;
	}

	/**
	* Cleanup input "params" field
	*/
	function _cleanup_input_params($params = array()) {
		if (empty($params)) {
			return false;
		}
		if (is_numeric($params)) {
			return false;
		}
		// Allows params as comma-separated pairs ("active = 1, group = 2")
		// Allowed symbols: "=" "!=" ">" "<"
		if (is_string($params)) {
			$_tmp = array();
			foreach (explode(",", $params) as $item) {
				$k = "";
				$v = "";
				if (false !== strpos($item, "=")) {
					list($k, $v) = explode("=", $item);
				}
				$k = trim($k);
				$v = trim($v);
				if (!strlen($k)) {
					continue;
				}
				$_tmp[$k] = $v;
			}
			$params = array("WHERE" => $_tmp);
		}
		if (!is_array($params)) {
			return false;
		}
		foreach ((array)$params as $sql_param => $sql_value) {
			// Check values
			if (is_array($sql_value)) {
				foreach ((array)$sql_value as $k => $v) {
					if (empty($k)) {
						if (isset($params[$sql_param][$k])) {
							unset($params[$sql_param][$k]);
						}
					}
				}
			} elseif (is_string($sql_value) || is_numeric($sql_value) || is_float($sql_value)) {
				// nothing yet
			}
			$sql_value = $params[$sql_param];
			if (empty($sql_param) || !in_array($sql_param, $this->_allowed_sql_params) || empty($sql_value)) {
				unset($params[$sql_param]);
			}
		}
		if (!$params["LIMIT"] && !$params["OFFSET"] && $params["LIMIT"] != -1) {
			$params["LIMIT"]	= $this->DEF_RECORDS_LIMIT;
			$params["OFFSET"]	= 0;
		}
		return $params;
	}

	/**
	* Cleanup input "data" field
	*/
	function _cleanup_input_data($data = array()) {
		if (empty($data) || !is_array($data)) {
			return false;
		}
		// Cleanup data array
		$_tmp = array();
		foreach ((array)$data as $k => $v) {
			$k = trim($k);
			if (!strlen($k)) {
				continue;
			}
			$_tmp[$k] = $v;
		}
		$data = $_tmp;
		unset($_tmp);
		// Remove non-existed fields from query
		$avail_fields = $this->_get_avail_fields();
		foreach ((array)$data as $k => $v) {
			if (!isset($avail_fields[$k])) {
				unset($data[$k]);
			}
		}
		// Last check
		if (empty($data) || !is_array($data)) {
			return false;
		}
		return $data;
	}

	/**
	* Get array of availiable fields names
	*/
	function _get_avail_fields() {
		$avail_fields = array();
		if ($this->MODE == "SIMPLE") {
			$avail_fields = $this->_get_fields_map_simple();
		} else {
			$avail_fields = $this->_get_fields_map_dynamic(true);
		}
		return $avail_fields;
	}
}
