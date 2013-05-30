<?php

class profy_manage_sphinx {

	/** @var */
	var $CONF_PATH			= "/usr/local/sphinx/etc/";
	/** @var */
	var $BIN_PATH			= "/usr/local/sphinx/bin/";
	/** @var */
	var $DATA_PATH			= "/usr/local/sphinx/data/";
	/** @var */
	var $LOG_PATH			= "/usr/local/sphinx/log/";
	/** @var */
	var $INDEXER_NAME		= 'indexer';
	/** @var */
	var $SEARCHD_NAME		= 'searchd';
	/** @var */
	var $MAX_MATCHES		= 1000;
	/** @var */
	var $CONNECT_RETRIES	= 3;
	/** @var */
	var $CONNECT_WAIT_TIME	= 300;
	/** @var */
	var $SPHINX_TABLE		= "sphinx";
	/**	@var */
	var $USE_STOP_WORDS		= false;
	/** @var */
	var $MEM_LIMIT			= 32; // In megabytes
	/** @var Add current configuration to the current config or replace it completely */
	var $REPLACE_CONFIG		= true;
	/** @var */
	var $WIN32_SERVICE		= "sphinxsearch";

	/**
	* Framework constructor
	*/
	function _init() {
		$this->SPHINX_TABLE = DB_PREFIX. $this->SPHINX_TABLE;

		if (OS_WINDOWS) {
			$this->CONF_PATH	= "d:/www/sphinx/";
			$this->BIN_PATH		= "d:/www/sphinx/bin/";
			$this->DATA_PATH	= "d:/www/sphinx/data/";
			$this->LOG_PATH		= "d:/www/sphinx/log/";
			$this->WIN32_SERVICE= "sphinxsearch";
		}
	}

	/**
	* Default method
	*/
	function show() {
// TODO: more user-friendly interface :-)

		$this->_update_config();
	}

	/**
	* Get used countries from system sites
	*/
	function _get_countries() {
		if (isset($this->_countries)) {
			return $this->_countries;
		}
		$countries = array();
		$Q = db()->query("SELECT DISTINCT(`country`) AS `code` FROM `".db('sites')."` WHERE `country` != '' AND `active`='1' ORDER BY `country` ASC");
		while ($A = db()->fetch_assoc($Q)) {
			$countries[$A["code"]] = $A["code"];
		}
		// Cache this
		$this->_countries = $countries;

		return $countries;
	}

