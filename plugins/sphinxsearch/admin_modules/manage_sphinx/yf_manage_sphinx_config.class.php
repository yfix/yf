<?php

/**
* sphinx_config
* An object representing the sphinx configuration
* Can read it from file and write it back out after modification
* @package search
*/
class yf_manage_sphinx_config {

	public $loaded = false;
	public $sections = array();

	/**
	* Constructor which optionally loads data from a file
	*/
	function __construct($filename = false) {
		if ($filename !== false && file_exists($filename))
		{
			$this->read($filename);
		}
	}

	/**
	* Get a section object by its name
	*/
	function &get_section_by_name($name) {
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
	*/
	function &add_section($name) {
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
