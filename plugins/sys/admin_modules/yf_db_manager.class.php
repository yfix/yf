<?php

/**
* Simple database manager
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_db_manager {

	/** @var bool */
	public $AUTO_GET_TABLES_STATUS		= true;
	/** @var string @conf_skip */
	public $TABLES_CONSTS_PREFIX		= 'dbt_';
	/** @var bool */
	public $USE_HIGHLIGHT				= 1;
	/** @var int Number of records in extended export mode to use in one INSERT block */
	public $EXPORT_EXTENDED_PER_BLOCK	= 500;
	/** @var string Path where auto-backups will be stored */
	public $BACKUP_PATH					= 'backup_sql/';
	/** @var string Path to mysql.exe */
	public $MYSQL_CLIENT_PATH			= "d:\\www\\mysql\\bin\\";
	/** @var int Max number of backups files to store */
	public $MAX_BACKUP_FILES			= 5;

	/**
	*/
	function _init () {
/*
		$this->_boxes = array(
			'tables'		=> 'multi_select("tables",		$this->_tables_names,	$selected, false, 2, " size=10 class=small_for_select ", false)',
			'export_type'	=> 'radio_box("export_type",	$this->_export_types,	$selected ? $selected : "insert", false, 2, "", false)',
			'compress'		=> 'radio_box("compress",		$this->_compress_types,	$selected ? $selected : "gzip", false, 2, "", false)',
		);
		$this->_export_types = array(
			"insert"	=> "INSERT",
			"replace"	=> "REPLACE",
		);
		$this->_compress_types = array(
			""		=> "None",
			"gzip"	=> "Gzip",
		);
*/
	}

	/**
	*/
	function show () {
		$data = $this->_get_tables_infos();
		return table($data, ['id' => 'name', 'condensed' => 1, 'pager_records_on_page' => 10000])
// TODO: group actions: truncate, check, optimize, repair, drop(?)
			->check_box('name', ['width' => '1%'])
			->link('name', './?object='.$_GET['object'].'&action=table_show&id=%d')
			->text('rows', ['width' => '1%'])
			->text('data_size', ['width' => '1%'])
			->text('engine', ['width' => '1%'])
			->text('collation', ['width' => '1%'])
			->btn('Structure', './?object='.$_GET['object'].'&action=table_structure&id=%d')
			->btn('Export', './?object='.$_GET['object'].'&action=table_export&id=%d')
			->header_link('import sql', './?object='.$_GET['object'].'&action=import')
#			->footer_link('backup')
		;
	}

	/**
	*/
	function _get_table_name($table = '') {
		if (!$table) {
			$table = $_GET['id'];
		}
		$table = preg_replace('/[^a-z0-9_]+/ims', '', $table);
		if (defined('DB_PREFIX') && strlen(DB_PREFIX) && strlen($table) && substr($table, 0, strlen(DB_PREFIX)) == DB_PREFIX) {
			$table = substr($table, strlen(DB_PREFIX));
		}
		return $table;
	}

	/**
	*/
	function table_show() {
		$table = $this->_get_table_name($_GET['id']);
		if (!$table) {
			return _e('Wrong params');
		}
		return table2('SELECT * FROM '.db($table), ['auto_no_buttons' => 1])
			->btn_edit('', url('/@object/table_edit/%d/?table='.$table))
			->btn_delete('', url('/@object/table_delete/%d/?table='.$table))
			->footer_add('', url('/@object/table_add/'.$table))
			->auto();
	}

	/**
	*/
	function table_edit() {
		$id = intval($_GET['id']);
		$table = $this->_get_table_name($_GET['table']);
		if (!$id || !$table) {
			return _e('Wrong params');
		}
		$replace = _class('admin_methods')->edit([
			'table' 	=> $table,
			'links_add' => '&table='.$table,
			'back_link'	=> url('/@object/table_show/'.$table),
		]);
		return form2($replace)
			->auto(db($table), $id, ['links_add' => '&table='.$table]);
	}

	/**
	*/
	function table_add() {
		$table = $this->_get_table_name($_GET['id']);
		if (!$table) {
			return _e('Wrong params');
		}
		$replace = _class('admin_methods')->add([
			'table' 	=> $table,
			'links_add' => '&table='.$table,
			'back_link'	=> url('/@object/table_show/'.$table),
		]);
		return form2($replace)
			->auto(db($table), $id, ['links_add' => '&table='.$table]);
	}

	/**
	*/
	function table_delete() {
		$id = intval($_GET['id']);
		$table = $this->_get_table_name($_GET['table']);
		if (!$id || !$table) {
			return _e('Wrong params');
		}
		return _class('admin_methods')->delete(['table' => $table, 'links_add' => '&table='.$table]);
	}

	/**
	*/
	function table_structure () {
		$table = $this->_get_table_name($_GET['id']);
		if (!strlen($table)) {
			return _e('Empty table name');
		}
		$table = DB_PREFIX. $table;

		$body .= '<h1>'.$table.'</h1>';

		$body .= '<h3>'.t('Columns').'</h3>';
		$body .= table(db()->get_all('SHOW FULL COLUMNS FROM '.$table), ['auto_no_buttons' => 1])->auto();

		$body .= '<h3>'.t('Indexes').'</h3>';
		$body .= table(db()->get_all('SHOW INDEX FROM '.$table), ['auto_no_buttons' => 1])->auto();

		$body .= '<h3>'.t('SHOW CREATE TABLE').'</h3>';
		list(, $create_table) = array_values(db()->get('SHOW CREATE TABLE '.$table));
		$body .= '<pre>'._prepare_html($create_table).'</pre>';

		return $body;
	}

	/**
	*/
	function table_truncate () {
		no_graphics(true);
		if (empty($_POST['tables'])) {
			return false;
		}
		$tables = rtrim($_POST['tables'], ',');
		$tables = explode(',', $tables);
		foreach ((array)$tables as $table) {
			db()->query('TRUNCATE '.$table);
		}
// TODO: use common()->message_success()
		echo '<div class="alert alert-success">completed</div>';
	}

	/**
	*/
	function table_drop () {
		no_graphics(true);
		if (empty($_POST['tables'])) {
			return false;
		}
		$tables = rtrim($_POST['tables'], ',');
		$tables = explode(',', $tables);
		foreach ((array)$tables as $table) {
			db()->query('DROP TABLE '.$table);
		}
// TODO: use common()->message_success()
		echo '<div class="alert alert-success">completed</div>';
	}

	/**
	*/
	function table_optimize () {
		no_graphics(true);

		if (empty($_POST['tables'])) {
			return false;
		}
		$tables = rtrim($_POST['tables'], ',');

		$text .= '<table>';
		$Q = db()->query('OPTIMIZE TABLE '.$tables);
		while ($A = db()->fetch_assoc($Q)) {
			$text .= '<tr>';
			$text .= '<td>'.$A['Table'].'</td>';
			$text .= '<td>optimize</td>';
			$text .= '<td>'.$A['Msg_text'].'</td>';
			$text .= '</tr>';
		}
		$text .= '</table>';
// TODO: use common()->message_success()
		echo $text;
	}
	
	/**
	*/
	function table_check () {
		no_graphics(true);
		
		if(empty($_POST['tables'])){
			return false;
		}
		$tables = rtrim($_POST['tables'], ',');

		$text .= '<table>';
		$Q = db()->query('CHECK TABLE '.$tables);
		while ($A = db()->fetch_assoc($Q)) {
			$text .= '<tr>';
			$text .= '<td>'.$A['Table'].'</td>';
			$text .= '<td>check</td>';
			$text .= '<td>'.$A['Msg_text'].'</td>';
			$text .= '</tr>';
		}
		$text .= '</table>';
// TODO: use common()->message_success()
		echo $text;
	}
	
	/**
	*/
	function table_repair () {
		no_graphics(true);
		
		if(empty($_POST['tables'])){
			return false;
		}
		$tables = rtrim($_POST['tables'], ',');
		
		$text .= '<table>';
		$Q = db()->query('REPAIR TABLE '.$tables);
		while ($A = db()->fetch_assoc($Q)) {
			$text .= '<tr>';
			$text .= '<td>'.$A['Table'].'</td>';
			$text .= '<td>repair</td>';
			$text .= '<td>'.$A['Msg_text'].'</td>';
			$text .= '</tr>';
		}
		$text .= '</table>';
		
// TODO: use common()->message_success()
		echo $text;
	}

	/**
	*/
	function _get_tables_infos () {
		if ($this->AUTO_GET_TABLES_STATUS) {
			$Q = db()->query('SHOW TABLE STATUS LIKE "'.DB_PREFIX.'%"');
			while ($A = db()->fetch_assoc($Q)) {
				$table_name = $A['Name'];
				if (substr($table_name, 0, strlen(DB_PREFIX)) != DB_PREFIX) {
					continue;
				}
				$tables_infos[$table_name] = [
					'name'		=> $table_name,
					'engine'	=> $A['Engine'],
					'rows'		=> $A['Rows'],
					'data_size'	=> $A['Data_length'],
					'collation'	=> $A['Collation'],
				];
			}
		} else {
			$Q = db()->query('SHOW TABLES LIKE "'.DB_PREFIX.'%"');
			while ($A = db()->fetch_row($Q)) {
				$table_name = $A[0];
				if (substr($table_name, 0, strlen(DB_PREFIX)) != DB_PREFIX) {
					continue;
				}
				$tables_infos[$table_name] = [
					'name'		=> $table_name,
					'engine'	=> '',
					'rows'		=> '',
					'data_size'	=> '',
					'collation'	=> '',
				];
			}
		}
		return $tables_infos;
	}

	/**
	* Import SQL
	*/
	function import () {
		if (!empty($_FILES['sql_file']['tmp_name'])) {
			$path = $_FILES['sql_file']['tmp_name'];

// FIXME: add ability to parse ZIP files

			if ($_FILES['sql_file']['type'] == 'application/x-gzip') {
				if (function_exists('gzopen')) {
					$file = gzopen($path, 'rb');
					if (!$file) {
						return false;
					}
					$content = '';
					while (!gzeof($file)) {
						$content .= gzgetc($file);
					}
					gzclose($file);
				} else {
					return false;
				}
			} elseif($_FILES['sql_file']['type'] == 'text/plain' || substr($_FILES['sql_file']['name'], -4, 4) == '.sql') {
				$content = file_get_contents($path);
			}
			if (strlen($content) < 20) {
				_re('Filetype not supported');
			}
			$_POST['sql'] = $content;
		}

		$exec_success = false;
		$splitted_sql = [];

		$POSTED_SQL = $_POST['sql'] ? $_POST['sql'] : urldecode($_GET['id']);
		if (!empty($POSTED_SQL)) {
			$_query_time_start = microtime(true);
			$splitted_sql = db()->split_sql($POSTED_SQL);
			foreach ((array)$splitted_sql as $query) {
				$result = db()->query($query);
				if (!$result) {
					$db_error = db()->error();
					_re(t('Error while executing the query<br>@text1<br>CAUSE: @text2', [
						'@text1' => nl2br(_prepare_html($query, 0)), 
						'@text2' => $db_error['message'],
					]));
					break;
				}
			}
			if (!empty($splitted_sql) && !empty($result) && !common()->_error_exists()) {
				$exec_success = true;
			}
			$_query_exec_time = microtime(true) - $_query_time_start;
		}
		$sql = &$_POST['sql'];
		$num_queries = count($splitted_sql);

		$fetch_result = '';
		if ($num_queries && $num_queries < 100) {
			$last_query = end($splitted_sql);
			$last_query = trim(preg_replace("/^#[^\n]+\$/ims", '', str_replace("\r", "\n", trim($last_query['query']))));
			$last_query = preg_replace("/[\n]{2,}/ims", PHP_EOL, $last_query);
		}
		$last_query_total = 0;

		$data = [];
		if ($last_query) {
			$tmp_last_query = preg_replace('#/\*.*?\*/#ms', '', preg_replace('#\s+#', ' ', str_replace(["\r","\n","\t"], ' ', trim($last_query))));
			$tmp_last_query = trim($tmp_last_query, ')({}[]');
			list($tmp_first_keyword,) = explode(' ', $tmp_last_query);
			$tmp_first_keyword = strtoupper($tmp_first_keyword);
			if (in_array($tmp_first_keyword, ['SELECT','SHOW','DESCRIBE','EXPLAIN'])) {

				$last_query_total = db()->num_rows($result);
				if (!preg_match('/\sLIMIT\s+[0-9]+/ims', $last_query) && $tmp_first_keyword == 'SELECT') {
					$add_sql = ' LIMIT 0,30';
				}
				$Q = db()->query($last_query. $add_sql);
				while ($A = db()->fetch_assoc($Q)) {
					$data[] = $A;
				}
			}
		}
		if (!empty($data)) {
			$fetch_result .= '<div class="alert alert-info"><pre>'._prepare_html($last_query).'</pre></div>'
				.'Total records: <b>'.intval($last_query_total).'</b><br>';
			$fetch_result .= table($data, ['auto_no_buttons' => 1, 'no_pages' => 1])->auto();
		}
		$replace = [
			'form_action'		=> url('/@object/@action'),
			'error_message'		=> _e(),
			'sql'				=> strlen($sql) < 10000 ? nl2br(_prepare_html($sql, 0)) : t('%num queries executed successfully', ['%num' => $num_queries]),
			'exec_success'		=> (int)($exec_success),
			'exec_time'			=> $_query_exec_time ? common()->_format_time_value($_query_exec_time) : '',
			'back_link'			=> url('/@object'),
			'num_queries'		=> intval($num_queries),
			'fetch_result'		=> $fetch_result,
		];
// TODO: form display file upload to import
		return tpl()->parse('@object/import', $replace);
	}

	/**
	* Export SQL
	*/
	function table_export ($params = []) {
		$SINGLE_TABLE = !empty($_GET['table']) ? DB_PREFIX. $_GET['table'] : '';
		if ($SINGLE_TABLE) {
			$A = db()->query_fetch('SHOW TABLE STATUS LIKE "'.$SINGLE_TABLE.'"');
			$_single_table_info = [
				'name'		=> $A['Name'],
				'engine'	=> $A['Engine'],
				'rows'		=> $A['Rows'],
				'data_size'	=> $A['Data_length'],
				'collation'	=> $A['Collation'],
			];
		}
		if (!isset($this->_tables_names)) {
			foreach ((array)db()->meta_tables() as $cur_table_name) {
				$this->_tables_names[$cur_table_name] = $cur_table_name;
			}
		}
		$SILENT_MODE = $params['silent_mode'];
		$USE_TEMP_FILE = false;
		if (!$SINGLE_TABLE || $_single_table_info['rows'] >= 10000 || $_single_table_info['size'] >= 1000000) {
			$USE_TEMP_FILE = true;
		}
		if(!empty($params['where'])){
			$USE_TEMP_FILE = false;
		}
		if (!empty($_POST['go']) || $SILENT_MODE) {

			set_time_limit(600);

			if ($params['single_table']) {
				$SINGLE_TABLE = $params['single_table'];
			}
			$TABLES = $_POST['tables'];
			if ($params['tables']) {
				$TABLES = $params['tables'];
			}
			$INSERT_FULL		= $_POST['full_inserts'];
			if ($params['full_inserts']) {
				$INSERT_FULL = $params['full_inserts'];
			}
			$INSERT_EXTENDED	= $_POST['ext_inserts'];
			if ($params['ext_inserts']) {
				$INSERT_EXTENDED = $params['ext_inserts'];
			}
			$EXPORT_TYPE = $_POST['export_type'];
			if ($params['export_type']) {
				$EXPORT_TYPE = $params['export_type'];
			}
			$EXPORTED_SQL		= '';
			$tables_to_export = [];

			if (!empty($SINGLE_TABLE)) {
				$tables_to_export[$SINGLE_TABLE] = $params['where'][$SINGLE_TABLE];
			} elseif (!empty($TABLES)) {
				foreach ((array)$TABLES as $cur_table_name) {
					if (!isset($this->_tables_names[$cur_table_name])) {
						continue;
					}
					$tables_to_export[$cur_table_name] = $params['where'][$cur_table_name];
				}
			} else {
				foreach ((array)$this->_tables_names as $v) {
					$tables_to_export[$v] = $params['where'][$v];
				}
			}
			if (empty($tables_to_export)) {
				_re('No tables to export!');
			}
			if (!isset($this->_export_types[$EXPORT_TYPE])) {
				_re('Wrong export type!');
			}
// checking
			if ($USE_TEMP_FILE) {
				$_temp_file_path = $this->_quick_export_with_mysqldump($tables_to_export);
				if ($_temp_file_path && file_exists($_temp_file_path) && filesize($_temp_file_path) > 2) {
					$QUICK_DUMPED = true;
				}
			}
// TODO
//				$tables_infos $this->_get_tables_infos();
			if (!common()->_error_exists() && !$QUICK_DUMPED) {

				if ($USE_TEMP_FILE) {
					$_temp_file_name	= 'db_export'.($SINGLE_TABLE ? '__'.$SINGLE_TABLE : '').'_'.date('YmdHis', time()).'.sql';
					$_temp_file_path	= INCLUDE_PATH.'uploads/tmp/'.$_temp_file_name;
					_mkdir_m(dirname($_temp_file_path));
					if (file_exists(dirname($_temp_file_path))) {
						$fh = fopen($_temp_file_path, 'w');
						$_temp_file_name2	= $_temp_file_name.'.tmp';
						$_temp_file_path2	= $_temp_file_path.'.tmp';
					} else {
						$USE_TEMP_FILE = false;
					}
				}
				if ($params['add_create_table']) {
					$_add_create_table = PHP_EOL.'/*!40101 SET NAMES utf8 */;'.PHP_EOL;
					if ($USE_TEMP_FILE) {
						fwrite($fh, $_add_create_table);
					} else {
						$EXPORTED_SQL	= $_add_create_table;
					}
				}
				foreach ((array)$tables_to_export as $cur_table_name => $WHERE_COND) {
					$sql_1 = $sql_2 = $sql_3 = $sql_4 = '';
					$cols_names_array = [];
					$counter = 0;
					if ($params['add_create_table']) {
						$A = db()->query_fetch('SHOW CREATE TABLE '.db()->escape_key($cur_table_name));
						$_table_sql_header = PHP_EOL.'DROP TABLE IF EXISTS '.db()->escape_key($cur_table_name).';\n';
						$_table_sql_header .= str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $A['Create Table']).';\n\n';
						if ($USE_TEMP_FILE) {
							fwrite($fh, $_table_sql_header);
						} else {
							$EXPORTED_SQL	.= $_table_sql_header;
						}
					}
					$meta_columns = db()->meta_columns($cur_table_name);
					foreach ((array)$meta_columns as $cur_col_name => $cur_col_info) {
						$cols_names_array[$cur_col_name] = db()->escape_key($cur_col_name);
					}
					$sql_1	= ($EXPORT_TYPE == 'insert' ? 'INSERT' : 'REPLACE').' INTO '.db()->escape_key($cur_table_name).' ';
					$sql_2	= $INSERT_FULL ? '('.implode(', ', $cols_names_array).') ' : '';
					$sql_3	= 'VALUES \n';
					$Q = db()->query(
						'SELECT * FROM '.db()->escape_key(_es($cur_table_name))
						.($WHERE_COND ? ' WHERE '.$WHERE_COND : '')
					);
					if (!db()->num_rows($Q)) {
						continue;
					}
					if ($USE_TEMP_FILE) {
						$fh2 = fopen($_temp_file_path2, 'w');
						if ($INSERT_EXTENDED) {
							fwrite($fh2, $sql_1. $sql_2. $sql_3);
						}
					}
					while ($A = db()->fetch_assoc($Q)) {
						$cols_values_array = [];
						foreach ((array)$meta_columns as $cur_col_name => $cur_col_info) {
							$cols_values_array[$cur_col_name] = db()->escape_val(_es(stripslashes($A[$cur_col_name])));
						}
						$need_break		= $INSERT_EXTENDED && $counter >= $this->EXPORT_EXTENDED_PER_BLOCK;
						if ($need_break && strlen($sql_4)) {
							$sql_4 = substr($sql_4, 0, -2).';';
							if ($USE_TEMP_FILE && $fh2) {
								fseek($fh2, -2, SEEK_CUR);
								fwrite($fh2, ';');
							}
						}
						$sql_4_tmp = '';
						$sql_4_tmp .= !$INSERT_EXTENDED || $need_break ? PHP_EOL.''.$sql_1. $sql_2. $sql_3 : '';
						$sql_4_tmp .= '('.implode(', ', $cols_values_array).')';
						$sql_4_tmp .= $INSERT_EXTENDED ? ',' : ';';
						$sql_4_tmp .= PHP_EOL.'';
						if ($need_break) {
							$counter = 0;
						} else {
							$counter++;
						}
						if ($USE_TEMP_FILE && $fh2) {
							fwrite($fh2, $sql_4_tmp);
						} else {
							$sql_4 .= $sql_4_tmp;
						}
					}
					if ($INSERT_EXTENDED) {
						$sql_4 = substr($sql_4, 0, -2).';';
						if ($USE_TEMP_FILE && $fh2) {
							fseek($fh2, -2, SEEK_CUR);
							fwrite($fh2, ';');
						}
					}
					if ($USE_TEMP_FILE && $fh2) {
						fclose($fh2);
					}
					// Glue all SQL parts togetther with options
					if ($USE_TEMP_FILE) {
						fwrite($fh, file_get_contents($_temp_file_path2));
						unlink($_temp_file_path2);
					} else {
						$EXPORTED_SQL .= ($INSERT_EXTENDED ? $sql_1. $sql_2. $sql_3 : ''). $sql_4. PHP_EOL.'';
					}
				}
				if ($USE_TEMP_FILE) {
					fclose($fh);
				}
			}
			$EXPORTED_SQL = trim($EXPORTED_SQL);
			// Compress SQL and throw as file
			if ($_POST['compress']) {
				$_exported_name = 'export'.($SINGLE_TABLE ? '__'.$SINGLE_TABLE : '').'.sql';

				if ($USE_TEMP_FILE) {
					$_exported_file_path = $_temp_file_path;
				} else {
					$_exported_file_path = INCLUDE_PATH.'uploads/tmp/'.$_exported_name;
					_mkdir_m(dirname($_exported_file_path));
					if (file_exists(dirname($_exported_file_path))) {
						file_put_contents($_exported_file_path, $EXPORTED_SQL);
					}
				}
			}
			// Compress, stage 2
			if ($_POST['compress'] && file_exists($_exported_file_path) && filesize($_exported_file_path) > 2) {
				// Free some memory
				$EXPORTED_SQL = null;
				// Try to Gzip result (degrade gracefully if could not gzip)
				$gzip_path	= defined('OS_WINDOWS') && OS_WINDOWS ? 'd:\\' : '';
				exec($gzip_path.'gzip -fq9 '.$_exported_file_path);
				if (file_exists($_exported_file_path.'.gz') && filesize($_exported_file_path.'.gz') > 2) {
					if (file_exists($_exported_file_path)) {
						unlink($_exported_file_path);
					}
					$_exported_name			.= '.gz';
					$_exported_file_path	.= '.gz';
				// Manual method
				} elseif (function_exists('gzwrite')) {
					$gz = gzopen ($_exported_file_path.'.gz', 'w1');
					gzwrite ($gz, file_get_contents($_exported_file_path));
					gzclose ($gz);
					if (file_exists($_exported_file_path.'.gz') && filesize($_exported_file_path.'.gz') > 2) {
						unlink($_exported_file_path);
						$_exported_name			.= '.gz';
						$_exported_file_path	.= '.gz';
					}
				}
				no_graphics(true);
				header('Content-Type: application/force-download; name=\''.$_exported_name.'\'');
				header('Content-Disposition: attachment; filename=\''.$_exported_name.'\'');
				header('Content-Transfer-Encoding: binary');
				header('Content-Length: '.intval(filesize($_exported_file_path)));
				readfile($_exported_file_path);
				unlink($_exported_file_path);
				exit();
				return false; // Not needed with exit(), but leave it here :-)
			}
			if ($USE_TEMP_FILE && file_exists($_temp_file_path)) {
				$EXPORTED_SQL = file_get_contents($_temp_file_path);
				unlink($_temp_file_path);
			}
			if ($SILENT_MODE) {
				return $EXPORTED_SQL;
			}
			if (!common()->_error_exists()) {
				$replace2 = [
					'sql_text'	=> _prepare_html($EXPORTED_SQL, 0),
					'back_link'	=> url('/@object'),
				];
				return tpl()->parse('@object/export_text_result', $replace2);
			}
		}
		$replace = [
			'form_action'		=> url('/@object/@action'),
			'error_message'		=> _e(),
			'back_link'			=> url('/@object'),
			'single_table'		=> _prepare_html($SINGLE_TABLE),
#			'tables_box'		=> $this->_box('tables', ''),
#			'export_type_box'	=> $this->_box('export_type', ''),
#			'compress_box'		=> $this->_box('compress', ''),
			'table_num_rows'	=> intval($_single_table_info['rows']),
			'table_size'		=> common()->format_file_size($_single_table_info['data_size']),
		];
		return tpl()->parse('@object/export', $replace);
	}

	/**
	* Try quick export with mysqldump
	*/
	function _quick_export_with_mysqldump($tables_to_export = []) {
		if (count($tables_to_export) == 1) {
			$SINGLE_TABLE = current($tables_to_export);
		}
		// Prepare temp name
		$_temp_file_name	= 'db_export'.($SINGLE_TABLE ? '__'.$SINGLE_TABLE : '').'_'.date('YmdHis', time()).'.sql';
		$_temp_file_path	= INCLUDE_PATH.'uploads/tmp/'.$_temp_file_name;
		_mkdir_m(dirname($_temp_file_path));

		$mysql_path	= defined('OS_WINDOWS') && OS_WINDOWS ? $this->MYSQL_CLIENT_PATH : '';

		$cmd = $mysql_path.'mysqldump --host='.DB_HOST.' --user='.DB_USER.' --password='.DB_PSWD.' '
					. '--opt --comments=false --quote-names '
					. DB_NAME.' '.implode(' ', array_keys($tables_to_export))
					. ' > '.$_temp_file_path;
		exec($cmd);

		return $_temp_file_path;
	}

	/**
	* Sort array of files by creation date (use for usort)
	*/
	function _sort_by_date ($a, $b) {
		if ($a['file_mtime'] == $b['file_mtime']) {
			return 0;
		}
		return ($a['file_mtime'] > $b['file_mtime']) ? -1 : 1;
	}

	/**
	* Show available backups and backuping form
	*/
	function show_backup() {
		$backup_folder_path = INCLUDE_PATH. $this->BACKUP_PATH;

		if ($_FILES['import_file']['tmp_name']){
			$import_data = file_get_contents($_FILES['import_file']['tmp_name']);
			file_put_contents($backup_folder_path. $_FILES['import_file']['name'], $import_data);
		}

		// Find all backups in backup folder
		$backup_files = _class('dir')->scan_dir($backup_folder_path, true, '/\.(sql|gz)$/i');

		$_files_infos = [];
		if (!empty($backup_files)) {		
			foreach ((array)$backup_files as $fpath) {
				$_files_infos[] = [
					'fpath'		=> $fpath,
					'file_mtime'=> filemtime($fpath),
					'file_size'	=> filesize($fpath),
				];
			}
		}
		usort($_files_infos, [&$this, '_sort_by_date']);
		foreach ((array)$_files_infos as $_info) {
			$fpath = $_info['fpath'];
			$id = urlencode(basename($fpath));
			$replace2 = [
				'backup_date'	=> _format_date($_info['file_mtime'], 'long'),
				'backup_fsize'	=> common()->format_file_size($_info['file_size']),
				'backup_name'	=> basename($fpath),
				'delete_url'	=> url('/@object/delete_backup/'.$id),
				'restore_url'	=> url('/@object/restore/'.$id),
				'download_url'	=> url('/@object/export_backup/'.$id),
			];
			$items .= tpl()->parse('@object/backup_item', $replace2);
		}

		// Show form
		$replace = [
			'items'				=> $items,
			'form_action'		=> url('/@object/backup'),
			'import_form_action'=> url('/@object/show_backup'),
			'error_message'		=> _e(),
			'back_link'			=> url('/@object'),
		];
		return tpl()->parse('@object/backup', $replace);
	}

	/**
	* Delete backup file
	*/
	function delete_backup() {
		$fname = urldecode($_GET['id']);
		$fpath = INCLUDE_PATH. $this->BACKUP_PATH. $fname;
		if (file_exists($fpath)) {
			unlink($fpath);
		}
		return js_redirect($_SERVER['HTTP_REFERER']);
	}

	/**
	* Export backup
	*/
	function export_backup() {
		$fname = urldecode($_GET['id']);
		$fpath = INCLUDE_PATH. $this->BACKUP_PATH. $fname;
		if (file_exists($fpath)) {

			$body = file_get_contents($fpath);
			no_graphics(true);
			// Throw headers
			header('Content-Type: application/force-download; name=\''.$fname.'\'');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: '.strlen($body));
			header('Content-Disposition: attachment; filename=\''.$fname.'\'');
			// Throw content
			echo $body;
		}
		exit;
	}

	/**
	* Restore from backup
	*/
	function restore() {
		$fname = urldecode($_GET['id']);
		$fpath = INCLUDE_PATH. $this->BACKUP_PATH. $fname;
		if (file_exists($fpath)) {
			$command = (OS_WINDOWS ? $this->MYSQL_CLIENT_PATH : '').'mysql --user='.DB_USER.' --password='.DB_PSWD.' --host='.DB_HOST.' '.DB_NAME.' < \''.$fpath.'\'';
			$result = system($command);
		}
		return js_redirect($_SERVER['HTTP_REFERER']);
	}

	/**
	* backup db
	*/
	function backup($silent_mode = false) {
		$fname_start	= $_SERVER['HTTP_HOST'];
		$backup_path	= INCLUDE_PATH. $this->BACKUP_PATH;
		$mysql_path		= OS_WINDOWS ? $this->MYSQL_CLIENT_PATH : '';
		$gzip_path		= OS_WINDOWS ? "d:\\" : '';

		if (!file_exists($backup_path)) {
			mkdir($backup_path);
			file_put_contents($backup_path.'.htaccess', 'Order Allow, Deny'.PHP_EOL.'Deny From All');
		}

		$backup_name = $backup_path. $fname_start.'-'.date('YmdHis').'.sql';
		// Backup with mysqldump
		$cmd = $mysql_path.'mysqldump --user='.DB_USER.' '.(DB_PSWD ? '-p '.DB_PSWD : '').' --opt --comments=false --quote-names '.DB_NAME.' > '.$backup_name;
		exec($cmd);

		// Success with mysqldump
		if (!file_exists($backup_name)) {
			// Try our internal exporter method
			// Prepare db export params
			$params = [
				'single_table'		=> '',
				'tables'			=> '',//array(db('menus'), db('menu_items')),
				'full_inserts'		=> 1,
				'ext_inserts'		=> 1,
				'export_type'		=> 'insert',
				'silent_mode'		=> true,
				'add_create_table'	=> true,
			];
			$EXPORTED_SQL = $this->export($params);
			if (!function_exists('_file_put_contents')) {
				$this->_file_put_contents($backup_name, $EXPORTED_SQL);
			}
		}
		// Gzip result
		exec($gzip_path.'gzip -fq9 '.$backup_name);
		if (file_exists($backup_name.'.gz') && filesize($backup_name.'.gz') > 2) {
			if (file_exists($backup_name)) {
				unlink($backup_name);
			}
			$backup_name .= '.gz';
		}

		// Garbage collect
		$files = _class('dir')->scan_dir($backup_path, true, '/\.(sql|gz)$/i');
		foreach ((array)$files as $item_name) {
			$mtimes[filemtime($item_name)] = $item_name;						
		}

		$max_files = $this->MAX_BACKUP_FILES; // Number of old files to leave
		$num_files = count($files);
		if ($num_files > $max_files) {
			ksort($mtimes);
			foreach ((array)$mtimes as $v) {
				unlink($v);
				$num_files--;
				if ($num_files <= $max_files) {
					break;
				}
			}
		}

		if ($silent_mode) {
			return $backup_name;
		} else {
			return js_redirect($_SERVER['HTTP_REFERER'], 0);
		}
	}

	/**
	* put data from db in file
	*/
	function _file_put_contents ($filename, $data) {
		if (!$fp = @fopen($filename, 'w')) return false;
		$res = fwrite($fp, $data, strlen($data));
		fclose ($fp);
		return $res;
	}

	/**
	* Reads (and decompresses) a (compressed) file into a string
	*
	* @param   string   the path to the file
	* @param   string   the MIME type of the file, if empty MIME type is autodetected
	*
	* @return  string   the content of the file or
	*		  boolean  FALSE in case of an error.
	*/
	function _read_sql_file($path, $mime = '') {
		if (!file_exists($path)) {
			return FALSE;
		}
		switch ($mime) {
			case '':
				$file = @fopen($path, 'rb');
				if (!$file) {
					return FALSE;
				}
				$test = fread($file, 3);
				fclose($file);
				if ($test[0] == chr(31) && $test[1] == chr(139)) {
					return $this->_read_sql_file($path, 'application/x-gzip');
				}
				if ($test == 'BZh') {
					return $this->_read_sql_file($path, 'application/x-bzip');
				}
				if ($test == 'PK'.chr(3)) {
					return $this->_read_sql_file($path, 'application/zip');
				}
				return $this->_read_sql_file($path, 'text/plain');
			case 'text/plain':
				$file = @fopen($path, 'rb');
				if (!$file) {
					return FALSE;
				}
				$content = fread($file, filesize($path));
				fclose($file);
				break;
			case 'application/x-gzip':
				if (@function_exists('gzopen')) {
					$file = @gzopen($path, 'rb');
					if (!$file) {
						return FALSE;
					}
					$content = '';
					while (!gzeof($file)) {
						$content .= gzgetc($file);
					}
					gzclose($file);
				} else {
					return FALSE;
				}
				break;
			case 'application/x-bzip':
				if (@function_exists('bzdecompress')) {
					$file = @fopen($path, 'rb');
					if (!$file) {
						return FALSE;
					}
					$content = fread($file, filesize($path));
					fclose($file);
					$content = bzdecompress($content);
				} else {
					return FALSE;
				}
				break;
			case 'application/zip':
// FIXME: need to add decompress code
/*
*/
				break;
			default:
				return FALSE;
		}
		return $content;
	}

	/**
	*/
	function _hook_widget__db_tables ($params = []) {
// TODO
	}

	/**
	*/
	function _hook_settings(&$selected = []) {
/*
		return array(
			array('yes_no_box', 'db_manager__AUTO_GET_TABLES_STATUS'),
			array('yes_no_box', 'db_manager__USE_HIGHLIGHT'),
		);
*/
	}
}