	/**
	* Current config creation here
	*/
	function _current_config() {
		$config_data = array();

		$source_main = array(
			array("type",		"mysql"),
			array("sql_host",	DB_HOST),
			array("sql_user",	DB_USER),
			array("sql_pass",	DB_PSWD),
			array("sql_db",		DB_NAME),
			array("sql_port",	3306),
		);

		$config_data = array(
			"indexer" => array(
				array("mem_limit",			$this->MEM_LIMIT . "M"),
			),
			"searchd" => array(
				array("log",				$this->LOG_PATH . "searchd.log"),
				array("query_log",			$this->LOG_PATH . "query.log"),
				array("read_timeout",		"5"),
				array("max_children",		"200"),
				array("pid_file",			$this->LOG_PATH . "searchd.pid"),
				array("max_matches",		(string) $this->MAX_MATCHES),
			),
		);
/*
		$source_homes = array(
			// prevent main table locking when indexing
			array("sql_query_range",	"SELECT MIN(id), MAX(id) FROM %%DB_TABLE_VERTICAL%%"),
			array("sql_range_step",		5000),
			array("sql_query",			
				"SELECT id, title, content, date, site_id, source_id, city, region, 
					price, type, floor_area, bedrooms, bathrooms, 
					(price / floor_area) AS sq_price, IF(pictures > 0, 1, 0) AS with_photos 
				FROM %%DB_TABLE_VERTICAL%% 
				WHERE id >= \$start AND id <= \$end 
			"),
			array("sql_query_info",		"SELECT id, title, content FROM %%DB_TABLE_VERTICAL%% WHERE id=\$id"),
			array("sql_attr_timestamp",	"date"),
			array("sql_attr_uint",		"site_id"),
			array("sql_attr_uint",		"source_id"),
			array("sql_attr_uint",		"city"),
			array("sql_attr_uint",		"region"),
			array("sql_attr_uint",		"price"),
			array("sql_attr_uint",		"type"),
			array("sql_attr_uint",		"floor_area"),
			array("sql_attr_uint",		"bedrooms"),
			array("sql_attr_uint",		"bathrooms"),
			array("sql_attr_float",		"sq_price"),
			array("sql_attr_bool",		"with_photos"),
		);

		$source_cars = array(
			// prevent main table locking when indexing
			array("sql_query_range",	"SELECT MIN(id), MAX(id) FROM %%DB_TABLE_VERTICAL%%"),
			array("sql_range_step",		5000),
			array("sql_query",			
				"SELECT id, title, content, date, site_id, source_id, city, region,
					price, make, model, fuel, year, doors, mileage, IF(pictures > 0, 1, 0) AS with_photos
				FROM %%DB_TABLE_VERTICAL%%
				WHERE id >= \$start AND id <= \$end
			"),
			array("sql_query_info",		"SELECT id, title, content FROM %%DB_TABLE_VERTICAL%% WHERE id=\$id"),
			array("sql_attr_timestamp",	"date"),
			array("sql_attr_uint",		"site_id"),
			array("sql_attr_uint",		"source_id"),
			array("sql_attr_uint",		"city"),
			array("sql_attr_uint",		"region"),
			array("sql_attr_uint",		"price"),
			array("sql_attr_uint",		"make"),
			array("sql_attr_uint",		"model"),
			array("sql_attr_uint",		"fuel"),
			array("sql_attr_uint",		"year"),
			array("sql_attr_uint",		"doors"),
			array("sql_attr_uint",		"mileage"),
			array("sql_attr_bool",		"with_photos"),
		);

		$source_jobs = array(
			// prevent main table locking when indexing
			array("sql_query_range",	"SELECT MIN(id), MAX(id) FROM %%DB_TABLE_VERTICAL%%"),
			array("sql_range_step",		5000),
			array("sql_query",			
				"SELECT id, title, content, date, site_id, source_id, city, region,
					salary
				FROM %%DB_TABLE_VERTICAL%%
				WHERE id >= \$start AND id <= \$end
			"),
			array("sql_query_info",		"SELECT id, title, content FROM %%DB_TABLE_VERTICAL%% WHERE id=\$id"),
			array("sql_attr_timestamp",	"date"),
			array("sql_attr_uint",		"site_id"),
			array("sql_attr_uint",		"source_id"),
			array("sql_attr_uint",		"city"),
			array("sql_attr_uint",		"region"),
			array("sql_attr_uint",		"salary"),
		);

		$index_share = array(
			array("docinfo",			"extern"),
			array("morphology",			"none"),
			array("stopwords",			(file_exists($this->CONF_PATH . "sphinx_stopwords.txt") && $this->USE_STOP_WORDS) ? $this->CONF_PATH . "sphinx_stopwords.txt" : ""),
			array("min_word_len",		"2"),
			array("charset_type",		"utf-8"),
			array("charset_table",		"U+FF10..U+FF19->0..9, 0..9, U+FF41..U+FF5A->a..z, U+FF21..U+FF3A->a..z, A..Z->a..z, a..z, U+0149, U+017F, U+0138, U+00DF, U+00FF, U+00C0..U+00D6->U+00E0..U+00F6, U+00E0..U+00F6, U+00D8..U+00DE->U+00F8..U+00FE, U+00F8..U+00FE, U+0100->U+0101, U+0101, U+0102->U+0103, U+0103, U+0104->U+0105, U+0105, U+0106->U+0107, U+0107, U+0108->U+0109, U+0109, U+010A->U+010B, U+010B, U+010C->U+010D, U+010D, U+010E->U+010F, U+010F, U+0110->U+0111, U+0111, U+0112->U+0113, U+0113, U+0114->U+0115, U+0115, U+0116->U+0117, U+0117, U+0118->U+0119, U+0119, U+011A->U+011B, U+011B, U+011C->U+011D, U+011D, U+011E->U+011F, U+011F, U+0130->U+0131, U+0131, U+0132->U+0133, U+0133, U+0134->U+0135, U+0135, U+0136->U+0137, U+0137, U+0139->U+013A, U+013A, U+013B->U+013C, U+013C, U+013D->U+013E, U+013E, U+013F->U+0140, U+0140, U+0141->U+0142, U+0142, U+0143->U+0144, U+0144, U+0145->U+0146, U+0146, U+0147->U+0148, U+0148, U+014A->U+014B, U+014B, U+014C->U+014D, U+014D, U+014E->U+014F, U+014F, U+0150->U+0151, U+0151, U+0152->U+0153, U+0153, U+0154->U+0155, U+0155, U+0156->U+0157, U+0157, U+0158->U+0159, U+0159, U+015A->U+015B, U+015B, U+015C->U+015D, U+015D, U+015E->U+015F, U+015F, U+0160->U+0161, U+0161, U+0162->U+0163, U+0163, U+0164->U+0165, U+0165, U+0166->U+0167, U+0167, U+0168->U+0169, U+0169, U+016A->U+016B, U+016B, U+016C->U+016D, U+016D, U+016E->U+016F, U+016F, U+0170->U+0171, U+0171, U+0172->U+0173, U+0173, U+0174->U+0175, U+0175, U+0176->U+0177, U+0177, U+0178->U+00FF, U+00FF, U+0179->U+017A, U+017A, U+017B->U+017C, U+017C, U+017D->U+017E, U+017E, U+4E00..U+9FFF"),
			array("min_prefix_len",		"0"),
			array("min_infix_len",		"0"),
		);

		// Get used countries from system sites
		$countries = $this->_get_countries();

		$sources = array("source source_main" => $source_main);
		foreach ((array)$countries as $code) {
			$tmp = $source_homes;
			foreach ((array)$tmp as $k => $v) {
				$tmp[$k][1] = str_replace("%%DB_TABLE_VERTICAL%%", DB_PREFIX."homes_".$code, $v[1]);
			}
			$sources["source source_homes_".$code." : source_main"]	= $tmp;

			$tmp = $source_cars;
			foreach ((array)$tmp as $k => $v) {
				$tmp[$k][1] = str_replace("%%DB_TABLE_VERTICAL%%", DB_PREFIX."cars_".$code, $v[1]);
			}
			$sources["source source_cars_".$code." : source_main"]	= $tmp;

			$tmp = $source_jobs;
			foreach ((array)$tmp as $k => $v) {
				$tmp[$k][1] = str_replace("%%DB_TABLE_VERTICAL%%", DB_PREFIX."jobs_".$code, $v[1]);
			}
			$sources["source source_jobs_".$code." : source_main"]	= $tmp;
		}

		$indexes = array();
		foreach ((array)$countries as $code) {
			$indexes["index homes_".$code]	= array_merge(array(
				array("source",	"source_homes_".$code),
				array("path",	$this->DATA_PATH . "index_homes_".$code),
			), $index_share);

			$indexes["index cars_".$code]	= array_merge(array(
				array("source",	"source_cars_".$code),
				array("path",	$this->DATA_PATH . "index_cars_".$code),
			), $index_share);

			$indexes["index jobs_".$code]	= array_merge(array(
				array("source",	"source_jobs_".$code),
				array("path",	$this->DATA_PATH . "index_jobs_".$code),
			), $index_share);
		}
*/
		$config_data = array_merge($sources, $indexes, $config_data);

		return $config_data;
	}

