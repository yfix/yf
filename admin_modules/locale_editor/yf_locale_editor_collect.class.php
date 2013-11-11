<?php

/**
*/
class yf_locale_editor_collect {

	/**
	* Collect vars from source files (Framework included)
	*/
	function collect_vars () {
		// Select all known variables from db
		$Q = db()->query('SELECT * FROM '.db('locale_vars').' ORDER BY value ASC');
		while ($A = db()->fetch_assoc($Q)) {
			$this->_locale_vars[$A['value']] = $A;
		}
		// Try to get variables from the source code
		$vars_from_code = $this->_parse_source_code_for_vars();
		// Process vars and update or insert if records are outdated
		foreach ((array)$vars_from_code as $cur_var_name => $var_files_info) {
			$location_array = array();
			foreach ((array)$var_files_info as $file_name => $line_numbers) {
				$location_array[] = $file_name.':'.$line_numbers;
			}
			$location	= implode('; ', $location_array);
			$sql_array	= array(
				'value'		=> _es($cur_var_name),
				'location'	=> $location,
			);
			// If variable exists - use update
			if (isset($this->_locale_vars[$cur_var_name])) {
				db()->UPDATE('locale_vars', $sql_array, 'id='.intval($this->_locale_vars[$cur_var_name]['id']));
			} else {
				db()->INSERT('locale_vars', $sql_array);
			}
		}
		// Return user back
		js_redirect('./?object='.$_GET['object'].'&action=show_vars');
	}

	/**
	* Collect vars from source files, no framework, just project and given module name (internal use only method)
	*/
	function collect_vars_for_module () {
		main()->NO_GRAPHICS = true;

		$module_name = preg_replace('/[^a-z0-9\_]/i', '', strtolower(trim($_GET['id'])));
		if (!$module_name) {
			return print 'Error, no module name';
		}

		$vars = $this->_parse_source_code_for_vars(array(
			'only_project'	=> 1,
			'only_module'	=> $module_name,
		));

		echo '<pre>';
		foreach ((array)$vars as $var => $paths) {
			echo $var.PHP_EOL;
		}
		echo '</pre>';
	}
}
