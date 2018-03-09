<?php

/**
* sphinx_config_variable
* Represents a single variable inside the sphinx configuration
*/
class yf_manage_sphinx_config_variable {

	public $name;
	public $value;
	public $comment;

	/**
	* Constructs a new variable object
	*
	* @param	string	$name		Name of the variable
	* @param	string	$value		Value of the variable
	* @param	string	$comment	Optional comment after the variable in the
	*								config file
	*/
	function __construct($name, $value, $comment)
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