	/**
	* Do update config and start/restart sphinx
	*/
	function _update_config($name = "", $no_indexing = false, $no_write = false) {
		$conf_file_path = $this->CONF_PATH. "sphinx.conf";
		if (file_exists($conf_file_path) && !is_readable($conf_file_path)) {
			return _e("Error!. File ".$conf_file_path." is not readable! Please check permissions.");
		}

		$config_object = new sphinx_config($this->CONF_PATH . "sphinx.conf");

		$non_unique = array(
			"sql_group_column"			=> true,
			"sql_date_column"			=> true,
			"sql_str2ordinal_column"	=> true,
			"sql_attr_uint" 			=> true,
			"sql_attr_float" 			=> true,
			"sql_attr_bool" 			=> true,
			"sql_attr_string" 			=> true,
			"sql_attr_multi" 			=> true,
			"sql_attr_timestamp"		=> true,
			"sql_attr_str2ordinal"		=> true,
			"sql_query_pre"				=> true,
			"sql_field_string"			=> true,
		);

		$config_data = $this->_current_config();

		// Empty current loaded config
		if ($this->REPLACE_CONFIG) {
			$config_object->sections = null;
		}

		foreach ((array)$config_data as $section_name => $section_data) {
			$section = &$config_object->get_section_by_name($section_name);
			if (!$section) {
				$section = &$config_object->add_section($section_name);
			}

			foreach ((array)$non_unique as $key => $void) {
				$section->delete_variables_by_name($key);
			}

			foreach ((array)$section_data as $entry) {
				$key = $entry[0];
				$value = $entry[1];

				if (!isset($non_unique[$key])) {
					$variable = &$section->get_variable_by_name($key);
					if (!$variable) {
						$variable = &$section->create_variable($key, $value);
					} else {
						$variable->set_value($value);
					}
				} else {
					$variable = &$section->create_variable($key, $value);
				}
			}
		}

		if ($no_write) {
			return $config_object->to_string();
		}
		if (file_exists($conf_file_path) && !is_writeable($conf_file_path)) {
			return _e("Error!. File ".$conf_file_path." is not writeable! Please check permissions.")
				."\n".$config_object->to_string();
		}

		$config_object->write($conf_file_path);

		if (!$no_indexing) {
			$this->_shutdown_searchd();
			$this->_create_index($name);
			$this->_start_searchd();
		}

		return true;
	}

