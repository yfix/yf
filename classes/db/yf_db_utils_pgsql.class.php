<?php

/**
*/
load('db_utils_driver', 'framework', 'classes/db/');
class yf_db_utils_pgsql extends yf_db_utils_driver {


	/**
	* Meta Columns
	*/
	function meta_columns($table) {
		$retarr = array();

		$sql = "SELECT a.attname,t.typname,a.attlen,a.atttypmod,a.attnotnull,a.atthasdef,a.attnum 
			FROM pg_class c, pg_attribute a,pg_type t 
			WHERE relkind IN ('r','v') AND (c.relname='%s' or c.relname = lower('%s')) AND a.attname NOT LIKE '....%%'
			AND a.attnum > 0 AND a.atttypid = t.oid AND a.attrelid = c.oid ORDER BY a.attnum";

		$Q = $this->db->query(sprintf($sql, $table));
		while ($A = $this->db->fetch_row($Q)) {
			$fld = array();

			$fld['name']= $A[0];
			$type		= $A[1];

			$fld['scale'] = null;
			if (preg_match('/^(.+)\((\d+),(\d+)/', $type, $query_array)) {
				$fld['type'] = $query_array[1];
				$fld['max_length'] = is_numeric($query_array[2]) ? $query_array[2] : -1;
				$fld['scale'] = is_numeric($query_array[3]) ? $query_array[3] : -1;
			} elseif (preg_match('/^(.+)\((\d+)/', $type, $query_array)) {
				$fld['type'] = $query_array[1];
				$fld['max_length'] = is_numeric($query_array[2]) ? $query_array[2] : -1;
			} elseif (preg_match('/^(enum)\((.*)\)$/i', $type, $query_array)) {
				$fld['type'] = $query_array[1];
				$fld['max_length'] = max(array_map('strlen',explode(',',$query_array[2]))) - 2; // PHP >= 4.0.6
				$fld['max_length'] = ($fld['max_length'] == 0 ? 1 : $fld['max_length']);
			} else {
				$fld['type'] = $type;
				$fld['max_length'] = -1;
			}
			$fld['not_null']		= ($A[2] != 'YES');
			$fld['primary_key']		= ($A[3] == 'PRI');
			$fld['auto_increment']	= (strpos($A[5], 'auto_increment') !== false);
			$fld['binary']			= (strpos($type,'blob') !== false);
			$fld['unsigned']		= (strpos($type,'unsigned') !== false);
			if (!$fld['binary']) {
				$d = $A[4];
				if ($d != '' && $d != 'NULL') {
					$fld['has_default'] = true;
					$fld['default_value'] = $d;
				} else {
					$fld['has_default'] = false;
				}
			}
			$retarr[strtolower($fld['name'])] = $fld;
		}
		return $retarr;
	}

	/**
	* Meta Tables
	*/
	function meta_tables($DB_PREFIX = '') {
		$sql = 'SELECT tablename,\'T\' FROM pg_tables WHERE tablename NOT LIKE \'pg\_%\'
					AND tablename NOT IN (\'sql_features\', \'sql_implementation_info\', \'sql_languages\', \'sql_packages\', \'sql_sizing\', \'sql_sizing_profiles\') 
				UNION 
					SELECT viewname,\'V\' FROM pg_views WHERE viewname NOT LIKE \'pg\_%\'';
		$q = $this->db->query($sql);
		while ($a = $this->db->fetch_row($q)) {
			$name = $a['0'];
			// Skip tables without prefix of current connection
			if (strlen($DB_PREFIX) && substr($name, 0, strlen($DB_PREFIX)) != $DB_PREFIX) {
				continue;
			}
			$tables[$name] = $name;
		}
		return $tables;
	}

	// TODO
}