	/**
	* Destroy old cache entries and create new if said
	*/
	function _tidy($create = false, $single_index = "") {
		$indexes = "--all";
		if ($single_index) {
			$indexes = $single_index;
		}
		if ($this->_index_created() || $create) {
//			$rotate = ($this->_searchd_running()) ? " --rotate" : "";
			$rotate = " --rotate";

			$log_file_path	= $this->LOG_PATH . "indexer.log";
			$conf_file_path	= $this->CONF_PATH . "sphinx.conf";

			$cwd = getcwd();
			chdir($this->BIN_PATH);
			exec("echo ".date("Y-m-d H:i:s")." >> " . $log_file_path);
			if (OS_WINDOWS) {
				exec($this->INDEXER_NAME . $rotate . " --config ".$conf_file_path." ".$indexes." >> " . $log_file_path);
			} else {
				exec("./" . $this->INDEXER_NAME . $rotate . " --config ".$conf_file_path." ".$indexes." >> " . $log_file_path." 2>&1 &");
			}
			chdir($cwd);
		}
	}

	/**
	* Start searchd process
	*/
	function _start_searchd () {
		$pid_path = $this->LOG_PATH . "searchd.pid";
		if (!file_exists($pid_path)) {
			$this->_shutdown_searchd();

			if (OS_WINDOWS) {
				exec("net start ".$this->WIN32_SERVICE);
			} else {
				$cwd = getcwd();
				chdir($this->BIN_PATH);
				exec("./" . $this->SEARCHD_NAME . " --config " . $this->CONF_PATH . "sphinx.conf >> " . $this->LOG_PATH . "searchd-startup.log 2>&1 &");
				chdir($cwd);
			}
		}
	}

	/**
	* Kills the searchd process and makes sure there's no locks left over
	*/
	function _shutdown_searchd() {
		if (OS_WINDOWS) {
			exec("net stop ".$this->WIN32_SERVICE);
		} else {
			exec("killall -9 " . $this->SEARCHD_NAME . " >> /dev/null 2>&1 &");
		}

		$pid_path = $this->LOG_PATH . "searchd.pid";

		if (file_exists($pid_path) && is_writeable($pid_path)) {
			unlink($pid_path);
		}

		$this->_unlink_by_pattern($this->LOG_PATH, "#^index_.*\.spl$#");
	}

	/**
	* Checks whether searchd is running, if it's not running it makes sure there's no left over
	* files by calling _shutdown_searchd.
	*
	* @return	boolean	Whether searchd is running or not
	*/
	function _searchd_running() {
		$pid_path = $this->LOG_PATH . "searchd.pid";

		if (file_exists($pid_path)) {
			$pid = file_get_contents($pid_path);
		}
		if ($pid) {
			if (OS_WINDOWS) {
				if (!is_writeable($pid_path)) {
					return true;
				}
			} else {
				$output = array();
				$pidof_command = "pidof";

				exec("whereis -b pidof", $output);
				if (sizeof($output) > 1) {
					$output = explode(" ", $output[0]);
					$pidof_command = $output[1]; // 0 is pidof:
				}

				exec($pidof_command . " " . $this->SEARCHD_NAME, $output);
				if ($output && $output[0] == $pid) {
					return true;
				}
			}
		}

		// make sure it's really not running
		$this->_shutdown_searchd();

		return false;
	}

	/**
	* Create sphinx table
	*/
	function _create_index($name = "") {
		$this->_shutdown_searchd();
/*
		if (!$this->_index_created()) {
			$sql = "CREATE TABLE IF NOT EXISTS " . SPHINX_TABLE . " (
				counter_id INT NOT NULL PRIMARY KEY,
				max_doc_id INT NOT NULL
			)";
			$db->sql_query($sql);

			$sql = "TRUNCATE TABLE " . SPHINX_TABLE;
			$db->sql_query($sql);
		}
*/
		// start indexing process
		$this->_tidy(true, $name);

		$this->_shutdown_searchd();

		return false;
	}

	/**
	* Returns true if the sphinx table was created
	*/
	function _index_created($allow_new_files = true) {
		$created = false;

		foreach ((array)$this->_get_countries() as $code) {
			$i_homes	= $this->DATA_PATH . "index_homes_".$code.".spd";
			$i_cars		= $this->DATA_PATH . "index_cars_".$code.".spd";
			$i_jobs		= $this->DATA_PATH . "index_jobs_".$code.".spd";
			if (file_exists($i_homes) && file_exists($i_cars) && file_exists($i_jobs)) {
				$created = true;
			} else {
				$created = false;
				break;
			}
		}
		return $created;
	}

	/**
	* Collects stats that can be displayed on the index maintenance page
	*/
	function _get_stats() {
		$this->stats["last_searches"] = "";

		$file_path = $this->LOG_PATH . "query.log";

		if (file_exists($file_path)) {
			if (!is_readable($file_path)) {
				return _e("Error!. File ".$file_path." is not readable! Please check permissions.");
			}
			$last_searches = explode("\n", _prepare_html($this->_read_last_lines($file_path, 50)));

			$this->stats["last_searches"] = implode("\n", $last_searches);
		}
		return $this->stats;
	}

	/**
	 * Updates wordlist and wordmatch tables when a message is posted or changed
	 *
	 * @param string   $mode    Contains the post mode: edit, post, reply, quote
	 * @param int      $post_id The id of the post which is modified/created
	 * @param string   &$message   New or updated post content
	 * @param string   &$subject   New or updated post subject
	 * @param int      $poster_id  Post author's user id
	 * @param int      $forum_id   The id of the forum in which the post is located
	 *
	 * @access   public
	 */
	function _index($mode, $post_id, &$message, &$subject, $poster_id, $forum_id) {
/*
		global $config, $db;

		if ($mode == 'edit')
		{
			$this->sphinx->UpdateAttributes($this->indexes, array('forum_id', 'poster_id'), array((int)$post_id => array((int)$forum_id, (int)$poster_id)));
		}
		else if ($mode != 'post' && $post_id)
		{
			// update topic_last_post_time for full topic
			$sql = 'SELECT p2.post_id
				FROM ' . POSTS_TABLE . ' p1 LEFT JOIN ' . POSTS_TABLE . ' p2 ON (p1.topic_id = p2.topic_id)
				WHERE p2.post_id = ' . $post_id;
			$result = $db->sql_query($sql);

			$post_updates = array();
			$post_time = time();
			while ($row = $db->sql_fetchrow($result))
			{
				$post_updates[(int)$row['post_id']] = array((int) $post_time);
			}
			$db->sql_freeresult($result);

			if (sizeof($post_updates))
			{
				$this->sphinx->UpdateAttributes($this->indexes, array('topic_last_post_time'), $post_updates);
			}
		}

		if ($this->_index_created())
		{
			$rotate = ($this->_searchd_running()) ? ' --rotate' : '';
	
			$cwd = getcwd();
			chdir($this->BIN_PATH);
			exec("./" . $this->INDEXER_NAME . $rotate . " --config " . $this->CONF_PATH . "sphinx.conf index_phpbb_" . $this->id . "_delta >> " . $this->LOG_PATH . "indexer.log 2>&1 &");
			chdir($cwd);
		}
*/
	}

	/**
	* Delete a post from the index after it was deleted
	*/
	function _index_remove($post_ids, $author_ids, $forum_ids) {
/*
		$values = array();
		foreach ((array)$post_ids as $post_id)
		{
			$values[$post_id] = array(1);
		}

		$this->sphinx->UpdateAttributes($this->indexes, array("deleted"), $values);
*/
	}

	/**
	* Drop sphinx table
	*/
	function _delete_index($acp_module, $u_action) {
		$this->_shutdown_searchd();

		$this->_unlink_by_pattern($this->DATA_PATH, "#^index_.*$#");

		if (!$this->_index_created()) {
			return false;
		}

		$this->_shutdown_searchd();

		return false;
	}

	/**
	* Deletes all files from a directory that match a certain pattern
	*
	* @param	string	$path		Path from which files shall be deleted
	* @param	string	$pattern	PCRE pattern that a file needs to match in order to be deleted
	*/
	function _unlink_by_pattern($path, $pattern) {
		$dir = opendir($path);
		while (false !== ($file = readdir($dir))) {
			if (is_file($path . $file) && preg_match($pattern, $file) && is_writeable($path . $file)) {
				unlink($path . $file);
			}
		}
		closedir($dir);
	}

	/**
	* Reads the last from a file
	*
	* @param	string	$file		The filename from which the lines shall be read
	* @param	int		$amount		The number of lines to be read from the end
	* @return	string				Last lines of the file
	*/
	function _read_last_lines($file, $amount) {
		if (!is_readable($file)) {
			return _e("Error!. File ".$file." is not readable! Please check permissions.");
		}
		$fp = fopen($file, 'r');
		fseek($fp, 0, SEEK_END);

		$c = '';
		$i = 0;

		while ($i < $amount) {
			fseek($fp, -2, SEEK_CUR);
			$c = fgetc($fp);
			if ($c == "\n") {
				$i++;
			}
			if (feof($fp)) {
				break;
			}
		}

		$string = fread($fp, 8192);
		fclose($fp);

		return $string;
	} 
}

/**
* sphinx_config
* An object representing the sphinx configuration
* Can read it from file and write it back out after modification
* @package search
*/
class sphinx_config
{
	var $loaded = false;
	var $sections = array();

	/**
	* Constructor which optionally loads data from a file
	*
	* @param	string	$filename	The path to a file containing the sphinx configuration
	*/
	function sphinx_config($filename = false)
	{
		if ($filename !== false && file_exists($filename))
		{
			$this->read($filename);
		}
	}

	/**
	* Get a section object by its name
	*
	* @param	string 					$name	The name of the section that shall be returned
	* @return	sphinx_config_section			The section object or null if none was found
	*/
	function &get_section_by_name($name)
	{
		for ($i = 0, $n = sizeof($this->sections); $i < $n; $i++)
		{
			// make sure this is really a section object and not a comment
			if (is_a($this->sections[$i], 'sphinx_config_section') && $this->sections[$i]->get_name() == $name)
			{
				return $this->sections[$i];
			}
		}
		$null = null;
		return $null;
	}

	/**
	* Appends a new empty section to the end of the config
	*
	* @param	string					$name	The name for the new section
	* @return	sphinx_config_section			The newly created section object
	*/
	function &add_section($name)
	{
		$this->sections[] = new sphinx_config_section($name, '');
		return $this->sections[sizeof($this->sections) - 1];
	}

	/**
	* Parses the config file at the given path, which is stored in $this->loaded for later use
	*
	* @param	string	$filename	The path to the config file
	*/
	function read($filename)
	{
		// split the file into lines, we'll process it line by line
		$config_file = file($filename);

		$this->sections = array();

		$section = null;
		$found_opening_bracket = false;
		$in_value = false;

		foreach ((array)$config_file as $i => $line)
		{
			// if the value of a variable continues to the next line because the line break was escaped
			// then we don't trim leading space but treat it as a part of the value
			if ($in_value)
			{
				$line = rtrim($line);
			}
			else
			{
				$line = trim($line);
			}
			$line = str_replace("\r", "", $line);

			// if we're not inside a section look for one
			if (!$section)
			{
				// add empty lines and comments as comment objects to the section list
				// that way they're not deleted when reassembling the file from the sections
				if (!$line || $line[0] == '#')
				{
					$this->sections[] = new sphinx_config_comment($config_file[$i]);
					continue;
				}
				else
				{
					// otherwise we scan the line reading the section name until we find
					// an opening curly bracket or a comment
					$section_name = '';
					$section_name_comment = '';
					$found_opening_bracket = false;
					for ($j = 0, $n = strlen($line); $j < $n; $j++)
					{
						if ($line[$j] == '#')
						{
							$section_name_comment = substr($line, $j);
							break;
						}

						if ($found_opening_bracket)
						{
							continue;
						}

						if ($line[$j] == '{')
						{
							$found_opening_bracket = true;
							continue;
						}

						$section_name .= $line[$j];
					}

					// and then we create the new section object
					$section_name = trim($section_name);
					$section = new sphinx_config_section($section_name, $section_name_comment);
				}
			}
			else // if we're looking for variables inside a section
			{
				$skip_first = false;

				// if we're not in a value continuing over the line feed
				if (!$in_value)
				{
					// then add empty lines and comments as comment objects to the variable list
					// of this section so they're not deleted on reassembly
					if (!$line || $line[0] == '#')
					{
						$section->add_variable(new sphinx_config_comment($config_file[$i]));
						continue;
					}
	
					// as long as we haven't yet actually found an opening bracket for this section
					// we treat everything as comments so it's not deleted either
					if (!$found_opening_bracket)
					{
						if ($line[0] == '{')
						{
							$skip_first = true;
							$line = substr($line, 1);
							$found_opening_bracket = true;
						}
						else
						{
							$section->add_variable(new sphinx_config_comment($config_file[$i]));
							continue;
						}
					}
				}

				// if we did not find a comment in this line or still add to the previous line's value ...
				if ($line || $in_value)
				{
					if (!$in_value)
					{
						$name = '';
						$value = '';
						$comment = '';
						$found_assignment = false;
					}
					$in_value = false;
					$end_section = false;

					// ... then we should prase this line char by char:
					// - first there's the variable name
					// - then an equal sign
					// - the variable value
					// - possibly a backslash before the linefeed in this case we need to continue
					//   parsing the value in the next line
					// - a # indicating that the rest of the line is a comment
					// - a closing curly bracket indicating the end of this section
					for ($j = 0, $n = strlen($line); $j < $n; $j++)
					{
						if ($line[$j] == '#')
						{
							$comment = substr($line, $j);
							break;
						}
						else if ($line[$j] == '}')
						{
							$comment = substr($line, $j + 1);
							$end_section = true;
							break;
						}
						else if (!$found_assignment)
						{
							if ($line[$j] == '=')
							{
								$found_assignment = true;
							}
							else
							{
								$name .= $line[$j];
							}
						}
						else
						{
							if ($line[$j] == '\\' && $j == $n - 1)
							{
								$value .= "\n";
								$in_value = true;
								continue 2; // go to the next line and keep processing the value in there
							}
							$value .= $line[$j];
						}
					}

					// if a name and an equal sign were found then we have append a new variable object to the section
					if ($name && $found_assignment)
					{
						$section->add_variable(new sphinx_config_variable(trim($name), trim($value), ($end_section) ? '' : $comment));
						continue;
					}

					// if we found a closing curly bracket this section has been completed and we can append it to the section list
					// and continue with looking for the next section
					if ($end_section)
					{
						$section->set_end_comment($comment);
						$this->sections[] = $section;
						$section = null;
						continue;
					}
				}

				// if we did not find anything meaningful up to here, then just treat it as a comment
				$comment = ($skip_first) ? "\t" . substr(ltrim($config_file[$i]), 1) : $config_file[$i];
				$section->add_variable(new sphinx_config_comment($comment));
			}
		}

		// keep the filename for later use
		$this->loaded = $filename;
	}

	/**
	* Writes the config data into a file
	*
	* @param	string	$filename	The optional filename into which the config data shall be written.
	*								If it's not specified it will be written into the file that the config
	*								was originally read from.
	*/
	function write($filename = false) {
		if ($filename === false && $this->loaded) {
			$filename = $this->loaded;
		}

		$data = "";
		foreach ((array)$this->sections as $section) {
			$data .= $section->to_string();
		}

		$fp = fopen($filename, 'wb');
		fwrite($fp, $data);
		fclose($fp);
	}

	/**
	* Return the config data as string
	*
	* @param	string	$filename	The optional filename into which the config data shall be written.
	*								If it's not specified it will be written into the file that the config
	*								was originally read from.
	*/
	function to_string() {
		$data = "";
		foreach ((array)$this->sections as $section) {
			$data .= $section->to_string();
		}
		return $data;
	}
}

/**
* sphinx_config_section
* Represents a single section inside the sphinx configuration
*/
class sphinx_config_section
{
	var $name;
	var $comment;
	var $end_comment;
	var $variables = array();

	/**
	* Construct a new section
	*
	* @param	string	$name		Name of the section
	* @param	string	$comment	Comment that should be appended after the name in the
	*								textual format.
	*/
	function sphinx_config_section($name, $comment)
	{
		$this->name = $name;
		$this->comment = $comment;
		$this->end_comment = '';
	}

	/**
	* Add a variable object to the list of variables in this section
	*
	* @param	sphinx_config_variable	$variable	The variable object
	*/
	function add_variable($variable)
	{
		$this->variables[] = $variable;
	}

	/**
	* Adds a comment after the closing bracket in the textual representation
	*/
	function set_end_comment($end_comment)
	{
		$this->end_comment = $end_comment;
	}

	/**
	* Getter for the name of this section
	*
	* @return	string	Section's name
	*/
	function get_name()
	{
		return $this->name;
	}

	/**
	* Get a variable object by its name
	*
	* @param	string 					$name	The name of the variable that shall be returned
	* @return	sphinx_config_section			The first variable object from this section with the
	*											given name or null if none was found
	*/
	function &get_variable_by_name($name)
	{
		for ($i = 0, $n = sizeof($this->variables); $i < $n; $i++)
		{
			// make sure this is a variable object and not a comment
			if (is_a($this->variables[$i], 'sphinx_config_variable') && $this->variables[$i]->get_name() == $name)
			{
				return $this->variables[$i];
			}
		}
		$null = null;
		return $null;
	}

	/**
	* Deletes all variables with the given name
	*
	* @param	string	$name	The name of the variable objects that are supposed to be removed
	*/
	function delete_variables_by_name($name)
	{
		for ($i = 0; $i < sizeof($this->variables); $i++)
		{
			// make sure this is a variable object and not a comment
			if (is_a($this->variables[$i], 'sphinx_config_variable') && $this->variables[$i]->get_name() == $name)
			{
				array_splice($this->variables, $i, 1);
				$i--;
			}
		}
	}

	/**
	* Create a new variable object and append it to the variable list of this section
	*
	* @param	string					$name	The name for the new variable
	* @param	string					$value	The value for the new variable
	* @return	sphinx_config_variable			Variable object that was created
	*/
	function &create_variable($name, $value)
	{
		$this->variables[] = new sphinx_config_variable($name, $value, '');
		return $this->variables[sizeof($this->variables) - 1];
	}

	/**
	* Turns this object into a string which can be written to a config file
	*
	* @return	string	Config data in textual form, parsable for sphinx
	*/
	function to_string()
	{
		$content = $this->name . " " . $this->comment . "\n{\n";

		// make sure we don't get too many newlines after the opening bracket
		while ($this->variables && trim($this->variables[0]->to_string()) == "")
		{
			array_shift($this->variables);
		}
		foreach ((array)$this->variables as $variable)
		{
			$content .= $variable->to_string();
		}
		$content .= '}' . $this->end_comment . "\n";

		return $content;
	}
}

/**
* sphinx_config_variable
* Represents a single variable inside the sphinx configuration
*/
class sphinx_config_variable
{
	var $name;
	var $value;
	var $comment;

	/**
	* Constructs a new variable object
	*
	* @param	string	$name		Name of the variable
	* @param	string	$value		Value of the variable
	* @param	string	$comment	Optional comment after the variable in the
	*								config file
	*/
	function sphinx_config_variable($name, $value, $comment)
	{
		$this->name = $name;
		$this->value = $value;
		$this->comment = $comment;
	}

	/**
	* Getter for the variable's name
	*
	* @return	string	The variable object's name
	*/
	function get_name()
	{
		return $this->name;
	}

	/**
	* Allows changing the variable's value
	*
	* @param	string	$value	New value for this variable
	*/
	function set_value($value)
	{
		$this->value = $value;
	}

	/**
	* Turns this object into a string readable by sphinx
	*
	* @return	string	Config data in textual form
	*/
	function to_string()
	{
		return "\t" . $this->name . ' = ' . str_replace("\n", "\\\n", str_replace("\r", "", $this->value)) . ' ' . $this->comment . "\n";
	}
}


/**
* sphinx_config_comment
* Represents a comment inside the sphinx configuration
*/
class sphinx_config_comment
{
	var $exact_string;

	/**
	* Create a new comment
	*
	* @param	string	$exact_string	The content of the comment including newlines, leading whitespace, etc.
	*/
	function sphinx_config_comment($exact_string)
	{
		$this->exact_string = $exact_string;
	}

	/**
	* Simply returns the comment as it was created
	*
	* @return	string	The exact string that was specified in the constructor
	*/
	function to_string()
	{
		return $this->exact_string;
	}
}
